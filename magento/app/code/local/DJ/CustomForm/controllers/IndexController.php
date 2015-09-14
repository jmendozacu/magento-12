<?php
class DJ_CustomForm_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
	  $this->loadLayout();   
    $this->getLayout()->getBlock("head")->setTitle($this->__("Contact"));
    $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
    $breadcrumbs->addCrumb("home", array(
          "label" => $this->__("Home Page"),
          "title" => $this->__("Home Page"),
          "link"  => Mage::getBaseUrl()
    ));

    $breadcrumbs->addCrumb("contact", array(
          "label" => $this->__("Contact"),
          "title" => $this->__("Contact")
    ));

    session_start();
    session_destroy();
    if(Mage::app()->getRequest()->getPost()){
      $post = $this->getRequest()->getPost();    
      $model = Mage::getModel('customform/customform')->setData($post);
      $insertId = $model->save()->getId();
      if($insertId > 0){
          $_SESSION['custom_id'] = 1;
          Mage::getSingleton('core/session')->addSuccess('Your inquiry has been submitted and we will be respond you as soon as possible');
      }   
      else{
          $_SESSION['custom_id'] = 0;
          Mage::getSingleton('core/session')->addError('There is some problem submitting your inquiry. please try again');
      }
    }

      $this->renderLayout(); 
	  
    }
}