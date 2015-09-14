<?php
class DJ_ProductFilter_Block_Catalog_Product_List extends Mage_Catalog_Block_Product_List
{

    /**
     * Default Category that is going to load
     *
     * @var string
     */
    protected $_defaultCategoryId = '12';

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
            $category = Mage::getModel('catalog/category')->load($this->_defaultCategoryId);
            if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                    $this->addModelTags($category);
            }

            $this->_productCollection = $layer->getProductCollection();

            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
        }

        return $this->_productCollection;
    }

    /*
        Calling method from view
    */
    public function getLoadedProductCollection()
    {
        return $this->_getProductCollection();
    }
}