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
$installer = $this;
$installer->startSetup();

$installer->run(
    sprintf("CREATE TABLE IF NOT EXISTS `%s` (
		`order_id` int(11) NOT NULL,
		`entity_id` int(11) NOT NULL,
		`method` varchar(3) NOT NULL,
		`transaction_id` varchar(32) NOT NULL,
		`bank_account` varchar(15) NOT NULL,
		`bank_status` varchar(20) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        $installer->getTable('ginger_payments')
    )
);

$installer->endSetup();
