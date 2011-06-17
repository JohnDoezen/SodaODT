<?php 

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