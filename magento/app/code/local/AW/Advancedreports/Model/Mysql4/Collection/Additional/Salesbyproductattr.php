<?php
class AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbyproductattr
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    public function reInitItemSelect($filterList)
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
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


        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "main_table.entity_id = item.order_id"
            )
            ->joinLeft(
                array('item2' => $itemTable),
                "(item.parent_item_id IS NOT NULL AND item.parent_item_id = item2.item_id AND item2.product_type = 'configurable')",
                array()
            )
            ->order("main_table.{$filterField} DESC")
        ;

        $this->getSelect()->columns(array(
                "base_row_subtotal"     => "IFNULL(item2.base_row_total, item.base_row_total)",
                'base_row_total'        => "IFNULL(item2.base_row_total, item.base_row_total)
                                             - ABS(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                                             + IFNULL(item2.base_tax_amount, item.base_tax_amount)",
                'base_tax_amount'       => "IFNULL(item2.base_tax_amount, item.base_tax_amount)",
                'base_row_invoiced'     => "IFNULL(item2.base_row_invoiced, item.base_row_invoiced)
                                            + IFNULL(item2.base_hidden_tax_invoiced, item.base_hidden_tax_invoiced)
                                            + IFNULL(item2.base_tax_invoiced, item.base_tax_invoiced)
                                            - IFNULL(item2.base_discount_invoiced, item.base_discount_invoiced)",
                'base_amount_refunded'  => "(IF((IFNULL(item2.qty_refunded, item.qty_refunded) > 0), 1, 0)
                                            * (
                                                IFNULL(item2.qty_refunded, item.qty_refunded) / IFNULL(item2.qty_invoiced, item.qty_invoiced)
                                                * (IFNULL(item2.base_row_invoiced, item.base_row_invoiced) - ABS(IFNULL(item2.base_discount_amount, item.base_discount_amount)) )
                                                + IF((IFNULL(item2.qty_refunded, item.qty_refunded) > 0), ( (IFNULL(item2.qty_refunded, item.qty_refunded) / IFNULL(item2.qty_invoiced, item.qty_invoiced)) * IFNULL(item2.base_tax_amount, item.base_tax_amount)), 0)
                                            ))",
            )
        );

        $filterList = $this->_prepareFilterForCondition($filterList);
        $this->_addProductAttributeFilter($filterList);

        return $this;
    }

    protected function _addProductAttributeFilter($filterList)
    {
        if (!$filterList) {
            return $this;
        }
        $attributeCodeList = array();
        foreach ($filterList as $key => $filter) {
            if (!isset($filter['attribute'])) {
                unset($filterList[$key]);
                continue;
            }
            $attributeCodeList[] = $filter['attribute'];
        }
        $productAttributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->setCodeFilter($attributeCodeList)
        ;
        foreach ($productAttributeCollection as $productAttribute) {
            foreach ($filterList as $key => $filter) {
                if ($filter['attribute'] != $productAttribute->getAttributeCode()) {
                    continue;
                }
                $filterList[$key]['table'] = $productAttribute->getBackendTable();
                $filterList[$key]['attribute_id'] = $productAttribute->getId();
            }
        }
        $eavTable = Mage::helper('advancedreports/sql')->getTable('eav_attribute');

        $whereCondition = "1=1";
        $counter = 0;
        foreach ($filterList as $filter) {
            $this->getSelect()->join(
                array('eav'.$counter => $eavTable),
                "eav".$counter.".attribute_code ='". $filter['attribute'] . "'",
                array()
            );
            $valueAlias = $filter['attribute'] . $counter . '_' . 'value';
            $value = "";
            if (array_key_exists('value', $filter)) {
                if (is_array($filter['value'])) {
                    $value = array_map('trim', $filter['value']);
                } else {
                    $value = trim($filter['value']);
                }
            }
            $fieldName = 'value';
            $fieldAttrId = $valueAlias . '.attribute_id';
            if ($filter['attribute'] == 'sku') {
                $fieldName = 'sku';
                $fieldAttrId = $filter['attribute_id'];
            }
            $condition = join(' AND ', array(
                $this->_getConditionSql(
                    $valueAlias . '.entity_id',
                    array('eq' => new Zend_Db_Expr('IFNULL(item.product_id, item2.product_id)'))
                ),
                $this->_getConditionSql(
                    "eav".$counter.".attribute_id",
                    array('eq' => new Zend_Db_Expr($fieldAttrId))
                )
            ));
            $this->getSelect()->joinInner(
                array($valueAlias => $filter['table']),
                $condition,
                array()
            );
            $whereCondition .= ' ' . $filter['operand'] . ' '
                . $this->_getConditionSql(
                    $valueAlias . '.' . $fieldName,
                    array($filter['condition'] => $value)
                )
            ;
            $counter++;
        }
        $this->getSelect()->where($whereCondition);
    }

    protected function _prepareFilterForCondition($filterList)
    {
        if (!$filterList) {
            return array();
        }
        foreach($filterList as $key => $filter) {
            if(!isset($filter['condition']) || !isset($filter['value'])) {
                continue;
            }

            switch($filter['condition']) {
                case 'like':
                case 'nlike':
                    $filterList[$key]['value'] = '%'.trim($filter['value']).'%';
                    break;
                case 'in':
                case 'nin':
                    $filterList[$key]['value'] = explode(',', trim($filter['value']));
            }
        }

        return $filterList;
    }
}