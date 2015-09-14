<?php
class Extendware_EWAuthenticator_Model_Override_Mage_Admin_Session extends Extendware_EWAuthenticator_Model_Override_Mage_Admin_Session_Bridge
{
	public function login($username, $password, $request = null)
    {
		if (empty($username)) {
			return;
		}
		
		if(!$request instanceof Mage_Core_Controller_Request_Http) {
			return parent::login($username, $password, $request);
		}
		
		if (Mage::helper('ewauthenticator/config')->isEnabled() === false) {
			return parent::login($username, $password, $request);
		}

		if (Mage::helper('ewauthenticator')->canLoginFromIp(Mage::helper('core/http')->getRemoteAddr()) === false) {
			 Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Logins from your IP address are not allowed.'));
             $request->setParam('messageSent', true);
             return;
		}
		
    	if (Mage::helper('ewauthenticator/config')->isRateLimitingEnabled() === true) {
    		$isLocked = false;
	    	$collection = Mage::getModel('ewauthenticator/log')->getCollection();
	    	$collection->addFieldToFilter('status', 'failed');
			$collection->addFieldToFilter('ip_address', Mage::helper('core/http')->getRemoteAddr());
			$collection->addDateToFilter('created_at', '>=', now(), -1*Mage::helper('ewauthenticator/config')->getRateLimitingPeriodMagnitude(), 'second');
			if ($collection->getSize() >= Mage::helper('ewauthenticator/config')->getRateLimitingMaxAttempts()) $isLocked = true;
			if ($isLocked === true) {
				if ($request && !$request->getParam('messageSent')) {
	                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('You are making too many login attempts. Please wait a while before trying again.'));
	                $request->setParam('messageSent', true);
	            }
	            return;
			}
    	}
    	
		$verificationCode = trim($request->getPost('verification_code', ''));
		$user = Mage::getModel('admin/user')->loadByUsername($username);
		$model = Mage::getModel('ewauthenticator/user')->loadByAdminUserId($user->getId());
		if (!$model->getId()) {
			// default settings
			$model->addData(array('mode' => 'password'));
		}
		
		if (!$user->getId()) {
			if ($request && !$request->getParam('messageSent')) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Invalid User Name or Password or Verification Code.'));
                $request->setParam('messageSent', true);
            }
            Mage::helper('ewauthenticator')->recordLoginAttempt('failed', $username, $user->getId(), $verificationCode, Mage::helper('core/http')->getRemoteAddr());
            return;
		}
		
    	$isPasswordVerified = false;
		try {
            if (Mage::getModel('admin/user')->authenticate($username, $password) === true) {
                $isPasswordVerified = true;
            }
        } catch (Mage_Core_Exception $e) {}
        
    	$isPasscodeVerified = false;
		try {
			if ($model->getTwoFactorMode() == 'both') {
	    		$isPasscodeVerified = (bool)Mage::helper('ewauthenticator')->isIpWhitelistedForTwoFactor(Mage::helper('core/http')->getRemoteAddr());
	    	}
	    	if ($isPasscodeVerified === false) {
	        	$isPasscodeVerified = $model->doesVerificationCodePass($verificationCode);
	        	if ($isPasscodeVerified === true) {
	        		// only allow a verification code to work one time within the tolerance range
	        		$collection = Mage::getModel('ewauthenticator/log')->getCollection();
			    	$collection->addFieldToFilter('status', 'success');
					$collection->addFieldToFilter('verification_code', $verificationCode);
					$collection->addDateToFilter('created_at', '>=', now(), -30*(Mage::helper('ewauthenticator/config')->getToleranceLevel()+1), 'second');
					if ($collection->getSize() > 0) {
						$isPasscodeVerified = false;
					}
	        	}
	    	}
        } catch (Mage_Core_Exception $e) {}
		
        $requirePassword = $requirePasscode = true;
        if ($model->getMode() == 'password') $requirePasscode = false;
        elseif ($model->getMode() == 'verification_code') $requirePassword = false;
        
		if (
			($requirePassword === true and $isPasswordVerified !== true)
			or ($requirePasscode === true and $isPasscodeVerified !== true)
		) {
			if ($request && !$request->getParam('messageSent')) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Invalid User Name or Password or Verification Code.'));
                $request->setParam('messageSent', true);
            }
            Mage::helper('ewauthenticator')->recordLoginAttempt('failed', $username, $user->getId(), $verificationCode, Mage::helper('core/http')->getRemoteAddr());
            return;
		}
		
		 Mage::helper('ewauthenticator')->recordLoginAttempt('success', $username, $user->getId(), $verificationCode, Mage::helper('core/http')->getRemoteAddr());
		 
		$user->getResource()->recordLogin($user);
    	$this->renewSession();
		
		if (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
			Mage::getSingleton('adminhtml/url')->renewSecretUrls();
		}
		$this->setIsFirstPageAfterLogin(true);
		$this->setUser($user);
		$this->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
		
		$requestUri = $this->_getRequestUri($request);
		if ($requestUri) {
			Mage::dispatchEvent('admin_session_user_login_success', array('user' => $user));
			header('Location: ' . $requestUri);
			exit;
		}
                
		return $user;
    }
}
