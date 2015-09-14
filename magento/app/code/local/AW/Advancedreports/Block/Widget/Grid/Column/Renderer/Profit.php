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


class AW_Advancedreports_Block_Widget_Grid_Column_Renderer_Profit
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function renderExport(Varien_Object $row)
    {
        $value = round($this->_getValue($row));
        return $value . '%';
    }

    public function render(Varien_Object $row)
    {
        $value = round($this->_getValue($row));
        $width = round($value/2);
        if ($width > 50) {
            $width = 50;
        }
        $html = '<span class="arep-profit-margin-box" style="width: '.$width.'px"></span>';
        $html .= $value . '%';

        return $html;
    }
}