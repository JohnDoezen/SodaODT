<?php 

class SodaODTFile {
	
	protected $file = false;
	
	public function __construct( $file ) {
		$this->file = $file;
	}
	
	
	public function getBlob() {
		return file_get_contents( $this->file );
	}	
	
	
	public function getMimeType() {
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mimeType = $finfo->file( $this->file );
		
		return $mimeType;
	}
	
	
	public function getName() {
		return basename( $this->file );
	}
	
	
	public function getMD5() {
		return md5_file( $this->file );
	}
	
}