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


class AW_Advancedreports_Block_Widget_Grid_Column_Renderer_Percent
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency
{
    public function renderExport(Varien_Object $row)
    {
        $output = parent::render($row);
        if ($this->getColumn()->getType() == 'number') {
            $output = (int)$this->_getValue($row);
        }
        return $output;
    }

    public function render(Varien_Object $row)
    {
        $html = '';
        $output = parent::render($row);
        if ($this->getColumn()->getType() == 'number') {
            $output = (int)$this->_getValue($row);
        }
        $html .= $output;
        if ($this->getColumn()->getDir()
            || (!$this->getColumn()->getGrid()->getRequest()->getParam('sort')
                && $this->getColumn()->getIndex() == $this->getColumn()->getGrid()->getDefaultPercentField()
            )
        ) {
            $grandTotals = $this->getColumn()->getGrid()->getGrandTotals();
            $value = $this->_getValue($row);
            $total = $grandTotals[$this->getColumn()->getIndex()];
            if (!$total) {
                $total = 1;
            }
            $percent = round($value/$total*100, 2);

            $html .= '<span class="arep-sorted-percent">('. number_format($percent, 2) . '%)</span>';
        }
        return $html;
    }
}