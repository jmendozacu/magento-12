<?php
require_once 'Mage/Contacts/controllers/IndexController.php';

class DJ_ContactsPlus_IndexController extends Mage_Contacts_IndexController
{
     const XML_PATH_EMAIL_RECIPIENT  = 'contacts/email/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'contacts/email/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'contacts/email/email_template';
    const XML_PATH_ENABLED          = 'contacts/contacts/enabled';

    public function preDispatch()
    {
        parent::preDispatch();

        if( !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED) ) {
            $this->norouteAction();
        }
    }

    public function indexAction()
    {
		       // parent::indexAction();


        $this->loadLayout();
        $this->getLayout()->getBlock('contactForm')
            ->setFormAction( Mage::getUrl('*/*/post') );

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function postAction()
    {
        
        $formId = 'contact_form';
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        if ($captchaModel->isRequired()) {
            if (!$captchaModel->isCorrect($this->_getCaptchaString($this->getRequest(), $formId))) {
                    Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__('Incorrect CAPTCHA.'));
                    $this->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    Mage::getSingleton('customer/session')->setCustomerFormData($this->getRequest()->getPost());
                    $this->getResponse()->setRedirect(Mage::getUrl('contacts'));
                    return;
            }
        } 

        $post = $this->getRequest()->getPost();
        
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
				$_captchError = '';
				
                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                /*if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }
*/
                if ($error) {
                    /*throw new Exception();*/
                }
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    /*throw new Exception();*/
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded as soon as possible. Thank you for contacting us.'));
                $this->_redirect('contacts/');

                return;
            } catch (Exception $e) {

                
                $translate->setTranslateInline(true);
				if($_captchError == ''){
					Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
				} else {
                    
					Mage::getSingleton('customer/session')->addError($_captchError);
				}                
                $this->_redirect('contacts/');
                return;
            }

        } else {
            $this->_redirect('contacts/');
        }
    }
protected function _getCaptchaString($request, $formId) {
    $captchaParams = $request->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
    return $captchaParams[$formId];
} 

public function postwholesaleAction()
    {              
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
				$_captchError = '';
				if (trim($post['captcha']) != Mage::getSingleton('core/session')->getCaptcha()) {
					$_captchError = "Invalid captcha code. Please, try again.";
                    $error = true;
                }				

                if (!Zend_Validate::is(trim($post['first_name']) , 'NotEmpty')) {
                    $error = true;
                }
				 if (!Zend_Validate::is(trim($post['last_name']) , 'NotEmpty')) {
                    $error = true;
                }
                 if (!Zend_Validate::is(trim($post['city']) , 'NotEmpty')) {
                    $error = true;
                }
                  if (!Zend_Validate::is(trim($post['state']) , 'NotEmpty')) {
                    $error = true;
                }
                  if (!Zend_Validate::is(trim($post['country']) , 'NotEmpty')) {
                    $error = true;
                }
                
                  if (!Zend_Validate::is(trim($post['how_to_hear']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if ($error) {
                    throw new Exception();
                }     
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
				//echo self::XML_PATH_EMAIL_TEMPLATE;
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        4,
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

	              
    
    
                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }
    			


                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__(' Thank you for submitting your inquiry.'));
               $this->_redirect('wholesale-inquiry');
			   
			   

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);
				if($_captchError == ''){
					Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
				} else {
					Mage::getSingleton('customer/session')->addError($_captchError);
				}                
                $this->_redirect('wholesale-inquiry');
                return;
            }

        } else {
           $this->_redirect('wholesale-inquiry');
        }
    }
    			
          
          

public function postdistributorAction()
    {              
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
			

                if (!Zend_Validate::is(trim($post['first_name']) , 'NotEmpty')) {
                    $error = true;
                }
				 if (!Zend_Validate::is(trim($post['last_name']) , 'NotEmpty')) {
                    $error = true;
                }
                 if (!Zend_Validate::is(trim($post['city']) , 'NotEmpty')) {
                    $error = true;
                }
                  if (!Zend_Validate::is(trim($post['state']) , 'NotEmpty')) {
                    $error = true;
                }
                  if (!Zend_Validate::is(trim($post['country']) , 'NotEmpty')) {
                    $error = true;
                }
                
                  if (!Zend_Validate::is(trim($post['how_to_hear']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if ($error) {
                    throw new Exception();
                }     
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
				//echo self::XML_PATH_EMAIL_TEMPLATE;
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        5,
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

	              
    
    
                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }
    			


                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__(' Thank you for submitting your inquiry.'));
               $this->_redirect('distributor');
			   
			   

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('distributor');
                return;
            }

        } else {
           $this->_redirect('distributor');
        }
    }
    	
      
      
