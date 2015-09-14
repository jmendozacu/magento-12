<?php

$installer = $this;
$installer->startSetup();

$command  = "
	ALTER TABLE `ewauthenticator_user` 
	DROP FOREIGN KEY `fk_skm0mp2ycj99vyi`  ;


	/* Alter table in target */
	ALTER TABLE `ewauthenticator_user` 
		ADD COLUMN `two_factor_mode` enum('verification_code','both')  COLLATE utf8_general_ci NULL DEFAULT 'both' after `mode` , 
		CHANGE `admin_user_id` `admin_user_id` int(10) unsigned   NOT NULL after `two_factor_mode` , 
		CHANGE `secret_key` `secret_key` text  COLLATE utf8_general_ci NOT NULL after `admin_user_id` , 
		CHANGE `tolerance_level` `tolerance_level` int(10) unsigned   NULL after `secret_key` , 
		CHANGE `updated_at` `updated_at` datetime   NOT NULL after `tolerance_level` , 
		CHANGE `created_at` `created_at` datetime   NOT NULL after `updated_at` ; 
	
	/* The foreign keys that were dropped are now re-created*/
	ALTER TABLE `ewauthenticator_user` 
		ADD CONSTRAINT `fk_skm0mp2ycj99vyi` 
		FOREIGN KEY (`admin_user_id`) REFERENCES `admin_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
";

$command = @preg_replace('/(EXISTS\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(ON\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(REFERENCES\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(TABLE\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);

$installer->run($command);


$installer->endSetup();