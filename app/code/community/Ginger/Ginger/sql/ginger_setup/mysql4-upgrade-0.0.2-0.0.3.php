<?php

/** @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();


//$this->addAttribute('quote', 'ginger_order_id', array('type'=>'varchar'));
//$this->addAttribute('order', 'ginger_order_id', array('type'=>'varchar'));

//$this->addAttribute('quote', 'ginger_banktransfer_reference', array('type'=>'varchar'));
//$this->addAttribute('order', 'ginger_banktransfer_reference', array('type'=>'varchar'));

//$this->addAttribute('quote', 'ginger_ideal_issuer_id', array('type'=>'varchar'));
//$this->addAttribute('order', 'ginger_ideal_issuer_id', array('type'=>'varchar'));

/** @var $conn Varien_Db_Adapter_Pdo_Mysql */
$conn = $this->getConnection();

$conn->addColumn($this->getTable('sales/quote'), 'ginger_order_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger order id',
));

$conn->addColumn($this->getTable('sales/quote'), 'ginger_banktransfer_reference', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger banktransfer reference',
));

$conn->addColumn($this->getTable('sales/quote'), 'ginger_ideal_issuer_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger ideal issuer id',
));

$conn->addColumn($this->getTable('sales/order'), 'ginger_order_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger order id',
));

$conn->addColumn($this->getTable('sales/order'), 'ginger_banktransfer_reference', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger banktransfer reference',
));

$conn->addColumn($this->getTable('sales/order'), 'ginger_ideal_issuer_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => NULL,
    'comment'   => 'ginger ideal issuer id',
));


$this->endSetup();