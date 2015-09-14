<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
Mage::app();
Mage::app('admin');
Mage::register('isSecureArea', true);

$collection = Mage::getResourceModel('catalog/product_collection')
                  ->addAttributeToFilter('entity_id',array('from' => '18000','to' => '20692'))
                  ->AddAttributeToSelect('*');

$csvfile = fopen('name_sku_price_cost_shipping_list.csv','a+');
$headers = array('Product Name','SKU','Price','Cost','Shipping From');
fputcsv($csvfile,$headers,',');

foreach($collection as $product) {
    $productName = $product->getName();
    $configured = Mage::getModel('catalog/product')->loadByAttribute('name',$productName);
    if($configured){
        $productSku = $product->getSKU();
        $productPrice = $product->getPrice();
        $productCost = $product->getCost();
        $productShippingFrom = $product->getAttributeText('shipping_from');
        $collectionProduct = array('name'=>$productName,'sku'=>$productSku,'Price'=>$productPrice,'cost'=>$productCost,'shippingfrom'=>$productShippingFrom);
        fputcsv($csvfile,$collectionProduct,',');      
        if($configured->getTypeId() == "configurable"){
        $config = Mage::getModel('catalog/product_type_configurable')->setProduct($configured);
        $simpleCollection = $config->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach($simpleCollection as $simpleProduct){
               $simpleProductName = $simpleProduct->getName();
			         $simpleProductSku = $simpleProduct->getSKU();
			         $simpleProductPrice = $simpleProduct->getPrice();
			         $simpleProductCost = $simpleProduct->getCost();
			         $simpleProductShippingFrom = $simpleProduct->getAttributeText('shipping_from');	
               $collectionsSimpleProduct = array('name'=>$simpleProductName,'sku'=>$simpleProductSku,'Price'=>$simpleProductPrice,'cost'=>$simpleProductCost,'shippingfrom'=>$simpleProductShippingFrom);
               fputcsv($csvfile,$collectionSimpleProduct,',');  
            }
        }
    }     
}
echo 'finished';