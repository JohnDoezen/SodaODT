<?php 

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