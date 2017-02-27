<?php

/** @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();


//$this->addAttribute('quote_payment', 'ginger_order_id', array());
//$this->addAttribute('order_payment', 'ginger_order_id', array());

//$this->addAttribute('quote_payment', 'ginger_banktransfer_reference', array());
//$this->addAttribute('order_payment', 'ginger_banktransfer_reference', array());

//$this->addAttribute('quote_payment', 'ginger_ideal_issuer_id', array());
//$this->addAttribute('order_payment', 'ginger_ideal_issuer_id', array());

/** @var $conn Varien_Db_Adapter_Pdo_Mysql */
$conn = $this->getConnection();

$conn->addColumn($this->getTable('sales/quote_payment'), 'ginger_order_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger order id',
));

$conn->addColumn($this->getTable('sales/quote_payment'), 'ginger_banktransfer_reference', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger banktransfer reference',
));

$conn->addColumn($this->getTable('sales/quote_payment'), 'ginger_ideal_issuer_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger ideal issuer id',
));

$conn->addColumn($this->getTable('sales/order_payment'), 'ginger_order_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger order id',
));

$conn->addColumn($this->getTable('sales/order_payment'), 'ginger_banktransfer_reference', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger banktransfer reference',
));

$conn->addColumn($this->getTable('sales/order_payment'), 'ginger_ideal_issuer_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger ideal issuer id',
));


$this->endSetup();