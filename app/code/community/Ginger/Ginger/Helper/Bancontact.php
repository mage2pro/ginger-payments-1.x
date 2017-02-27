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
class Ginger_Ginger_Helper_Bancontact
{
    protected $order_id = null;
    protected $issuer_id = null;
    protected $amount = 0;
    protected $description = null;
    protected $return_url = null;
    protected $payment_url = null;
    protected $order_status = null;
    protected $consumer_info = array();
    protected $error_message = '';
    protected $error_code = 0;
    protected $gingerLib = null;

    /**
     * Ginger_Ginger_Helper_bancontact constructor.
     */
    public function __construct()
    {
        require_once(Mage::getBaseDir('lib').DS.'Services'.DS.'Ginger'.DS.'vendor'.DS.'autoload.php');

        $this->gingerLib = \GingerPayments\Payment\Ginger::createClient(Mage::getStoreConfig("payment/ginger/apikey"));
    }

    /**
     * @param string $order_id
     * @param string $amount
     * @param string $currency
     * @param string $description
     * @param string $return_url
     * @param array $customer
     * @return bool
     */
    public function createOrder($order_id, $amount, $currency, $description, $return_url, array $customer)
    {
        if (!$this->setOrderId($order_id)
            || !$this->setAmount($amount)
            || !$this->setDescription($description)
            || !$this->setReturnUrl($return_url)
        ) {
            $this->error_message = "Error in the given payment data";
            return false;
        }

        $order = $this->gingerLib->createbancontactOrder(
            Ginger_Ginger_Helper_Data::getAmountInCents($amount),
            $currency,
            $description,
            $order_id,
            $return_url,
            null,
            $customer
        );

        Mage::Log($order->toArray());

        $this->order_id = (string) $order->getId();
        $this->payment_url = (string) $order->firstTransactionPaymentUrl()->toString();

        return true;
    }

    public function getOrderDetails($ginger_order_id)
    {
        return $this->gingerLib->getOrder($ginger_order_id)->toArray();
    }

    public function setIssuerId($issuer_id)
    {
        return ($this->issuer_id = $issuer_id);
    }

    public function getIssuerId()
    {
        return $this->issuer_id;
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

    public function setReturnURL($return_url)
    {
        if (!preg_match('|(\w+)://([^/:]+)(:\d+)?(.*)|', $return_url)) {
            return false;
        }

        return ($this->return_url = $return_url);
    }

    public function getReturnURL()
    {
        return $this->return_url;
    }

    public function getPaymentURL()
    {
        return (string) $this->payment_url;
    }

    public function getErrorMessage()
    {
        return $this->error_message;
    }
}
