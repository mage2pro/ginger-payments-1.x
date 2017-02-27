<?php

/**
 *   ╲          ╱
 * ╭──────────────╮  COPYRIGHT (C) 2016 GINGER PAYMENTS B.V.
 * │╭──╮      ╭──╮│
 * ││//│      │//││  This software is released under the terms of the
 * │╰──╯      ╰──╯│  GNU General Public License version 3 or later.
 * ╰──────────────╯
 *   ╭──────────╮    https://www.gnu.org/licenses/gpl-3.0.txt
 *   │ () () () │
 *
 * @category    Ginger
 * @package     Ginger_Ginger
 * @author      Ginger Payments B.V. (info@gingerpayments.com)
 * @version     v0.0.4
 * @copyright   Copyright (c) 2016 Ginger Payments B.V. (https://www.gingerpayments.com)
 * @license     https://www.gnu.org/licenses/gpl-3.0.txt
 *
 **/
class Ginger_Ginger_IdealController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Ginger_Ginger_Helper_Ideal
     */
    protected $_ideal;
    protected $_read;
    protected $_write;
    protected $_helper;
    /**
     * @var Mage_Core_Helper_Http
     */
    protected $_coreHttp;

    /**
     * Get iDEAL core
     * Give $_write mage writing resource
     * Give $_read mage reading resource
     */
    public function _construct()
    {
        $this->_ideal = Mage::helper('ginger/ideal');
        $this->_helper = Mage::helper('ginger');
        $this->_coreHttp = Mage::helper('core/http');
        $this->_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');

        parent::_construct();
    }

    /**
     * Gets the current checkout session with order information
     *
     * @return array
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Creats the order and sets the redirect url
     *
     */
    public function paymentAction()
    {
        // Load last order
        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckout()->last_real_order_id);

        try {
            // Assign required value's
            $issuer_id = Mage::app()->getRequest()->getParam('issuer_id');
            $amount = $order->getGrandTotal();
            $order_id = $order->getIncrementId();
            $description = str_replace('%', $order_id,
                Mage::getStoreConfig("payment/ginger_ideal/description", $order->getStoreId()));
            $return_url = Mage::getUrl('ginger/ideal/return');
            $customer = $this->_getCustomerData($order);
            $currency = $order->getOrderCurrencyCode();

            if ($this->_ideal->createOrder($order_id, $issuer_id, $amount, $currency, $description, $return_url, $customer)) {
                if (!$order->getId()) {
                    Mage::log('No order found for processing');
                    Mage::throwException('No order found for processing');
                }

                // Creates transaction
                /** @var $payment Mage_Sales_Model_Order_Payment */
                $payment = $order->getPayment();

                if (!$payment->getId()) {
                    $payment = Mage::getModel('sales/order_payment')->setId(null);
                }

                //->setMethod('iDEAL')
                $payment->setIsTransactionClosed(false)
                    ->setGingerOrderId($this->_ideal->getOrderId())
                    ->setGingerIdealIssuerId($issuer_id);

                // Sets the above transaction
                $order->setPayment($payment);

                $order->setGingerOrderId($this->_ideal->getOrderId())
                    ->setGingerIdealIssuerId($issuer_id);
                $order->save();

                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

                $pendingMessage = Mage::helper('ginger')->__(Ginger_Ginger_Model_Banktransfer::PAYMENT_FLAG_PENDING);
                if ($order->getData('ginger_order_id')) {
                    $pendingMessage .= '. '.'Ginger order ID: '.$order->getData('ginger_order_id');
                }

                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    $pendingMessage,
                    false
                );

                $order->save();

                Mage::log("Issuer url: ".$this->_ideal->getPaymentUrl());
                $this->_redirectUrl($this->_ideal->getPaymentUrl());
            }
        } catch (Exception $e) {
            Mage::log($e);
            Mage::throwException(
                "Could not start transaction. Contact the owner.<br />
                Error message: ".$this->_banktransfer->getErrorMessage()
            );
        }
    }

    /**
     * This action is getting called by Ginger to report the payment status
     */
    public function webhookAction()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            die("Invalid JSON");
        }

        if (in_array($input['event'], array("status_changed"))) {

            $ginger_order_id = $input['order_id'];

            $ginger_order = $this->_ideal->getOrderDetails($ginger_order_id);

            $order_id = $ginger_order['merchant_order_id'];
            $order_status = $ginger_order['status'];

            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

            try {
                if ($order->getData('status') == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                    // Creates transaction
                    $payment = Mage::getModel('sales/order_payment')
                        ->setMethod('iDEAL')
                        ->setIsTransactionClosed(true);

                    // Sets the above transaction
                    $order->setPayment($payment);
                    $order_amount_cents = (int) round($order->getGrandTotal() * 100);

                    switch ($order_status) {
                        case "completed":
                            // store the amount paid in the order
                            $amount_paid_cents = (int) $ginger_order['amount'];
                            // $this->_setAmountPaid($order, $amount_paid_cents / 100);

                            if ($amount_paid_cents == $order_amount_cents) {
                                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

                                $order->setState(
                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                    Mage::helper('ginger')->__(Ginger_Ginger_Model_Ideal::PAYMENT_FLAG_COMPLETED),
                                    true
                                );
                                $order->save();

                                $order->setState(
                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                    Mage::helper('sales')->__('Registered notification about captured amount of %s.',
                                        $this->_formatPrice($order, $order->getGrandTotal())),
                                    false
                                );
                                $order->save();

                                // Sends email to customer.
                                if (Mage::getStoreConfig("payment/ginger_ideal/send_invoice_mail",
                                    $order->getStoreId())
                                ) {
                                    $order->sendNewOrderEmail()->setEmailSent(true)->save();
                                }

                                // Create invoice.
                                if (Mage::getStoreConfig("payment/ginger_ideal/generate_invoice_upon_completion",
                                    $order->getStoreId())
                                ) {
                                    $this->_savePaidInvoice($order);
                                }
                            } else {
                                $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                                    Mage_Sales_Model_Order::STATUS_FRAUD,
                                    Mage::helper('ginger')->__(Ginger_Ginger_Model_Ideal::PAYMENT_FLAG_FRAUD),
                                    false)->save();
                            }
                            break;
                        case "cancelled":
                            $order->cancel();
                            $order->setState(
                                Mage_Sales_Model_Order::STATE_CANCELED,
                                Mage_Sales_Model_Order::STATE_CANCELED,
                                Mage::helper('ingkassacompleet')->__(ING_KassaCompleet_Model_Ideal::PAYMENT_FLAG_CANCELLED),
                                true
                            );
                            $order->save();
                            break;
                        case "error":
                        case "pending":
                        case "see-transactions":
                        case "new":
                        default:
                            // just wait
                            break;
                    }
                }
            } catch (Exception $e) {
                Mage::log($e);
                Mage::throwException($e);
            }
        }
    }

    /**
     * Customer returning from ginger with a ginger order_id
     * Depending on the order state redirected to the corresponding page
     */
    public function returnAction()
    {
        $order_id = Mage::app()->getRequest()->getParam('order_id');

        try {
            if (!empty($order_id)) {
                $ginger_order_details = $this->_ideal->getOrderDetails($order_id);

                $payment_status = isset($ginger_order_details['status']) ? $ginger_order_details['status'] : null;

                if ($payment_status == "completed") {
                    // Redirect to success page
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                } else {
                    $this->_restoreCart();
                    // Redirect to failure page
                    $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                }
            }
        } catch (Exception $e) {
            $this->_restoreCart();

            Mage::log($e);
            $this->_redirectUrl(Mage::getBaseUrl());
        }
    }

    protected function _restoreCart()
    {
        $session = Mage::getSingleton('checkout/session');
        $orderId = $session->getLastRealOrderId();
        if (!empty($orderId)) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        }
        $quoteId = $order->getQuoteId();

        $quote = Mage::getModel('sales/quote')->load($quoteId)->setIsActive(true)->save();

        Mage::getSingleton('checkout/session')->replaceQuote($quote);
    }

    protected function _setAmountPaid($order, $order_amount)
    {
        // set the amounts paid
        $curr_base = Mage::app()->getStore()->getBaseCurrencyCode();
        $curr_store = Mage::app()->getStore()->getCurrentCurrencyCode();

        $amount_base = Mage::helper('directory')->currencyConvert($order_amount, 'EUR', $curr_base);
        $amount_store = Mage::helper('directory')->currencyConvert($order_amount, 'EUR', $curr_store);

        $order->setBaseTotalPaid($amount_base);
        $order->setTotalPaid($amount_store);
    }

    protected function _savePaidInvoice(Mage_Sales_Model_Order $order, $transaction_id = null)
    {
        $invoice = $order->prepareInvoice()
            ->register()
            ->setTransactionId($transaction_id)
            ->pay();

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        if (Mage::getStoreConfig("payment/ginger_ideal/send_invoice_mail", $order->getStoreId())) {
            $invoice->sendEmail();
        }

        return true;
    }

    /**
     * Format price with currency sign
     *
     * @param Mage_Sales_Model_Order $order
     * @param float $amount
     * @return string
     */
    protected function _formatPrice($order, $amount)
    {
        return $order->getBaseCurrency()->formatTxt($amount);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getCustomerData($order)
    {
        $billingAddress = $order->getBillingAddress();

        list($address, $houseNumber) = $this->_helper->parseAddress($billingAddress->getStreetFull());

        return array(
            'merchant_customer_id' => $order->getCustomerId(),
            'email_address' => $order->getCustomerEmail(),
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname(),
            'address_type' => 'billing',
            'address' => trim($billingAddress->getCity()).' '.trim($address),
            'postal_code' => $billingAddress->getPostcode(),
            'housenumber' => $houseNumber,
            'country' => $billingAddress->getCountryId(),
            'phone_numbers' => [$billingAddress->getTelephone()],
            'user_agent' => $this->_coreHttp->getHttpUserAgent(),
            'referrer' => $this->_coreHttp->getHttpReferer(),
            'ip_address' => $this->_coreHttp->getRemoteAddr(),
            'forwarded_ip' => $this->getRequest()->getServer('HTTP_X_FORWARDED_FOR'),
            'gender' => $order->getCustomerGender() ? ('1' ? 'male' : ('2' ? 'female' : null)) : null,
            'birth_date' => $order->getCustomerDob(),
            'locale' => substr(Mage::getStoreConfig('general/locale/code'), 0, 2),
        );
    }
}
