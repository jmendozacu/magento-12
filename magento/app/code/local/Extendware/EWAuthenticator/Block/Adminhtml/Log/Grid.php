<?php

class Extendware_EWAuthenticator_Block_Adminhtml_Log_Grid extends Extendware_EWCore_Block_Mage_Adminhtml_Widget_Grid
{
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->setDefaultSort('log_id');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ewauthenticator/log')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('log_id', array(
        	//'type'		=> 'number',
            'header'    => $this->__('ID'),
        	'index'     => 'log_id',
            'align'     => 'right',
            'width'     => '50px',
        ));

        $this->addColumn('status', array(
            'header'    => $this->__('Status'),
            'index'     => 'status',
        	'type'      => 'options',
            'options'   => Mage::getSingleton('ewauthenticator/log_data_option_status')->toGridOptionArray(),
        	'width'     => '100px',
        ));

        $this->addColumn('username', array(
            'header'    => $this->__('Username'),
            'index'     => 'username',
        	'default'	=> ' ---- ',
        ));
		
        $this->addColumn('ip_address', array(
            'header'    => $this->__('IP Address'),
            'index'     => 'ip_address',
        	'default'	=> ' ---- ',
        ));
        
        $this->addColumn('verification_code', array(
            'header'    => $this->__('Verification Code'),
            'index'     => 'verification_code',
        	'default'	=> ' ---- ',
        ));
        
        $this->addColumn('created_at', array(
            'header'    => $this->__('Created'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => '155px',
            'gmtoffset' => true,
            'default'	=> ' ---- ',
        ));
		
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
    	return null;
    }

    protected function _prepareMassaction(){
        $this->setMassactionIdField('log_id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        return $this;
    }
}