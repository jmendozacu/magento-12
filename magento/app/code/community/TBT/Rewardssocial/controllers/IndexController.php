<?php
/**
 * WDCA - Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the WDCA SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, WDCA is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by WDCA, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time WDCA spent
 * during the support process.
 * WDCA does not guarantee compatibility with any other framework extension.
 * WDCA is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * RewardsSocial Index Controller
 *
 * @category   TBT
 * @package    TBT_RewardsSocial
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewardssocial_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (Mage::getConfig()->getModuleConfig('TBT_Rewards')->is('active', 'false')) {
            throw new Exception(Mage::helper('rewardssocial')->__("Sweet Tooth must be installed on the server in order to use the Sweet Tooth Social system."));
        }
        die(Mage::helper('rewardssocial')->__("If you're seeing this page it confirms that Sweet Tooth is installed and the Sweet Tooth Social system is ready for use."));

        return $this;
    }

    /**
     * This function is hit after each tweet by an AJAX call
     *
     */
    public function processTweetsAction()
    {
        try {
            $url = $_SERVER['HTTP_REFERER'];
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in for us to reward you for tweeting."), 110);
            }

            $tweet = Mage::getModel('rewardssocial/twitter_tweet');

            if ($tweet->hasAlreadyTweetedUrl($customer, $url)) {
                throw new Exception($this->__("You've already tweeted about this page."), 120);
            }

            $minimumWait = $tweet->getTimeUntilNextTweetAllowed($customer);
            if($minimumWait > 0) {
                throw new Exception($this->__("Please wait %s second(s) before tweeting another page if you want to be rewarded.", $minimumWait), 130);
            }

            if ($tweet->isMaxDailyTweetsReached($customer)) {
                $maxTweets = $this->_getMaxTweetsPerDay($customer);
                throw new Exception($this->__("You've reached the tweet-rewards limit for today (%s tweets per day)", $maxTweets), 140);
            }

            $tweet->setCustomerId($customer->getId())
                ->setUrl($url)
                ->save();

            if (!$tweet->getId()) {
                throw new Exception($this->__("TWEET model was not saved for some reason."), 10);
            }

            $validatorModel = Mage::getModel('rewardssocial/twitter_tweet_validator');
            $validatorModel->initReward($customer->getId(), $url);

            $message = $this->__("Thanks for tweeting this page!");
            $predictedPoints = $validatorModel->getPredictedTwitterTweetPoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for tweeting!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));
        } catch (Exception $ex) {
            // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customer->getId()}) for tweeting about a page ({$url}): ".
                $ex->getMessage());

            $message = $this->__('There was a problem trying to reward you for tweeting about this page.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }
        //$this->addPointToEarply();
        return $this;
    }

    public function processFollowAction()
    {
        try {
            $customer       = Mage::getSingleton('customer/session')->getCustomer();
            $customerId     = $customer->getId();

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in for us to reward you for following us on Twitter!"), 110);
            }

            $socialCustomer = Mage::getModel('rewardssocial/customer')->load($customerId);

            if ($socialCustomer->getIsFollowing()) {
                throw new Exception($this->__("You've already been rewarded for following us on Twitter!"), 120);
            }

            $socialCustomer->setId($customerId)
                ->setIsFollowing(true)
                ->save();

            if (!$socialCustomer->getId()) {
                throw new Exception($this->__("CUSTOMER model was not saved for some reason. Customer ID {$customerId}."), 10);
            }

            $validatorModel = Mage::getModel('rewardssocial/twitter_follow_validator');
            $validatorModel->initReward($customerId);

            $message = $this->__("Thanks for following us!");
            $predictedPoints = $validatorModel->getPredictedTwitterFollowPoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for following us on Twitter!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));
        } catch (Exception $ex) {
             // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customerId}) for following on Twitter: \n".
                $ex);

            $message = $this->__('There was a problem trying to reward you for following us on Twitter.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }

        return $this;
    }

    public function referralShareAction()
    {
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId     = $customer->getId();

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in to share your referral link!"), 110);
            }

            // make sure this is not called from elsewhere
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest") {
                throw new Exception($this->__("Referral share link accessed from wrong endpoint!"), 50);
            }


            $socialCustomer = Mage::getModel('rewardssocial/customer')->load($customerId)
                ->setData($customer->getData());

            if (!$socialCustomer->getId()) {
                $socialCustomer->setId($customerId);
            }

            $minimumWait = $socialCustomer->getTimeUntilNextReferralShareAllowed();
            if($minimumWait > 0) {
                throw new Exception($this->__("Please wait %s second(s) before sharing your referral link again, if you want to be rewarded.", $minimumWait), 120);
            }

            if ($socialCustomer->isMaxDailyReferralShareReached()) {
                $maxTweets = $this->_getMaxReferralSharesPerDay($socialCustomer);
                throw new Exception($this->__("You've reached the rewards limit for today for sharing your referral link (%s shares per day)", $maxTweets), 130);
            }

            $referralShare = Mage::getModel('rewardssocial/referral_share')
                ->setCustomerId($customerId)
                ->save();

            if (!$referralShare->getId()) {
                throw new Exception($this->__("REFERRAL SHARE model was not saved for some reason."), 10);
            }

            $validatorModel = Mage::getModel('rewardssocial/referral_share_validator');
            $validatorModel->initReward($customerId, $referralShare->getId());

            $message = $this->__("Thanks for sharing your referral link!");
            $predictedPoints = $validatorModel->getPredictedPoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for sharing your referral link!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));
        } catch (Exception $ex) {
            // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customerId}) for sharing his referral link: ".
                $ex->getMessage());

            $message = $this->__('There was a problem trying to reward you for sharing your referral link.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }

        return $this;
    }

    /**
     * This will be pinged by Pinterest when the customer clicks the Pin button and the Pinterest modal shows. It is
     *  used to aknowledge that the pin was made or not and then redirect Pinterest to the image being pinned.
     *
     * - First time it will be pinged to display the image in the popup, here we don't do anything just redirect to
     *  the image.
     *
     * - Second time it's when the user click the 'Pin It' button in the modal to actually do the pinning (the image
     *  is saved to their CDN). This is what we are interested in. We'll know this is the case because the request
     *  useragent will be "Pinterest/0.1 +http://pinterest.com/".
     *
     * @return this
     */
    public function observePinningAction()
    {
        $data = $this->getRequest()->getParam('data');
        if (!$data) {
            return $this;
        }

        $data      = (array)json_decode(Mage::helper('rewardssocial/crypt')->decrypt($data));
        $productId = $data['productId'];
        if (!$productId) {
            return $this;
        }

        $product = Mage::getModel('catalog/product')->load($productId);
        $image   = Mage::helper('catalog/image')->init($product, 'image');
        $this->getResponse()->setRedirect($image, 301);

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        // if user didn't pinned the item or there's no customer logged in, just redirect to the image (done above)
        if (!preg_match('/^pinterest.*$/', strtolower($userAgent)) || !isset($data['customerId']) || !isset($data['url']) ) {
            return $this;
        }

        $this->_processPin($data['customerId'], $data['url']);

        return $this;
    }

    /**
     * Saves a pin, if $productUrl wasn't already pinned and limits not reached.
     *
     * @param  int $customerId  Customer ID
     * @param  string $productUrl Url being pinned
     *
     * @return $this
     */
    protected function _processPin($customerId, $productUrl)
    {
        try {

            $pin = Mage::getModel('rewardssocial/pinterest_pin');
            if ($pin->hasAlreadyPinnedUrl($customerId, $productUrl)) {
                return $this;
            }

            $customer = Mage::getModel('customer/customer')->load($customerId);

            // if minimum wait time not satisfied, exit
            $minimumWait = $pin->getTimeUntilNextPinAllowed($customer);
            if ($minimumWait > 0) {
                return $this;
            }

            // if we reached here save the pin
            $pin->setCustomerId($customerId)
                ->setPinnedUrl($productUrl)
                ->setIsProcessed(false)
                ->save();

            // if limit reached, exit
            if ($pin->isMaxDailyPinsReached($customer, false)) {
                return $this;
            }

            // reward the pin
            $validatorModel = Mage::getModel('rewardssocial/pinterest_pin_validator');
            $validatorModel->initReward($customer->getId(), $pin->getId());

            $pin->setIsProcessed(true)
                ->save();
        } catch (Exception $e) {
            // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customerId}) for pinning a product ({$productUrl}) on Pinterest: ".
                $e->getMessage());
            Mage::helper('rewards')->logException($e);
        }

        return $this;
    }

    public function processPinAction()
    {
        try {
            $thisUrl           = $_SERVER['HTTP_REFERER'];
            $hostname          = $_SERVER['HTTP_HOST'];
            $customer          = Mage::getSingleton('customer/session')->getCustomer();
            $customerId        = $customer->getId();

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in for us to reward you for pinning."), 110);
            }

            $pin = Mage::getModel('rewardssocial/pinterest_pin')->loadByCustomerAndUrl($customerId, $thisUrl);

            if (!$pin->getId()) {
                $minimumWait = $this->_getMinSecondsBetweenPins($customer);
                throw new Exception($this->__("Please make sure you successfully pin the product on Pinterest and you wait %s second(s) between pins if you want to be rewarded.", $minimumWait), 170);
            }

            if ($pin->isMaxDailyPinsReached($customer, false)) {
                $maxPins = $this->_getMaxPinsPerDay($customer);
                $pin->delete();
                throw new Exception($this->__("You've reached the Pinterest rewards limit for today (%s pins per day)", $maxPins), 150);
            }

            if (!$pin->getIsProcessed()) {
                $pin->delete();
                throw new Exception($this->__("There was a problem rewarding points for a pin for customer with ID {$customerId} and product {$thisUrl}. Check previous logs for more details."), 10);
            }

            $validatorModel = Mage::getModel('rewardssocial/pinterest_pin_validator');

            $message = $this->__("Thanks for pinning this page!");
            $predictedPoints = $validatorModel->getPredictedPinterestPinPoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for pinning!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));
        } catch (Exception $ex) {
            $message = $this->__('There was a problem trying to reward your pinterest pin.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }
        //$this->addPointToEarply();
        return $this;
    }

    public function processFbProductShareAction()
    {
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            $productId = $this->getRequest()->getParam('product_id');
            $postId    = $this->getRequest()->getParam('post_id');
            if (!$productId) {
                throw new Exception($this->__("No product found to reward sharing on Facebook"), 10);
            }

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in for us to reward you for sharing a product on Facebook!"), 110);
            }

            $customerId = $customer->getId();
            $productShare = Mage::getModel('rewardssocial/facebook_share');

            if ($productShare->hasAlreadySharedProduct($customerId, $productId)) {
                throw new Exception($this->__("You've already been rewarded for sharing this product on Facebook."), 120);
            }

            $minimumWait = $productShare->getTimeUntilNextShareAllowed($customer);
            if ($minimumWait > 0) {
                throw new Exception($this->__("Please wait %s second(s) before sharing another product if you want to be rewarded.", $minimumWait), 130);
            }

            if ($productShare->isMaxDailyProductSharesReached($customer)) {
                $maxShares = $this->_getMaxProductSharesPerDay($customer);
                throw new Exception($this->__("You've reached Facebook product share rewards limit for today (%s shares per day)", $maxShares), 140);
            }

            $productShare->setCustomerId($customerId)
                ->setProductId($productId)
                ->setPostId($postId)
                ->save();

            if (!$productShare->getId()) {
                throw new Exception($this->__("Facebook Share model was not saved for some reason."), 20);
            }

            $validatorModel = Mage::getModel('rewardssocial/facebook_share_validator');
            $validatorModel->initReward($customer, $productShare->getId());

            $message = $this->__("Thanks for sharing this product on Facebook!");
            $predictedPoints = $validatorModel->getPredictedPoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for sharing this product on Facebook!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));

        } catch (Exception $ex) {
            // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customer->getId()}) for sharing a product ({$productId}) on Facebook: " . $ex->getMessage());

            $message = $this->__('There was a problem trying to reward your Facebook product share.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }
        //$this->addPointToEarply();
        return $this;
    }

    public function processPurchaseShareAction()
    {
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            $productId  = $this->getRequest()->getParam('product_id');
            $actionType = $this->getRequest()->getParam('action_type');
            $orderId    = $this->getRequest()->getParam('order_id');
            if (!$productId) {
                throw new Exception($this->__("No product found to reward sharing your purchase."), 10);
            }
            if (!$actionType) {
                throw new Exception($this->__("No action type found to reward sharing your purchase."), 20);
            }
            if (!$orderId) {
                throw new Exception($this->__("No order found to reward sharing your purchase."), 30);
            }

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in for us to reward you for sharing your purchase!"), 110);
            }

            $customerId = $customer->getId();
            $purchaseShare = Mage::getModel('rewardssocial/purchase_share');

            if ($purchaseShare->hasAlreadySharedPurchase($customerId, $productId, $orderId, $actionType)) {
                throw new Exception($this->__("You've already been rewarded for sharing this purchase on "
                    . ucfirst($actionType) . "."), 120);
            }

            $purchaseShare->setCustomerId($customerId)
                ->setProductId($productId)
                ->setOrderId($orderId)
                ->setTypeId($purchaseShare->getActionTypeId($actionType))
                ->save();

            if (!$purchaseShare->getId()) {
                throw new Exception($this->__("Purchase Share model was not saved for some reason."), 40);
            }

            $validatorModel = Mage::helper('rewardssocial/purchase_share')->getValidator($actionType);
            $validatorModel->initReward($customer, $purchaseShare->getId(), $actionType);

            $message = $this->__("Thanks for sharing this purchase on " . ucfirst($actionType) . "!");
            $predictedPoints = $validatorModel->getPredictedPoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for sharing this purchase on " . ucfirst($actionType)
                    . "!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));

        } catch (Exception $ex) {
            // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customer->getId()}) for sharing a purchased product ({$productId}) from order {$orderId}: " . $ex->getMessage());

            $message = $this->__('There was a problem trying to reward your purchase share.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }

        return $this;
    }

    /**
     * This function is hit after each tweet by an AJAX call
     *
     */
    public function processPlusOneAction()
    {
        try {
            $url      = $_SERVER['HTTP_REFERER'];
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                throw new Exception($this->__("You must be logged in for us to reward you for +1'ing a page!"), 110);
            }

            $plusone = Mage::getModel('rewardssocial/google_plusOne');

            if ($plusone->hasAlreadyPlusOnedUrl($customer, $url)) {
                throw new Exception($this->__("You've already +1'd this page."), 120);
            }

            $minimumWait = $plusone->getTimeUntilNextPlusOneAllowed($customer);
            if($minimumWait > 0) {
                throw new Exception($this->__("Please wait %s second(s) before +1'ing another page if you want to be rewarded.", $minimumWait), 130);
            }

            if ($plusone->isMaxDailyPlusOnesReached($customer)) {
                $maxTweets = $this->_getMaxPlusOnesPerDay($customer);
                throw new Exception($this->__("You've reached the Google+ rewards limit for today (%s +1's per day)", $maxTweets), 140);
            }

            $plusone->setCustomerId($customer->getId())
                ->setUrl($url)
                ->save();

            if (!$plusone->getId()) {
                throw new Exception($this->__("PLUSONE model was not saved for some reason."), 10);
            }

            $validatorModel = Mage::getModel('rewardssocial/google_plusOne_validator');
            $validatorModel->initReward($customer->getId(), $url);

            $message = $this->__("Thanks for +1'ing this page!");
            $predictedPoints = $validatorModel->getPredictedGooglePlusOnePoints();
            if (count($predictedPoints) > 0) {
                $pointsString = (string) Mage::getModel('rewards/points')->set($predictedPoints);
                $message = $this->__("You've earned <b>%s</b> for +1'ing this page!", $pointsString);
            }

            $this->_jsonSuccess(array(
                'success' => true,
                'message' => $message
            ));
        } catch (Exception $ex) {
            // log the exception
            Mage::helper('rewards')->logException("There was a problem rewarding customer {$customer->getEmail()} (ID: {$customer->getId()}) for +1'ing this page ({$url}) on Google+: ".
                $ex->getMessage());

            $message = $this->__('There was a problem trying to reward youfor +1\'ing this page.<br/>Try again and contact us if you still encounter this issue.');
            if ($ex->getCode() > 100) {
                $message = $ex->getMessage();
            }

            $this->_jsonError(array(
                'success' => false,
                'message' => $message
            ));
        }

        return $this;
    }

    protected function _getMaxTweetsPerDay($customer)
    {
        return Mage::helper('rewardssocial/twitter_config')->getMaxTweetRewardsPerDay($customer->getStore());
    }

    protected function _getMaxPinsPerDay($customer)
    {
        return Mage::helper('rewardssocial/pinterest_config')->getMaxPinRewardsPerDay($customer->getStore());
    }

    protected function _getMinSecondsBetweenPins($customer)
    {
        return Mage::helper('rewardssocial/pinterest_config')->getMinSecondsBetweenPins($customer->getStore());
    }

    protected function _getMaxPlusOnesPerDay($customer)
    {
        return Mage::helper('rewardssocial/google_config')->getMaxPlusOneRewardsPerDay($customer->getStore());
    }

    protected function _getMaxProductSharesPerDay($customer)
    {
        return Mage::helper('rewardssocial/facebook_config')->getMaxFacebookProductShareRewardsPerDay($customer->getStore());
    }

    protected function _getMaxReferralSharesPerDay($customer)
    {
        return Mage::helper('rewardssocial/referral_config')->getMaxReferralSharesPerDay($customer->getStore());
    }

    protected function _jsonSuccess($response)
    {
        $this->getResponse()->setBody(json_encode($response));
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        return $this;
    }

    protected function _jsonError($response)
    {
        return $this->_jsonSuccess($response);
    }

    public function addPointToEarply(){
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {

            $process = Mage::getModel('index/indexer')->getProcessByCode('rewards_transfer')->reindexAll();
            
            $storeId = 2;
            $erplyModel = Mage::getModel('Erply/Erply');

            if($erplyModel->verifyUser($storeId)){
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');    

                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $customerAddressId = $customer->getDefaultBilling();
                $phone = '';
                if($customerAddressId){
                    $address = Mage::getModel('customer/address')->load($customerAddressId);
                    $phone = $address->getBillingTelephone();
                }
                $customerId = $customer->getId();
                $erplyCustomerId = $customer->getErplyCustomerId();
                $email = $customer->getEmail();        
                $newCustomer = false;

                if($erplyCustomerId =='' || $erplyCustomerId == 0){

                    if($email != ''){
                        $params = array(
                            'searchName' => $email,
                        );
                        $responseJson = $erplyModel->sendRequest('getCustomers', $params);
                        $response = json_decode($responseJson, true);
                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                            break;
                        }else{
                            if(!isset($response['records']) || !count($response['records'])){
                                if($phone != ''){
                                    $params = array(
                                        'searchName'    => $phone,
                                    );
                                    $responseJson = $erplyModel->sendRequest('getCustomers', $params);
                                    $response = json_decode($responseJson, true);
                                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                        break;
                                    }else{
                                        if(isset($response['records'])){
                                            if(!count($response['records'])){
                                                $newCustomer = true; 
                                            }else{
                                                if(count($response['records']) > 1){
                                                    $newCustomer = true; 
                                                }else{
                                                    if(isset($response['records'][0]['customerID'])){
                                                        $customerID = $response['records'][0]['customerID'];
                                                        $customer->setErplyCustomerId($customerID);
                                                        $customer->save();
                                                        $erplyCustomerId = $customerID;
                                                    }
                                                    $newCustomer = false; 
                                                }                           
                                            }
                                        }else{
                                            $newCustomer = true; 
                                        }    
                                    }                            
                                }else{
                                    $newCustomer = true; 
                                }
                            }else{
                                if(isset($response['records'][0]['customerID'])){
                                    $customerID = $response['records'][0]['customerID'];
                                    $customer->setErplyCustomerId($customerID);
                                    $customer->save();
                                    $erplyCustomerId = $customerID;
                                }                        
                                $newCustomer = false; 
                            }
                        }                    
                    }

                    if($newCustomer){
                        $params = array(
                            'firstName'    => $customer->getFirstname(),
                            'lastName'    => $customer->getLastname(),
                            'email'    => $customer->getEmail(),
                            'phone'    => $customer->getBillingTelephone(),
                        );
                        $responseJson = $erplyModel->sendRequest('saveCustomer', $params);
                        $response = json_decode($responseJson, true);
                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                            break;
                        }else{
                            if(isset($response['records'][0]['customerID'])){
                                $customerID = $response['records'][0]['customerID'];
                                $customer->setErplyCustomerId($customerID);
                                $customer->save();
                                $erplyCustomerId = $customerID;
                            }
                        }              
                    }else{
                        //echo "customer already exist";
                    }

                }else{
                    
                }

                if($customer->getErplyCustomerId() != '' && $customer->getErplyCustomerId() != 0){

                    $params = array(
                        'customerID' => $erplyCustomerId,
                    );
                    $responseJson = $erplyModel->sendRequest('getCustomerRewardPoints', $params);
                    $response = json_decode($responseJson, true);

                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                        break;
                    }else{
                        
                        $erplyPoints = 0;
                        if(isset($response['records'][0]['points'])){
                            $erplyPoints = $response['records'][0]['points'];
                        }
                        $sql = "SELECT * FROM rewards_customer_index_points WHERE customer_id = ".$customer->getId();
                        $rows = $readConnection->fetchAll($sql);
                        $magentoPoints = 0;
                        if(isset($rows[0]['customer_points_usable'])){
                            $magentoPoints = $rows[0]['customer_points_usable'];
                        }else{
                            $sqlInsert = "INSERT INTO rewards_customer_index_points ( customer_id,customer_points_usable,customer_points_pending_event,customer_points_pending_time,customer_points_pending_approval,customer_points_active ) VALUES ('".$customerId."',0,0,0,0,0) ";
                            $writeConnection->query($sqlInsert);
                        }

                        // $params = array(
                     //        'customerID' => $erplyCustomerId,
                     //    );
                     //    $responseJson = $erplyModel->sendRequest('getEarnedRewardPointRecords', $params);
                     //    $response = json_decode($responseJson, true);
                     //    echo "<pre>"; print_r($response);exit;

                        if($magentoPoints != $erplyPoints){

                            // Reward Points Erply >To> Magento
                            $params = array(
                                'customerID' => $erplyCustomerId,
                            );
                            $responseJson = $erplyModel->sendRequest('getEarnedRewardPointRecords', $params);
                            $response = json_decode($responseJson, true);
                            
                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                break;
                            }else{
                                if(isset($response['records'])){
                                    foreach ($response['records'] as $records) {
                                        $sqlSelect = "SELECT erply_transaction_id FROM rewards_transfer WHERE erply_transaction_id = '".$records['transactionID']."'";
                                        $resultRows = $readConnection->fetchAll($sqlSelect);            
                                        if(!count($resultRows)){
                                            $invoiceNo = $records['invoiceNo'];
                                            $comments = '';
                                            if($invoiceNo != ''){
                                                $comments = 'Erply Invoice # $invoiceNo - Order Received - Point Earned';
                                            }else{
                                                $comments = 'Erply Points Earned';
                                            }
                                            $earnedPoints = $records['earnedPoints'];
                                            $transactionID = $records['transactionID'];
                                            $currentDate = date('Y-m-d h:i:s');

                                            if($invoiceNo =='' || empty(Mage::getModel('sales/order')->loadByIncrementId($invoiceNo)->getData())){

                                                $sqlInsert = "INSERT INTO rewards_transfer 
                                                (customer_id, quantity, comments, status, currency_id, erply_transaction_id , creation_ts , last_update_ts)
                                                VALUES 
                                                ('$customerId', '$earnedPoints','$comments', 5,1,'$transactionID', '$currentDate', '$currentDate'); commit;";
                                                $writeConnection->query($sqlInsert);

                                                $customer_points_usable = $magentoPoints + $earnedPoints;
                                                $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '$customer_points_usable', customer_points_active = '$customer_points_usable'  WHERE customer_id = ".$customer->getId();
                                                $writeConnection->query($sqlUpdate);
                                            }
                                        }
                                    }
                                }
                            }

                            $params = array(
                                'customerID' => $erplyCustomerId,
                            );
                            $responseJson = $erplyModel->sendRequest('getUsedRewardPointRecords', $params);
                            $response = json_decode($responseJson, true);

                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                break;
                            }else{
                                if(isset($response['records'])){
                                    foreach ($response['records'] as $records) {
                                        $sqlSelect = "SELECT erply_transaction_id FROM rewards_transfer WHERE erply_transaction_id = '".$records['transactionID']."'";
                                        $resultRows = $readConnection->fetchAll($sqlSelect);                                        
                                        if(!count($resultRows)){
                                            $invoiceNo = $records['invoiceNo'];
                                            $comments = '';
                                            if($invoiceNo != ''){                                        
                                                $comments = 'Erply Invoice # $invoiceNo - Order Received - Point Spent';
                                            }else{
                                                $comments = 'Erply Points Spent';
                                            }
                                            $usedPoints = $records['usedPoints'] * -1;
                                            $transactionID = $records['transactionID'];
                                            $currentDate = date('Y-m-d h:i:s');
                                            if($invoiceNo =='' || empty(Mage::getModel('sales/order')->loadByIncrementId($invoiceNo)->getData())){
                                                $sqlInsert = "INSERT INTO rewards_transfer 
                                                (customer_id, quantity, comments, status, currency_id, erply_transaction_id , creation_ts , last_update_ts)
                                                VALUES 
                                                ('$customerId', '$usedPoints','$comments', 5,1,'$transactionID', '$currentDate', '$currentDate'); commit;";
                                                $writeConnection->query($sqlInsert);

                                                $customer_points_usable = $magentoPoints + $usedPoints;
                                                $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '$customer_points_usable' , customer_points_active = '$customer_points_usable'  WHERE customer_id = ".$customer->getId();
                                                $writeConnection->query($sqlUpdate);
                                            }
                                        }
                                    }
                                }
                            }


                            // Reward Points Magento >To> Erply

                            


                            $sqlSelect = "SELECT * FROM rewards_transfer WHERE customer_id = '".$customerId."' AND erply_transaction_id = 0 AND status = 5";
                            $resultRows = $readConnection->fetchAll($sqlSelect);
                            foreach ($resultRows as $result) {
                                if($result['erply_transaction_id'] == 0 || $result['erply_transaction_id'] == ''){
                                    $rewards_transfer_id  = $result['rewards_transfer_id'];
                                    
                                    if($result['quantity'] > 0){
                                        $params = array(
                                            'customerID'    => $customer->getErplyCustomerId(),
                                            'points' => $result['quantity'],
                                            'description' => 'Magento Transaction: '. $result['comments'],
                                        );
                                        $responseJson = $erplyModel->sendRequest('addCustomerRewardPoints', $params);                
                                        $response = json_decode($responseJson, true);
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                            break;
                                        }else{
                                            if(isset($response['records'][0]['transactionID'])){
                                                $transactionID = $response['records'][0]['transactionID'];
                                                $sqlUpdate = "UPDATE rewards_transfer SET erply_transaction_id = '$transactionID' WHERE rewards_transfer_id = ".$rewards_transfer_id;
                                                $writeConnection->query($sqlUpdate);
                                            }
                                        }                                
                                    }



                                    if($result['quantity'] < 0){
                                        $params = array(
                                            'customerID'    => $customer->getErplyCustomerId(),
                                            'points' => $result['quantity'] * -1,
                                            'description' => 'Magento Transaction: '. $result['comments'],
                                        );
                                        $responseJson = $erplyModel->sendRequest('subtractCustomerRewardPoints', $params);
                                        $response = json_decode($responseJson, true);
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                            break;
                                        }else{
                                            if(isset($response['records'][0]['transactionID'])){
                                                $transactionID = $response['records'][0]['transactionID'];
                                                $sqlUpdate = "UPDATE rewards_transfer SET erply_transaction_id = '$transactionID' WHERE rewards_transfer_id = ".$rewards_transfer_id;
                                                $writeConnection->query($sqlUpdate);
                                            }
                                        }                                       
                                    }



                                }
                            }

                            $params = array(
                                'customerID' => $erplyCustomerId,
                            );
                            $responseJson = $erplyModel->sendRequest('getCustomerRewardPoints', $params);
                            $response = json_decode($responseJson, true); 
                        

                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                break;
                            }else{
                                

                                $erplyPoints = 0;
                                if(isset($response['records'][0]['points'])){
                                    $erplyPoints = $response['records'][0]['points'];
                                }
                                
                                $sql = "SELECT * FROM rewards_customer_index_points WHERE customer_id = ".$customer->getId();
                                $rows = $readConnection->fetchAll($sql);
                                $magentoPoints = 0;
                                if(isset($rows[0]['customer_points_usable'])){
                                    $magentoPoints = $rows[0]['customer_points_usable'];
                                }else{
                                    $sqlInsert = "INSERT INTO rewards_customer_index_points ( customer_id,customer_points_usable,customer_points_pending_event,customer_points_pending_time,customer_points_pending_approval,customer_points_active ) VALUES ('".$customerId."',0,0,0,0,0) ";
                                    $writeConnection->query($sqlInsert);
                                }


                                if($magentoPoints != $erplyPoints){
                                    // if($magentoPoints > $erplyPoints){
                                    //     $diff = $magentoPoints - $erplyPoints;
                                    //     $params = array(
                                    //         'customerID'    => $customer->getErplyCustomerId(),
                                    //         'points' => $diff,
                                    //         'description' => 'Magento - Erply Point Difference Adjustment',
                                    //     );
                                    //     $responseJson = $erplyModel->sendRequest('addCustomerRewardPoints', $params);                
                                    //     $response = json_decode($responseJson, true);
                                    //     if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                    //         Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                    //         break;
                                    //     }else{
                                    //         // Transfer Done
                                    //     }
                                    // }

                                    // if($magentoPoints < $erplyPoints){

                                    //     $diff = $erplyPoints - $magentoPoints;
                                    //     $currentDate = date('Y-m-d h:i:s');
                                        
                                    //     $sqlInsert = "INSERT INTO rewards_transfer 
                                    //     (customer_id, quantity, comments, status, currency_id, erply_transaction_id , creation_ts , last_update_ts)
                                    //     VALUES 
                                    //     ('$customerId', '$diff','Erply - Magento Point Different Adjustment', 5,1,1, '$currentDate', '$currentDate'); commit;";
                                    //     $writeConnection->query($sqlInsert);

                                    //     $customer_points_usable = $magentoPoints + $diff;
                                    //     $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '$customer_points_usable', customer_points_active = '$customer_points_usable'   WHERE customer_id = ".$customer->getId();
                                    //     $writeConnection->query($sqlUpdate);
                                    // }
                                }


                            }
                                
                        }
                    }
                }
            }
        }
    }
}
