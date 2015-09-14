<?php

class AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
    extends Mage_Customer_Model_Entity_Customer_Collection
{
    /**
     * Reinitialize collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function reInitSelect()
    {
        $customerTable = Mage::helper('advancedreports/sql')->getTable('customer_entity');
        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('e' => $customerTable),
            array('email')
        );
        return $this;
    }

    /**
     * Set Date Filter
     *
     * @param $from
     * @param $to
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function setDateFilter($from, $to)
    {
        $this->getSelect()
            ->where("wish_item.added_at >= ?", $from)
            ->where("wish_item.added_at <= ?", $to)
        ;
        return $this;
    }


    /**
     * Filter collection by Store Ids
     *
     * @param $storeIds
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function setStoreFilter($storeIds)
    {
        $this->getSelect()
            ->where("wish_item.store_id in ('" . implode("','", $storeIds) . "')")
        ;
        return $this;
    }

    /**
     * Add wishlist
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function addWishlist()
    {
        $wish = Mage::helper('advancedreports/sql')->getTable('wishlist');
        $wishItem = Mage::helper('advancedreports/sql')->getTable('wishlist_item');

        $this->getSelect()
            ->join(array('wish' => $wish), "wish.customer_id = e.entity_id", array())
            ->join(
                array('wish_item' => $wishItem), "wish_item.wishlist_id = wish.wishlist_id",
                array('store_id', 'added_at', 'description')
            );
        return $this;
    }

    /**
     * Add customer firstname and lastname
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function addCustomerName()
    {
        $this->addExpressionAttributeToSelect('firstname', null, 'firstname');
        $this->addExpressionAttributeToSelect('lastname', null, 'lastname');
        return $this;
    }

    /**
     * Add product info
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function addProductInfo()
    {
        $productEntity = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity');
        $attribute = Mage::helper('advancedreports/sql')->getTable('eav_attribute');
        $eavTypes = Mage::helper('advancedreports/sql')->getTable('eav_entity_type');
        $values = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity_varchar');

        $this->getSelect()
            ->join(array('product' => $productEntity), "product.entity_id = wish_item.product_id", 'sku')
            ->join(array('e_type' => $eavTypes), "e_type.entity_type_code = 'catalog_product'", array())
            ->join(
                array('attr' => $attribute),
                "attr.attribute_code = 'name' AND attr.entity_type_id = e_type.entity_type_id",
                array()
            )
            ->join(
                array('value' => $values),
                "value.entity_id = wish_item.product_id AND value.entity_type_id = e_type.entity_type_id "
                . "AND value.attribute_id = attr.attribute_id AND value.store_id = '0'",
                array('product_name' => 'value')
            );
        return $this;
    }

    /**
     * Add customer group
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Userswishlists
     */
    public function addCustomerGroup()
    {
        $customerGroup = Mage::helper('advancedreports/sql')->getTable('customer_group');
        $this->getSelect()
            ->join(
                array('c_gr' => $customerGroup), "c_gr.customer_group_id = e.group_id",
                array('customer_group' => 'c_gr.customer_group_code')
            );
        return $this;
    }

    /**
     * Adding item to item array
     *
     * @param  Varien_Object $item
     * @throws Exception
     * @return Varien_Data_Collection
     */
    public function addItem(Varien_Object $item)
    {
        $itemId = uniqid();

        if (!is_null($itemId)) {
            if (isset($this->_items[$itemId])) {
                throw new Exception('Item (' . get_class($item) . ') with the same id "' . $item->getId()
                    . '" already exist');
            }
            $this->_items[$itemId] = $item;
        } else {
            $this->_addItem($item);
        }
        return $this;
    }

    public function addFieldToFilter($attribute, $condition = null)
    {
        return Varien_Data_Collection_Db::addFieldToFilter($attribute, $condition);
    }

    public function getAttributeTableAlias($attributeCode)
    {
        return parent::_getAttributeTableAlias($attributeCode);
    }
}
