<?php
Mage::helper('ewcore/cache')->clean();
$installer = $this;
$installer->startSetup();


$command = "
DROP TABLE IF EXISTS `ewauthenticator_log`;
CREATE TABLE `ewauthenticator_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` enum('success','failed') NOT NULL,
  `admin_user_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) NOT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_admin_user_id` (`admin_user_id`),
  CONSTRAINT `fk_vhngx6b8rakv80t` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ewauthenticator_user`;
CREATE TABLE `ewauthenticator_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mode` enum('password','verification_code','both') NOT NULL DEFAULT 'password',
  `admin_user_id` int(10) unsigned NOT NULL,
  `secret_key` text NOT NULL,
  `tolerance_level` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `idx_admin_user_id` (`admin_user_id`),
  CONSTRAINT `fk_skm0mp2ycj99vyi` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$command = @preg_replace('/(EXISTS\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(REFERENCES\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(TABLE\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);

if ($command) $installer->run($command);

$collection = Mage::getModel('admin/user')->getCollection();
foreach ($collection as $user) {
	Mage::helper('ewauthenticator')->getSetUser($user->getId());
}

$installer->endSetup(); 