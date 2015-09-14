<?php

class TBT_Rewards_Model_Mysql4_Customer_Collection extends Mage_Customer_Model_Entity_Customer_Collection
{
    protected $_logJoined = false;

    protected function _construct()
    {
        $this->_init('rewards/customer');
    }

    /**
     * Joins this collection with the log/customer collection
     * and produces a last_login column for the last store the customer visited
     *
     * @param int|array storeIds (optional) to specify which stores to grab the last login for
     * @return TBT_Rewards_Model_Mysql4_Customer_Collection
     */
    public function addLastLoginToSelect($storeIds = null)
    {
       if ($this->_logJoined) {
           return $this;
       }

       $logTable = $this->getTable('log/customer');

       $this->getSelect()->join(
                   array('log' => $logTable),
                   'e.entity_id = log.customer_id',
                   array(
                         "last_login" => "MAX(log.login_at)",
                        )
               );

       if (!empty($storeIds)){
           if (!is_array($storeIds)){
               $storeIds = array($storeIds);
           }

           $this->getSelect()->where("`log`.`store_id` IN('".implode("','", $storeIds)."')");
       }

       $this->getSelect()->group('log.customer_id');

       $this->_logJoined = true;

       return $this;
    }

    /**
     * Provides a non-buggy way to count elements in this collection using MySQL if there is a join on this table.
     * @see Varien_Data_Collection::getSelectCountSql()
     * @return Varien_Db_Select|string
     */
    public function getSelectCountSql()
    {
    	if ($this->_logJoined){    		
    		return "SELECT COUNT(*) FROM ({$this->getSelectSql(true)}) AS collection";
    	}    	
    	return parent::getSelectCountSql();
    }

    /**
     * Provides a non-buggy way to get all Ids of this collection using MySQL if there is a join on this table.
     * @see Varien_Data_Collection::_getAllIdsSelect()
     * @return Varien_Db_Select
     */
    protected function _getAllIdsSelect($limit = null, $offset = null)
    {
    	$idsSelect = $this->getConnection()->select();
    	$idsSelect->from($this->getSelect());
    	$idsSelect->reset(Zend_Db_Select::COLUMNS);
    	$idsSelect->columns('t.' . $this->getEntity()->getIdFieldName());
    	$idsSelect->limit($limit, $offset);
    
    	return $idsSelect;
    }
}
