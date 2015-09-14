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


class AW_Advancedreports_Helper_Date extends AW_Advancedreports_Helper_Data
{
    public function incSec($datetime)
    {
        $date = new Zend_Date(
            $datetime,
            self::MYSQL_ZEND_DATE_FORMAT,
            $this->getLocale()->getLocaleCode()
        );
        $date->addSecond(1);
        return $date->toString(self::MYSQL_ZEND_DATE_FORMAT);
    }

    public function decSec($datetime)
    {
        $date = new Zend_Date(
            $datetime,
            self::MYSQL_ZEND_DATE_FORMAT,
            $this->getLocale()->getLocaleCode()
        );
        $date->subSecond(1);
        return $date->toString(self::MYSQL_ZEND_DATE_FORMAT);
    }

    /**
     * Retrieves day period (timezone offset is included)
     *
     * @param string $datetime
     *
     * @return array
     */
    public function getThisDayPeriod($datetime)
    {
        $date = new Zend_Date(
            $datetime,
            Zend_Date::ISO_8601
        );

        $dateFrom = clone $date;
        $dateFrom->setHour(0)->setMinute(0)->setSecond(0)->addSecond($this->getTimeZoneOffset());

        $dateTo = clone $date;
        $dateTo->setHour(23)->setMinute(59)->setSecond(59)->addSecond($this->getTimeZoneOffset());

        return array(
            'from' => $dateFrom->toString(self::MYSQL_ZEND_DATE_FORMAT),
            'to'   => $dateTo->toString(self::MYSQL_ZEND_DATE_FORMAT)
        );
    }

    public function toTimestamp($date)
    {
        $dateObj = new Zend_Date();
        if ($date) {
            try {
                $dateObj = new Zend_Date($date, self::FRONTEND_ZEND_DATE_FORMAT, Mage_Core_Model_Locale::DEFAULT_LOCALE);
            } catch (Exception $ex) {
                    $timestamp = @strftime($date);
                    $dateObj = new Zend_Date($timestamp, null, $this->getLocale()->getLocaleCode());
            }
            $dateObj->setTime(0);
        }
        return $dateObj->getTimestamp();
    }

    public function fromTimestamp($timestamp, $format = Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
    {
        $dateFormat = Mage::app()->getLocale()->getDateFormat($format);
        $date = new Zend_Date();
        $date->setTimestamp($timestamp);
        return $date->toString($dateFormat);
    }

    public function getQuarterMonthStart($quaterValue)
    {
        switch ($quaterValue) {
            case 1:
                $result = 1;
                break;
            case 2:
                $result = 4;
                break;
            case 3:
                $result = 7;
                break;
            case 4:
                $result = 10;
                break;
            default:
                $result = 1;
        }
        return $result;
    }
}