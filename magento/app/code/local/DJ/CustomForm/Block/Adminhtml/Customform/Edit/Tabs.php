<?php
class DJ_CustomForm_Block_Adminhtml_Customform_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
		public function __construct()
		{
				parent::__construct();
				$this->setId("customform_tabs");
				$this->setDestElementId("edit_form");
				$this->setTitle(Mage::helper("customform")->__("Item Information"));
		}
		protected function _beforeToHtml()
		{
				$this->addTab("form_section", array(
				"label" => Mage::helper("customform")->__("Item Information"),
				"title" => Mage::helper("customform")->__("Item Information"),
				"content" => $this->getLayout()->createBlock("customform/adminhtml_customform_edit_tab_form")->toHtml(),
				));
				return parent::_beforeToHtml();
		}

}
