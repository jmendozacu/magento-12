<?php

class AW_Advancedreports_Block_Additional_Userswishlists_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Additional_Userswishlists::ROUTE_ADDITIONAL_USERSWHISHLISTS;
    protected $_stores;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalUserswishlists');
        $this->setUseAjax(true);
        $this->setFilterVisibility(true);
    }

    public function getHideShowBy()
    {
        return true;
    }

    public function hasRecords()
    {
        return false;
    }

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return;
    }

    public function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->prepareReportCollection();
        $this->_preparePage();
        $this->_prepareData();

        return $this;
    }

    public function prepareReportCollection()
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_userswishlists');
        $this->setCollection($collection);

        $this
            ->_setUpReportKey()
            ->_setUpFilters()
        ;

        $collection
            ->addCustomerName()
            ->addAttributeToSelect('email')
        ;
        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));

        $collection->setDateFilter($dateFrom, $dateTo);
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $collection->addWishlist()
            ->addProductInfo()
            ->addCustomerGroup()
        ;

        $this->_saveFilters();
        $this->_setColumnFilters();

        return $this;
    }

    public function getStore($store_id = null)
    {
        if (!$store_id) {
            return null;
        }
        if (!$this->_stores) {
            $this->_stores = Mage::app()->getStores();
        }
        if (isset($this->_stores[$store_id])) {
            return $this->_stores[$store_id];
        }
        return null;
    }

    protected function _prepareData()
    {
        foreach ($this->getCollection() as $row) {
            /** @var $row Mage_Wishlist_Model_Item */
            if ($row->getStoreId()) {
                $row->setStoreName($this->getStore($row->getStoreId())->getName());
            }
            if ($row->getAddedAt()) {
                $row->setDaysInWishlist(round((time() - strtotime($row->getAddedAt())) / 86400));
            }
            $row->setVisibleIn($row->getStoreId());

            $this->_addCustomData($row->getData());
        }

        Mage::helper('advancedreports')->setChartData($this->_customData, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        parent::_prepareData();
        return $this;
    }

    protected function _prepareColumns()
    {
        $wishlistModel = Mage::getResourceModel('advancedreports/collection_additional_userswishlists');
        $this->addColumn(
            'firstname',
            array(
                 'header' => $this->__('First Name'),
                 'index'  => 'firstname',
                 'type'   => 'text',
                 'width'  => '80px',
                 'filter_index' => $wishlistModel->getAttributeTableAlias('firstname').'.value',
            )
        );

        $this->addColumn(
            'lastname',
            array(
                 'header' => $this->__('Last Name'),
                 'index'  => 'lastname',
                 'type'   => 'text',
                 'width'  => '80px',
                 'filter_index'  => $wishlistModel->getAttributeTableAlias('lastname').'.value',
            )
        );

        $this->addColumn(
            'email',
            array(
                 'header' => $this->__('Email'),
                 'index'  => 'email',
                 'type'   => 'text',
                 'width'  => '80px',
            )
        );

        $this->addColumn(
            'customer_group',
            array(
                 'header' => $this->__('Customer Group'),
                 'index'  => 'customer_group',
                 'type'   => 'text',
                 'width'  => '80px',
                 'filter_index'  => 'c_gr.customer_group_code',
            )
        );

        $this->addColumn(
            'sku',
            array(
                 'header' => $this->__('SKU'),
                 'index'  => 'sku',
                 'type'   => 'text',
                 'width'  => '80px',
                 'filter_index'  => 'product.sku',
            )
        );

        $this->addColumn(
            'product_name',
            array(
                 'header' => $this->__('Product Name'),
                 'index'  => 'product_name',
                 'type'   => 'text',
                 'width'  => '120px',
                 'filter_index'  => 'value.value',
            )
        );

        $this->addColumn(
            'description',
            array(
                 'header' => $this->__('User Description'),
                 'index'  => 'description',
                 'type'   => 'text',
                 'escape' => true,
                 'width'  => '120px',
                 'filter_index'  => 'wish_item.description',
            )
        );

        $this->addColumn(
            'visible_in',
            array(
                 'header' => Mage::helper('customer')->__('Visible In'),
                 'index'  => 'store_id',
                 'type'   => 'store',
                 'width'  => '100px',
                 'filter_index'  => 'wish_item.store_id',

            )
        );

        $this->addColumn(
            'added_at',
            array(
                 'header'    => Mage::helper('customer')->__('Date Added'),
                 'index'     => 'added_at',
                 'gmtoffset' => true,
                 'type'      => 'date',
                 'filter'    => false
            )
        );

        $this->addColumn(
            'days_in_wishlist',
            array(
                 'header' => Mage::helper('customer')->__('Days in Wishlist'),
                 'index'  => 'days_in_wishlist',
                 'type'   => 'number',
                 'width'  => '100px',
                 'filter' => false
            )
        );

        $this->addExportType('*/*/exportOrderedCsv/name/' . $this->_getName(), $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel/name/' . $this->_getName(), $this->__('Excel'));

        return $this;
    }

    public function getCustomVarData()
    {
        # Old method
        if ($this->_customVarData) {
            return $this->_customVarData;
        }
        $this->_customVarData = array();
        foreach ($this->_customData as $d) {
            $obj = new Varien_Object();
            $obj->setData($d);
            $this->_customVarData[] = $obj;
        }
        if (!$this->hasAggregation()) {
            if ($this->_customVarData && is_array($this->_customVarData) && $this->_getSort() && $this->_getDir()) {
                @usort($this->_customVarData, array(&$this, "_compareVarDataElements"));
            }
        }
        return $this->_customVarData;
    }

    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_PIE3D;
    }

    public function getGridUrl()
    {
        $params = Mage::app()->getRequest()->getParams();
        $params['_secure'] = Mage::app()->getStore(true)->isCurrentlySecure();
        return $this->getUrl('*/*/grid', $params);
    }
}
