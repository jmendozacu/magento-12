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


class AW_Advancedreports_Block_Reports_Pager extends Mage_Adminhtml_Block_Template
{
    const PAGES_DISPLAYED_COUNT = 2;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('advancedreports/pager.phtml');
    }

    public function getPageList()
    {
        $curPage = $this->getCollection()->getCurPage();
        $lastPage = $this->getCollection()->getLastPageNumber();
        $firstPage = 1;

        $displayCount = $lastPage;
        if ($displayCount > self::PAGES_DISPLAYED_COUNT) {
            $displayCount = self::PAGES_DISPLAYED_COUNT;
        }

        $pageCounter = $curPage;
        if ($curPage > $firstPage) {
            $pageCounter -= 1;
        }

        if ($curPage == $lastPage) {
            $pageCounter = $lastPage - $displayCount;
        }

        $pageList = array();

        for ($i = $firstPage; $i <= $lastPage; $i++) {
            $pageList[$i] = $i;
        }

        foreach ($pageList as $key => $page) {
            if ($page == $pageCounter && $displayCount >= 0) {
                $pageCounter++;
                $displayCount--;
                continue;
            }
            if ($page == $firstPage || $page == $lastPage || $page == $curPage) {
                continue;
            }
            unset($pageList[$key]);
        }
        //prepare "..." for pages
        $firstElem = reset($pageList);
        $nextElem = next($pageList);

        if ($nextElem - $firstElem > 1) {
            $pageList[$nextElem - $firstElem] = null;
        }
        $lastElem = end($pageList);
        $prevElem = prev($pageList);
        if ($lastElem - $prevElem > 1) {
            $key = key($pageList);
            $pageList[$key + 1] = null;
        }
        ksort($pageList);
        return $pageList;
    }

    public function getFirstEntry()
    {
        $pageSize = $this->getCollection()->getPageSize();
        $curPage  = $this->getCollection()->getCurPage();
        $firstEntry = 1 + ($curPage - 1) * $pageSize;
        if (!$this->getCollection()->getSize()) {
            $firstEntry = 0;
        }
        return $firstEntry;
    }

    public function getLastEntry()
    {
        $pageSize = $this->getCollection()->getPageSize();
        $curPage  = $this->getCollection()->getCurPage();
        $lastEntry = $pageSize + ($curPage - 1) * $pageSize;
        if ($lastEntry > $this->getCollection()->getSize()) {
            $lastEntry = $this->getCollection()->getSize();
        }
        return $lastEntry;
    }
}