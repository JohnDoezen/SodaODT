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
 * SodaODTInstruction - Used to carry processing informations
 *
 * @author Jonathan Doelfs
 * @copyright  AGPL License v3 - Jonathan Doelfs / Sodatech AG
 */
class SodaODTInstruction {
    
	protected $instruction = false;
	
	
	
	public function __construct( $instruction ) {
		$this->instruction = $instruction;
	} 
	
	public function getInstruction() {
		return $this->instruction;
	}
	
}
