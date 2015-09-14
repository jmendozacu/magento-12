<?php
class Extendware_EWAuthenticator_Block_Adminhtml_Log extends Extendware_EWCore_Block_Mage_Adminhtml_Widget_Grid_Container
{
	public function __construct()
	{
		parent::__construct();

		$this->_headerText = $this->__('Login Logs');
		$this->_removeButton('add');
	}
}
