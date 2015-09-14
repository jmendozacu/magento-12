<?php

require_once 'Mage/Adminhtml/controllers/Sales/Order/CreateController.php';

/**
 * Admihtml ThreeStep Payment Controller
 *
 * @category   Local
 * @package    Posixtech_NetworkMerchants_Adminhtml
 * @author     GPS
 */
class Posixtech_NetworkMerchants_Adminhtml_PaymentController
    extends Mage_Adminhtml_Sales_Order_CreateController
{
    /**
     * Get session model
     *
     * @return Posixtech_NetworkMerchants_Adminhtml_Model_Session
     */
    protected function _getNetworkMerchantsSession()
    {
        return Mage::getSingleton('networkmerchants/session');
    }

    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getOrderSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Retrieve order create model
     *
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }

    /**
     * Send step 1 request to GPS and build step 2 for submit
     *
     */
    public function placeAction()
    {
        $paymentParam = $this->getRequest()->getParam('payment');
        $controller = $this->getRequest()->getParam('controller');
        $this->getRequest()->setPost('collect_shipping_rates', 1);
        $this->_processActionData('save');
        
        //get confirmation by email flag
        $orderData = $this->getRequest()->getPost('order');
        
        // Email confirmation checkbox 
        $sendConfirmationFlag = 0;
        if ($orderData) {
            $sendConfirmationFlag = (!empty($orderData['send_confirmation'])) ? 1 : 0;
        } else {
            $orderData = array();
        }

        if (isset($paymentParam['method'])) {
            $saveOrderFlag = Mage::getStoreConfig('payment/'.$paymentParam['method'].'/create_order_before');
            $result = array();
            $params = Mage::helper('threestep')->getSaveOrderUrlParams($controller);
            
            //create order partially
            $this->_getOrderCreateModel()->setPaymentData($paymentParam);
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentParam);

            $orderData['send_confirmation'] = 0;
            $this->getRequest()->setPost('order', $orderData);

            try {
                $oldOrder = $this->_getOrderCreateModel()->getSession()->getOrder();
                $oldOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);

                $order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($this->getRequest()->getPost('order'))
                    ->createOrder();
                         
                $order->setSendConfirmationFlag($sendConfirmationFlag);
                
                $adminUrl = Mage::getSingleton('adminhtml/url');
                if ($adminUrl->useSecretKey()) {
                    $order->setKey(
                            $adminUrl->getSecretKey('payment','redirect')
                    );
                }
                
                $order->setControllerActionName($controller);                

                $payment = $order->getPayment();
                if ($payment && $payment->getMethod() == Mage::getModel('networkmerchants/paymentmethod')->getCode()) {
                    //return json with data.
                    $session = $this->_getNetworkMerchantsSession();
                    $session->addCheckoutOrderIncrementId($order->getIncrementId());
                    $session->setLastOrderIncrementId($order->getIncrementId());

                    $requestToPaygate = $payment->getMethodInstance()->generateRequestFromOrder($order);
                    $requestToPaygate->setStoreId($this->_getOrderCreateModel()->getQuote()->getStoreId());

                    $threestep = Mage::getModel('networkmerchants/paymentmethod');
                    
                    // Submit Step One
                    $formUrl = $threestep->doStepOne($requestToPaygate->getData());
                    $result['formUrl'] = $formUrl;

                    $result['networkmerchants'] = array('fields' => $requestToPaygate->getData());
                }

                $result['success'] = 1;
                $isError = false;
            }
            catch (Mage_Core_Exception $e) {
                $message = $e->getMessage();
                if( !empty($message) ) {
                    $this->_getSession()->addError($message);
                }
                $isError = true;
            }
            catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
                $isError = true;
            }

            if ($isError) {
                $result['success'] = 0;
                $result['error'] = 1;
                $result['redirect'] = Mage::getSingleton('adminhtml/url')->getUrl('*/sales_order_create/');
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
        else {
            $result = array(
                'error_messages' => $this->__('Please, choose payment method')
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Retrieve params and put javascript into iframe
     *
     */
    public function redirectAction()
    {
       
        $redirectParams = $this->getRequest()->getParams();

        $params = array();
        if (!empty($redirectParams['success']) && isset($redirectParams['controller_action_name'])
        ) {
            $params['redirect_parent'] = Mage::helper('networkmerchants')->getSuccessOrderUrl($redirectParams);
            $this->_getNetworkMerchantsSession()->unsetData('quote_id');
            
            //clear sessions
            $this->_getSession()->clear();

            Mage::getSingleton('adminhtml/session')->clear();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
        }

        if (!empty($redirectParams['error_msg'])) {
            $cancelOrder = empty($redirectParams['order_id']);
            $this->_returnQuote(true, $redirectParams['error_msg']);
        }

        $block = $this->getLayout()
            ->createBlock('networkmerchants/payment_iframe')
            ->setParams(array_merge($params, $redirectParams));
        $this->getResponse()->setBody($block->toHtml());
    }

    /**
     * Return order quote by ajax
     *
     */
    public function returnQuoteAction()
    {
        $this->_returnQuote();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('success' => 1)));
    }

    /**
     * Return quote
     *
     * @param bool $cancelOrder
     * @param string $errorMsg
     */
    protected function _returnQuote($cancelOrder = false, $errorMsg = '')
    {
        $incrementId = $this->_getNetworkMerchantsSession()->getLastOrderIncrementId();
        if ($incrementId &&
            $this->_getNetworkMerchantsSession()
                ->isCheckoutOrderIncrementIdExist($incrementId)
        ) {
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            if ($order->getId()) {
                $this->_getNetworkMerchantsSession()->removeCheckoutOrderIncrementId($order->getIncrementId());
                if ($cancelOrder && $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                    $order->registerCancellation($errorMsg)->save();
                }
            }
        }
    }
}
