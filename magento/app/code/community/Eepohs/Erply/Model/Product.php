<?php

class Eepohs_Erply_Model_Product extends Eepohs_Erply_Model_Erply
{
    public function importProductsDJ($erplyProducts, $storeId, $store){
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $erplyModel = Mage::getModel('Erply/Erply');
        $erplyModel->verifyUser($storeId);

        if(!empty($erplyProducts)) {
            foreach ($erplyProducts as $erplyProduct) {
                $sku = '';
                if ($erplyProduct["code"]) {
                    $sku = $erplyProduct["code"];
                } elseif ($erplyProduct["code2"]) {
                    $sku = $erplyProduct["code2"];
                } else {
                    $sku = $erplyProduct["code3"];
                }
                $name = $erplyProduct["name"];

                // Mage::log('Erply Product SKU :'.$sku,null,'erply_productimport.log');
                // Mage::log('--- Product Name :'.$name,null,'erply_productimport.log');
                Mage::log('Erply Product SKU :'.$sku,null,'erply_qty_cost.log');
                
                if($name != '' && $sku != ''){
                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

                    // Product Update
                    /*if(!$product){
                        $product = new Mage_Catalog_Model_Product();
                        $product->setStoreId($storeId);
                        $product->setTypeId('simple');
                        $product->setWeight(1.0000);
                        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                        $product->setStatus(1);
                        $product->setSku($sku);
                        $product->setTaxClassId(0);
                        $product->setName($name);
                        $product->setWebsiteIds(array($store->getWebsiteId()));
                        $product->setAttributeSetId((int)Mage::getStoreConfig('eepohs_erply/product/attribute_set', $storeId));
                        $product->setStockData(
                                    array(
                                           'manage_stock'=>1,
                                           'is_in_stock' => 0,
                                           'qty' => 0
                                       )
                                    );
                        $product->setPrice(0);
                        $product->save();
                        Mage::log('New Product Created',null,'erply_productimport.log');
                        unset($product);
                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                    }  */                

                    if($product){
                        if($product->getTypeId() == 'configurable'){
                            //Mage::log('--- Configurable Product with SKU :'.$sku,null,'erply_productimport.log');
                        }else{
                            // Stock Update                        
                            if(isset($erplyProduct['warehouses']) && count($erplyProduct['warehouses'])){
                                foreach ($erplyProduct['warehouses'] as $warehouses) {
                                    $erplyWarehouseId = (int) Mage::getStoreConfig('eepohs_erply/product/warehouse',0);
                                    if($warehouses['warehouseID'] == $erplyWarehouseId){
                                        if(isset($warehouses['totalInStock'])){
                                            $qty = (int) $warehouses['totalInStock'];
                                            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
                                            if (!$stockItem->getId()) {
                                                $stockItem->setData('product_id', $product->getId());
                                                $stockItem->setData('stock_id', 1);
                                            }
                                            if ($stockItem->getQty() != $qty) {
                                                $stockItem->setData('qty', $qty);
                                                $stockItem->setData('is_in_stock', $qty ? 1 : 0);
                                                $stockItem->save();

                                                Mage::log('New QTY :'.$qty,null,'erply_qty_cost.log');

                                                //Mage::log('--- Stock Data Updated',null,'erply_productimport.log');
                                                // try {
                                                //     $product->save();
                                                // } catch (Exception $e) {                                                
                                                //     Mage::log('Caught exception: '.$e->getMessage(),null,'erply_productimport.log');
                                                // }
                                            }                                            
                                        }                                        
                                    }
                                }
                            }

                            // Price Update                        
                            if(isset($erplyProduct['FIFOCost'])){
                                if($erplyProduct['FIFOCost'] != 0 && $erplyProduct['FIFOCost'] != ''){
                                    $price = $erplyProduct['FIFOCost'];
                                    $product->setCost($erplyProduct['FIFOCost']);
                                    $product->save();
                                    Mage::log('New Cost :'.$price,null,'erply_qty_cost.log');
                                }
                            }
                        }
                        unset($product);
                    }else{
                        //Mage::log('--- Product not Found with SKU :'.$sku,null,'erply_productimport.log');
                    }
                }
            }
        }
        //Mage::log('importProductsDJ ended',null,'erply_product.log');
    }

    public function findProduct($sku) {
        $storeId = Mage::app()->getStore()->getId();
        $this->verifyUser($storeId);
        $params = array(
            'searchName' => $sku
        );
        $product = $this->sendRequest('getProducts', $params);
        $product = json_decode($product, true);
        Mage::log('Eepohs_Erply_Model_Product findProduct() Total API Calls: 1',null,'erply_limit.log');
        if($product["status"]["responseStatus"] == "ok" && count($product["records"]) > 0) {
            foreach($product["records"] as $_product) {
                if ($_product["code2"]) {
                    $code = $_product["code2"];
                } elseif ($_product["code"]) {
                    $code = $_product["code"];
                } else {
                    $code = $_product["code3"];
                }
                if($code == $sku) {
                    return $_product;
                }
            }
        }
    }

    public function findProductByID($productId){ 
        $storeId = Mage::app()->getStore()->getId();
        $this->verifyUser($storeId);
        $params = array(
            'productID' => $productId
        );
        $product = $this->sendRequest('getProducts', $params);
        $product = json_decode($product, true);
        Mage::log('Eepohs_Erply_Model_Product findProductByID() Total API Calls: 1',null,'erply_limit.log');
        if($product["status"]["responseStatus"] == "ok" && count($product["records"]) > 0) {
            if(isset($product['records'][0])){
                return $product['records'][0];    
            }else{
                return false;    
            }
        }else{
            return false;
        }
    }

    public function findProductBySKU($sku){        
        $storeId = Mage::app()->getStore()->getId();
        $this->verifyUser($storeId);
        $params = array(
            'code' => $sku
        );
        $product = $this->sendRequest('getProducts', $params);
        $product = json_decode($product, true);        
        Mage::log('Eepohs_Erply_Model_Product findProductBySKU() Total API Calls: 1',null,'erply_limit.log');
        if($product["status"]["responseStatus"] == "ok" && count($product["records"]) > 0) {
            foreach($product["records"] as $_product) {
                if ($_product["code"]) {
                    $code = $_product["code"];
                } elseif ($_product["code2"]) {
                    $code = $_product["code2"];
                } else {
                    $code = $_product["code3"];
                }
                if($code == $sku) {
                    return $_product;
                }
            }
        }
    }
}