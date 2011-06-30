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
 * SodaODTDate - Used to add dates with the SodaODT->setValue or SodaODT->setValueList methods
 *
 * @author Jonathan Doelfs
 * @copyright  AGPL License v3 - Jonathan Doelfs / Sodatech AG
 */
class SodaODTDate {
    
    protected $timestamp = false;
    

    
    public function __construct( $dateStrOrTimestamp, $format=false ) {
    	if( !$dateStrOrTimestamp ) {
    		return;
    	}
    	
        if( $format ) {
            $datetime = DateTime::createFromFormat( $format, $dateStrOrTimestamp );
            $this->timestamp = $datetime->getTimestamp();
            unset( $datetime );
            
        }
        elseif( is_string( $dateStrOrTimestamp ) ) {
            $this->timestamp = strtotime( $dateStrOrTimestamp );
            
        }
        else {
            $this->timestamp = intval( $dateStrOrTimestamp );
        }
    }
    
    
    public function getTimestamp() {
        return $timestamp;
    }
    
    
    public function getDateStr( $format='Y-m-d H:i:s' ) {
    	if( !$this->timestamp ) {
    		return '';
    	}
    	
        return date( $format, $this->timestamp );
    }
    
}