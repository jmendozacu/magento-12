<?php
class Extendware_EWAuthenticator_Override_Mage_Adminhtml_IndexController extends Extendware_EWAuthenticator_Override_Mage_Adminhtml_IndexController_Bridge {
public function loginAction() {
		parent::loginAction();

		// this hack is the only way to be compatible with < 1.7
		if (Mage::helper('ewauthenticator/config')->isEnabled()) {
			$block = $this->getLayout()->createBlock('adminhtml/template')->setTemplate('extendware/ewauthenticator/login.phtml');
			$body = $this->getResponse()->getBody();
			$replace = (strpos($body, '<!--ewauthenticator:hole_punch-->') !== false ? '<!--ewauthenticator:hole_punch-->' : '<div class="form-buttons">');
			$this->getResponse()->setBody(str_replace($replace, $block->toHtml().'<div class="form-buttons">', $body));
		}
	}
}