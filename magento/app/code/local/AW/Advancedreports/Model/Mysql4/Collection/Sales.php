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


class AW_Advancedreports_Model_Mysql4_Collection_Sales extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Sales
     */
    public function reInitSelect($isAllStores = false)
    {
        if ($isAllStores) {
            $currencyRate = "main_table.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("'1'");
        }

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();

        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                'order_created_at'   => $filterField,
                'order_id'           => 'entity_id',
                'order_increment_id' => 'increment_id',
                'customer_email'     => 'customer_email',
                'status' => 'status'
            )
        );

        $this->getSelect()
            ->columns(array('product_id' => "IFNULL(item.product_id, item2.product_id)"))
            # name
            ->columns(array('xname' => "item.name"))
            # sku
            ->columns(array('xsku' => "IFNULL(item.sku, realP.sku)"))
            # price
            ->columns(array('base_xprice' => "COALESCE(IFNULL(item.base_price, item2.base_price) * $currencyRate, 0)"))
            ->columns(array('base_original_xprice' => "COALESCE(IFNULL(item.base_original_price, item2.base_original_price) * $currencyRate,0)"))
            # subtotal
            ->columns(
                array(
                    'base_row_subtotal' => "COALESCE(( IFNULL(item.qty_ordered, item2.qty_ordered) "
                        . "* IFNULL(item.base_price, item2.base_price) ) * $currencyRate,0)"
                )
            )
            //discount
            ->columns(
                array(
                    'base_xdiscount_amount' => "COALESCE(-(IF(item.base_discount_amount = 0, SUM(item2.base_discount_amount), item.base_discount_amount) * $currencyRate), 0)")
            )
            //tax
            ->columns(
                array(
                    'base_xtax_amount' => "COALESCE((IFNULL(item.base_tax_amount, item2.base_tax_amount) * $currencyRate), 0)")
            )
            # total
            ->columns(
                array(
                    'base_row_xtotal_incl_tax' => "( IFNULL(item.base_row_total, item2.base_row_total) "
                        . "+ IFNULL(item.base_tax_amount, item2.base_tax_amount) "
                        . "+ IFNULL(item.base_hidden_tax_amount, 0.0000) "
                        . "+ IFNULL(item.base_weee_tax_applied_amount, item2.base_weee_tax_applied_amount) "
                        . "- COALESCE(IF(item.base_discount_amount = 0, SUM(item2.base_discount_amount), item.base_discount_amount), 0) ) * $currencyRate"
                )
            )
            ->columns(
                array(
                    'base_row_xtotal' => "COALESCE(( IFNULL(item.base_row_total, item2.base_row_total) "
                        . "+ IFNULL(item.base_hidden_tax_amount, 0.0000) "
                        . "+ IFNULL(item.base_weee_tax_applied_amount, item2.base_weee_tax_applied_amount) "
                        . "- COALESCE(IF(item.base_discount_amount = 0, SUM(item2.base_discount_amount), item.base_discount_amount), 0) ) * $currencyRate,0)"
                )
            )
            # invoiced
            ->columns(
                array(
                    'base_row_xinvoiced' => "COALESCE(( IFNULL(item.base_row_invoiced, item2.base_row_invoiced) "
                        . "+ IFNULL(item.base_hidden_tax_invoiced, item2.base_hidden_tax_invoiced) "
                        . "- IFNULL(item.base_discount_invoiced, item2.base_discount_invoiced) ) * $currencyRate, 0)"
                )
            )
            ->columns(
                array(
                    'base_tax_invoiced' =>  "COALESCE(item.base_tax_invoiced, item2.base_tax_invoiced, 0)"
                )
            )
            ->columns(
                array(
                    'base_row_xinvoiced_incl_tax' => "COALESCE(( IFNULL(item.base_row_invoiced, item2.base_row_invoiced) "
                        . "+ IFNULL(item.base_hidden_tax_invoiced, item2.base_hidden_tax_invoiced) "
                        . "+ IFNULL(item.base_tax_invoiced, item2.base_tax_invoiced) "
                        . "- IFNULL(item.base_discount_invoiced, item2.base_discount_invoiced) ) * $currencyRate, 0)"
                )
            )
            # refunded
            ->columns(
                array(
                    'base_row_xrefunded' => "COALESCE(( (IF((IFNULL(item.qty_refunded, item2.qty_refunded) > 0), 1, 0) "
                        . "* (  (IFNULL(item.qty_refunded, item2.qty_refunded) / IFNULL(item.qty_invoiced, item2.qty_invoiced)) "
                        . "* ( IFNULL(item.qty_invoiced, item2.qty_invoiced) * IFNULL(item.base_price, item2.base_price) "
                        . "- ABS( COALESCE(IF(item.base_discount_amount = 0, SUM(item2.base_discount_amount), item.base_discount_amount),0) ) )  ) ) ) * $currencyRate, 0)"
                )
            )
            ->columns(
                array(
                    'base_tax_xrefunded' => "COALESCE(IF(( IFNULL(item.qty_refunded, item2.qty_refunded) > 0), ( IFNULL(item.qty_refunded, item2.qty_refunded) "
                        . "/ IFNULL(item.qty_invoiced, item2.qty_invoiced) "
                        . "*  IFNULL(item.base_tax_invoiced, item2.base_tax_invoiced) ), 0) * $currencyRate, 0)"
                )
            )
            ->columns(
                array(
                    'base_row_xrefunded_incl_tax' => "COALESCE(((IF(( IFNULL(item.qty_refunded, item2.qty_refunded) > 0), 1, 0) "
                        . "* (  (IFNULL(item.qty_refunded, item2.qty_refunded) * ( IFNULL(item.qty_invoiced, item2.qty_invoiced) * IFNULL(item.base_price, item2.base_price) - ABS( COALESCE(IF(item.base_discount_amount = 0, SUM(item2.base_discount_amount), item.base_discount_amount), 0) ) ) "
                        . "/ IFNULL(item.qty_invoiced, item2.qty_invoiced) ) "
                        . "+ IF((IFNULL(item.qty_refunded, item2.qty_refunded) > 0) , ( IFNULL(item.qty_refunded, item2.qty_refunded) / IFNULL(item.qty_invoiced, item2.qty_invoiced)  "
                        . "*  IFNULL(item.base_tax_invoiced, item2.base_tax_invoiced) ), 0) )  )) * $currencyRate, 0)"
                )
            )
            ->columns(array('xqty_ordered' => 'COALESCE(IFNULL(item.qty_ordered, item2.qty_ordered), 0)'))
            ->columns(array('xqty_invoiced' => 'COALESCE(IFNULL(item.qty_invoiced, item2.qty_invoiced), 0)'))
            ->columns(array('xqty_shipped' => 'COALESCE(IFNULL(item.qty_shipped, item2.qty_shipped), 0)'))
            ->columns(array('xqty_refunded' => 'COALESCE(IFNULL(item.qty_refunded, item2.qty_refunded), 0)'));

        return $this;
    }

    /**
     * Exclude refunded
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Sales
     */
    public function excludeRefunded()
    {
        $this->getSelect()
            ->where('? > 0', new Zend_Db_Expr('(item.qty_ordered - item.qty_refunded)'));
        return $this;
    }

    /**
     * Add order items
     *
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Sales
     */
    public function addOrderItems()
    {
        $productTable = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity');
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "(item.order_id = main_table.entity_id AND item.parent_item_id IS NULL)",
                array()
            );
        $this->getSelect()
            ->joinLeft(
                array('item2' => $itemTable),
                "(item2.order_id = main_table.entity_id AND item2.parent_item_id IS NOT NULL AND item2.parent_item_id = item.item_id AND item.product_type IN ('configurable', 'bundle'))",
                array()
            )
            ->joinLeft(
                array('realP' => $productTable),
                "item.product_id = realP.entity_id",
                array()
            )
            ->group("item.item_id")
            ->order("main_table.{$filterField} DESC");
        return $this;
    }

    /**
     * Add customer info
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Sales
     */
    public function addCustomerInfo()
    {
        $customerEntity = Mage::helper('advancedreports/sql')->getTable('customer_entity');
        $customerGroup = Mage::helper('advancedreports/sql')->getTable('customer_group');

        $this->getSelect()
            ->joinLeft(array('c_entity' => $customerEntity), "main_table.customer_id = c_entity.entity_id", array())
            ->joinLeft(
                array('c_group' => $customerGroup),
                "IFNULL(c_entity.group_id, 0) = c_group.customer_group_id",
                array('customer_group' => "c_group.customer_group_code")
            );

        return $this;
    }

    /**
     * Add manufacturer
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Sales
     */
    public function addManufacturer()
    {
        $entityProduct = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity');
        $entityValuesVarchar = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity_varchar');
        $entityValuesInt = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity_int');
        $entityAtribute = Mage::helper('advancedreports/sql')->getTable('eav_attribute');
        $eavAttrOptVal = Mage::helper('advancedreports/sql')->getTable('eav_attribute_option_value');
        $this->getSelect()
            ->join(
                array('_product' => $entityProduct),
                "_product.entity_id = item.product_id",
                array()
            )
            ->joinLeft(
                array('_manAttr' => $entityAtribute),
                "_manAttr.attribute_code = 'manufacturer'",
                array()
            )
            ->joinLeft(
                array('_manValVarchar' => $entityValuesVarchar),
                "_manValVarchar.attribute_id = _manAttr.attribute_id AND _manValVarchar.entity_id = _product.entity_id",
                array()
            )
            ->joinLeft(
                array('_manValInt' => $entityValuesInt),
                "_manValInt.attribute_id = _manAttr.attribute_id AND _manValInt.entity_id = _product.entity_id",
                array()
            )
            ->joinLeft(
                array('_optVal' => $eavAttrOptVal),
                "_optVal.option_id = IFNULL(_manValInt.value, _manValVarchar.value) AND _optVal.store_id = 0",
                array('product_manufacturer' => 'value')
            );
        return $this;
    }

    public function addAddress()
    {
        $salesFlatOrderAddress = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_address');
        $this->getSelect()
            ->joinLeft(
                array('flat_order_addr_ship' => $salesFlatOrderAddress),
                "flat_order_addr_ship.parent_id = main_table.entity_id AND flat_order_addr_ship.address_type = 'shipping'",
                array()
            )
            ->joinLeft(
                array('flat_order_addr_bil' => $salesFlatOrderAddress),
                "flat_order_addr_bil.parent_id = main_table.entity_id AND flat_order_addr_bil.address_type = 'billing'",
                array()
            );

        //order country id
        $this->getSelect()->columns(
            array(
                'order_country'  => 'COALESCE(flat_order_addr_ship.country_id, flat_order_addr_bil.country_id, "")',
                'order_region'   => 'COALESCE(flat_order_addr_ship.region, flat_order_addr_bil.region, "")',
                'order_city'     => 'COALESCE(flat_order_addr_ship.city, flat_order_addr_bil.city, "")',
                'order_postcode' => 'COALESCE(flat_order_addr_ship.postcode, flat_order_addr_bil.postcode, "")',
            )
        );

        //customer_name
        $this->getSelect()->columns(
            array(
                'customer_name' => "IFNULL(
                    CONCAT(main_table.customer_firstname,' ',main_table.customer_lastname),
                    CONCAT(flat_order_addr_bil.firstname, ' ', flat_order_addr_bil.lastname)
                )")
        );
        return $this;
    }
}