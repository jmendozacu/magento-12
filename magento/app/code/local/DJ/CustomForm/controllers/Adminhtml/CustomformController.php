<?php

class DJ_CustomForm_Adminhtml_CustomformController extends Mage_Adminhtml_Controller_Action
{
		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("customform/customform")->_addBreadcrumb(Mage::helper("adminhtml")->__("Customform  Manager"),Mage::helper("adminhtml")->__("Customform Manager"));
				return $this;
		}
		public function indexAction() 
		{
			    $this->_title($this->__("CustomForm"));
			    $this->_title($this->__("Manager Customform"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{			    
			    $this->_title($this->__("CustomForm"));
				$this->_title($this->__("Customform"));
			    $this->_title($this->__("Edit Item"));
				
				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("customform/customform")->load($id);
				if ($model->getId()) {
					Mage::register("customform_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("customform/customform");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Customform Manager"), Mage::helper("adminhtml")->__("Customform Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Customform Description"), Mage::helper("adminhtml")->__("Customform Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("customform/adminhtml_customform_edit"))->_addLeft($this->getLayout()->createBlock("customform/adminhtml_customform_edit_tabs"));
					$this->renderLayout();
				} 
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("customform")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("CustomForm"));
		$this->_title($this->__("Customform"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("customform/customform")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("customform_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("customform/customform");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Customform Manager"), Mage::helper("adminhtml")->__("Customform Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Customform Description"), Mage::helper("adminhtml")->__("Customform Description"));


		$this->_addContent($this->getLayout()->createBlock("customform/adminhtml_customform_edit"))->_addLeft($this->getLayout()->createBlock("customform/adminhtml_customform_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {

						

						$model = Mage::getModel("customform/customform")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Customform was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setCustomformData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setCustomformData($this->getRequest()->getPost());
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
						$model = Mage::getModel("customform/customform");
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

		
		public function massRemoveAction()
		{
			try {
				$ids = $this->getRequest()->getPost('customform_ids', array());
				foreach ($ids as $id) {
                      $model = Mage::getModel("customform/customform");
					  $model->setId($id)->delete();
				}
				Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item(s) was successfully removed"));
			}
			catch (Exception $e) {
				Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
			}
			$this->_redirect('*/*/');
		}
			
		/**
		 * Export order grid to CSV format
		 */
		public function exportCsvAction()
		{
			$fileName   = 'customform.csv';
			$grid       = $this->getLayout()->createBlock('customform/adminhtml_customform_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
		} 
		/**
		 *  Export order grid to Excel XML format
		 */
		public function exportExcelAction()
		{
			$fileName   = 'customform.xml';
			$grid       = $this->getLayout()->createBlock('customform/adminhtml_customform_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
		}
}
