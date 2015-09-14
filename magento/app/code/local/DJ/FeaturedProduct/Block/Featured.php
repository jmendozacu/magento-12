<?php
class DJ_FeaturedProduct_Block_Featured extends Mage_Catalog_Block_Product_List
{
    protected $_productCollection;
    public function fetchProducts()
    {
        $collection = Mage::getResourceModel('catalog/product_collection');

        $attributes = Mage::getSingleton('catalog/config')
                ->getProductAttributes();

        $collection->addAttributeToSelect($attributes)
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addAttributeToFilter(array(array( // Flat Catalog Product workaround
                    'attribute' => 'inchoo_featured_product',
                    'eq' => 1,
                )), null, 'left')
                ->addStoreFilter()
                ->getSelect()->order('rand()')->limit(10);

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        $this->productCollection = $collection;

       /*$this->productCollection =Mage::getModel('catalog/product')->getCollection()
        ->addAttributeToSelect('*')
        ->addFieldToFilter('inchoo_featured_product', array('eq' => '1'));

        $this->productCollection->getSelect()->order(new Zend_Db_Expr('RAND()'));
        $this->productCollection->getSelect()->limit(10,0);*/
      return $this->productCollection;
    }
}