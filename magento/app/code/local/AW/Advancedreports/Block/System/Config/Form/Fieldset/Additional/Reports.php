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


class AW_Advancedreports_Block_System_Config_Form_Fieldset_Additional_Reports
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_dummyElement;
    protected $_fieldRenderer;
    protected $_values;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $allAdditionalReports = Mage::getModel('advancedreports/additional_reports')->getAllAdditionalReportsUrl();
        if (count($items = Mage::getModel('advancedreports/additional_reports')->getAdditionalReports())) {
            //TODO Sort by name
            foreach ($items as $item) {
                $url = '';
                if (array_key_exists($item->getName(), $allAdditionalReports)) {
                    $url = $allAdditionalReports[$item->getName()]['url'];
                    unset($allAdditionalReports[$item->getName()]);
                }

                $html .= $this->_getFieldHtml(
                    $element,
                    $item->getTitle(),
                    $item->getVersion(),
                    !Mage::helper('advancedreports/additional')->getVersionCheck($item),
                    $url
                );
            }
        }

        if (count($allAdditionalReports)) {
            foreach ($allAdditionalReports as $report) {
                $html .= $this->_getFieldHtml(
                    $element,
                    $report['title'],
                    '',
                    false,
                    $report['url'],
                    false
                );
            }
        }

        $html .= $this->_getFooterHtml($element);
        return $html;
    }

    protected function _getDummyElement()
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new Varien_Object(array('show_in_default' => 1, 'show_in_website' => 1));
        }
        return $this->_dummyElement;
    }

    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }
        return $this->_fieldRenderer;
    }

    protected function _getFieldHtml($fieldset, $moduleName, $ver, $isRed = false, $url, $installed = true)
    {
        $redOpen = $isRed ? '<span style="color: #E44D55;">' : '';
        $redClose = $isRed ? '</span>' : '';

        $urlOpen = $url ? '<a href="'.$url.'">': '';
        $urlClose = $url ? '</a>': '';

        $notInstalled = !$installed ? "<span style='color: #E44D55;'>{$this->__('Not installed')}</span>" : '';
        $ver = $ver ? $ver : $notInstalled;
        $html = '<tr><td class="label"><label>' . $redOpen . $urlOpen . $this->__($moduleName). $urlClose . $redClose .
            '</label></td><td class="value">' . $redOpen . $ver . $redClose . '</td></tr>';
        return $html;
    }
}