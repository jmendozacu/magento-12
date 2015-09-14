<?php


class Extendware_EWAuthenticator_Block_Adminhtml_Permissions_User_Edit_Tab_Authentication extends Extendware_EWCore_Block_Mage_Adminhtml_Widget_Form
{
	protected $authenticatorUser;
	protected function _prepareForm()
    {
        $form = new Extendware_EWCore_Block_Varien_Data_Form();
		$form->setHtmlIdPrefix('ewauthenticator_');
		
        $fieldset = $form->addFieldset('authentication', array(
        	'legend' => $this->__('Authentication Information'))
        );

        $fieldset->addField('mode', 'select', array(
        	'name'      => 'mode',
            'label'     => $this->__('Mode'),
            'values'	=> $this->getAuthenticatorUser()->getModeOptionModel()->toFormSelectOptionArray(),
        	'value'		=> $this->getAuthenticatorUser()->getMode() ? $this->getAuthenticatorUser()->getMode() : 'password',
        	'note' => $this->__('Verification code refers to the code given by Google Authenticator. If Password is selected, then the user will not need to provide a verification code.'),
            'required'  => true,
        ));
        
        $fieldset->addField('two_factor_mode', 'select', array(
        	'name'      => 'two_factor_mode',
            'label'     => $this->__('Two-Factor Mode'),
            'values'	=> $this->getAuthenticatorUser()->getTwoFactorModeOptionModel()->toFormSelectOptionArray(),
        	'value'		=> $this->getAuthenticatorUser()->getTwoFactorModeMode() ? $this->getAuthenticatorUser()->getTwoFactorModeMode() : 'verification_code',
        	'note' => $this->__('Allow the second factor to be the verification code only or verification code and IP address. IP addresses are configured in the extension configuration page.'),
            'required'  => true,
        ));
        
        $test = $fieldset->addField('verification_code', 'text', array(
            'name'  => 'verification_code',
            'label' => $this->__('Verification Code'),
            'value' => '',
        	'note' => $this->__('If you enabled verification code authentication / change secret key you must enter the current time-based passcode as appears in your Google authenticator app in order to save these changes.'),
        ));
        
        $test = $fieldset->addField('secret_key', 'text', array(
            'name'  => 'secret_key',
            'label' => $this->__('Secret Key'),
            'value' => $this->getAuthenticatorUser()->getSecretKey(),
        	'required' => true,
        	'note' => $this->__('This secret key will need to be given to the Google Authenticator (or other authenticator) so that your one time-based codes can be generated. Click away from field box to update QR Code.'),
        ));

        $fieldset->addField('qrcode', 'label', array(
            'name'  => 'qrcode',
        	'label' => $this->__('QR Code'),
        	'note' => $this->__('Scan this QR code by Google Authenticator to automatically add the account'),
        ));   
	        
        $form->addFieldNameSuffix('ewauthenticator');
		$form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    protected function getUser() {
    	return Mage::registry('permissions_user');
    }
    
    protected function getAuthenticatorUser() {
    	if (!$this->authenticatorUser) {
    		$this->authenticatorUser = Mage::helper('ewauthenticator')->getSetUser($this->getUser()->getId());
    	}
    	return $this->authenticatorUser;
    }
}
