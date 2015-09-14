<?php

class DJ_AdminLog_Block_Adminhtml_Productlog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("productlogGrid");
				$this->setDefaultSort("product_log_id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("adminlog/productlog")->getCollection()->setOrder('product_log_id','DESC');
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{
				$this->addColumn("product_log_id", array(
				"header" => Mage::helper("adminlog")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "product_log_id",
				));
                
				$this->addColumn("action", array(
				"header" => Mage::helper("adminlog")->__("Action"),
				"index" => "action",
				));
				$this->addColumn("product_name", array(
				"header" => Mage::helper("adminlog")->__("Product Name"),
				"index" => "product_name",
				));
				$this->addColumn("sku", array(
				"header" => Mage::helper("adminlog")->__("Sku"),
				"index" => "sku",
				));
				$this->addColumn("username", array(
				"header" => Mage::helper("adminlog")->__("Username"),
				"index" => "username",
				));
				$this->addColumn("email", array(
				"header" => Mage::helper("adminlog")->__("Email"),
				"index" => "email",
				));
					$this->addColumn('date', array(
						'header'    => Mage::helper('adminlog')->__('Date'),
						'index'     => 'date',
						'type'      => 'datetime',
					));
			$this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV')); 
			$this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			   return '#';
		}


		

}