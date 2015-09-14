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


class AW_Advancedreports_SalesdetailedController extends AW_Advancedreports_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('report/advancedreports/salesdetailed');
    }

    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('report/advancedreports')
            ->_setSetupTitle(Mage::helper('advancedreports')->__('Sales Detailed'))
            ->_addContent($this->getLayout()->createBlock('advancedreports/advanced_salesdetailed'))
            ->renderLayout();
    }

    public function exportOrderedCsvAction()
    {
        $fileName = 'product.csv';
        $content = $this->getLayout()
            ->createBlock('advancedreports/advanced_salesdetailed_grid')
            ->setIsExport(true)
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportOrderedExcelAction()
    {
        $fileName = 'product.xml';
        $content = $this->getLayout()
            ->createBlock('advancedreports/advanced_salesdetailed_grid')
            ->setIsExport(true)
            ->getExcelFile($fileName);

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function gridAction()
    {
        Mage::register(AW_Advancedreports_Block_Adminhtml_Setup::DATA_KEY_SECURE_CHECK, 'report/advancedreports', true);
        Mage::register(AW_Advancedreports_Block_Adminhtml_Setup::DATA_KEY_REPORT_TITLE, 'Sales Detailed', true);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('advancedreports/advanced_salesdetailed_grid')->toHtml()
        );
    }
}
