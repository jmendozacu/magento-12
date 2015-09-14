<?php

class TBT_Milestone_Model_Rule_Condition_Earned extends TBT_Milestone_Model_Rule_Condition
{
	const POINTS_REFERENCE_TYPE_ID = 701;
	
    public function getMilestoneName()
    {
        return Mage::helper('tbtmilestone')->__("Points Earned Milestone");
    }

    public function getMilestoneDescription()
    {
        $threshold = (string) Mage::getModel('rewards/points')->setPoints(1, $this->getThreshold());
        return Mage::helper('tbtmilestone')->__("milestone for earning %s", $threshold);
    }

    public function isSatisfied($customerId)
    {
        $statuses = array(TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED);
        if (Mage::helper('tbtmilestone/config')->doIncludePendingTransfers()) {
            $statuses = array_merge($statuses, array(
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT,
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL,
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME
            ));
        }

        $collection = Mage::getResourceModel('rewards/transfer_collection')
            ->addFieldToFilter('main_table.customer_id', $customerId)
            ->addFieldToFilter('main_table.status', array('in' => $statuses))
            ->addFieldToFilter('main_table.creation_ts', array('gteq' => $this->getFromDate()));
        $collection->getSelect()->join(
            array('customer_table' => $this->_getResource()->getTableName('customer/entity')),
            "customer_table.entity_id = main_table.customer_id",
            array()
        );
        $collection->addFieldToFilter('customer_table.website_id', array('in' => $this->getRule()->getWebsiteIds()));
        $collection->addFieldToFilter('customer_table.group_id', array('in' => $this->getRule()->getCustomerGroupIds()));

        if ($this->getToDate()) {
            $collection->addFieldToFilter('main_table.creation_ts', array('lt' => $this->getToDate()));
        }

        $totalPointsEarned = $this->_fetchPoints($collection);

        return $totalPointsEarned >= $this->getThreshold();
    }

    public function validateSave()
    {
        if (!$this->getThreshold()) {
            throw new Exception("Earned Points is a required field.");
        }

        return $this;
    }

    /**
     * @see TBT_Milestone_Model_Rule_Condition::getPointsReferenceTypeId()
     */
    public function getPointsReferenceTypeId()
    {
        return self::POINTS_REFERENCE_TYPE_ID;
    }

    protected function _fetchPoints($collection)
    {
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS);

        $collection->getSelect()
            ->group('main_table.customer_id');

        $collection->addExpressionFieldToSelect('total_points', "SUM(main_table.quantity)", array());

        return $collection->getFirstItem()->getData('total_points');
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource()
    {
        return Mage::getSingleton('core/resource');
    }
}
