<?php

class AW_Advancedreports_Block_Widget_Grid_Column_Renderer_ReturningPercent
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Number
{

    protected function _getValue(Varien_Object $row)
    {
        $data = parent::_getValue($row);
        return $data . ' %';
    }
}
