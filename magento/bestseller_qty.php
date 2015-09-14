<?php
require_once('app/Mage.php');
Mage::app();

$date = date('Y-m-d');
$fromDate = date('Y-m-d',strtotime($date .' -3 months'));
$toDate = $date;
$storeId    = Mage::app()->getStore()->getId();

$products = Mage::getResourceModel('reports/product_collection')->setStoreId($storeId)->addOrderedQty($fromDate, $toDate)->setOrder('ordered_qty', 'desc');

$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

$query = "UPDATE catalog_product_entity SET qty_sold = 0";
$writeConnection->query($query);

foreach ($products->getData() as $product) {
	$entityId = $product['entity_id'];
	$orderedQty = $product['ordered_qty'];	
	
	$query = "UPDATE catalog_product_entity SET qty_sold = ".$orderedQty." WHERE entity_id = ".(int)$entityId;
	$writeConnection->query($query);
	
	echo $entityId.'updated<br>';
}

echo "Done";exit;