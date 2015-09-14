<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
Mage::app();
Mage::app('admin');
Mage::register('isSecureArea', true);


//SKU and Cost Reading From File 
$file = fopen('cost1.csv', 'r');
$fileRead = fgetcsv($file);
$fileSku = array();
while($fileRead = fgetcsv($file)){
    $_sku = 'DS-'.trim($fileRead[6]);
    $_cost = $fileRead[1];
    
    if( $_sku != '' && $_cost !=''){
      $fileSku[$_sku] = $_cost;  
    }
    
}
echo count($fileSku);
echo '<pre>';
print_r($fileSku);


//SKU Reading from Magento Store And Updating
$collection = Mage::getResourceModel('catalog/product_collection')
                  ->addAttributeToFilter('entity_id',array('from' => '17500','to' => '20692'))
                  ->AddAttributeToSelect('*');
$collectionSku  =  array();

foreach($collection as $product) {
    $sku = $product->getSku();

    if($product && isset($fileSku[$sku]) && $fileSku[$sku] != ''){
        $cost = $fileSku[$sku];
        if($product->getTypeId() == "configurable"):
            $config = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
            $simpleCollection = $config->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach($simpleCollection as $simpleProduct){
                $simpleProduct->setCost($cost);
                $simpleProduct->save();
                Mage::log($simpleProduct->getSku(),null,'demoUpdate.log');
            }
        endif;
        
        $product->setCost($cost);
        $product->save();
        Mage::log($product->getSku(),null,'demoUpdate.log');
    }    
}
echo 'Finished';
