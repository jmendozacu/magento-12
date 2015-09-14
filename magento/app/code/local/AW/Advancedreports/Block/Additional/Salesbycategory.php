<?php
class AW_Advancedreports_Block_Additional_Salesbycategory extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'advancedreports';
        $this->_controller = 'additional_salesbycategory';
        $this->_headerText = $this->__(
            Mage::helper('advancedreports/additional')->getReports()->getTitle('salesbycategory')
        );
        parent::__construct();
        $this->_removeButton('add');
    }

    protected function _prepareLayout()
    {
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        $headBlock->addItem('skin_js', 'aw_advancedreports/js/additional/salesbycategory.js');
        $headBlock->addItem('skin_css', 'aw_advancedreports/css/additional/salesbycategory.css');
        return parent::_prepareLayout();
    }
}
