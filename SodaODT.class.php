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

include_once( dirname(__FILE__) .'/SodaODTParser.class.php' );
include_once( dirname(__FILE__) .'/SodaODTFile.class.php' );
include_once( dirname(__FILE__) .'/SodaODTImage.class.php' );
include_once( dirname(__FILE__) .'/SodaODTDate.class.php' );
include_once( dirname(__FILE__) .'/SodaODTInstruction.class.php' );


/**
 * SodaODT - OpenOffice document template parser for PHP
 *
 * Depends on PECL modules 
 *  - ZipArchive
 *  - Fileinfo
 *  - Imagick (to reduce image filesize of embedded images if possible)
 *  - DOM (PHP5 DOM XML)
 *
 * @author Jonathan Doelfs
 * @copyright  AGPL License v3 - Jonathan Doelfs / Sodatech AG
 */
class SodaODT {
	
	protected $templateFile 	= false;
	protected $replaceValueList = array();
	
	protected $finishedPlaceholders = array();
	protected $embedFileList		= array();
	protected $nextTagNameNr		= 1;
	protected $xmlDom				= false;
	protected $tmpResultFile		= false;
	
	protected $repeatableTags		= array(  array(self::NS_TABLE, 'table-row'),
											  array(self::NS_DRAW, 'frame') 
										   );
	
	const PIXEL_TO_CM = 0.026458333;
	
	const NS_TEXT  = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
	const NS_TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
	const NS_DRAW  = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
	const NS_STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
	const NS_SVG   = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
	const NS_XLINK = 'http://www.w3.org/1999/xlink';
	const NS_MANIFEST = 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0'; 
	
	static protected $nsPrefixList = array( self::NS_TEXT => 'text',
											self::NS_TABLE => 'table',
											self::NS_DRAW => 'draw',
											self::NS_STYLE => 'style',
											self::NS_SVG => 'svg',
											self::NS_XLINK => 'xlink',
											self::NS_MANIFEST => 'manifest'
										  );
										  
										  
	
	
	
	public function __construct( $templateFile ) {
		$this->templateFile = $templateFile;
	}
	
	
	public function __destruct() {
		if( $this->tmpResultFile && file_exists($this->tmpResultFile) ) {
			unlink( $this->tmpResultFile );
		}
	}
	
	
	public function setValueList( $valueList ) {
		if( !is_array( $valueList ) ) {
			return false;
		}
		
		$this->replaceValueList = $valueList;
		
		return true;
	}
	
	
	public function setValue( $name, $value ) {
		$this->replaceValueList[ $name ] = $value;
	}
	
	
	public function getValueList() {
		return $this->replaceValueList;
	}
	
	
	public function getValue( $name ) {
		if( !isset( $this->replaceValueList[ $name ] ) ) {
			return null;
		}
		
		return $this->replaceValueList[ $name ];
	}
	
	
	public function render() {
		$this->initRender();
		
		$this->tmpResultFile = 'odt_'. md5( uniqid( rand(), true ) ) .'.odt';
		
		if( !copy( $this->templateFile, $this->tmpResultFile ) ) {
			throw new Exception("Could not create temporary file from '". $this->templateFile ."'");
		}
			  
		$zip = new ZipArchive();
		$zip->open( $this->tmpResultFile );
		
		// Parse main content
		$this->xmlDom = new DOMDocument();
		$this->xmlDom->loadXML( $zip->getFromName('content.xml') );
		$this->normalizeTextNodes( $this->xmlDom );
		$this->parseXmlElement( $this->xmlDom );
		$zip->addFromString( 'content.xml', $this->xmlDom->saveXML() );
		
		// Parse styles file
		$this->xmlDom = new DOMDocument();
		$this->xmlDom->loadXML( $zip->getFromName('styles.xml') );
		$this->normalizeTextNodes( $this->xmlDom );
		$this->parseXmlElement( $this->xmlDom );
		$zip->addFromString( 'styles.xml', $this->xmlDom->saveXML() );
		
		// Prepare all other files
		$this->embedFiles( $zip );
		$this->createMetaFile( $zip );
		
		// Delete the 'layout-cache' file which can cause trouble with the page layout
		$zip->deleteName('layout-cache');
		
		$zip->close();
		
		return true;
	}
	
	
	protected function initRender() {
		$this->finishedPlaceholders = array();
		$this->embedFileList		= array();
		$this->nextTagNameNr		= 1;
		$this->xmlDom				= false;
		
		if( $this->tmpResultFile ) {
			@unlink( $this->tmpResultFile );
		}
		$this->tmpResultFile		= false;
	}
	
	
	public function getBlob() {
		if( !$this->tmpResultFile || !file_exists($this->tmpResultFile) ) {
			return false;
		}
		
		return file_get_contents( $this->tmpResultFile );
	}
	
	
	public function save( $filename ) {
		if( !$this->tmpResultFile || !file_exists($this->tmpResultFile) ) {
			return false;
		}
		
		return copy( $this->tmpResultFile, $filename );
	}
	
	
	public function outputToBrowser( $filename=false ) {
		if( !$this->tmpResultFile || !file_exists($this->tmpResultFile) ) {
			return false;
		}
		
		header( 'Content-Type: application/vnd.oasis.opendocument.text');
		header( 'Content-Length: '. filesize( $this->tmpResultFile ) );
		
		if( $filename ) {
			header( 'Content-Disposition: attachment; filename="'. $filename .'"' );
		}

		readfile( $this->tmpResultFile );
		
		
		return true;
	}
	
	
	
	
	
