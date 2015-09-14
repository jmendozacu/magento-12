<?php
ini_set('memory_limit', '1024M');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
require_once 'app/Mage.php';
umask(0);
Mage::app();
Mage::register('isSecureArea', true);

$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
$sql = "SHOW PROCESSLIST";
$rows = $readConnection->fetchAll($sql);
echo "<pre>";
foreach ($rows as $process) {
	if($process['User'] == 'root'){
		//if($process['db'] == 'prod_mag_vapestore'){
			if($process['Time'] > 0){
				$sqlKill = "KILL ".$process['Id'];
		    	$writeConnection->query($sqlKill);
		    	print_r($process);
			}
		//}
	}	
}
echo "</pre>";
echo "Killed Idle Processes";exit;
?>