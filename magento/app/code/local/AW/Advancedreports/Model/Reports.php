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


class AW_Advancedreports_Model_Reports extends Varien_Object
{
    const UNIT_NAME_PREFIX = 'AW_ARUnit';

    protected $_additionalReports = array();
    protected $_reports = array();

    public function getAdditionalReports()
    {
        if (!$this->_additionalReports) {
            $reports = array();
            $adminSession = Mage::getSingleton('admin/session');
            # Collect additional reports data here
            $unitsName = $this->_getUnitsName();
            foreach ($unitsName as $name) {
                if (!$adminSession->isAllowed('report/advancedreports/'.$name)) {
                    continue;
                }
                $moduleName = self::UNIT_NAME_PREFIX . ucfirst($name);
                $fileData = $this->_getFileData($moduleName);
                if (!$fileData) {
                    continue;
                }
                $report = $this->_createReportItem($name, $fileData);
                if (!$report) {
                    continue;
                }
                $reports[] = $report;

            }
            $this->_additionalReports = $reports;
        }
        return $this->_additionalReports;
    }

    protected function _getFileData($moduleName)
    {
        $searchDir = Mage::getModuleDir('etc', $moduleName);
        if (!is_dir($searchDir)) {
            return null;
        }
        $files = scandir($searchDir);
        if (!in_array('reports.xml', $files)) {
            return null;
        }
        $fileName = $searchDir . DS . 'reports.xml';
        if (!is_file($fileName)) {
            return null;
        }
        try {
            $fileData = simplexml_load_file($fileName);
        } catch (Exception $e) {
            //TODO Catch same error
            return null;
        }

        return $fileData;
    }

    protected function _createReportItem($name, $fileData)
    {
        try {
            $item = Mage::getModel('advancedreports/reports_item');
            $item->setName($name);
            $item->setTitle((string)$fileData->$name->title);
            $item->setType((string)$fileData->$name->type);
            $item->setAction((string)$fileData->$name->action);
            $item->setSortOrder((string)$fileData->$name->sort_order);
        } catch (Exception $e) {
            return null;
        }

        return $item;
    }

    protected function _getUnitsName()
    {
        $unitsName = array();
        $searchDir = Mage::getModuleDir('etc','AW_Advancedreports') . DS . "additional";
        if (!is_dir($searchDir)) {
            return $unitsName;
        }
        $files = scandir($searchDir);
        foreach ($files as $file) {
            $fileName = $searchDir . DS . $file;
            if (!is_file($fileName)) {
                continue;
            }
            $info = pathinfo($fileName);
            if (isset($info['extension']) && strtolower($info['extension']) == "xml") {
                $name = basename($fileName, ".xml");
                try {
                    $fileData = simplexml_load_file($fileName);
                } catch (Exception $e) {
                    //TODO Catch same error
                    continue;
                }
                if ($fileData && strtolower($fileData->$name->active) == "true") {
                    $unitsName[] = $name;
                }
            }
        }
        return $unitsName;
    }

    public function getReports()
    {
        if (!$this->_reports) {
            $reports = array();
            $fileData = $this->_getFileData("AW_Advancedreports");
            if (!$fileData) {
                return $reports;
            }
            $reportsInfoArray = (array)$fileData;

            $adminSession = Mage::getSingleton('admin/session');
            foreach ($reportsInfoArray as $reportName => $reportInfo) {
                if (!$adminSession->isAllowed('report/advancedreports/'.$reportName) && (string)$reportInfo->type != 'dashboard') {
                    continue;
                }
                $item = Mage::getModel('advancedreports/reports_item');
                $item->setName($reportName);
                $item->setTitle((string)$reportInfo->title);
                $item->setType((string)$reportInfo->type);
                $item->setAction((string)$reportInfo->action);
                $item->setSortOrder((string)$reportInfo->sort_order);
                $reports[] = $item;
            }
            $this->_reports = $reports;
        }
        return $this->_reports;
    }

    public function getReportByCode($code) {
        $additionalReports = $this->getAdditionalReports();
        $reports = $this->getReports();
        $allReports = array_merge($reports, $additionalReports);
        foreach($allReports as $report) {
            if ($report->getName() == $code) {
                return $report;
            }
        }
        return null;
    }

    public function isAdditionalReport($code) {
        $additionalReports = $this->getAdditionalReports();
        $result = false;
        foreach($additionalReports as $report) {
            if ($report->getName() == $code) {
                $result = true;
            }
        }
        return $result;
    }
}