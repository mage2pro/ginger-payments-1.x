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
class Ginger_Ginger_Model_Ideal extends Mage_Payment_Model_Method_Abstract
{
    /**
     * iDEAL settings for Magento
     */
    protected $_ideal;
    protected $_code = "ginger_ideal";
    protected $_formBlockType = 'ginger/payment_ideal_form';
    protected $_infoBlockType = 'ginger/payment_ideal_info';
    protected $_paymentMethod = 'iDEAL';
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
        $this->_ideal = Mage::helper('ginger/ideal');
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
     * iDEAL is only active if 'EURO' is currency
     *
     * @param string $currencyCode
     * @return bool
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
     * @param mixed $data
     * @return Ginger_Ginger_Model_Ideal
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
     * Redirects the client on click 'Place Order' to selected iDEAL bank
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(
            'ginger/ideal/payment',
            array(
                '_secure' => true,
                '_query' => array(
                    'issuer_id' => Mage::registry('issuer_id')
                )
            )
        );
    }
}
