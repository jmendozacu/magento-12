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

class AW_Advancedreports_Model_Export_Cron
{
    const LOCK_CACHE_PROCESS_EXPORT_REPORTS = 'AW_Advancedreports_Model_Export_Cron::precessedExportReport';
    const LOCK_CACHE_LIFETIME = 1800;

    const LOCK_CACHE_AFTER_SENT_ID = 'exportSentTimestamp';
    const LOCK_CACHE_AFTER_SENT_ADMIN = 1;
    const LOCK_CACHE_AFTER_SENT_PATH = 'export_timestamp';

    const DEFAULT_EXPORT_TIME = '00,00,00';

    public function precessedExportReport()
    {
        if ($this->_isLocked(self::LOCK_CACHE_PROCESS_EXPORT_REPORTS)) {
            return $this;
        }
        if ($this->_canSendReport()) {
            $this->_sendReports();
            $this->_copyReportsInFolder();

            $currentDate = Mage::app()->getLocale()->date();
            $currentDate->subTimestamp($currentDate->getGmtOffset());

            $lastSentTimestamp = Mage::getModel('advancedreports/option');
            $lastSentTimestamp
                ->setReportId(self::LOCK_CACHE_AFTER_SENT_ID)
                ->setAdminId(self::LOCK_CACHE_AFTER_SENT_ADMIN)
                ->setPath(self::LOCK_CACHE_AFTER_SENT_PATH)
                ->setValue($currentDate->getTimestamp())
                ->save();
        }
        Mage::app()->removeCache(self::LOCK_CACHE_PROCESS_EXPORT_REPORTS);
        return $this;
    }

    protected function _sendReports()
    {
        $exportPeriod = Mage::helper('advancedreports/export')->getExportPeriod();
        $datePeriods = Mage::helper('advancedreports')->getRangeValues();

        $from = null;
        $to = null;

        foreach($datePeriods as $period) {
            if (!isset($period['key']) || !($period['key'] == $exportPeriod)) {
                continue;
            }
            $from = $period['from'];
            $to = $period['to'];
        }

        $exportEmails = Mage::helper('advancedreports/export')->getExportEmails();
        $emailTemplateId = Mage::helper('advancedreports/export')->getEmailTemplate();
        $sender = Mage::helper('advancedreports/export')->getEmailSender();
        $exportList = Mage::helper('advancedreports/export')->getExportList();

        $exportPeriodVar = $from . ' - ' . $to;
        $exportListVar = '';
        foreach ($exportList as $reportName) {
            $report = Mage::getModel('advancedreports/reports')->getReportByCode($reportName);
            if (!$report) {
                continue;
            }
            $exportListVar .= "<li>{$report->getTitle()}</li>";
        }
        $frequencyValue = Mage::helper('advancedreports/export')->getFrequency();
        $frequencies = Mage::getModel('advancedreports/system_config_source_frequency')->toOptionHash();
        $frequency = '';

        if (array_key_exists($frequencyValue, $frequencies)) {
            $frequency = $frequencies[$frequencyValue];
        }

        $subject = 'Sales reports for ' . $exportPeriodVar;

        $data = array(
            'export_period'       => $exportPeriodVar,
            'export_reports_list' => $exportListVar,
            'frequency'           => $frequency
        );

        $attachments = $this->_generateAttachments($from, $to);

        foreach ($exportEmails as $email){
            $mailTemplate = Mage::getModel('core/email_template');
            $mailTemplate->setTemplateSubject($subject);

            foreach($attachments as $attachment) {
                if (!isset($attachment['content']) || !isset($attachment['filename'])) {
                    continue;
                }
                $attach = $mailTemplate->getMail()->createAttachment($attachment['content']);
                $attach->filename = $attachment['filename'];
            }

            $mailTemplate->sendTransactional(
                $emailTemplateId,
                $sender,
                $email,
                null,
                $data
            );
        }

        return $this;
    }

    protected function _copyReportsInFolder()
    {
        if (!$exportPath = Mage::helper('advancedreports/export')->getExportBackup()) {
            return $this;
        }

        $exportPeriod = Mage::helper('advancedreports/export')->getExportPeriod();
        $datePeriods = Mage::helper('advancedreports')->getRangeValues();

        $from = null;
        $to = null;

        foreach($datePeriods as $period) {
            if (!isset($period['key']) || !($period['key'] == $exportPeriod)) {
                continue;
            }
            $from = $period['from'];
            $to = $period['to'];
        }

        $attachments = $this->_generateAttachments($from, $to);
        foreach($attachments as $attachment) {
            if (!isset($attachment['content']) || !isset($attachment['filename'])) {
                continue;
            }
            $pathToBackupDir = Mage::getBaseDir() . '/' .$exportPath;
            if (!file_exists($pathToBackupDir)) {
                mkdir($pathToBackupDir);
            }

            $pathToBackupFile = $pathToBackupDir . $attachment['filename'];
            $backupFile = fopen($pathToBackupFile, 'w');
            if (!$backupFile) {
                return $this;
            }
            $fwrite = fwrite($backupFile, $attachment['content']);

            fclose($backupFile);
        }

        return $this;
    }

