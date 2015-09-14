<?php
class Extendware_EWAuthenticator_Block_Adminhtml_Config_Form_Field_Ipaddress extends Extendware_EWCore_Block_Mage_Adminhtml_System_Config_Form_Field_Array_Abstract
{
	protected $_addAfter = false;
    public function __construct()
    {
    	$this->addColumn('key', array(
            'label' => $this->__('Label'),
    		'style'	=> 'width: 100px',
    		'class' => 'required-entry',
        ));
    	
        $this->addColumn('value', array(
            'label' => $this->__('IP Rule'),
        	'style'	=> 'width: 100px',
        	'class' => 'required-entry',
        ));
        
        $this->_addButtonLabel = $this->__('Add Rule');
        parent::__construct();
    }
}