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
class Ginger_Ginger_Model_Creditcard extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Creditcard settings for Magento
     */
    protected $_creditcard;
    protected $_code = "ginger_creditcard";
    protected $_formBlockType = 'ginger/payment_creditcard_form';
    protected $_infoBlockType = 'ginger/payment_creditcard_info';
    protected $_paymentMethod = 'creditcard';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canCapture = false;

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
        $this->_creditcard = Mage::helper('ginger/creditcard');
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
     * Redirects the client on click 'Place Order' to selected iDEAL bank
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(
            'ginger/creditcard/payment',
            array(
                '_secure' => true,
                '_query' => array()
            )
        );
    }
}