	protected function normalizeTextNodes( $xmlElement ) {
		$lastNode = false;
		$removeNodeList = array();
		
		$nodeList = $this->toArray( $xmlElement->getElementsByTagNameNS( self::NS_TEXT, '*') );
		foreach( $nodeList as $node ) {
			if( !$node || !is_object($node) ) {
				continue;
			}
			$this->normalizeChildNodes( $node, $lastNode, $removeNodeList );
		}
		
		foreach( $removeNodeList as $removeNode ) {
			$removeNode->parentNode->removeChild( $removeNode );
		}
	}



	protected function normalizeChildNodes( $element, &$lastNode, &$removeNodeList ) {
		if( $element->firstChild ) {
			$node = $element->firstChild;
			do {
				
				$this->normalizeChildNodes( $node, $lastNode, $removeNodeList );
			}
			while( $node = $node->nextSibling );
			
		}
		else {
			// Skip nodes which dont include text (i.e. images)
			if( $element->nodeName != '#text' ) {
				return;
			}
			
			$startChrPos = strrpos( $element->nodeValue, '{' );
			$endChrPos   = strrpos( $element->nodeValue, '}' );
			
			if( $lastNode ) {
				if( $endChrPos !== false ) {
					$currentEndChrPos   = strpos( $element->nodeValue, '}' );
					 
					$lastNode->nodeValue .= substr( $element->nodeValue, 0, $currentEndChrPos+1 );
					
					$element->nodeValue = substr( $element->nodeValue, $currentEndChrPos+1 );
					
					// Check if there is another starting placeholder without end tag
					$startChrPos = strrpos( $element->nodeValue, '{' );
					$endChrPos   = strrpos( $element->nodeValue, '}' );
					
					if( $startChrPos !== false && $startChrPos > $endChrPos ) {
						$lastNode = $element;
					}
					else {
						$lastNode = false;
					}
					
				}
				else {
					$lastNode->nodeValue .= $element->nodeValue;
					$removeNodeList[] = $element;
				}
				
			}
			elseif( $startChrPos !== false && ($endChrPos === false || $startChrPos > $endChrPos) ) {
				$lastNode = $element;
			}
			
		}
	}
	
	
	
	
	protected function parseXmlElement( $xmlElement, $replaceCurrent=false, $replaceCurrentValues=false ) {
		$placeholderCount = 0;
		
		// Get all ODT text nodes 
		$nodeList = $this->toArray( $xmlElement->getElementsByTagNameNS( self::NS_TEXT, '*') );
		foreach( $nodeList as $node ) {
			if( !$node || !is_object($node) ) {
				continue;
			}
			
			$replaceVars = array();
			
			$childNodeList = $this->toArray( $node->childNodes );
			
			// Parse the placeholders and prepare the data for them
			foreach( $childNodeList as $element ) {
				if( !$node || !is_object($node) ) {
					continue;
				}
				
				// Skip nodes which dont include text (i.e. images)
				if( $element->nodeName != '#text' ) {
					continue;
				}
				
				
				// Skip empty nodes
				if( !$element->nodeValue ) {
					continue;
				}
				
				// Get all placeholders in current node
				$placeholderList = SodaODTParser::parse( $element->nodeValue );
				if( !$placeholderList ) {
					continue;
				}
				$placeholderCount++;

				foreach( $placeholderList as $placeholder ) {
					if( !is_array( $this->replaceValueList[ $placeholder['name'] ] ) ) {	// single static values
						$replaceVars[ $placeholder['replaceString'] ] = $this->getReplaceValue( $this->replaceValueList[ $placeholder['name'] ], $placeholder, $element );
						
					}
					elseif( $placeholder['name'] == $replaceCurrent ) {	// replace placeholders of table row data lists
						if( isset( $replaceCurrentValues[ $placeholder['subname'] ] ) ) {
							$tmpVal = $this->getReplaceValue( $replaceCurrentValues[ $placeholder['subname'] ], $placeholder, $element );
							
							if( is_a( $tmpVal, 'SodaODTInstruction' ) ) {
								if( $tmpVal->getInstruction() == 'hide_block' ) {
									return false;
								}
								elseif( $tmpVal->getInstruction() == 'show_block' ) {
									// nothing
								}
							} 
							
							$replaceVars[ $placeholder['replaceString'] ] = $tmpVal;
						}
						else {	// just remove invalid data list placeholders 
							$replaceVars[ $placeholder['replaceString'] ] = '';
						}
						
					}
					else {	// handle table row data lists
						if( isset( $this->finishedPlaceholders[ $placeholder['name'] ] ) ) {
							continue;
						}
						
						// Get the row element which should be repeated
						$removeNodeList = array( self::$nsPrefixList[ self::NS_TEXT ] .':soft-page-break');
						$repeatElementList = $this->getParentElementListOfType( $element, $this->repeatableTags, $removeNodeList, $removeNodeList );
						if( !$repeatElementList ) {
							continue;
						}
						
						// Create a new table row for every entry in $this->replaceValueList
						foreach( $this->replaceValueList[ $placeholder['name'] ] as $listReplaceValues ) {
							foreach( $repeatElementList as $tmpKey => $repeatElement ) {
								$newNode = $repeatElement->cloneNode( true );
								$parseInfo = $this->parseXmlElement( $newNode, $placeholder['name'], $listReplaceValues );
								
								if( $parseInfo === 0 ) {
									unset( $repeatElementList[ $tmpKey ] );
								}
								elseif( $parseInfo !== false ) {
									$repeatElementList[0]->parentNode->insertBefore( $newNode, $repeatElementList[0] );
								}
							}
						}
						
						// Remove the row with the plain placeholder tags
						foreach( $repeatElementList as $repeatElement ) {
							$repeatElement->parentNode->removeChild( $repeatElement );
						}
						
						$this->finishedPlaceholders[ $placeholder['name'] ] = true;
					}
				}
			}
			
			
			if( $replaceVars ) {
				foreach( $replaceVars as $tmpKey => $tmpVal ) {
					if( is_a( $tmpVal, 'SodaODTInstruction' ) ) {
						if( $tmpVal->getInstruction() == 'hide' ) {
							$instructionData = $tmpVal->getData();
							$element = $instructionData['element'];

							$matchList = false;
							if( $instructionData['mode'] == 'row' ) {
								$matchList = array( array(self::NS_TABLE, 'table-row') );
							}
							elseif( $instructionData['mode'] == 'table' ) {
								$matchList = array( array(self::NS_TABLE, 'table') );
							}
							elseif( $instructionData['mode'] == 'frame' ) {
								$matchList = array( array(self::NS_DRAW, 'frame') );
							}
							elseif( $instructionData['mode'] == 'paragraph' ) {
								$matchList = array( array(self::NS_TEXT, 'p') );
							}

							if( $matchList ) {							
								$removeElement = $this->getParentElementOfType( $element, $matchList );
								if( $removeElement ) {
									if( $removeElement->parentNode ) {
										$removeElement->parentNode->removeChild( $removeElement );
									}
									else {
										return false;
									}
								}
							}
						}
						
						$replaceVars[ $tmpKey ] = '';
					}
					
					
				}
			}
			
			
			// Do the actual replacement of the placeholders
			if( $replaceVars ) { 
				foreach( $node->childNodes as $element ) {
					if( $element->nodeName != '#text' ) {
						continue;
					}
					$element->nodeValue = strtr( $element->nodeValue, $replaceVars );
				}
			}
			
		}

		return $placeholderCount;
	}



