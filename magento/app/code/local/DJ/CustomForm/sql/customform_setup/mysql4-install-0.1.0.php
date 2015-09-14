<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
create table dj_customform(
	customform_id int(100) not null auto_increment,
	email varchar(255),
	firstname varchar(255),
	lastname varchar(255),
	address text,
	phone varchar(255),
	primary key(customform_id));
SQLTEXT;

$installer->run($sql);
//demo 
//Mage::getModel('core/url_rewrite')->setId(null);
//demo 
$installer->endSetup();
	 