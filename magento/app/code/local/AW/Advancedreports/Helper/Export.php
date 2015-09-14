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


class AW_Advancedreports_Helper_Export extends AW_Advancedreports_Helper_Abstract
{
    const XML_PATH_SHEDULED_EMAIL_EXPORT_LIST = 'advancedreports/sheduled_email/export_list';
    const XML_PATH_SHEDULED_EMAIL_EXPORT_EMAIL = 'advancedreports/sheduled_email/export_email';
    const XML_PATH_SHEDULED_EMAIL_EXPORT_PERIOD = 'advancedreports/sheduled_email/export_period';
    const XML_PATH_SHEDULED_EMAIL_FREQUENCY = 'advancedreports/sheduled_email/frequency';
    const XML_PATH_SHEDULED_EMAIL_GROUP = 'advancedreports/sheduled_email/group_by';
    const XML_PATH_SHEDULED_EMAIL_WEEKDAYS = 'advancedreports/sheduled_email/weekdays';
    const XML_PATH_SHEDULED_EMAIL_MONTH_PERIOD = 'advancedreports/sheduled_email/month_period';
    const XML_PATH_SHEDULED_EMAIL_TIME_PERIOD = 'advancedreports/sheduled_email/time_period';
    const XML_PATH_SHEDULED_EMAIL_FILE_FORMAT = 'advancedreports/sheduled_email/file_format';
    const XML_PATH_SHEDULED_EMAIL_EXPORT_BACKUP = 'advancedreports/sheduled_email/export_backup';
    const XML_PATH_SHEDULED_EMAIL_EMAIL_SENDER = 'advancedreports/sheduled_email/email_sender';
    const XML_PATH_SHEDULED_EMAIL_EMAIL_TEMPLATE = 'advancedreports/sheduled_email/email_template';

    public function getExportList()
    {
        $exportList = Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_EXPORT_LIST);
        if ($exportList) {
            return explode(',', $exportList);
        }
        return array();
    }

    public function getExportEmails()
    {
        $exportEmails = Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_EXPORT_EMAIL);
        if ($exportEmails) {
            return explode(',', $exportEmails);
        }
        return array();
    }

    public function getExportPeriod()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_EXPORT_PERIOD);
    }

    public function getFrequency()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_FREQUENCY);
    }

    public function getWeekdays()
    {
        $weekdays = Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_WEEKDAYS);
        return explode(',', $weekdays);
    }

    public function getMonthPeriod()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_MONTH_PERIOD);
    }

    public function getTimePeriod()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_TIME_PERIOD);
    }

    public function getFileFormat()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_FILE_FORMAT);
    }

    public function getExportBackup()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_EXPORT_BACKUP);
    }

    public function getEmailSender()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_EMAIL_SENDER);
    }

    public function getEmailTemplate()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_EMAIL_TEMPLATE);
    }

    public function getGroupBy()
    {
        return Mage::getStoreConfig(self::XML_PATH_SHEDULED_EMAIL_GROUP);
    }
}