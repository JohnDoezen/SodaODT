<?php
include_once('../SodaODT.class.php');

$odt = new SodaODT( 'example.odt' );


// Text entries
$odt->setValue( 'name', 'John Doe' );
$odt->setValue( 'text', "This is a test" );


// Image
$image	= new SodaODTImage('./php.png');
$odt->setValue( 'test_image', $image );


// Dates
// text date parsed by a certain format
$date_1 = new SodaODTDate('24.04.2011 09-12-34', 'd.m.Y H-i-s');
$odt->setValue( 'test_date_1', $date_1 );

// text date parsed by php 'strtotime' function
$date_2 = new SodaODTDate('2011-04-24 09:12:34');
$odt->setValue( 'test_date_2', $date_2 );

// int timestamp
$timestamp = mktime( 9, 12, 34, 4, 24, 2011 );
$date_3 = new SodaODTDate( $timestamp );
$odt->setValue( 'test_date_3', $date_3 );


// list
$itemList = array();
$itemList[0] = array( 'name' => 'First name', 'number' => "first number" );
$itemList[1] = array( 'name' => 'Second name', 'number' => "second number" );
$odt->setValue( 'items', $itemList );


// Create parsed ODT file
$odt->render();

// Output parsed ODT file
$odt->save('parsed_example.odt');