	protected function toArray( $list ) {
		$array = array();
		foreach( $list as $entry ) {
			$array[] = $entry;
		}
		
		return $array;
	}
	
	
	
	protected function getParentElementListOfType( $element, $matchList, $ignoreList=array(), $removeList=array() ) {
		$repeatElement = $this->getParentElementOfType( $element, $matchList );
		if( !$repeatElement ) {
			return false;
		}
		
		
		$elementList = array( $repeatElement );
		while( $repeatElement->nextSibling ) {
			
			if($repeatElement->nextSibling->localName == $repeatElement->localName && $repeatElement->nextSibling->namespaceURI == $repeatElement->namespaceURI) {
				$repeatElement = $repeatElement->nextSibling;
				$elementList[] = $repeatElement;
			}
			elseif( in_array( $repeatElement->nextSibling->nodeName, $removeList ) ) {
				$repeatElement->nextSibling->parentNode->removeChild( $repeatElement->nextSibling );
			}
			elseif( in_array( $repeatElement->nextSibling->nodeName, $ignoreList ) ) {
				$repeatElement = $repeatElement->nextSibling;
			}
			else {
				break;
			}
			
			
		}
		
		return $elementList;
	}
	
	
	
	protected function getParentElementOfType( $element, $matchList ) {
		$parentElement = $element;
		
		while( $parentElement ) {
			foreach( $matchList as $matchTag ) {
				if( $parentElement->localName == $matchTag[1] && $parentElement->namespaceURI == $matchTag[0] ) {
					return $parentElement;
				}
			}
			$parentElement = $parentElement->parentNode;
		}
		
		return false;
	}
	
	

