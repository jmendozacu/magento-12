<?php
class DJ_AdminLog_Model_Observer
{

			public function AfterSaveProduct(Varien_Event_Observer $observer)
			{

				$product = $observer->getData('product');
				$productName = $product->getName();
				$sku = $product->getSku();
				$user = Mage::getSingleton('admin/session'); 
                $userEmail = $user->getUser()->getEmail();
                $userUsername = $user->getUser()->getUsername();
                $object = $observer->getEvent()->getDataObject();
				
				$createdDate = $product->getCreatedAt();
				$updatedDate = $product->getUpdatedAt();

				$insertData['username'] = $userUsername;
				$insertData['email'] = $userEmail;
				$insertData['product_name'] = $productName;
				$insertData['sku'] = $sku;
				$insertData['date'] = Date('Y-m-d h:i:s');

				if($createdDate == $updatedDate) {
					$insertData['action'] = 'Add';
				}
				else{
					$insertData['action'] = 'Update';
				}

				$model = Mage::getModel('adminlog/productlog')->setData($insertData);
				$model->save();
				//Mage::dispatchEvent('admin_session_user_login_success', array('user'=>$user));
				//$user = $observer->getEvent()->getUser();
				//$user->doSomething();
			}
		
			public function AfterDeleteProduct(Varien_Event_Observer $observer)
			{
				$product = $observer->getData('product');
				$productName = $product->getName();
				$sku = $product->getSku();
				$user = Mage::getSingleton('admin/session'); 
                $userEmail = $user->getUser()->getEmail();
                $userUsername = $user->getUser()->getUsername();
                $object = $observer->getEvent()->getDataObject();
				
				$createdDate = $product->getCreatedAt();
				$updatedDate = $product->getUpdatedAt();

				$insertData['action'] = 'Delete';
				$insertData['username'] = $userUsername;
				$insertData['email'] = $userEmail;
				$insertData['product_name'] = $productName;
				$insertData['sku'] = $sku;
				$insertData['date'] = Date('Y-m-d h:i:s');
				$model = Mage::getModel('adminlog/productlog')->setData($insertData);
				$model->save();
				//Mage::dispatchEvent('admin_session_user_login_success', array('user'=>$user));
				//$user = $observer->getEvent()->getUser();
				//$user->doSomething();
			}
		
}
