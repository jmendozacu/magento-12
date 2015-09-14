<?php


class DJ_CustomForm_Block_Adminhtml_Customform extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_customform";
	$this->_blockGroup = "customform";
	$this->_headerText = Mage::helper("customform")->__("Customform Manager");
	$this->_addButtonLabel = Mage::helper("customform")->__("Add New Item");
	parent::__construct();
	
	}

}