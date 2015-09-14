<?php

class DJ_CustomForm_Block_Adminhtml_Customform_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("customformGrid");
				$this->setDefaultSort("customform_id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("customform/customform")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{
				$this->addColumn("customform_id", array(
				"header" => Mage::helper("customform")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "customform_id",
				));
                
				$this->addColumn("email", array(
				"header" => Mage::helper("customform")->__("Email"),
				"index" => "email",
				));
				$this->addColumn("firstname", array(
				"header" => Mage::helper("customform")->__("Firstname"),
				"index" => "firstname",
				));
				$this->addColumn("lastname", array(
				"header" => Mage::helper("customform")->__("Lastname"),
				"index" => "lastname",
				));
				$this->addColumn("phone", array(
				"header" => Mage::helper("customform")->__("Phone Number"),
				"index" => "phone",
				));
			$this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV')); 
			$this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			   return $this->getUrl("*/*/edit", array("id" => $row->getId()));
		}


		
		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('customform_id');
			$this->getMassactionBlock()->setFormFieldName('customform_ids');
			$this->getMassactionBlock()->setUseSelectAll(true);
			$this->getMassactionBlock()->addItem('remove_customform', array(
					 'label'=> Mage::helper('customform')->__('Remove Customform'),
					 'url'  => $this->getUrl('*/adminhtml_customform/massRemove'),
					 'confirm' => Mage::helper('customform')->__('Are you sure?')
				));
			return $this;
		}
			

}