<?php
class DJ_CustomForm_Block_Adminhtml_Customform_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
		protected function _prepareForm()
		{

				$form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("customform_form", array("legend"=>Mage::helper("customform")->__("Item information")));

				
						$fieldset->addField("email", "text", array(
						"label" => Mage::helper("customform")->__("Email"),					
						"class" => "required-entry",
						"required" => true,
						"name" => "email",
						));
					
						$fieldset->addField("firstname", "text", array(
						"label" => Mage::helper("customform")->__("Firstname"),
						"name" => "firstname",
						));
					
						$fieldset->addField("lastname", "text", array(
						"label" => Mage::helper("customform")->__("Lastname"),
						"name" => "lastname",
						));
					
						$fieldset->addField("address", "textarea", array(
						"label" => Mage::helper("customform")->__("Address"),
						"name" => "address",
						));
					
						$fieldset->addField("phone", "text", array(
						"label" => Mage::helper("customform")->__("Phone Number"),
						"name" => "phone",
						));
					

				if (Mage::getSingleton("adminhtml/session")->getCustomformData())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getCustomformData());
					Mage::getSingleton("adminhtml/session")->setCustomformData(null);
				} 
				elseif(Mage::registry("customform_data")) {
				    $form->setValues(Mage::registry("customform_data")->getData());
				}
				return parent::_prepareForm();
		}
}
