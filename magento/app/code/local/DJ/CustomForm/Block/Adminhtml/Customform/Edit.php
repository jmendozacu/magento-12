<?php
	
class DJ_CustomForm_Block_Adminhtml_Customform_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "customform_id";
				$this->_blockGroup = "customform";
				$this->_controller = "adminhtml_customform";
				$this->_updateButton("save", "label", Mage::helper("customform")->__("Save Item"));
				$this->_updateButton("delete", "label", Mage::helper("customform")->__("Delete Item"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("customform")->__("Save And Continue Edit"),
					"onclick"   => "saveAndContinueEdit()",
					"class"     => "save",
				), -100);



				$this->_formScripts[] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
		}

		public function getHeaderText()
		{
				if( Mage::registry("customform_data") && Mage::registry("customform_data")->getId() ){

				    return Mage::helper("customform")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("customform_data")->getId()));

				} 
				else{

				     return Mage::helper("customform")->__("Add Item");

				}
		}
}