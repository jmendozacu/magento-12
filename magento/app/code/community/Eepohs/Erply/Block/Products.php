<?php
/**
 * NB! This is a BETA release of Erply Connector.
 *
 * Use with caution and at your own risk.
 *
 * The author does not take any responsibility for any loss or damage to business
 * or customers or anything at all. These terms may change without further notice.
 *
 * License terms are subject to change. License is all-restrictive until
 * said otherwise.
 *
 * @author Eepohs Ltd
 */
class Eepohs_Erply_Block_Products extends Mage_Adminhtml_Block_Template
{
    protected function _toHtml()
    {
    	$erplyCalls = 0;

		$erply = Mage::getModel('Erply/Erply');
	    $erply->verifyUser(1);
	    $params = array("pageNo" => 1, "recordsOnPage" => 10);
	    $erplyCalls++;
		$result = $erply->sendRequest('getProducts', $params);

		$output = json_decode($result, true);
		$return = print_r($output, true);
		$out = print_r((Mage::getStoreConfig('eepohs_erply/product/attribute_set', 1)), true);

		Mage::log('Eepohs_Erply_Block_Products _toHtml() Total API Calls:'.$erplyCalls,null,'erply_limit.log');
		
		return $out."<br/>".$erply->getUrl() . "<pre>$return</pre>";
    }
}