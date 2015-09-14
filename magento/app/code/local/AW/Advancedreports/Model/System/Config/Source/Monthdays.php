<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancedreports
 * @version    2.6.3
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancedreports_Model_System_Config_Source_Monthdays
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1', 'label' => Mage::helper('advancedreports')->__('1st')),
            array('value' => '2', 'label' => Mage::helper('advancedreports')->__('2nd')),
            array('value' => '3', 'label' => Mage::helper('advancedreports')->__('3rd')),
            array('value' => '4', 'label' => Mage::helper('advancedreports')->__('4th')),
            array('value' => '5', 'label' => Mage::helper('advancedreports')->__('5th')),
            array('value' => '6', 'label' => Mage::helper('advancedreports')->__('6th')),
            array('value' => '7', 'label' => Mage::helper('advancedreports')->__('7th')),
            array('value' => '8', 'label' => Mage::helper('advancedreports')->__('8th')),
            array('value' => '9', 'label' => Mage::helper('advancedreports')->__('9th')),
            array('value' => '10', 'label' => Mage::helper('advancedreports')->__('10th')),
            array('value' => '11', 'label' => Mage::helper('advancedreports')->__('11th')),
            array('value' => '12', 'label' => Mage::helper('advancedreports')->__('12th')),
            array('value' => '13', 'label' => Mage::helper('advancedreports')->__('13th')),
            array('value' => '14', 'label' => Mage::helper('advancedreports')->__('14th')),
            array('value' => '15', 'label' => Mage::helper('advancedreports')->__('15th')),
            array('value' => '16', 'label' => Mage::helper('advancedreports')->__('16th')),
            array('value' => '17', 'label' => Mage::helper('advancedreports')->__('17th')),
            array('value' => '18', 'label' => Mage::helper('advancedreports')->__('18th')),
            array('value' => '19', 'label' => Mage::helper('advancedreports')->__('19th')),
            array('value' => '20', 'label' => Mage::helper('advancedreports')->__('20th')),
            array('value' => '21', 'label' => Mage::helper('advancedreports')->__('21th')),
            array('value' => '22', 'label' => Mage::helper('advancedreports')->__('22th')),
            array('value' => '23', 'label' => Mage::helper('advancedreports')->__('23th')),
            array('value' => '24', 'label' => Mage::helper('advancedreports')->__('24th')),
            array('value' => '25', 'label' => Mage::helper('advancedreports')->__('25th')),
            array('value' => '26', 'label' => Mage::helper('advancedreports')->__('26th')),
            array('value' => '27', 'label' => Mage::helper('advancedreports')->__('27th')),
            array('value' => '28', 'label' => Mage::helper('advancedreports')->__('28th')),
            array('value' => '29', 'label' => Mage::helper('advancedreports')->__('29th')),
            array('value' => '30', 'label' => Mage::helper('advancedreports')->__('30th')),
            array('value' => '31', 'label' => Mage::helper('advancedreports')->__('31th')),
            array('value' => '32', 'label' => Mage::helper('advancedreports')->__('Last Day'))
        );
    }
}