public function postinternationalAction()
    {              
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
			

                if (!Zend_Validate::is(trim($post['first_name']) , 'NotEmpty')) {
                    $error = true;
                }
				 if (!Zend_Validate::is(trim($post['last_name']) , 'NotEmpty')) {
                    $error = true;
                }
                 if (!Zend_Validate::is(trim($post['city']) , 'NotEmpty')) {
                    $error = true;
                }
                  if (!Zend_Validate::is(trim($post['state']) , 'NotEmpty')) {
                    $error = true;
                }
                  if (!Zend_Validate::is(trim($post['country']) , 'NotEmpty')) {
                    $error = true;
                }
                
                  if (!Zend_Validate::is(trim($post['how_to_hear']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if ($error) {
                    throw new Exception();
                }     
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
				//echo self::XML_PATH_EMAIL_TEMPLATE;
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        6,
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

	              
    
    
                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }
    			


                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__(' Thank you for submitting your inquiry.'));
               $this->_redirect('international');
			   
			   

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('international');
                return;
            }

        } else {
           $this->_redirect('international');
        }
    }

	public function postvaporstorewholesaleAction()
    {
        $post = $this->getRequest()->getPost();		
		$myfile = fopen(Mage::getBaseDir()."/captcha.txt", "r") or die("Unable to open file!");
		$_captcha = fread($myfile,filesize(Mage::getBaseDir()."/captcha.txt"));		
		fclose($myfile);
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
				$_captchError = '';
				if (trim($post['captcha']) != $_captcha) {
					$_captchError = "Invalid captcha code. Please, try again.";
                    $error = true;
                }				
                if (!Zend_Validate::is(trim($post['firstname']) , 'NotEmpty')) {
                    $error = true;
                } 
				if (!Zend_Validate::is(trim($post['lastname']) , 'NotEmpty')) {
                    $error = true;
                } 
				if (!Zend_Validate::is(trim($post['company']) , 'NotEmpty')) {
                    $error = true;
                } 
				if (!Zend_Validate::is(trim($post['type_of_distribution']) , 'NotEmpty')) {
                    $error = true;
                } 
				if (!Zend_Validate::is(trim($post['address']) , 'NotEmpty')) {
                    $error = true;
                } 
				if (!Zend_Validate::is(trim($post['city']) , 'NotEmpty')) {
                    $error = true;
                }
				if (!Zend_Validate::is(trim($post['state']) , 'NotEmpty')) {
                    $error = true;
                }
				if (!Zend_Validate::is(trim($post['zip']) , 'NotEmpty')) {
                    $error = true;
                }
				if (!Zend_Validate::is(trim($post['contry']) , 'NotEmpty')) {
                    $error = true;
                }
				if (!Zend_Validate::is(trim($post['telephone']) , 'NotEmpty')) {
                    $error = true;
                }

                /* if (!Zend_Validate::is(trim($post['msg']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['telephone']), 'NotEmpty')) {
                    $error = true;
                } */
				
                if ($error) {
                    throw new Exception();
                }				
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        8,
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig('trans_email/ident_custom2/email'),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('../../wholesale-contactus');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);
				if($_captchError == ''){
					Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
				} else {
					Mage::getSingleton('customer/session')->addError($_captchError);
				}                
                $this->_redirect('../../wholesale-contactus');
                return;
            }

        } else {
            $this->_redirect('../../wholesale-contactus');
        }
    }
	
	public function postvaporstoreAction()
    {
        $post = $this->getRequest()->getPost();
		$myfile = fopen(Mage::getBaseDir()."/captcha.txt", "r") or die("Unable to open file!");
		$_captcha = fread($myfile,filesize(Mage::getBaseDir()."/captcha.txt"));		
		fclose($myfile);
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;
				$_captchError = '';
				if (trim($post['captcha']) != $_captcha) {
					$_captchError = "Invalid captcha code. Please, try again.";
                    $error = true;
                }
                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['msg']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        7,
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('../../contact-us');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);
				if($_captchError == ''){
					Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
				} else {
					Mage::getSingleton('customer/session')->addError($_captchError);
				}                
                $this->_redirect('../../contact-us');
                return;
            }

        } else {
            $this->_redirect('../../contact-us');
        }
    }

}