	protected function getReplaceValue( $value, $placeholder, $element ) {
		try {
			if( $value instanceof SodaODTImage ) {
				$value = $this->getReplaceValueImage( $value, $placeholder, $element );
			}
			elseif( $value instanceof SodaODTDate ) {
				$value = $this->getReplaceValueDate( $value, $placeholder, $element );
			}
			else {
				$value = $this->getReplaceValueText( $value, $placeholder, $element );
			}
		}
		catch( Exception $error ) {
			return '';	
		}

		return $value;
	}
	
	
	
	protected function getReplaceValueImage( $value, $placeholder, $element ) {
		$width = $value->getWidth() * self::PIXEL_TO_CM;
		$height = $value->getHeight() * self::PIXEL_TO_CM;
		$value->freeImagick();
		
		// Handle resize functions on the image
		if( $placeholder['functions'] ) {
			foreach( $placeholder['functions'] as $function ) {
				$command = strtolower( $function['name'] );
				$param = floatval( $function['args'][0] );
				
				if( ($command == 'max-width' && $width > $param ) || ($command == 'min-width' && $width < $param ) || ($command == 'width') ) {
				    list( $width, $height ) = $this->resizeImageDimensions( $width, $height, $param, false );
				}
				elseif( ($command == 'max-height' && $height > $param ) || ($command == 'min-height' && $height < $param ) || ($command == 'height') ) {
				    list( $width, $height ) = $this->resizeImageDimensions( $width, $height, false, $param );
				}
			}
		}
		
		$imageNode = $this->getXmlImageNode( $element, $value, $width, $height );
		$this->insertDomNodeBetween( $element, $element->nodeValue, $placeholder['replaceString'], $imageNode );
		
		return '';
	}
	
	
	protected function resizeImageDimensions( $width, $height, $newWidth, $newHeight ) {
	    if( $newWidth ) {
		    $factor = $height/$width;
		    $width = $newWidth;
		    $height = $width * $factor;
	    }
	    else {
    		$factor = $width/$height;
    		$height = $newHeight;
    		$width = $height * $factor;
	    }
		
		return array( $width, $height );
	}
	
	
	
	protected function getReplaceValueDate( $value, $placeholder, $element ) {
		$dateFormat = 'd.m.Y H:i:s';
		
		if( $placeholder['functions'] && $placeholder['functions'][0]['name'] == 'date' ) {
		    $dateFormat = $placeholder['functions'][0]['args'][0];
		}
		
		return $value->getDateStr( $dateFormat );
	}
	
	
	
