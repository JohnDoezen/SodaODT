<?php  
/*-------------------------------------------------------+
| SodaODT - OpenOffice document template parser for PHP
| Copyright (C) 2011 Jonathan Doelfs / Sodatech AG
| http://www.sodatech.com/
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/


/**
 * SodaODTImage - Used to add images with the SodaODT->setValue or SodaODT->setValueList methods
 *
 * Depends on Imagick PECL module 
 *
 * @author Jonathan Doelfs
 * @copyright  AGPL License v3 - Jonathan Doelfs / Sodatech AG
 */
class SodaODTImage extends SodaODTFile {
	
	protected $imagick = false;
	
	
	
	public function getWidth() {
		$im = $this->getImagick();
		return $im->getImageWidth();
	}
	
	
	public function getHeight() {
		$im = $this->getImagick();
		return $im->getImageHeight();
	}
	
	
	public function getImagick() {
		if( !$this->imagick ) {
			$this->imagick = new Imagick();
			$this->imagick->readImageBlob( $this->getBlob() );
		}
		
		return $this->imagick;
	}
	
	
	public function freeImagick() {
		if( $this->imagick ) {
			$this->imagick->clear();
			unset( $this->imagick ); 
		}
	}
}