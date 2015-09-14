<?php
class DJ_CustomForm_Adminhtml_CustomformbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Custom"));
	   $this->renderLayout();
    }
}