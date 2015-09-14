<?php
class AW_Advancedreports_Model_Mysql4_Collection_Additional_Manufacturer
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{

    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Manufacturer
     */
    public function reInitSelect()
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();

        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                 'order_created_at'   => $filterField,
                 'order_id'           => 'entity_id',
                 'order_increment_id' => 'increment_id',
            )
        );
        return $this;
    }

    /**
     * Add order items
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Manufacturer
     */
    public function addOrderItems()
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();

        $priceAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'price');
        $priceTable = $priceAttr->getBackendTable();

        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "main_table.entity_id = item.order_id",
                array()
            )
            ->joinLeft(
                array('item2' => $itemTable),
                "(main_table.entity_id = item2.order_id AND item.parent_item_id IS NOT NULL AND item.parent_item_id = item2.item_id AND item2.product_type = 'configurable')",
                array()
            )
            ->joinLeft(
                array('item_price' => $priceTable),
                "item_price.entity_id = item.product_id AND item_price.attribute_id = {$priceAttr->getId()}",
                array()
            )
            ->where("(item.product_type <> 'bundle' OR item_price.value > 0)")
            ->where("(item.product_type <> 'configurable')")
            ->order("main_table.{$filterField} DESC")
        ;

        $this->getSelect()
            # subtotal
            ->columns(array('base_row_subtotal' => "COALESCE(SUM((IFNULL(item2.qty_ordered, item.qty_ordered) * IFNULL(item2.base_price, item.base_price))), 0)"))
            ->columns(array('qty_ordered' => "COALESCE(SUM(IFNULL(item2.qty_ordered, item.qty_ordered)), 0)"))
            # total
            ->columns(
                array(
                     'base_row_xtotal_incl_tax' => "SUM(COALESCE((
                     IFNULL(item2.base_row_total, item.base_row_total)
                     - ABS(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                     + IFNULL(item2.base_tax_amount, item.base_tax_amount)), 0))"
                )
            )
            //discount
            ->columns(
                array(
                    'base_xdiscount_amount' => "-SUM(COALESCE((IFNULL(item2.base_discount_amount, item.base_discount_amount)), 0))")
            )
            ->columns(array('base_tax_xamount' => "SUM(COALESCE((IFNULL(item2.base_tax_amount, item.base_tax_amount)), 0))"))
            ->columns(
                array('base_row_xtotal' => "SUM(COALESCE((IFNULL(item2.base_row_total, item.base_row_total)
                - ABS(IFNULL(item2.base_discount_amount, item.base_discount_amount))), 0))")
            )

            # invoiced
            ->columns(
                array('base_row_xinvoiced' => "SUM(COALESCE((IFNULL(item2.base_row_invoiced, item.base_row_invoiced)
                - IFNULL(item2.base_discount_invoiced, item.base_discount_invoiced)), 0))")
            )
            ->columns(array('base_tax_xinvoiced' => "SUM(COALESCE(((item.qty_invoiced / item.qty_ordered) *  item.base_tax_amount), 0))"))
            ->columns(
                array('base_row_xinvoiced_incl_tax' => "SUM(COALESCE((IFNULL(item2.base_row_invoiced, item.base_row_invoiced)
                + IFNULL(item2.base_hidden_tax_invoiced, item.base_hidden_tax_invoiced)
                + IFNULL(item2.base_tax_invoiced, item.base_tax_invoiced)
                - IFNULL(item2.base_discount_invoiced, item.base_discount_invoiced)), 0))")
            )

            # refunded
            ->columns(
                array('base_row_xrefunded' => "SUM(COALESCE( (IF((IFNULL(item2.qty_refunded, item.qty_refunded) > 0), 1, 0)
                    * (  (IFNULL(item2.qty_refunded, item.qty_refunded) / IFNULL(item2.qty_invoiced, item.qty_invoiced) )
                    * (
                        IFNULL(item2.qty_invoiced, item.qty_invoiced)
                        * IFNULL(item2.base_price, item.base_price)
                        - ABS(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                    )  )), 0)) ")
            )
            ->columns(
                array('base_tax_xrefunded' => "SUM(
                    COALESCE((IFNULL(item2.qty_refunded, item.qty_refunded)
                    / IFNULL(item2.qty_invoiced, item.qty_invoiced)
                    *  IFNULL(item2.base_tax_amount, item.base_tax_amount)), 0))")
            )
            ->columns(
                array('base_row_xrefunded_incl_tax' => "SUM(IFNULL(item2.qty_refunded, item.qty_refunded))")
            )
        ;
        return $this;
    }

    /**
     * Add manufacturer
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Manufacturer
     */
    public function addProductAttribute($attributeCode)
    {
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attributeCode);

        if (!$attribute->getId()) {
            return $this;
        }

        $attributeTable = $attribute->getBackendTable();
        $attributeTypeId = $attribute->getEntityTypeId();

        $eavTable = Mage::helper('advancedreports/sql')->getTable('eav_attribute');
        $eavOptionTable = Mage::helper('advancedreports/sql')->getTable('eav_attribute_option');
        $eavOptionValueTable = Mage::helper('advancedreports/sql')->getTable('eav_attribute_option_value');
        $this->getSelect()->join(
            array('eav' => $eavTable),
            "eav.attribute_code = '{$attributeCode}' AND eav.entity_type_id = {$attributeTypeId}",
            array()
        );

        $valueAlias = $attributeCode . '_' . 'value';
        $optionAlias = $attributeCode . '_' . 'option';
        $optionValueAlias = $attributeCode . '_' . 'option_value';

        $condition = join(' AND ', array(
                $this->_getConditionSql(
                    $valueAlias . '.entity_id',
                    array('eq' => new Zend_Db_Expr('IFNULL(item.product_id, item2.product_id)'))
                ),
                $this->_getConditionSql(
                    'eav.attribute_id',
                    array('eq' => new Zend_Db_Expr($valueAlias . '.attribute_id'))
                )
            ));

        $storeId = Mage::app()->getStore()->getId();
        $this->getSelect()
            ->joinLeft(
                array($valueAlias => $attributeTable),
                $condition,
                array()
            )
            ->joinLeft(
                array($optionAlias => $eavOptionTable),
                "eav.attribute_id = {$optionAlias}.attribute_id AND {$optionAlias}.option_id = {$valueAlias}.value",
                array()
            )
            ->joinLeft(
                array($optionValueAlias => $eavOptionValueTable),
                "{$optionAlias}.option_id = {$optionValueAlias}.option_id AND {$optionValueAlias}.store_id = {$storeId}",
                array()
            )
            ->columns(array('product_attribute' => "COALESCE({$optionValueAlias}.value, 'Not Set')"))
            ->group($optionValueAlias . '.value');
        return $this;
    }
}