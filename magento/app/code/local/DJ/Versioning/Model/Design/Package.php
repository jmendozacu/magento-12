<?php
class DJ_Versioning_Model_Design_Package extends Mage_Core_Model_Design_Package
{

   /**
      * Get the timestamp of the newest file
      * 
      * @param array $files
      * @return int $timeStamp
      */
    protected function getNewestFileTimestamp($srcFiles) {
         $timeStamp = null;
         foreach ($srcFiles as $file) {
             if(is_null($timeStamp)) {
                //if is first file, set $timeStamp to filemtime of file
                $timeStamp = filemtime($file);
            } else {
                //get max of current files filemtime and the max so far
                $timeStamp = max($timeStamp, filemtime($file));
            }
         }
         return $timeStamp;
    }
     
     
    /**
     * Merge specified css files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        // secure or unsecure
        $isSecure = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);
        if (!$targetDir) {
            return '';
        }

        // base hostname & port
        $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        $hostname = parse_url($baseMediaUrl, PHP_URL_HOST);
        $port = parse_url($baseMediaUrl, PHP_URL_PORT);
        if (false === $port) {
            $port = $isSecure ? 443 : 80;
        }
        
        //get timestamp of newest source file
        $filesTimeStamp = $this->getNewestFileTimestamp($files);
        
        // merge into target file
        $targetFilename = md5(implode(',', $files) . "|{$hostname}|{$port}") . "_" . $filesTimeStamp . '.css';        
        
        //If the file with the proper timestamp as part of its filename already exists, there's no reason to check again to see if
        //we need to remerge the css files
        if(!file_exists($targetDir . DS . $targetFilename)) {        
            $mergeFilesResult = $this->_mergeFiles(
                $files, $targetDir . DS . $targetFilename,
                false,
                array($this, 'beforeMergeCss'),
                'css'
            );
            if ($mergeFilesResult) {
                return $baseMediaUrl . $mergerDir . '/' . $targetFilename;
            }        
        } else {
            return $baseMediaUrl . $mergerDir . '/' . $targetFilename;
        }        
        return '';
    }     

}