	protected function getXmlImageNode( $element, $imageObj, $width, $height ) {
		$nsDrawPrefix  = $this->getNsPrefix( self::NS_DRAW );
		$nsTextPrefix  = $this->getNsPrefix( self::NS_TEXT );
		$nsSvgPrefix   = $this->getNsPrefix( self::NS_SVG );
		$nsXlinkPrefix = $this->getNsPrefix( self::NS_XLINK );
		
		$imageTagName = 'phpOdtEmbed'. $this->nextTagNameNr++ ;
		$posX = '0cm';
		$posY = '0cm';
		
		$embeddedImageFile = $this->registerFile( $imageObj, 'Picture/' );
		
		
		$frameNode = $this->xmlDom->createElement( $nsDrawPrefix .':frame' );
		$frameNode->setAttribute( $nsDrawPrefix .':style-name', 'fr1' );
		$frameNode->setAttribute( $nsDrawPrefix .':name', $imageTagName );
		$frameNode->setAttribute( $nsTextPrefix .':anchor-type', 'aschar' );
		$frameNode->setAttribute( $nsSvgPrefix .':x', $posX );
		$frameNode->setAttribute( $nsSvgPrefix .':y', $posY );
		$frameNode->setAttribute( $nsSvgPrefix .':width', $width .'cm' );
		$frameNode->setAttribute( $nsSvgPrefix .':height', $height .'cm' );
		$frameNode->setAttribute( $nsDrawPrefix .':z-index', '0' );
		
		$imageNode = $this->xmlDom->createElement( $nsDrawPrefix .':image');
		$frameNode->appendChild( $imageNode );
		$imageNode->setAttribute( $nsXlinkPrefix .':href', $embeddedImageFile );
		$imageNode->setAttribute( $nsXlinkPrefix .':type', 'simple' );
		$imageNode->setAttribute( $nsXlinkPrefix .':show', 'embed' );
		$imageNode->setAttribute( $nsXlinkPrefix .':actuate', 'onLoad' );
		
		
		return $frameNode;
	}
	
	
	
	protected function getReplaceValueText( $value, $placeholder, $element ) {
		
		// Handle functions/operations to be executed on the value
		if( $placeholder['functions'] ) {
			foreach( $placeholder['functions'] as $function ) {
				
				switch( strtolower( $function['name'] ) ) {
					case 'lower':
						$value = mb_strtolower($value);
						break;
						
					case 'upper':
						$value = mb_strtoupper($value);
						break;
						
					case 'ucfirst':
						$value = ucfirst($value);
						break;
						
					case 'ucwords':
						$value = ucwords($value);
						break;
						
					case 'ceil':
						$value = ceil($value);
						break;
						
					case 'floor':
						$value = floor($value);
						break;
						
					case 'substr':
					    array_unshift( $function['args'], $value );
					    $value = call_user_func_array('mb_substr', $function['args'] );
						break;
						
					case 'format':
					    $value = sprintf( $function['args'][0], $value );
						break;
						
					case 'number_format':
					    array_unshift( $function['args'], $value );
					    $value = call_user_func_array('number_format', $function['args'] );
						break;
						
					case 'replace':
					    $function['args'][] = $value;
					    $value = call_user_func_array('str_replace', $function['args'] );
						break;
						
					case 'hide_block':
					case 'show_block':
						$argMatch = false;
						
						foreach( $function['args'] as $arg ) {
							if( $value == $arg ) {
								$argMatch = true;
								break;
							}
						}
						
						$funcName = strtolower( $function['name'] );
						if( ($argMatch && $funcName == 'hide_block') || (!$argMatch && $funcName == 'show_block') ) {
					    	$value = new SodaODTInstruction('hide_block');
						}
						elseif( !is_a($value, 'SodaODTInstruction') || $value->getInstruction() != 'hide_block') {
							$value = new SodaODTInstruction('show_block');
						}
						break;
						

						
					case 'hide':
					case 'show':
						$argList = $function['args'];
						$mode = strtolower( trim( array_shift( $argList ) ) );
						
						if( $mode != 'row' && $mode != 'table' && $mode != 'frame' && $mode != 'paragraph') {
							continue;
						}
						
						$argMatch = false;
						foreach( $argList as $arg ) {
							if( $value == $arg ) {
								$argMatch = true;
								break;
							}
						}
						
						$displayData = array( 'mode' => $mode, 'element' => $element );
						
						$funcName = strtolower( $function['name'] );
						if( ($argMatch && $funcName == 'hide') || (!$argMatch && $funcName == 'show') ) {
					    	$value = new SodaODTInstruction('hide', $displayData );
						}
						elseif( !is_a($value, 'SodaODTInstruction') || $value->getInstruction() != 'hide') {
							$value = new SodaODTInstruction('show', $displayData );
						}
						break;
				}
			}
		}
		
		if( !is_object($value) ) {
			// Replace new-lines with appropriate xml odt newline
			$newLine = $this->xmlDom->createElement( $this->getNsPrefix( self::NS_TEXT ) .':line-break' );
			$this->insertDomNodeBetween( $element, $value, "\n", $newLine );
		}
		
		return $value;
	}
	
	
	

