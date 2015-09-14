<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
create table product_log(
    	product_log_id int not null auto_increment, 
    	action varchar(100), 
    	username varchar(100), 
    	email varchar(255),
    	product_name varchar(255),
    	sku varchar(255),
    	date timestamp,
    	primary key(product_log_id));
SQLTEXT;

$installer->run($sql);
//demo 
//Mage::getModel('core/url_rewrite')->setId(null);
//demo 
$installer->endSetup();
	 