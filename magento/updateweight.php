<?php 
require_once 'app/Mage.php';
Mage::app();
Mage::app('admin');
Mage::register('isSecureArea', true);


$_productCOllection = Mage::getModel('catalog/product')->getCollection()
   ->addAttributeToSelect('*')
   ->addAttributeToFilter('entity_id', array(
    'from' => '17223',
    'to' => '19697'
    ))
    ->load();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
foreach ($_productCOllection as $product) {
	
	$product->setWeight('0.5');
	
	try {             
		$product->save();             
		echo $product->getId()." : Product updated";
		echo '<br/>';
		
	}
	catch(Exception $ex) 
	{   
		echo '<pre>';print_r($ex);
		
	}
	
}

