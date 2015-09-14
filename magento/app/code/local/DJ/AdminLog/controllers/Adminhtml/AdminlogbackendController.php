<?php
class DJ_AdminLog_Adminhtml_AdminlogbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Product Log"));
	   $this->renderLayout();
    }
}