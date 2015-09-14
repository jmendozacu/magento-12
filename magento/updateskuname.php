<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
Mage::app();
Mage::app('admin');
Mage::register('isSecureArea', true);

echo '<pre>';
//Name Reading From File 
$file = fopen('name1.csv', 'r');
$fileRead = fgetcsv($file);
$fileName = array();
while($fileRead = fgetcsv($file)){
    $fileName[] = trim($fileRead[0]); 
}
print_r($fileName);

//Name Reading From Store
$collection = Mage::getResourceModel('catalog/product_collection')
                  ->addAttributeToFilter('entity_id',array('from' => '17500','to' => '20692'))
                  ->AddAttributeToSelect('*');
$collectionName  =  array();
foreach($collection as $product) {
    $collectionName[] = $product->getName();  
}
print_r($collectionName);


//Common Name From Both Side
$nameArray = array_intersect($fileName,$collectionName);
print_r($nameArray);


//Update SKU and Shipping From
$finalProductSku = array();
foreach($nameArray as $productName){
    $configured = Mage::getModel('catalog/product')->loadByAttribute('name',$productName);
    if($configured){
        $sku = $configured->getSKU();
        $skuPart = explode('-', $sku);
        if($skuPart[0] !== 'DS'){
            $oldSku = $sku;
            $newSku = 'DS-'.$oldSku;
            $newShippingFrom = 1660;
            $finalProductSku[] = array($oldSku,$newSku);
            
            if($configured->getTypeId() == "configurable"){
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
            }
            
            $loadedProduct = Mage::getModel('catalog/product')->loadByAttribute('sku',$oldSku);
            $loadedProduct->setSku($newSku);
            $loadedProduct->setShippingFrom($newShippingFrom);
            $loadedProduct->save();

        }
    }    
}

//Generate Updated SKU Listing File
$csvfile = fopen('sku_update_name_new.csv','a+');

$headers = array('Old SKU','New SKU');
fputcsv($csvfile,$headers,',');

foreach ($finalProductSku as $value) {
    fputcsv($csvfile,$value,',');
}

echo 'finished';