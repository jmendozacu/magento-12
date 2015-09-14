<?php

/**
 * Copyright [2014] [Dexxtz]
 *
 * @package   Dexxtz_Scrolltop
 * @author    Dexxtz
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

class Dexxtz_Scrolltop_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getScrollContent()
	{
		$imageActive = Mage::getStoreConfig('dexxtz_scrolltop/general/active_image');
		$text = Mage::getStoreConfig('dexxtz_scrolltop/general/text');
		$src = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
		$content = '';
		
		if ($imageActive == 1) {
			$image = Mage::getStoreConfig('dexxtz_scrolltop/general/image_upload');
			$imageUrl = $src . "dexxtz/scrolltop/images/" . $image;		
			
			$content = '<img src="' . $imageUrl . '" />';
		} else {
			$string = ($text) ? $text : $this->__('Scroll top');
			$content = $this->getTextStructure($string);
		}
		
		return $content;
	}
	
	private function getImageStyle()
	{
		$width = $this->getImageWidth();
		$height = $this->getImageHeight();
		
		$style = $width . $height;
		
		return $style;
	}
	
	private function getImageWidth()
	{
		$data = Mage::getStoreConfig('dexxtz_scrolltop/general/image_width');
		$width = ($data) ? 'width: ' . $data . ';' : 'width: 40px;';
		
		return $width;
	}
	
	private function getImageHeight()
	{
		$data = Mage::getStoreConfig('dexxtz_scrolltop/general/image_height');
		$height = ($data) ? ' height: ' . $data . ';' : ' height: 40px;';
		
		return $height;
	}
	
	private function getTextStructure($string)
	{
		$span = '<span>' . $string . '</span>';
		
		return $span;
	}
	
	private function getTextStyle()
	{
		$padding = $this->getTextPadding();
		$size = $this->getFontSize();
		$bold = $this->getFontBold();
		$color = $this->getFontColor();
		$bColor = $this->getBackgroundColor();
	
		$style = $padding . $size . $bold . $color . $bColor;
		
		return $style;
	}
	
	private function getTextPadding()
	{
		$padding = Mage::getStoreConfig('dexxtz_scrolltop/general/text_padding');
		$textPadding = ($padding) ? 'padding: ' . $padding . ';' : '';
		
		return $textPadding;
	}
	
	private function getFontSize()
	{
		$size = Mage::getStoreConfig('dexxtz_scrolltop/general/font_size');
		$fontSize = ($size) ? ' font-size: ' . $size . ';' : 'font-size: 16px;';
		
		return $fontSize;
	}
	
	private function getFontBold()
	{
		$bold = Mage::getStoreConfig('dexxtz_scrolltop/general/font_bold');
		$fontBold = ($bold == 1) ? ' font-weight: bold;' : '';
		
		return $fontBold;
	}
	
	private function getFontColor()
	{
		$color = Mage::getStoreConfig('dexxtz_scrolltop/general/font_color');
		$fontColor = ($color) ?  ' color: ' . $color . ';' : '';
		
		return $fontColor;
	}

	private function getBackgroundColor()
	{
		$color = Mage::getStoreConfig('dexxtz_scrolltop/general/background_color');
		$backgroundColor = ($color) ? ' background-color: ' . $color . ';' : '';
		
		return $backgroundColor;
	}
	
	public function getPositionStyle()
	{
		$position = Mage::getStoreConfig('dexxtz_scrolltop/general/position');
		$dFooter = Mage::getStoreConfig('dexxtz_scrolltop/general/distance_footer');
		$dSide = Mage::getStoreConfig('dexxtz_scrolltop/general/distance_side');
		
		$footer = ($dFooter) ?  ' bottom: ' . $dFooter . ';' : '';
		$side = ($dSide) ? $position . ': ' . $dSide . ';' : $position . ': 0;';
		
		$style = $side . $footer;
		
		return $style;		
	}
	
	public function getContentStyle()
	{
		$imageActive = Mage::getStoreConfig('dexxtz_scrolltop/general/active_image');
		$style = '';
		
		if ($imageActive == 1) {
			$style = '.dextz-scrolltop img { ' . $this->getImageStyle() . ' }';
		} else {
			$style = '.dextz-scrolltop span { ' . $this->getTextStyle() . ' } ';
		}
		
		return $style;
	}
	
	public function getSpeedScrolling()
	{
		$data = Mage::getStoreConfig('dexxtz_scrolltop/general/speed_scrolling');
		$speed = ($data) ? $data : '600';
		
		return $speed;
	}
	
	public function getDistanceShow()
	{
		$data = Mage::getStoreConfig('dexxtz_scrolltop/general/distance_show');
		$distance = ($data) ? $data : '300';
		
		return $distance;
	}
}	