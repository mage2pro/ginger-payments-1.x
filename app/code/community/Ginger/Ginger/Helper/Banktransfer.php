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
class Ginger_Ginger_Helper_Banktransfer
{
    const XML_PATH_EMAIL_TEMPLATE = "payment/ginger_banktransfer/order_email_template";
    const XML_PATH_EMAIL_GUEST_TEMPLATE = "payment/ginger_banktransfer/order_email_template_guest";
    const GINGER_PAYMENT_DETAILS = "GINGER_PAYMENT_DETAILS";

    protected $order_id = null;
    protected $amount = 0;
    protected $description = null;
    protected $order_status = null;
    protected $consumer_info = array();
    protected $error_message = '';
    protected $error_code = 0;
    protected $gingerLib = null;

    public function __construct()
    {
        require_once(Mage::getBaseDir('lib').DS.'Services'.DS.'Ginger'.DS.'vendor'.DS.'autoload.php');

        $this->gingerLib = \GingerPayments\Payment\Ginger::createClient(Mage::getStoreConfig("payment/ginger/apikey"));
    }

    /**
     * @param $order_id
     * @param $amount
     * @param $currency
     * @param $description
     * @param array $customer
     * @return mixed
     */
    public function createOrder($order_id, $amount, $currency, $description, $customer = array())
    {
        if (!$this->setOrderId($order_id) OR
            !$this->setAmount($amount) OR
            !$this->setDescription($description)
        ) {
            $this->error_message = "Error in the given payment data";
            return false;
        }

        $order = $this->gingerLib->createSepaOrder(
            Ginger_Ginger_Helper_Data::getAmountInCents($amount),
            $currency,
            [],
            $description,
            $order_id,
            null,
            null,
            $customer
        );

        Mage::Log($order->toArray());

        if (!is_array($order->toArray()) OR array_key_exists('error', $order->toArray())) {
            // TODO: handle the error
            return false;
        } else {
            $this->order_id = (string) $order->getId();
            return $this->getPaymentReference($order);
        }
    }

    /**
     * @param array $gingerOrder
     * @return string
     */
    protected function getPaymentReference($gingerOrder)
    {
        $ginger_order = $gingerOrder->toArray();
        return $ginger_order['transactions'][0]['payment_method_details']['reference'];
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $reference
     * @return string
     */
    public function getSuccessHtml(Mage_Sales_Model_Order $order, $reference)
    {
        if ($order->getPayment()->getMethodInstance() instanceof Ginger_Ginger_Model_Banktransfer) {
            $paymentBlock = $order->getPayment()->getMethodInstance()->getMailingAddress($order->getStoreId());

            $grandTotal = $order->getGrandTotal();
            $currency = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->getSymbol();

            $amountStr = $currency.' '.round($grandTotal, 2);

            $paymentBlock = str_replace('%AMOUNT%', $amountStr, $paymentBlock);
            $paymentBlock = str_replace('%REFERENCE%', $reference, $paymentBlock);
            $paymentBlock = str_replace('\n', '<br/>', $paymentBlock);

            return $paymentBlock;
        }

        return '';
    }

    public function getOrderDetails($ginger_order_id)
    {
        return $this->gingerLib->getOrderDetails($ginger_order_id);
    }

    public function setAmount($amount)
    {
        return ($this->amount = $amount);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setOrderId($order_id)
    {
        return ($this->order_id = $order_id);
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function setDescription($description)
    {
        $description = substr($description, 0, 29);

        return ($this->description = $description);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getErrorMessage()
    {
        return $this->error_message;
    }
}
