<?php
class Extendware_EWAuthenticator_Override_Mage_Adminhtml_Permissions_UserController extends Extendware_EWAuthenticator_Override_Mage_Adminhtml_Permissions_UserController_Bridge
{
	
	public function editAction()
	{
		if (Mage::helper('ewcore/environment')->isDemoServer() === true) {
			Mage::getSingleton('adminhtml/session')->addNotice($this->__('Click the "Two-Factor Settings" tab to modify Two-Factor Authentication settings'));
		}
		return parent::editAction();
	}
	
	public function saveAction()
    {
		$user = Mage::getModel('admin/user')->load($this->getRequest()->getParam('user_id'));
		if ($user->getId() > 0) {
			$data = $this->getRequest()->getPost('ewauthenticator'); 
			if (is_array($data) === true) {
				try {
					$model = Mage::getModel('ewauthenticator/user')->loadByAdminUserId($user->getId());
					$model->setAdminUserId($user->getId());
					$model->addData($data);
					if ($user->getId() == Mage::getSingleton('admin/session')->getUser()->getId()) {
						if ($model->getData('mode') != 'password' and ((!$model->getOrigData('mode') == 'password' or $model->dataHasChangedFor('secret_key')))) {
							if (@$model->doesVerificationCodePass($data['verification_code']) === false) {
								Mage::getSingleton('adminhtml/session')->addError($this->__('You must enter a correct verification code in order to use verification code authenticaton.'));
								return $this->_redirect('adminhtml/permissions_user/edit', array('user_id' => $user->getId()));
							}
						}
					}
					$model->save();
					Mage::getSingleton('adminhtml/session')->addNotice($this->__('The user authentication data has been saved.'));
				} catch (Exception $e) {
					Mage::getSingleton('adminhtml/session')->addError($this->__('The user authentication data could not be saved.'));
					return $this->_redirect('admin/permissions_user/edit', array('user_id' => $user->getId()));
				}
			}
		}
    	return parent::saveAction();
    }
}