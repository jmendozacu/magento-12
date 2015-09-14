<?php
class Extendware_EWAuthenticator_Block_Override_Mage_Adminhtml_Permissions_User_Edit_Tabs extends Extendware_EWAuthenticator_Block_Override_Mage_Adminhtml_Permissions_User_Edit_Tabs_Bridge
{
	protected function _beforeToHtml()
    {
    	if ($this->getUser()->getId() > 0) {
			$this->addTabAfter('authenticator_section', array(
	            'label'     => Mage::helper('adminhtml')->__('Two-Factor Settings'),
	            'title'     => Mage::helper('adminhtml')->__('Two-Factor Settings'),
	            'content'   => $this->getLayout()->createBlock('ewauthenticator/adminhtml_permissions_user_edit_tab_authentication')->toHtml(),
	        ), 'roles_section');
    	}
        return parent::_beforeToHtml();
    }
    
	protected function getUser() {
    	return Mage::registry('permissions_user');
    }
}
