<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product list
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_List extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    /**
     * Product Collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_productCollection;

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollection()
    {

        if (is_null($this->_productCollection)) {
            $layer = $this->getLayer();
            /* @var $layer Mage_Catalog_Model_Layer */
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }

            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                    ->setPage(1, 1)
                    ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }

            }

            $origCategory = null;
             
            if ($this->getCategoryId()) {

                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                    $this->addModelTags($category);
                }
            }

            $this->_productCollection = $layer->getProductCollection();


            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }

            


            /* Dharmesh Filter Start */

            if($this->getRequest()->getControllerName() != 'result'){

                $currentCategoryId = 74;
                if(Mage::registry('current_category')){
                    $currentCategoryId = Mage::registry('current_category')->getId();
                }

                if(Mage::registry('current_category')){
                    if(Mage::registry('current_category')->getUrlKey() == 'brands'){
                        $category = Mage::getModel('catalog/category')->load(74);
                        $layer->setCurrentCategory($category);
                        $this->_productCollection = $layer->getProductCollection();    
                    }else{
                        $category = Mage::getModel('catalog/category')->load(Mage::registry('current_category')->getId());
                        $layer->setCurrentCategory($category);
                        $this->_productCollection = $layer->getProductCollection();

                    }
                }

                if($currentCategoryId == 105 || $currentCategoryId == 119 || $currentCategoryId == 120 || $currentCategoryId == 121){ // Starter Kits
                    $this->_productCollection->joinField('position', 'catalog_category_product', 'position', 'product_id = entity_id', array('category_id' => $currentCategoryId), 'left');
                    $this->_productCollection->getSelect()->order('position ASC'); 
                }else{
                    $sortBy = 'bestseller';
                    if(Mage::app()->getRequest()->getParam('sortBy')){
                        $sortBy = Mage::app()->getRequest()->getParam('sortBy');
                    }
                    if($sortBy == 'bestseller'){
                        $this->_productCollection->getSelect()->order('qty_sold DESC');
                    }else{
                        $this->_productCollection->addAttributeToSort($sortBy, 'ASC');    
                    }
                }

                // Filter By Categories 
                $categoryIds = array();
                if(Mage::app()->getRequest()->getParam('categoryId')){
                    $categoryStr = Mage::app()->getRequest()->getParam('categoryId');
                    $categoryIds = explode('-', $categoryStr);
                }
                if(count($categoryIds)){
                    $this->_productCollection->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left');
                    $this->_productCollection->addAttributeToFilter('category_id', array('in' => $categoryIds));                
                }

                // Filter By Price
                $priceFilter = 0;
                if(Mage::app()->getRequest()->getParam('priceStart') || Mage::app()->getRequest()->getParam('priceEnd')){
                    $priceFilter = 1;
                }

                if(Mage::app()->getRequest()->getParam('priceStart') >= 0 && Mage::app()->getRequest()->getParam('priceEnd') >= 0){
                    $priceStart = Mage::app()->getRequest()->getParam('priceStart');
                    $priceEnd = Mage::app()->getRequest()->getParam('priceEnd');
                    $this->_productCollection->addFieldToFilter('price',  array('from' => $priceStart,'to' => $priceEnd));
                }

                // Filter By Color
                if(Mage::app()->getRequest()->getParam('color')){
                    $colorStr = Mage::app()->getRequest()->getParam('color');
                    $colorIds = explode('-', $colorStr);
                    $flag_filter = array();
                    foreach ($colorIds as $ids) {
                        $flag_filter[]["finset"] = array($ids);
                    }
                    $this->_productCollection->addAttributeToFilter('product_color',  $flag_filter);
                }

                // Filter By Brand
                if(Mage::app()->getRequest()->getParam('brands')){
                    $brandStr = Mage::app()->getRequest()->getParam('brands');
                    $brandIds = explode('-', $brandStr);
                    $this->_productCollection->addAttributeToFilter('manufacturer',  array('in' => $brandIds));
                }

                
                $minPriceTmp = 0;
                $maxPriceTmp = 0;

                if($currentCategoryId != 105 && $currentCategoryId != 119 && $currentCategoryId != 120 && $currentCategoryId != 121){ // Starter Kits
                    $productCollectionPrice = clone $this->_productCollection;
                    $i = 0;
                    foreach ($productCollectionPrice as $productTmp) {
                        if(!$i) $minPriceTmp = $productTmp->getPrice(); $i++;

                        if($productTmp->getSpecialPrice() && $productTmp->getSpecialPrice() != 0){
                            if($productTmp->getSpecialPrice() <= $minPriceTmp){
                                $minPriceTmp = $productTmp->getSpecialPrice();
                            }
                            if($productTmp->getSpecialPrice() >= $maxPriceTmp){
                                $maxPriceTmp = $productTmp->getSpecialPrice();
                            }
                        }else{
                            if($productTmp->getPrice() <= $minPriceTmp){
                                $minPriceTmp = $productTmp->getPrice();
                            }
                            if($productTmp->getPrice() >= $maxPriceTmp){
                                $maxPriceTmp = $productTmp->getPrice();
                            }
                        }
                    }
                }                

                Mage::register('min_price',$minPriceTmp);
                Mage::register('max_price',$maxPriceTmp);
                Mage::register('price_filter',$priceFilter);

                $pageDJ = 1;
                if(Mage::app()->getRequest()->getParam('pageDJ')){
                    $pageDJ = Mage::app()->getRequest()->getParam('pageDJ');
                }

                //echo "<pre>"; print_r($this->_productCollection->getData());exit;

                //$this->_productCollection->setPageSize(12)->setCurPage($pageDJ);

            }
            /* Dharmesh Filter End */
        }

        return $this->_productCollection;
    }

    /**
     * Get catalog layer model
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getLoadedProductCollection()
    {
        return $this->_getProductCollection();
    }

    /**
     * Retrieve current view mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->getChild('toolbar')->getCurrentMode();
    }

    /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * another block (was problem with search result)
     */
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        // called prepare sortable parameters
        $collection = $this->_getProductCollection();

        // use sortable parameters
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }

        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);

        $this->setChild('toolbar', $toolbar);
        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $this->_getProductCollection()
        ));

        $this->_getProductCollection()->load();

        return parent::_beforeToHtml();
    }

    /**
     * Retrieve Toolbar block
     *
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function getToolbarBlock()
    {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }

    /**
     * Retrieve additional blocks html
     *
     * @return string
     */
    public function getAdditionalHtml()
    {
        return $this->getChildHtml('additional');
    }

    /**
     * Retrieve list toolbar HTML
     *
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    public function setCollection($collection)
    {
        $this->_productCollection = $collection;
        return $this;
    }

    public function addAttribute($code)
    {
        $this->_getProductCollection()->addAttributeToSelect($code);
        return $this;
    }

    public function getPriceBlockTemplate()
    {
        return $this->_getData('price_block_template');
    }

    /**
     * Retrieve Catalog Config object
     *
     * @return Mage_Catalog_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('catalog/config');
    }

    /**
     * Prepare Sort By fields from Category Data
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Mage_Catalog_Block_Product_List
     */
    public function prepareSortableFieldsByCategory($category) {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($category->getAvailableSortByOptions());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = $category->getDefaultSortBy()) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve block cache tags based on product collection
     *
     * @return array
     */
    public function getCacheTags()
    {
        return array_merge(
            parent::getCacheTags(),
            $this->getItemsTags($this->_getProductCollection())
        );
    }
}
