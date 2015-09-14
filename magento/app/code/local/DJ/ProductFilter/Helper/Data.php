<?php
class DJ_ProductFilter_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
     * Array of available orders to be used for sort by
     *
     * @return array
     */
    public function getAvailableOrders()
    {
        return array(
            //'name' => $this->__('Name'),
            // 'price' => $this->__('Price'),
            // 'position' => $this->__('Position')
        );
    }
 
    /**
     * Return product collection to be displayed by our list block
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getProductCollection()
    {
    	$categoryIds = array();
    	if(Mage::app()->getRequest()->getParam('categoryId')){
            $categoryStr = Mage::app()->getRequest()->getParam('categoryId');
            $categoryIds = explode('-', $categoryStr);
    	}

        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->load()
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('in' => $categoryIds))
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
            
        $sortBy = 'bestseller';
        if(Mage::app()->getRequest()->getParam('sortBy')){
            $sortBy = Mage::app()->getRequest()->getParam('sortBy');

            if($sortBy == 'bestseller'){
                $storeId = (int) Mage::app()->getStore()->getId();
                $date = new Zend_Date();
                $toDate = $date->setDay(1)->getDate()->get('Y-MM-dd');
                $fromDate = $date->subMonth(1)->getDate()->get('Y-MM-dd');
                $collection->getSelect()
                    ->joinLeft(
                        array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                        "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId} AND aggregation.period BETWEEN '{$fromDate}' AND '{$toDate}'",
                        array('SUM(aggregation.qty_ordered) AS sold_quantity')
                    )
                    ->group('e.entity_id')
                    ->order(array('sold_quantity DESC', 'e.created_at'));
            }else if($sortBy == 'position'){
                if(isset($categoryIds[0])){
                    if($categoryIds[0] == 105 || $categoryIds[0] == 119 || $categoryIds[0] == 120 || $categoryIds[0] == 121){
                        $collection->joinField('position', 'catalog_category_product', 'position', 'product_id = entity_id', array('category_id' => $categoryIds[0]), 'left');
                        $collection->getSelect()->order('position ASC');        
                    }else{
                        $collection->addAttributeToSort('position', 'ASC');
                    }
                }
            }else{
                $collection->addAttributeToSort($sortBy, 'ASC');
            }
        }
		
        if(Mage::app()->getRequest()->getParam('priceStart') >= 0 && Mage::app()->getRequest()->getParam('priceEnd')){
            $priceStart = Mage::app()->getRequest()->getParam('priceStart');
            $priceEnd = Mage::app()->getRequest()->getParam('priceEnd');
            $collection->addFieldToFilter('price',  array('from' => $priceStart,'to' => $priceEnd));
        }

        if(Mage::app()->getRequest()->getParam('color')){
            $colorStr = Mage::app()->getRequest()->getParam('color');
            $colorIds = explode('-', $colorStr);
            $flag_filter = array();
            foreach ($colorIds as $ids) {
                $flag_filter[]["finset"] = array($ids);
            }
            $collection->addAttributeToFilter('product_color',  $flag_filter);
        }    

        if(Mage::app()->getRequest()->getParam('brands')){
            $brandStr = Mage::app()->getRequest()->getParam('brands');
            $brandIds = explode('-', $brandStr);
            $collection->addAttributeToFilter('manufacturer',  array('in' => $brandIds));
        }
        
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        
        return $collection;
    }
}	 