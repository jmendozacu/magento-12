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


class AW_Advancedreports_Block_Advanced_Hours_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_HOURS;

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridHours');
        $this->setPagerVisibility(false);
    }

    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->prepareReportCollection();
        $this->_preparePage();
        $this->_prepareData();

        return $this;
    }

    public function prepareReportCollection()
    {
        $this->setCollection(Mage::getResourceModel('advancedreports/collection_hours'));
        $this->_prepareAbstractCollection();
        $storeIds = $this->getStoreIds();
        $this->getCollection()->setHourFilter(empty($storeIds));
        Mage::helper('advancedreports')->setNeedMainTableAlias(true);

        return $this;
    }

    protected function _addCustomData($row)
    {
        if (count($this->_customData)) {
            foreach ($this->_customData as &$d) {
                if ($d['hours'] === $row['hours']) {
                    $qty = $d['qty_ordered'];
                    $total = $d['total'];
                    unset($d['total']);
                    unset($d['qty_ordered']);
                    $d['total'] = $row['total'] + $total;
                    $d['qty_ordered'] = $row['qty_ordered'] + $qty;
                    return $this;
                }
            }
        }
        $this->_customData[] = $row;
        return $this;
    }

    /*
     * Prepare data array for Pie and Grid
     */
    protected function _prepareData()
    {
        for ($i = 0; $i < 24; $i++) {
            $row['hours'] = date('H:i', mktime($i, 0));
            $row['title'] = date('H', mktime($i, 0));
            $row['qty_ordered'] = 0;
            $row['total'] = 0;
            $this->_addCustomData($row);
        }

        foreach ($this->getCollection() as $order) {
            $row = array();

            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $row[$column->getIndex()] = $order->getData($column->getIndex());
                }
            }
            $row['hours'] = date('H:i', mktime($order->getHour(), 0));
            $row['qty_ordered'] = $order->getSumQty();
            $row['total'] = $order->getSumTotal();
            $this->_addCustomData($row);
        }

        if (!count($this->_customData)) {
            return $this;
        }

        $key = $this->getFilter('reload_key');
        if ($key === 'qty') {
            $this->setDefaultPercentField('qty_ordered');
            //All qty
            $qty = 0;
            foreach ($this->_customData as $d) {
                $qty += $d['qty_ordered'];
            }
            foreach ($this->_customData as $i => &$d) {
                $d['order'] = $i + 1;
                if ($qty) {
                    $d['percent_data'] = round($d['qty_ordered'] * 100 / $qty);
                    $d['data_for_chart'] = $d['qty_ordered'];
                } else {
                    $d['percent_data'] = 0;
                    $d['data_for_chart'] = $d['qty_ordered'];
                }
            }
        } elseif ($key === 'total') {
            $this->setDefaultPercentField('total');
            //All qty
            $total = 0;
            foreach ($this->_customData as $d) {
                $total += $d['total'];
            }
            foreach ($this->_customData as $i => &$d) {
                $d['order'] = $i + 1;
                if ($total) {
                    $d['percent_data'] = round($d['total'] * 100 / $total);
                    $d['data_for_chart'] = $d['total'];
                } else {
                    $d['percent_data'] = 0;
                    $d['data_for_chart'] = $d['total'];
                }
            }
        } else {
            return $this;
        }
        Mage::helper('advancedreports')->setChartData($this->_customData, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        parent::_prepareData();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'hours',
            array(
                'header' => $this->__('Hour'),
                'width'  => '60px',
                'align'  => 'right',
                'index'  => 'hours',
                'type'   => 'text',
            )
        );

        $this->addColumn(
            'qty_ordered',
            array(
                'header'   => $this->__('Quantity'),
                'width'    => '120px',
                'align'    => 'right',
                'index'    => 'qty_ordered',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number',
            )
        );

        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);
        $this->addColumn(
            'total',
            array(
                'header'        => $this->__('Total'),
                'width'         => '120px',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'index'         => 'total',
                'type'          => 'currency',
                'default'       => $defValue,
            )
        );

        $this->addExportType('*/*/exportOrderedCsv', $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel', $this->__('Excel'));

        return $this;
    }

    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_BARS;
    }
}