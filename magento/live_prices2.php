<?php
	require_once 'app/Mage.php';
	ini_set('memory_limit', '1024M');
	ini_set('max_execution_time', 0);
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	umask(0);
	Mage::app();
	Mage::register('isSecureArea', true);
	$file = fopen("products_prices2.csv","r");
	while(!feof($file)){		
		$line = fgetcsv($file);
		echo $sku = $line[0];
		echo "--";
		echo $price = $line[1];
		echo "<br>";
		if($sku != ''){
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			if($product){
				$product->setPrice($price);
            	$product->save();	
			}			
		}		
	}
	fclose($file);
?>