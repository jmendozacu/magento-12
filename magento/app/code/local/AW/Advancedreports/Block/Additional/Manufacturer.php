<?php
class AW_Advancedreports_Block_Additional_Manufacturer extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'advancedreports';
        $this->_controller = 'additional_manufacturer';
        $this->_headerText = $this->__(
            Mage::helper('advancedreports/additional')->getReports()->getTitle('manufacturer')
        );
        parent::__construct();
        $this->_removeButton('add');
    }
}