    protected function _canSendReport()
    {
        /** @var Zend_Date $currentDate */
        $currentDate = Mage::app()->getLocale()->date();
        $currentDate->subTimestamp($currentDate->getGmtOffset());

        //check timestamp for last export
        $lastSentTime = Mage::getModel('advancedreports/option')->load3params(
            self::LOCK_CACHE_AFTER_SENT_ID, self::LOCK_CACHE_AFTER_SENT_ADMIN, self::LOCK_CACHE_AFTER_SENT_PATH
        );

        //if email already sent at today
        if ($lastSentTime->getValue()) {
            $lastSentAsZendDate = new Zend_Date(null, null, Mage::app()->getLocale()->getLocaleCode());
            $lastSentAsZendDate->setTime($lastSentTime->getValue());
            $lastSentAsString = $lastSentAsZendDate->toString(Zend_Date::DATE_SHORT);
            $currentDateAsString = $currentDate->toString(Zend_Date::DATE_SHORT);
            if ($currentDateAsString === $lastSentAsString) {
                return false;
            }
        }

        $frequency = Mage::helper('advancedreports/export')->getFrequency();
        //check if valid day of month
        if ($frequency == AW_Advancedreports_Model_System_Config_Source_Frequency::MONTHLY_OPTION_VALUE) {
            $dayOfMonth = Mage::helper('advancedreports/export')->getMonthPeriod();
            $lastDayOfMonth = $currentDate->get(Zend_Date::MONTH_DAYS);
            if ($dayOfMonth > $lastDayOfMonth) {
                $dayOfMonth = $lastDayOfMonth;
            }
            if ($currentDate->toString(Zend_Date::DAY_SHORT) !== $dayOfMonth) {
                return false;
            }
        }

        //check if valid weekday
        if ($frequency == AW_Advancedreports_Model_System_Config_Source_Frequency::WEEKLY_OPTION_VALUE) {
            $weekdays = Mage::helper('advancedreports/export')->getWeekdays();
            if (!in_array($currentDate->toString(Zend_Date::WEEKDAY_DIGIT), $weekdays)) {
                return false;
            }
        }

        //check if valid time
        $exportAtTime = Mage::helper('advancedreports/export')->getTimePeriod();
        if (!$exportAtTime) {
            $exportAtTime = self::DEFAULT_EXPORT_TIME;
        }
        $exportDate = new Zend_Date(null, null, Mage::app()->getLocale()->getLocaleCode());
        $exportDate->setTime($exportAtTime);
        if ($exportDate->compare($currentDate) === 1) { //now is less than needed
            return false;
        }

        //check if emails configured
        $exportEmails = Mage::helper('advancedreports/export')->getExportEmails();
        if (!$exportEmails) {
            return false;
        }
        return true;
    }


    protected function _generateAttachments($from, $to)
    {
        $attachments = array();

        $exportList = Mage::helper('advancedreports/export')->getExportList();
        $fileFormat = Mage::helper('advancedreports/export')->getFileFormat();
        $reloadKey = Mage::helper('advancedreports/export')->getGroupBy();

        foreach ($exportList as $report) {
            if (!$report) {
                continue;
            }
            $reportName = 'advanced_'.$report;
            $name = null;
            if (Mage::getModel('advancedreports/reports')->isAdditionalReport($report)) {
                $reportName = 'additional_'.$report;
                $name = $report;
            }
            try {
                $block = Mage::app()->getLayout()->createBlock('advancedreports/'.$reportName.'_grid');
            } catch(Exception $e) {
                $block = null;
            }
            if (!$block) {
                continue;
            }
            Mage::unregister('aw_advancedreports_additional_name');
            Mage::register('aw_advancedreports_additional_name', $name);
            $block->getRequest()->setParam('sort', null);
            $block->setFilter('report_from', $from);
            $block->setFilter('report_to', $to);
            $block->setFilter('report_period', $reloadKey);
            $block->setFilter('reload_key', 'qty');
            $block->setParentFilters(true);
            $block->setIsExport(true);

            $filename = $report . 'Report_' . $from . '_' . $to;
            $filename .= '.' . $fileFormat;
            $content = '';
            if ($fileFormat == AW_Advancedreports_Model_System_Config_Source_Formats::CSV_OPTION_VALUE) {
                $content = $block->getCsv();
            }
            if ($fileFormat == AW_Advancedreports_Model_System_Config_Source_Formats::XML_OPTION_VALUE) {
                $content = $block->getExcel($filename);
            }

            $attachments[] = array('filename' => $filename, 'content' => $content);
        }

        return $attachments;
    }

    /**
     * @param $cacheId
     *
     * @return bool
     */
    protected function _isLocked($cacheId)
    {
        if (Mage::app()->loadCache($cacheId)) {
            return true;
        }
        Mage::app()->saveCache(time(), $cacheId, array(), self::LOCK_CACHE_LIFETIME);
        return false;
    }
}