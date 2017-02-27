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
class Ginger_Ginger_Model_Banktransfer extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Banktransfer settings for Magento
     */
    protected $_banktransfer;
    protected $_code = "ginger_banktransfer";
    protected $_formBlockType = 'ginger/payment_banktransfer_form';
    protected $_infoBlockType = 'ginger/payment_banktransfer_info';
    protected $_paymentMethod = 'Banktransfer';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canCapture = false;

    // Payment flags
    const PAYMENT_FLAG_PENDING = "Payment is pending";
    const PAYMENT_FLAG_COMPLETED = "Payment is completed";
    const PAYMENT_FLAG_CANCELLED = "Payment is cancelled";
    const PAYMENT_FLAG_ERROR = "Payment failed with an error";
    const PAYMENT_FLAG_FRAUD = "Amounts don't match. Possible fraud";

    /**
     * Build constructor
     */
    public function __construct()
    {
        parent::_construct();
        $this->_banktransfer = Mage::helper('ginger/banktransfer');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_getCheckout()->getQuote();
    }

    /**
     * Banktransfer is only active if 'EURO' is currency
     *
     * @param type $currencyCode
     * @return true/false
     */
    public function canUseForCurrency($currencyCode)
    {
        if ($currencyCode !== "EUR") {
            return false;
        }

        return parent::canUseForCurrency($currencyCode);
    }

    /**
     * On click payment button, this function is called to assign data
     *
     * @param array $data
     * @return Ginger_Ginger_Model_Banktransfer
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if (Mage::registry('issuer_id')) {
            Mage::unregister('issuer_id');
        }

        Mage::register('issuer_id', Mage::app()->getRequest()->getParam('issuer_id'));

        return $this;
    }

    /**
     * @param mixed $store
     * @return mixed|string
     */
    public function getMailingAddress($store = null)
    {
        /** @var $helper Ginger_Ginger_Helper_Data */
        $helper = Mage::helper('ginger');

        $paymentBlock = $helper->__(Ginger_Ginger_Helper_Banktransfer::GINGER_PAYMENT_DETAILS);

        if ($paymentBlock == Ginger_Ginger_Helper_Banktransfer::GINGER_PAYMENT_DETAILS) {
            $paymentBlock = Mage::getStoreConfig("payment/ginger_banktransfer/mailing_address", $store);
        }

        return $paymentBlock;
    }

    /**
     * Redirects the client on click 'Place Order' to selected Banktransfer bank
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(
            'ginger/banktransfer/payment',
            array(
                '_secure' => true,
                '_query' => array(
                    'issuer_id' => Mage::registry('issuer_id')
                )
            )
        );
    }
}
