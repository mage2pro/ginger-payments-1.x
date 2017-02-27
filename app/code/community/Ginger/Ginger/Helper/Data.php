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
class Ginger_Ginger_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Parse address for split street and house number
     *
     * @param $street_address
     * @return array
     */
    public function parseAddress($street_address)
    {
        $address = $street_address;
        $housenumber = "";

        $offset = strlen($street_address);

        while (($offset = $this->_rstrpos($street_address, ' ', $offset)) !== false) {
            if ($offset < strlen($street_address) - 1 && is_numeric($street_address[$offset + 1])) {
                $address = trim(substr($street_address, 0, $offset));
                $housenumber = trim(substr($street_address, $offset + 1));
                break;
            }
        }

        if (empty($housenumber) && strlen($street_address) > 0 && is_numeric($street_address[0])) {
            $pos = strpos($street_address, ' ');

            if ($pos !== false) {
                $housenumber = trim(substr($street_address, 0, $pos), ", \t\n\r\0\x0B");
                $address = trim(substr($street_address, $pos + 1));
            }
        }

        return array($address, $housenumber);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param null|int $offset
     * @return int
     */
    protected function _rstrpos($haystack, $needle, $offset = null)
    {
        $size = strlen($haystack);

        if (is_null($offset)) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return false;
        }

        return $size - $pos - strlen($needle);
    }

    /**
     * @param string|float $amount
     * @return int
     */
    public static function getAmountInCents($amount)
    {
        return (int) (100 * round($amount, 2, PHP_ROUND_HALF_UP));
    }
}
