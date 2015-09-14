<?php

class TBT_Testsweet_Model_Observer_Crontest {

    public function run() {
    	Mage::log("CRON TASK START: TBT_Testsweet_Model_Observer_Crontest::run @ " . time(), null, 'st_cron_debug.log', true);
        $timestamp = $this->getCurrentTimestamp();
        Mage::getConfig()
            ->saveConfig('testsweet/crontest/timestamp', $timestamp, 'default', 0)
            ->reinit();
        Mage::log("CRON TASK END: TBT_Testsweet_Model_Observer_Crontest::run @ " . time(), null, 'st_cron_debug.log', true);
        return $this;
    }
    
    public function getCurrentTimestamp() {
        $timestamp = (string)time();
        return $timestamp;
    }

    public function getCronTimestamp() {
        //$timestamp = (string)Mage::getConfig()->getNode('testsweet/crontest/timestamp', 'default', 0);
        $timestamp = Mage::getStoreConfig('testsweet/crontest/timestamp');
        return $timestamp;
    }

    public function isWorking() {
        $timestamp = $this->getCronTimestamp();
        if (empty($timestamp))
            return false;

        $seconds = $this->getCurrentTimestamp() - $timestamp;

        // if the timestamp is within 30 minuets return true
        return $seconds < (60 * 30);
    }

}
