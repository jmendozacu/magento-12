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


class AW_Advancedreports_Model_Mysql4_Report_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_from;
    protected $_to;
    protected $_period;

    protected $_model;

    protected $_intervals;

    protected $_storeIds;


    public function __construct()
    {

    }

    /**
     * Overrides standard periods
     *
     * @return array
     */
    public function getPeriods()
    {
        return array(
            'day'     => Mage::helper('advancedreports')->__('Day'),
            'week'    => Mage::helper('advancedreports')->__('Week'),
            'month'   => Mage::helper('advancedreports')->__('Month'),
            'quarter' => Mage::helper('advancedreports')->__('Quarter'),
            'year'    => Mage::helper('advancedreports')->__('Year'),
        );
    }

    public function setPeriod($period)
    {
        $this->_period = $period;
    }

    public function setInterval($from, $to)
    {
        $this->_from = $from;
        $this->_to = $to;
    }

    public function setStoreIds($storeIds)
    {
        $this->_storeIds = $storeIds;
    }

    public function getStoreIds()
    {
        return $this->_storeIds;
    }

    public function getSize()
    {
        return count($this->_intervals);
    }

    public function initReport($modelClass)
    {
        $this->_model = Mage::getModel('reports/report')
            ->setPageSize($this->getPageSize())
            ->setStoreIds($this->getStoreIds())
            ->initCollection($modelClass);
    }

    protected function processIntervals()
    {
        $timeZone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        $offset = Mage::getModel('core/date')->calculateOffset($timeZone);

        foreach ($this->_intervals as &$interval) {
            $dateStart = new Zend_Date($interval['start'], Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
            $dateStart->addTimestamp(-$offset);
            $interval['start'] = $dateStart->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
            $dateEnd = new Zend_Date($interval['end'], Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
            $dateEnd->addTimestamp(-$offset);
            $interval['end'] = $dateEnd->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
        }
    }

    /**
     * Overrides standard getIntervals
     *
     * @return array
     */
    public function getIntervals()
    {
        return $this->createIntervals();
    }

    public function getLimitedIntervals()
    {
        $offset = ($this->getCurPage() - 1) * $this->getPageSize();
        return array_slice($this->createIntervals(), $offset, $this->getPageSize(), true);
    }



    public function createIntervals()
    {
        if (!$this->_intervals) {
            $this->_intervals = array();
            if (!$this->_from && !$this->_to) {
                return $this->_intervals;
            }

            $t = array();

            $diff = 0;

            if ($this->_period == 'week') {
                $firstWeekDay = Mage::getStoreConfig('general/locale/firstday');
                $dateStart = new Zend_Date($this->_from, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
                $curWeekDay = $dateStart->toString('e');
                if ($curWeekDay > $firstWeekDay) {
                    $firstWeekDay += 7;
                }
                $diff = abs($curWeekDay - $firstWeekDay);
            }

            if ($this->_period == 'week' && ($diff > 0)) {
                $dateStart = new Zend_Date($this->_from, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
                $dateStart2 = new Zend_Date($this->_from, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
                $dateStart2->addDay($diff);

                $t['title'] = $dateStart->toString(Mage::app()->getLocale()->getDateFormat());
                $t['start'] = $dateStart->toString('yyyy-MM-dd 00:00:00');
                $dateStart->addDay($diff)->subDay(1);
                $t['title'] .= ' - ' . $dateStart->toString(Mage::app()->getLocale()->getDateFormat());
                $t['end'] = $dateStart->toString('yyyy-MM-dd 23:59:59');
                $dateStart->addDay(1);

                if (isset($t['title'])) {
                    $this->_intervals[$t['title']] = $t;
                }

                $dateStart2 = new Zend_Date($this->_from, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
                $dateEnd = new Zend_Date($this->_to, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());

            } else {
                $dateStart = new Zend_Date($this->_from, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
                $dateStart2 = new Zend_Date($this->_from, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
                $dateEnd = new Zend_Date($this->_to, Varien_Date::DATETIME_INTERNAL_FORMAT, Mage::app()->getLocale()->getLocaleCode());
            }

            while ($dateStart->compare($dateEnd) <= 0) {
                switch ($this->_period) {
                    case 'day' :
                        $t['title'] = $dateStart->toString(Mage::app()->getLocale()->getDateFormat());
                        $t['start'] = $dateStart->toString('yyyy-MM-dd 00:00:00');
                        $t['end'] = $dateStart->toString('yyyy-MM-dd 23:59:59');
                        $dateStart->addDay(1);
                        break;
                    case 'week':
                        $t['title'] = $dateStart->toString(Mage::app()->getLocale()->getDateFormat());
                        $t['start'] = $dateStart->toString('yyyy-MM-dd 00:00:00');
                        $dateStart->addWeek(1)->subDay(1);
                        $t['title'] .= ' - ' . $dateStart->toString(Mage::app()->getLocale()->getDateFormat());
                        $t['end'] = $dateStart->toString('yyyy-MM-dd 23:59:59');
                        $dateStart->addDay(1);
                        break;
                    case 'month':
                        $t['title'] = $dateStart->toString('MM/yyyy');
                        $t['start'] = $dateStart->toString('yyyy-MM-dd 00:00:00');
                        $dateStart->setDay(date('t', $dateStart->getTimestamp()));
                        if ($dateStart->compare($dateEnd) > 0) {
                            $t['end'] = $dateEnd->toString('yyyy-MM-dd 23:59:59');
                        } else {
                            $t['end'] = $dateStart->toString('yyyy-MM-dd 23:59:59');
                        }
                        $dateStart->addMonth(1);
                        $dateStart->setDay(1);
                        break;
                    case 'quarter':
                        $month = (integer)$dateStart->toString('MM');
                        $currentQuater = ceil($month / 3);
                        $quaterMonthStart = Mage::helper('advancedreports/date')->getQuarterMonthStart($currentQuater);

                        $t['title'] = Mage::helper('advancedreports')->__('Q') . $currentQuater . $dateStart->toString('/yyyy');
                        $t['start'] = $dateStart->toString('yyyy-MM-dd 00:00:00');
                        $dateStart->setMonth($quaterMonthStart);
                        $dateStart->addMonth(2);
                        $dateStart->setDay(date('t', $dateStart->getTimestamp()));
                        if ($dateStart->compare($dateEnd) > 0) {
                            $t['end'] = $dateEnd->toString('yyyy-MM-dd 23:59:59');
                        } else {
                            $t['end'] = $dateStart->toString('yyyy-MM-dd 23:59:59');
                        }
                        $dateStart->addMonth(1);
                        $dateStart->setDay(1);
                        break;
                    case 'year':
                        $t['title'] = $dateStart->toString('yyyy');
                        $t['start'] = $dateStart->toString('yyyy-MM-dd 00:00:00');
                        $dateStart->setMonth(12);
                        $dateStart->setDay(date('t', $dateStart->getTimestamp()));
                        if ($dateStart->compare($dateEnd) > 0) {
                            $t['end'] = $dateEnd->toString('yyyy-MM-dd 23:59:59');
                        } else {
                            $t['end'] = $dateStart->toString('yyyy-MM-dd 23:59:59');
                        }
                        $dateStart->addYear(1);
                        $dateStart->setMonth(1);
                        $dateStart->setDay(1);
                        break;
                    default:
                        Mage::throwException("Report tried to get intervals without a period.");
                }
                if (isset($t['title'])) {
                    $this->_intervals[$t['title']] = $t;
                }
            }

            if ($this->_period != 'day') {
                $titles = array_keys($this->_intervals);
                if (count($titles) > 0) {
                    $this->_intervals[$titles[0]]['start'] = $dateStart2->toString('yyyy-MM-dd 00:00:00');
                    $this->_intervals[$titles[count($titles) - 1]]['end'] = $dateEnd->toString('yyyy-MM-dd 23:59:59');
                    if ($this->_period == 'week') {
                        $t = $this->_intervals[$titles[count($titles) - 1]];
                        unset($this->_intervals[$titles[count($titles) - 1]]);
                        $date = new Zend_Date($t['start'], 'yyyy-MM-dd 00:00:00', Mage::app()->getLocale()->getLocaleCode());
                        $t['title'] = $date->toString(Mage::app()->getLocale()->getDateFormat());
                        unset($date);
                        $date = new Zend_Date($t['end'], 'yyyy-MM-dd 23:59:59', Mage::app()->getLocale()->getLocaleCode());
                        $t['title'] .= ' - ' . $date->toString(Mage::app()->getLocale()->getDateFormat());
                        $this->_intervals[$t['title']] = $t;
                    }
                }
            }
            $this->processIntervals();
        }
        return $this->_intervals;
    }

    public function getReportFull($from, $to)
    {
        return $this->_model->getReportFull($this->timeShift($from), $this->timeShift($to));
    }

    public function getReport($from, $to)
    {
        return $this->_model->getReport($this->timeShift($from), $this->timeShift($to));
    }

    public function timeShift($datetime)
    {
        return date('Y-m-d H:i:s', strtotime($datetime) - Mage::getModel('core/date')->getGmtOffset());
    }

    public function setSize($size)
    {
        $this->_totalRecords = $size;
        return $this;
    }
}