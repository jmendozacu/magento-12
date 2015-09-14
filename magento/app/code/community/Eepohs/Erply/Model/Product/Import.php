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
class Eepohs_Erply_Model_Product_Import extends Eepohs_Erply_Model_Erply
{
    public function getTotalRecords($storeId) {
        $erplyCalls = 0;
        $this->verifyUser($storeId);
        $parameters = array('recordsOnPage' => 1, 'pageNo' => 1);
        $erplyCalls++;
        $results = json_decode($this->sendRequest('getProducts', $parameters), true);
        Mage::log('Eepohs_Erply_Model_Product_Import getTotalRecords() Total API Calls:'.$erplyCalls,null,'erply_limit.log');
        return $results["status"]["recordsTotal"];
    }

    public function importProducts() {
        $erplyCalls = 0;
        $queue = Mage::getModel('Erply/Queue')->loadActive('erply_product_import');
        $params = array();
        if($queue) {
            $runEvery = Mage::getStoreConfig('eepohs_erply/queue/run_every', $queue->getStoreId());
            $loops = $queue->getLoopsPerRun();
            $pageSize = $queue->getRecordsPerRun();
            $recordsLeft = $queue->getTotalRecords() - $pageSize * $queue->getLastPageNo();
            if($queue->getChangedSince()) {
                $params = array('changedSince' => $queue->getChangedSince());
            }
            if( $loops * $pageSize > $recordsLeft ) {
                $loops = ceil( $recordsLeft / $pageSize );
                $queue->setStatus(0);
            } else {
                $thisRunTime = strtotime($queue->getScheduledAt());
                $newRunTime = strtotime('+'.$runEvery.'minute', $thisRunTime);
                $scheduleDateTime = date('Y-m-d H:i:s', $newRunTime);
                Mage::getModel('Erply/Cron')->addCronJob('erply_product_import', $scheduleDateTime);
                $queue->setScheduledAt($scheduleDateTime);
            }
            $loops--;
            $firstPage = $queue->getLastPageNo()+1;

            $queue->setLastPageNo($firstPage+$loops);
            $queue->setUpdatedAt(date('Y-m-d H:i:s', time()));

            $queue->save();
            $this->verifyUser($queue->getStoreId());
            $store = Mage::getModel('core/store')->load($queue->getStoreId());
            for($i = $firstPage; $i <= ($firstPage + $loops);$i++) {
                $parameters = array_merge(array('recordsOnPage' => $pageSize, 'pageNo'=>$i), $params);
                Mage::helper('Erply')->log("Erply request: ");
                Mage::helper('Erply')->log($parameters);
                $erplyCalls++;
                $result = $this->sendRequest('getProducts', $parameters);
                $return = "";
                Mage::helper('Erply')->log("Erply product import:");
                Mage::helper('Erply')->log($result);
                $output = json_decode($result, true);
                Mage::log('Eepohs_Erply_Model_Product_Import importProducts() Total API Calls:'.$erplyCalls,null,'erply_limit.log');
                $start = time();
                foreach($output["records"] as $_product) {

                    if($_product["code2"]) {
                        $sku = $_product["code2"];
                    } elseif($_product["code"]) {
                        $sku = $_product["code"];
                    } else {
                        $sku = $_product["code3"];
                    }
                    $product = Mage::getModel('catalog/product')
                        ->loadByAttribute('sku',$sku);

                    if(!$product){
                        $product = Mage::getModel('catalog/product')->load($_product["productID"]);
                        if(!$product->getName()) {
                            $product = new Mage_Catalog_Model_Product();
                            $product->setId($_product["productID"]);
                            Mage::helper('Erply')->log("Creating new product: ".$_product["productID"]);
                        } else {
                            Mage::helper('Erply')->log("Editing old product: ".$_product["productID"]);
                        }
                    }
                    // product does not exist so we will be creating a new one.
                    $product->setIsMassupdate(true);
                    $product->setExcludeUrlRewrite(true);
                    $product->setTypeId('simple');
                    $product->setWeight(1.0000);
                    $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                    $product->setStatus(1);
                    $product->setSku($sku);
                    $product->setTaxClassId(0);
                    $product->setAttributeSetId(4); // the product attribute set to use
                    $product->setName($_product["name"]);
                    $product->setCategoryIds(array($_product["groupID"])); // array of categories it will relate to
                    if (Mage::app()->isSingleStoreMode()) {
                        $product->setWebsiteIds(array(Mage::app()->getStore($queue->getStoreId())->getWebsiteId()));
                    }
                    else {
                        $product->setWebsiteIds(array($store->getWebsiteId()));
                    }
                    $product->setDescription($_product["longdesc"]);
                    $product->setShortDescription($_product["description"]);
                    $product->setPrice($_product["price"]);
                    $product->save();
                    Mage::helper('Erply')->log("Added: ".$product->getSku());
                }
                unset($output);
            }
        }
    }
}
