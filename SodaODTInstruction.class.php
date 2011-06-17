<?php


class SodaODTInstruction {
	protected $instruction = false;
	
	
	public function __construct( $instruction ) {
		$this->instruction = $instruction;
	} 
	
	public function getInstruction() {
		return $this->instruction;
	}
	
}
