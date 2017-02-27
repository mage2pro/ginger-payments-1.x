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
class Ginger_Ginger_BanktransferController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Ginger_Ginger_Helper_Banktransfer
     */
    protected $_banktransfer;

    /**
     * @var Ginger_Ginger_Helper_Data
     */
    protected $_helper;

    /**
     * @var Mage_Core_Helper_Http
     */
    protected $_coreHttp;
    protected $_read;
    protected $_write;

    /**
     * Give $_write mage writing resource
     * Give $_read mage reading resource
     */
    public function _construct()
    {
        $this->_banktransfer = Mage::helper('ginger/banktransfer');
        $this->_helper = Mage::helper('ginger');
        $this->_coreHttp = Mage::helper('core/http');

        $this->_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');

        parent::_construct();
    }

    /**
     * Create the order and sets the redirect url
     *
     * @return void
     */
    public function paymentAction()
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckout()->last_real_order_id);

        try {
            $amount = $order->getGrandTotal();
            $order_id = $order->getIncrementId();
            $description = str_replace('%', $order_id,
                Mage::getStoreConfig("payment/ginger_banktransfer/description", $order->getStoreId())
            );
            $customer = $this->_getCustomerData($order);
            $currency = $order->getOrderCurrencyCode();

            $reference = $this->_banktransfer->createOrder($order_id, $amount, $currency, $description, $customer);

            if ($reference) {
                if ($order->getPayment()->getMethodInstance() instanceof Ginger_Ginger_Model_Banktransfer &&
                    $paymentBlock = $order->getPayment()->getMethodInstance()->getMailingAddress($order->getStoreId())
                ) {
                    $details = array();
                    $grandTotal = $order->getGrandTotal();
                    $currency = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->getSymbol();

                    $amountStr = $currency.' '.round($grandTotal, 2);

                    $paymentBlock = str_replace('%AMOUNT%', $amountStr, $paymentBlock);
                    $paymentBlock = str_replace('%REFERENCE%', $reference, $paymentBlock);
                    $paymentBlock = str_replace('\n', '', $paymentBlock);

                    $details['mailing_address'] = $paymentBlock;
                    if (!empty($details)) {
                        $order->getPayment()->getMethodInstance()->getInfoInstance()->setAdditionalData(serialize($details));
                    }
                }

                if (!$order->getId()) {
                    Mage::log('No order found');
                    Mage::throwException('No order found');
                }

                // Creates transaction
                /** @var $payment Mage_Sales_Model_Order_Payment */
                $payment = $order->getPayment();

                if (!$payment->getId()) {
                    $payment = Mage::getModel('sales/order_payment')->setId(null);
                }

                $payment->setIsTransactionClosed(false)
                    ->setGingerOrderId($this->_banktransfer->getOrderId())
                    ->setGingerBanktransferReference($reference);

                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

                // Sets the above transaction
                $order->setPayment($payment);

                $order->setGingerOrderId($this->_banktransfer->getOrderId())
                    ->setGingerBanktransferReference($reference);
                $order->save();

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

                $order->addStatusToHistory($order->getStatus(), 'Reference: '.$reference)->save();

                if (Mage::getStoreConfig("payment/ginger_banktransfer/send_order_mail", $order->getStoreId())) {
                    if (!$order->getEmailSent()) {
                        $order->setEmailSent(true);
                        $order->sendNewOrderEmail();
                        $order->save();
                    }
                }

                $this->_redirect('checkout/onepage/success', array('_secure' => true, 'reference' => $reference));
            } else {
                $this->_restoreCart();

                // Redirect to failure page
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
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
     * Gets the current checkout session with order information
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return void
     */
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
