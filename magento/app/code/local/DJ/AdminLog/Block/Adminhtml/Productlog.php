<?php


class DJ_AdminLog_Block_Adminhtml_Productlog extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_productlog";
	$this->_blockGroup = "adminlog";
	$this->_headerText = Mage::helper("adminlog")->__("Productlog Manager");
	$this->_addButtonLabel = Mage::helper("adminlog")->__("Add New Item");
	parent::__construct();
	$this->_removeButton('add');
	}

}