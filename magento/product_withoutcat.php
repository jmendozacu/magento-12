<?php
require_once 'app/Mage.php';
umask(0);
Mage::app();
Mage::register('isSecureArea', true);

$products = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('visibility', array(2,3,4))->load();
$entity_attribute_setModel = Mage::getModel('eav/entity_attribute_set');
$fp = fopen("product_without_category.csv","w");
$count = 1;
foreach($products as $product){
    $pro = Mage::getModel('catalog/product')->load($product->getId());
    $cats = $pro->getCategoryIds();
    if(empty($cats)){        
        echo $id = $pro->getId();
        echo " - ";
        echo $name = $pro->getName();
        echo " - ";
        echo $sku = $pro->getSku();
        echo " - ";
        $attributeSetId = $pro->getAttributeSetId();
        $attributeSet = $entity_attribute_setModel->load($attributeSetId);
        echo $attributeSetName = $attributeSet->getAttributeSetName();
        $prodArray = array($id, $name,$sku, $attributeSetName);
        $list = array($prodArray);
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }
        echo "<br>";        
    }
    $count++;
}
echo "Count :".$count;
fclose($fp);
exit;
?>