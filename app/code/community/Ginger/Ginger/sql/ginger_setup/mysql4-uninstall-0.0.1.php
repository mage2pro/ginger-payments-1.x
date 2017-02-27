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
    sprintf("DROP TABLE IF EXISTS `%s`",
        $installer->getTable('ginger_payments')
    )
);

$installer->run("DELETE FROM `{$installer->getTable('core_config_data')}` where `path` = 'ginger/ideal/active';
	DELETE FROM `{$installer->getTable('core_config_data')}` where `path` = 'ginger/ideal/description';
	DELETE FROM `{$installer->getTable('core_config_data')}` where `path` = 'ginger/settings/apikey';
	DELETE FROM `{$installer->getTable('core_resource')}` where `code` = 'ginger_setup';"
);

$installer->endSetup();