	protected function insertDomNodeBetween( $element, $value, $placeholder, $insertNode ) {
		$partList = explode( $placeholder, $value );
		$c = count( $partList );
		
		if( $c > 1 ) {
			for( $i=0; $i<$c; $i++ ) {
				$newNode = $element->cloneNode(true);
				$newNode->nodeValue = $partList[ $i ];
				$element->parentNode->insertBefore( $newNode, $element );
				
				if( $i<$c-1 ) {
					$element->parentNode->insertBefore( $insertNode->cloneNode(true), $element );
				}
			}
			
			$element->parentNode->removeChild( $element );
		}
	}
	
	
	protected function getNsPrefix( $ns, $dom=false ) {
		if( !$dom ) {
			$dom = $this->xmlDom;
		}
		
		$nsPrefix  = $dom->lookupPrefix( $ns );
		if( !$nsPrefix ) {
			$nsPrefix = self::$nsPrefixList[ $ns ];
			$dom->setAttributeNS( $ns, 'xmlns:'. $nsPrefix, $ns );
		}
		
		return $nsPrefix;
	}
	
	
	protected function registerFile( $fileObj, $path ) {
		if( substr( $path, -1 ) != '/' ) {
			$path .= '/';
		}
		if( substr( $path, 0, 1 ) == '/' ) {
			$path = substr( $path, 1 );
		}
		
		$md5 = $fileObj->getMD5();
		$hash = $path .'_'. $md5;
		
		if( !isset( $this->embedFileList[ $hash ] ) ) {
			$extension = $this->getFileExtension( $fileObj->getName() );
			if( !$extension ) {
				$extension = 'jpg';
			}
			
			$embeddedName = $path . $md5 .'.'. $extension;
			
			$this->embedFileList[ $hash ] = array( 'fileObj' => $fileObj,
												   'embeddedName' => $embeddedName
												 );
		}
		
		return $this->embedFileList[ $hash ]['embeddedName'];
	}
	
	
	protected function getFileExtension( $filename ) {
		$tmpPos = strrpos( $filename, '.' );
		if( $tmpPos === false ) {
			return false;
		}
		
		return substr( $filename, $tmpPos+1 );
	}
	
	
	protected function embedFiles( $zip ) {
		
		$dom = new DOMDocument();
		$dom->loadXML( $zip->getFromName('META-INF/manifest.xml') );
		$manifest = $dom->firstChild;
		
		$nsPrefix = $this->getNsPrefix( self::NS_MANIFEST, $dom );
		
		foreach( $this->embedFileList as $fileData ) {
			$zip->addFromString( $fileData['embeddedName'], $fileData['fileObj']->getBlob() );
			
			$newNode = $dom->createElement( $nsPrefix .':file-entry' );
			$newNode->setAttribute( $nsPrefix .':media-type', $fileData['fileObj']->getMimeType() );
			$newNode->setAttribute( $nsPrefix .':full-path', $fileData['embeddedName'] );
			$manifest->appendChild( $newNode );
		}
		
		$zip->addFromString( 'META-INF/manifest.xml', $dom->saveXML() );
	}
	
	
	protected function createMetaFile( $zip ) {
		$metaXml = '<?xml version="1.0" encoding="UTF-8"?>' ."\n"
				  .'<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:grddl="http://www.w3.org/2003/g/data-view#" office:version="1.2" grddl:transformation="http://docs.oasis-open.org/office/1.2/xslt/odf2rdf.xsl">'
				  .'<office:meta>'
				  .'<meta:creation-date>'. date('Y-m-dTH:i:s') .'</meta:creation-date>'
				  .'<dc:date>'. date('Y-m-dTH:i:s') .'</dc:date>'
				  .'<meta:generator>Sodatech ODT generator</meta:generator>'
				  .'<meta:document-statistic />'
				 .'</office:meta>'
				 .'</office:document-meta>';
		
		$zip->addFromString( 'meta.xml', $metaXml );
	}
	
}
