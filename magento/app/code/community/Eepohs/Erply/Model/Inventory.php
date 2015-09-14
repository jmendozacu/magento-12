<?php
/**
 * NB! This is a BETA release of Erply Connector.
 *
 * Use with caution and at your own risk.
 *
 * The author does not take any responsibility for any loss or damage to business
 * or customers or anything at all. These terms may change without further notice.
 *
 * License terms are subject to change. License is all-restrictive until
 * said otherwise.
 *
 * @author Eepohs Ltd
 */
/**
 * Created by Rauno Väli
 * Date: 27.03.12
 * Time: 10:25
 */
class Eepohs_Erply_Model_Inventory extends Mage_Core_Model_Abstract
{
    public function _construct(){
        parent::_construct();
    }

    public function updateInventoryByStockData($stockData, $storeId){
        $productModel = Mage::getModel('Erply/Product');
        foreach ($stockData as $stock) {
            $productID = $stock['productID'];
            $amountInStock = $stock['amountInStock'];
            $_product = $productModel->findProductByID($productID);
            if($_product){            
                if($_product["code"]) {
                    $sku = $_product["code"];
                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                    if($product) {
                        if($product->getTypeId() == 'configurable'){
                            Mage::log('Configurable Product with SKU :'.$sku,null,'erply_qty.log');
                        }else{
                            $qty = (int) $amountInStock;
                            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
                            if (!$stockItem->getId()) {
                                $stockItem->setData('product_id', $product->getId());
                                $stockItem->setData('stock_id', 1);
                            }
                            if ($stockItem->getQty() != $qty) {
                                if($qty < 0){ $qty = 0;}
                                $stockItem->setData('qty', $qty);
                                $stockItem->setData('is_in_stock', $qty ? 1 : 0);
                                $stockItem->save();
                            }
                            $product->save();
                            Mage::log('Found Product with SKU :'.$sku.'  with QTY:'.$qty,null,'erply_qty.log');
                        }
                    }else{
                        Mage::log('Not Found Product with SKU :'.$sku,null,'erply_qty.log');
                    }
                }
            }
        }
    }

    public function updateInventoryOld($products, $storeId)
    {
        Mage::helper('Erply')->log("Running Erply own updateInventory");
        foreach ($products as $_product) {

            if ($_product["code"]) {
                $sku = $_product["code"];
            } elseif ($_product["code2"]) {
                $sku = $_product["code2"];
            } else {
                $sku = $_product["code3"];
            }

            $product = Mage::getModel('catalog/product')
                ->loadByAttribute('sku', $sku);
                
            if (!$product) {
                return false;

                // $product = Mage::getModel('catalog/product')->load($_product["productID"]);
                // if (!$product->getName()) {
                //     return false;
                // } else {
                //     Mage::helper('Erply')->log("Editing old product: " . $_product["productID"]);
                // }
            }
            /**
             * Update stock
             */
            
            $qty = $_product["amountInStock"];

            //$qty = $this->getProductQuantity($_product);
            

            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());

            if (!$stockItem->getId()) {
                $stockItem->setData('product_id', $product->getId());
                $stockItem->setData('stock_id', 1);
            }

            if ($stockItem->getQty() != $qty) {
                $stockItem->setData('qty', $qty);
                $stockItem->setData('is_in_stock', $qty ? 1 : 0);
                $stockItem->save();
            }

            /**
             * Update price
             */
            // $product->setPrice($_product["price"]);
            $product->save();
        }
    }

    private function getProductQuantity($product)
    {
        $quantity = 0;
        foreach ($product['warehouses'] as $warehouse) {
            $quantity += $warehouse['free'];
        }
        return $quantity;
    }
}