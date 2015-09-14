<?php

class Eepohs_Erply_Model_Price extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
    }

    public function updatePricesOld($rules, $storeId){
        Mage::helper('Erply')->log("Running price updates");
        if(!empty($rules)) {
            foreach($rules as $rule) {
                if($rule["type"] == 'PRODUCT') {
                    $productId = $rule["id"];
                    $price = $rule["price"];

                    $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
                    if($product) {
                        $product->setPrice($price);
                        if($product->validate()) {
                            $product->save();
                        }
                    }
                }
            }
        }
    }

    public function updatePricesByPriceData($rules, $storeId){        
        $productModel = Mage::getModel('Erply/Product');
        if(!empty($rules)) {
            foreach($rules as $rule) {
                if($rule["type"] == 'PRODUCT') {
                    $productId = $rule["id"];
                    $_product = $productModel->findProductByID($productId);
                    if($_product){
                        if ($_product["code"]) {
                            $sku = $_product["code"];
                        } elseif ($_product["code2"]) {
                            $sku = $_product["code2"];
                        } else {
                            $sku = $_product["code3"];
                        }
                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                        if ($product) {
                            $price = $rule["price"];                            
                            if($product) {
                                $product->setPrice($price);
                                if($product->validate()) {
                                    $product->save();
                                }
                            }
                        }                        
                    }
                }
            }
        }
    }
}
