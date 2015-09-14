<?php
class AW_Advancedreports_Block_Additional_Salesbyproductattr extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'advancedreports';
        $this->_controller = 'additional_salesbyproductattr';
        $this->_headerText = Mage::helper('advancedreports')->__(
            Mage::helper('advancedreports/additional')->getReports()->getTitle('salesbyproductattr')
        );
        parent::__construct();
        $this->_removeButton('add');
    }

    protected function _prepareLayout()
    {
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        $headBlock->addItem('skin_js', 'aw_advancedreports/js/additional/salesbyproductattr.js');
        $headBlock->addItem('skin_css', 'aw_advancedreports/css/additional/salesbyproductattr.css');
        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $html = parent::getGridHtml();
        return $html . $this->_getAdditionalScriptHtml();
    }

    protected function _getAdditionalScriptHtml()
    {
        $json = Zend_Json::encode($this->_getProductAttributeConfig());
        return "<script type='text/javascript'>var awReportsUnit_Salesbyproductattr_DATA = " . $json . "</script>";
    }

    protected function _getProductAttributeConfig()
    {
        $config = array(
            'attributes'       => array(),
            'current_filter'   => array(),
            'titles'           => array(),
            'reportGridObject' => 'gridAdditionalSalesbyproductattrJsObject'
        );
        $productAttributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
        ;
        foreach ($productAttributeCollection as $attribute) {
            if (in_array($attribute->getFrontendInput(), array('media_image', 'gallery'))) {
                continue;
            }
            $config['attributes'][$attribute->getAttributeCode()] = array(
                'code' => $attribute->getAttributeCode(),
                'type' => $attribute->getFrontendInput(),
                'label' => $attribute->getFrontendLabel(),
                'options' => $attribute->getSource()->getAllOptions(false, false)
            );
        }
        $config['current_filter'] = $this->getChild('grid')->getProductAttributeFilter();

        $config['titles'] = array(
            'add_attribute'     => $this->__('Add Attribute'),
            'applyReport'       => $this->__('Apply'),
            'remove'            => $this->__('Remove'),
            'operand_and'       => $this->__('AND'),
            'operand_or'        => $this->__('OR'),
            'condition_eq'      => $this->__('is'),
            'condition_neq'     => $this->__('is not'),
            'condition_like'    => $this->__('contains'),
            'condition_nlike'   => $this->__('does not contain'),
            'condition_gteq'    => $this->__('equals or greater than'),
            'condition_lteq'    => $this->__('equals or less than'),
            'condition_gt'      => $this->__('greater than'),
            'condition_lt'      => $this->__('less than'),
            'condition_in'      => $this->__('is one of'),
            'condition_nin'     => $this->__('is not one of'),
        );
        return $config;
    }
}