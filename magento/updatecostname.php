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
$file = fopen('cost2.csv', 'r');
$fileRead = fgetcsv($file);
$fileName = array();
while($fileRead = fgetcsv($file)){
    $_name = trim($fileRead[0]);
    $_cost = $fileRead[1];
    
    if( $_name != '' && $_cost !=''){
      $fileName[$_name] = $_cost;  
    }
    
}
echo count($fileName);
echo '<pre>';
print_r($fileName);

//SKU Reading from Magento Store And Updating
$collection = Mage::getResourceModel('catalog/product_collection')
                  ->addAttributeToFilter('entity_id',array('from' => '17500','to' => '20692'))
                  ->AddAttributeToSelect('*');

foreach($collection as $product) {
    $name = $product->getName();

    if($product && isset($fileName[$name]) && $fileName[$name] != ''){
        $cost = $fileName[$name];
        if($product->getTypeId() == "configurable"):
            $config = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
            $simpleCollection = $config->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach($simpleCollection as $simpleProduct){
                $simpleProduct->setCost($cost);
                $simpleProduct->save();
                Mage::log($simpleProduct->getName(),null,'demoUpdateCostByName.log');
            }
        endif;
        
        $product->setCost($cost);
        $product->save();
        Mage::log($product->getName(),null,'demoUpdateCostByName.log');
    }    
}
echo 'Finished';
