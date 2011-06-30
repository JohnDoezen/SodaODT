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
 * SodaODTFile - Abstract class currently only extended by SodaODTImage
 *
 * @author Jonathan Doelfs
 * @copyright  AGPL License v3 - Jonathan Doelfs / Sodatech AG
 */
abstract class SodaODTFile {
	
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