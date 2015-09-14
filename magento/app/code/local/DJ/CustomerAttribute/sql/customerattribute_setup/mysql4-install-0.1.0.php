<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute("customer", "erply_customer_id",  array(
    "type"     => "int",
    "backend"  => "",
    "label"    => "Erply Customer Id",
    "input"    => "text",
    "source"   => "",
    "visible"  => false,
    "required" => false,
    "default" => "0",
    "frontend" => false,
    "unique"     => false,
    "note"       => ""
));
$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "erply_customer_id");
$used_in_forms=array();
$used_in_forms[]="adminhtml_customer";
$attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 0)
        ->setData("sort_order", 100);
$attribute->save();
$installer->endSetup();