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


class AW_Advancedreports_Model_Additional_Reports extends Varien_Object
{
    const UNIT_NAME_PREFIX = 'AW_ARUnit';

    protected $_additionalReports = array();

    public function getAdditionalReports()
    {
        if (!$this->_additionalReports) {
            $reports = array();
            # Collect additional reports data here
            $searchDir = Mage::getModuleDir('etc','AW_Advancedreports') . DS . "additional";
            if (!is_dir($searchDir)) {
                return array();
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
                        $element = simplexml_load_file($fileName);
                    } catch (Exception $e) {
                        //TODO Catch same error
                        continue;
                    }
                    if ($element) {
                        if (strtolower($element->$name->active) == "true") {
                            $sysName = self::UNIT_NAME_PREFIX . uc_words($name);
                            $item = Mage::getModel('advancedreports/reports_item');
                            $item->setName($name);
                            $item->setTitle((string)$element->$name->title);
                            $item->setVersion((string)Mage::getConfig()->getNode("modules/{$sysName}/version"));
                            $item->setRequiredVersion((string)$element->$name->required_version);
                            $item->setSortOrder((string)$element->$name->sort_order);
                            $reports[] = $item;
                        }

                    }
                }
            }
            $this->_additionalReports = $reports;
        }
        return $this->_additionalReports;
    }

    public function getCount()
    {
        return count($this->getAdditionalReports());
    }

    public function getTitle($name)
    {
        foreach ($this->getAdditionalReports() as $report) {
            if ($report->getName() == $name) {
                return $report->getTitle();
            }
        }
        return '';
    }

    public function getAllAdditionalReportsUrl()
    {
        return array(
            "salesbyproductattr" => array(
                'title' => 'Sales by Product Attributes',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-by-product-attributes.html"
            ),
            "manufacturer" => array(
                'title' => 'Sales by Manufacturer',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-by-manufacturer.html"
            ),
            "salesbycouponcode" => array(
                'title' => 'Sales by Coupon Code',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-by-coupon-code.html"
            ),
            "salesbycategory" => array(
                'title' => 'Sales by Category',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-by-category.html"
            ),
            "customersbycountry" => array(
                'title' => 'Customers by Country',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/customers-by-country.html"
            ),
            "newvsreturning" => array(
                'title' => 'New vs Returning Customers',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/new-vs-returning-customers.html"
            ),
            "salesbypaymenttype" => array(
                'title' => 'Sales by Payment Type',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-by-payment-type.html"
            ),
            "salesbyzipcode" => array(
                'title' => 'Sales by ZIP Code',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-by-zip-code.html"
            ),
            "salesstatistics" => array(
                'title' => 'Sales Statistics',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/sales-statistics.html"
            ),
            "userswishlists" => array(
                'title' => 'Users Wishlists',
                'url' => "http://ecommerce.aheadworks.com/magento-extensions/magento-analytics/users-wishlists.html"
            ),
        );
    }
}