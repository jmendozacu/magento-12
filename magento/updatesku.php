<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
Mage::app();
Mage::app('admin');
Mage::register('isSecureArea', true);


//SKU Reading From File 
$file = fopen('products8.csv', 'r');
$fileRead = fgetcsv($file);
$fileSku = array();
while($fileRead = fgetcsv($file)){
	$sku = trim($fileRead[6]);
    $fileSku[] = $sku;
}
echo '<pre>';
print_r($fileSku);



//SKU Reading From Magento Store
$collection = Mage::getResourceModel('catalog/product_collection')
                  ->addAttributeToFilter('entity_id', array(
				    'from' => '17500',
				    'to' => '20500'
				    ))
                  ->AddAttributeToSelect('name')
                  ->AddAttributeToSelect('id')
                  ->AddAttributeToSelect('shipping_from')
                  ->AddAttributeToSelect('sku');

$collectionSku  =  array();
foreach($collection as $product) {
    $collectionSku[] = $product->getSKU();  
}
print_r($collectionSku);



//Common SKU From Both Side
$skuArray = array_intersect($collectionSku,$fileSku);
print_r($skuArray);

//Update SKU and Shipping From
$finalProductSku = array();
foreach($skuArray as $productSku){
    $oldSku = $productSku;
    $newSku = 'DS-'.$oldSku;
    $newShippingFrom = 1660;
    $finalProductSku[] = array($oldSku,$newSku);
    $configured = Mage::getModel('catalog/product')->loadByAttribute('sku',$oldSku);
    if($configured){
      if($configured->getTypeId() == "configurable"):
        $config = Mage::getModel('catalog/product_type_configurable')->setProduct($configured);
        $simpleCollection = $config->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
        foreach($simpleCollection as $simpleProduct){
           $oldSimpleSku = $simpleProduct->getSKU();
           $newSimpleSku = 'DS-'.$oldSimpleSku;
           $finalProductSku[] = array($oldSimpleSku,$newSimpleSku);
           $loadedProduct = Mage::getModel('catalog/product')->loadByAttribute('sku',$oldSimpleSku);
           $loadedProduct->setSku($newSimpleSku);
           $loadedProduct->setShippingFrom($newShippingFrom);
           $loadedProduct->save();
        }
      endif;  

      $loadedProduct = Mage::getModel('catalog/product')->loadByAttribute('sku',$oldSku);
      $loadedProduct->setSku($newSku);
      $loadedProduct->setShippingFrom($newShippingFrom);
      $loadedProduct->save();
    }
    
    
}
print_r($finalProductSku);


//Generate Updated SKU Listing File
$csvfile = fopen('sku_update_summary.csv','a+');

$headers = array('Old SKU','New SKU');
fputcsv($csvfile,$headers,',');

foreach ($finalProductSku as $value) {
	fputcsv($csvfile,$value,',');
}
