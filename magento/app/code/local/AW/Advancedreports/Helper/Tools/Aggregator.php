<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancedreports
 * @version    2.6.3
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancedreports_Helper_Tools_Aggregator extends AW_Advancedreports_Helper_Data
{
    /**
     * Type Periods
     */
    const TYPE_PERIODS = 'periods';

    /**
     * Type List
     */
    const TYPE_LIST = 'list';

    /**
     * Table Prefix
     */
    const TABLE_PREFIX = 'aw_arep_aggregated';

    /**
     * Date INdex Field
     */
    const DATE_KEY_FIELD = 'period_key';


    /**
     * Aggregator period
     *
     * @var string
     */
    protected $_type = self::TYPE_PERIODS;

    /**
     * Report id
     *
     * @var string
     */
    protected $_reportId = 'default';
    protected $_init = false;
    protected $_timeType = 'created_at';
    protected $_storeAppendix = 'main';
    protected $_md5 = null;

    /**
     * Grid
     *
     * @var AW_Advancedreports_Block_Advanced_Grid
     */
    protected $_grid = null;

    protected $_collection = array();

    /**
     * Initialize aggregator instance
     * This step is required
     *
     * @param AW_Advancedreports_Block_Advanced_Grid $grid     Active grid
     * @param string                                 $type     'list' or 'periods'
     * @param string                                 $aggId    Id of report
     * @param string                                 $timeType 'created at' or 'updated_at'
     *
     * @return AW_Advancedreports_Helper_Tools_Aggregator
     */
    public function initAggregator($grid = null, $type = null, $aggId = null, $timeType = null)
    {
        if ($aggId) {
            $this->_reportId = $aggId;
        }
        if ($type) {
            $this->_type = $type;
        }
        if ($timeType) {
            $this->_timeType = $timeType;
        }
        if ($grid) {
            $this->_grid = $grid;
            $this->_values = $grid->getOptionsValues();
        }
        $this->_init = true;
        return $this;
    }

    /**
     * Retrieve connection for read data
     *
     * @return  Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getReadAdapter()
    {
        return Mage::helper('advancedreports')->getReadAdapter();
    }

    /**
     * Retrieve connection for write data
     *
     * @return  Varien_Db_Adapter_Pdo_Mysql
     */
    public function _getWriteAdapter()
    {
        return Mage::helper('advancedreports')->getWriteAdapter();
    }

    /**
     * Set tore filter to aggregator
     *
     * @param string|integer|array $store
     *
     * @return AW_Advancedreports_Helper_Tools_Aggregator
     */
    public function setStoreFilter($store)
    {
        $this->_storeAppendix = is_array($store) ? implode("_", $store) : $store;
        return $this;
    }

    protected function _validateInstance()
    {
        if (!$this->_init) {
            Mage::throwException('Aggregator initialization required');
        }
    }

    protected function _getDBTable($tableName)
    {
        return $this->_getResource()->getTableName($tableName);
    }

    /**
     * Retrieves table name
     *
     * @return string
     */
    public function getTableName()
    {
        $this->_validateInstance();

        $arr = array(
            self::TABLE_PREFIX,
            md5(
                implode(
                    "_",
                    array(
                         $this->_reportId,
                         $this->_type,
                         $this->_timeType,
                         $this->_storeAppendix,
                         $this->_values,
                    )
                )
            )
        );

        return substr($this->_getDBTable(implode("_", $arr)), 0, 63);
    }

    /**
     * Retrieves active grid
     *
     * @return AW_Advancedreports_Block_Advanced_Grid
     */
    public function getGrid()
    {
        $this->_validateInstance();
        return $this->_grid;
    }

    /**
     * Setup aggregated collection
     * <ol>
     * <li>Create table if not exists</li>
     * <li>Check periods to aggregate</li>
     * <li>Aggregate them</li>
     * <li>Set up collection</li>
     * </ol>
     *
     * @param datetime $from
     * @param datetime $to
     *
     * @return AW_Advancedreports_Helper_Tools_Aggregator
     */
    public function prepareAggregatedCollection($from, $to)
    {
        $this->_validateInstance();

        $table = $this->getTableName();
        $preparedCollection = $this->getGrid()->getPreparedData($from, $to);
        $this->createTableFromSelect($table, $preparedCollection->getSelect(), true);

        return $this;
    }

    /**
     * Retrieves collection with cached data
     *
     * @return AW_Advancedreports_Model_Mysql4_Cache_Collection
     */
    public function getAggregatetCollection()
    {
        if (!$this->_collection) {
            $collection = Mage::getModel('advancedreports/cache')->getCollection();
            $collection->setMainTable($this->getTableName());

            $this->_collection = $collection;
        }

        return $this->_collection;
    }

    /**
     * WARNING: this method is left for compatibility with old version
     *
     * @return AW_Advancedreports_Helper_Tools_Aggregator
     */
    public function cleanCache()
    {
        return $this;
    }


    public function createTableFromSelect($tableName, Zend_Db_Select $select, $temporary = false)
    {
        $query = sprintf(
            'CREATE' . ($temporary ? ' TEMPORARY' : '') . ' TABLE IF NOT EXISTS `%s` AS (%s)',
            $tableName,
            (string)$select
        );
        $this->_getWriteAdapter()->query($query);
        return $this;
    }

    public function addOrderColumn()
    {
        if ($this->_isColumnExists($this->getTableName(), 'sort_order')) {
            return $this;
        }
        $query = "ALTER TABLE `{$this->getTableName()}` ADD `sort_order` INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`sort_order`)";
        $this->_getWriteAdapter()->query($query);
        return $this;
    }

    protected function _isColumnExists($tableName, $columnName)
    {
        return (bool) $this->_getWriteAdapter()->fetchCol("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
    }
}