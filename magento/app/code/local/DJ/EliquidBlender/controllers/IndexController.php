<?php
class DJ_EliquidBlender_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
        $this->loadLayout();   
        $this->getLayout()->getBlock("head")->setTitle($this->__("Eliquid Blender"));
        $this->renderLayout(); 
    }
    
    public function blenderdataAction(){
        $read = Mage::getSingleton( 'core/resource' )->getConnection( 'core_read' ); // To read from the database
        $write = Mage::getSingleton( 'core/resource' )->getConnection( 'core_write' ); // To write to the database
        $tableName = 'eliquid_blender';

        if($this->getRequest()->getPost()){
            $receiveData = $this->getRequest()->getPost();
            $blender_detail = $receiveData['data'];

            $write->beginTransaction();


            $__fields = array();
            $__values = array();

            if(isset($blender_detail['product_id1']) && $blender_detail['product_id1']!= ''){
                $__fields[] = 'product_id1';
                $__values[] = $blender_detail['product_id1'];
            }
            if(isset($blender_detail['product_id2']) && $blender_detail['product_id2']!= ''){
                $__fields[] = 'product_id2';
                $__values[] = $blender_detail['product_id2'];
            }
            if(isset($blender_detail['product_id3']) && $blender_detail['product_id3']!= ''){
                $__fields[] = 'product_id3';
                $__values[] = $blender_detail['product_id3'];
            }
            if(isset($blender_detail['product1_per']) && $blender_detail['product1_per']!= ''){
                $__fields[] = 'product1_per';
                $__values[] = $blender_detail['product1_per'];
            }
            if(isset($blender_detail['product2_per']) && $blender_detail['product2_per']!= ''){
                $__fields[] = 'product2_per';
                $__values[] = $blender_detail['product2_per'];
            }
            if(isset($blender_detail['product3_per']) && $blender_detail['product3_per']!= ''){
                $__fields[] = 'product3_per';
                $__values[] = $blender_detail['product3_per'];
            }
            if(isset($blender_detail['blender_name']) && $blender_detail['blender_name']!= ''){
                $__fields[] = 'blender_name';
                $__values[] = $blender_detail['blender_name'];
            }
            if(isset($blender_detail['extra_shot_id']) && $blender_detail['extra_shot_id']!= ''){
                $__fields[] = 'extra_shot_id';
                $__values[] = $blender_detail['extra_shot_id'];
            }
            if(isset($blender_detail['size']) && $blender_detail['size']!= ''){
                $__fields[] = 'size';
                $__values[] = $blender_detail['size'];
            }
            if(isset($blender_detail['nicotine']) && $blender_detail['nicotine']!= ''){
                $__fields[] = 'nicotine';
                $__values[] = $blender_detail['nicotine'];
            }
                
            $fieldStr = '('.implode(',', $__fields).')';
            $valueStr = '("'.implode('","', $__values).'")';

            

            $sql = 'insert into '.$tableName.' '.$fieldStr.' values '.$valueStr ;
            $write->query($sql);
            $write->commit();
            $iBlenderId = $write->fetchOne('SELECT last_insert_id()');
            echo $iBlenderId;exit;
            
        }
    }
}