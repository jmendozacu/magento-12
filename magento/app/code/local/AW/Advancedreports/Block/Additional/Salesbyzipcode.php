<?php
class AW_Advancedreports_Block_Additional_Salesbyzipcode extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'advancedreports';
        $this->_controller = 'additional_salesbyzipcode';
        $this->_headerText = Mage::helper('advancedreports')->__(
            Mage::helper('advancedreports/additional')->getReports()->getTitle('salesbyzipcode')
        );
        parent::__construct();
        $this->_removeButton('add');
    }
}
