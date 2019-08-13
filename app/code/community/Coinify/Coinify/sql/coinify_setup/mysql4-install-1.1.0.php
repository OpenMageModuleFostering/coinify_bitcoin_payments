<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$data = array(
    array(Coinify_Coinify_Helper_Data::ORDER_STATUS_PARTIALLY_PAID, 'Coinify Partially Paid'),
    array(Coinify_Coinify_Helper_Data::ORDER_STATUS_OVERPAID, 'Coinify Overpaid')
);
$connection->insertArray(
    $installer->getTable('sales/order_status'),
    array('status', 'label'),
    $data
);


$data = array(
    array(Coinify_Coinify_Helper_Data::ORDER_STATUS_PARTIALLY_PAID, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 0),
    array(Coinify_Coinify_Helper_Data::ORDER_STATUS_OVERPAID, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, 0)
);

$connection->insertArray(
    $installer->getTable('sales/order_status_state'),
    array('status', 'state', 'is_default'),
    $data
);

$installer->endSetup();