<?php
class DJ_ProductFilter_IndexController extends Mage_Core_Controller_Front_Action{
	public function IndexAction() {  	
	    $this->loadLayout();
	    $this->getLayout()->removeOutputBlock('root')->addOutputBlock('productfilter_index');
	    $this->renderLayout();
	}

	public function brandfilterAction(){
		if(Mage::app()->getRequest()->getParam('filterBrandId')){
			Mage::getSingleton('core/session')->setData('filterBrandId', Mage::app()->getRequest()->getParam('filterBrandId'));
	    	}
		$this->_redirect('brands.html');
	}
}