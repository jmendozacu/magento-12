<?php

class DJ_AdminLog_Adminhtml_ProductlogController extends Mage_Adminhtml_Controller_Action
{
		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("adminlog/productlog")->_addBreadcrumb(Mage::helper("adminhtml")->__("Productlog  Manager"),Mage::helper("adminhtml")->__("Productlog Manager"));
				return $this;
		}
		public function indexAction() 
		{
			    $this->_title($this->__("AdminLog"));
			    $this->_title($this->__("Manager Productlog"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{			    
			    $this->_title($this->__("AdminLog"));
				$this->_title($this->__("Productlog"));
			    $this->_title($this->__("Edit Item"));
				
				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("adminlog/productlog")->load($id);
				if ($model->getId()) {
					Mage::register("productlog_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("adminlog/productlog");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Productlog Manager"), Mage::helper("adminhtml")->__("Productlog Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Productlog Description"), Mage::helper("adminhtml")->__("Productlog Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("adminlog/adminhtml_productlog_edit"))->_addLeft($this->getLayout()->createBlock("adminlog/adminhtml_productlog_edit_tabs"));
					$this->renderLayout();
				} 
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("adminlog")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("AdminLog"));
		$this->_title($this->__("Productlog"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("adminlog/productlog")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("productlog_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("adminlog/productlog");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Productlog Manager"), Mage::helper("adminhtml")->__("Productlog Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Productlog Description"), Mage::helper("adminhtml")->__("Productlog Description"));


		$this->_addContent($this->getLayout()->createBlock("adminlog/adminhtml_productlog_edit"))->_addLeft($this->getLayout()->createBlock("adminlog/adminhtml_productlog_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {

						

						$model = Mage::getModel("adminlog/productlog")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Productlog was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setProductlogData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setProductlogData($this->getRequest()->getPost());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					return;
					}

				}
				$this->_redirect("*/*/");
		}



		public function deleteAction()
		{
				if( $this->getRequest()->getParam("id") > 0 ) {
					try {
						$model = Mage::getModel("adminlog/productlog");
						$model->setId($this->getRequest()->getParam("id"))->delete();
						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item was successfully deleted"));
						$this->_redirect("*/*/");
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					}
				}
				$this->_redirect("*/*/");
		}

		
		/**
		 * Export order grid to CSV format
		 */
		public function exportCsvAction()
		{
			$fileName   = 'productlog.csv';
			$grid       = $this->getLayout()->createBlock('adminlog/adminhtml_productlog_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
		} 
		/**
		 *  Export order grid to Excel XML format
		 */
		public function exportExcelAction()
		{
			$fileName   = 'productlog.xml';
			$grid       = $this->getLayout()->createBlock('adminlog/adminhtml_productlog_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
		}
}
