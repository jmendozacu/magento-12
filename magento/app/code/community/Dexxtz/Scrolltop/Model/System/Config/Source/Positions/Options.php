<?php

/**
 * Copyright [2014] [Dexxtz]
 *
 * @package   Dexxtz_Scrolltop
 * @author    Dexxtz
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

class Dexxtz_Scrolltop_Model_System_Config_Source_Positions_Options
{    
    public function toOptionArray()
    {
    	$helper = Mage::helper('scrolltop');
    	$options = array( array("value" => $helper->__("left"), "label" => $helper->__("Left")),
	                      array("value" => $helper->__("right"), "label" => $helper->__("Right"))
	                    );       
        return $options;
    }
}