<?php 



class SodaODTParser {
    
    static protected $parseStr      = '';
    static protected $strLength     = 0;
    static protected $lastPos       = 0;
    static protected $snippetEndPos = 0;
    
    
    
    static public function parse( $str ) {
        self::$parseStr = $str;
        self::$strLength = strlen( $str );
        
        $snippets = array();
        
        do {
            self::$lastPos = strpos( self::$parseStr, '{', self::$lastPos );
            if( self::$lastPos === false ) {
                break;
            }
            self::$lastPos++;
            
            $snippetStart = self::$lastPos-1;
            
            $tmpSnippet = self::parseSnippet();
            if( $tmpSnippet ) {
                $tmpSnippet['replaceString'] = substr( self::$parseStr, $snippetStart, self::$lastPos-$snippetStart+1 );
                $snippets[] = $tmpSnippet;
            }
            
        } while( self::$lastPos !== false );
        
        return $snippets;
    }
    
    
    protected function parseSnippet() {
        self::$snippetEndPos = strpos( self::$parseStr, '}', self::$lastPos );
        if( self::$snippetEndPos === false ) {
            return false;
        }
        
        $snippet = array( 'name' => false,
                          'subname' => false,
                          'functions' => array(),
                          'replaceString' => false
                        );
                        
        // Parse variable name and the list entry name if it exists
        preg_match('/^\s*([A-Za-z0-9_-]+)(?:\.([A-Za-z0-9_-]+))?\s*/', substr( self::$parseStr, self::$lastPos, self::$snippetEndPos ), $match );
        if( !$match ) {
            return false;
        }
        self::$lastPos += strlen( $match[0] );
        
        $snippet['name'] = $match[1];
        if( isset( $match[2] ) && $match[2] ) {
            $snippet['subname'] = $match[2];
        }
        
        
        if( self::$parseStr[ self::$lastPos ] == '}' ) {
            return $snippet;
        }
        elseif( self::$parseStr[ self::$lastPos ] != ';' ) {
            return false;
        }
        
        
        // parse functions
        while( self::$parseStr[ self::$lastPos ] == ';' ) {
            $tmpFunction = self::parseFunction();
            if( !$tmpFunction ) {
                return false;
            }
            
            $snippet['functions'][] = $tmpFunction;
        }
    
        
        if( self::$parseStr[ self::$lastPos ] != '}' ) {
            return false;
        }
        
        
        return $snippet;
    }
    
    
    protected function parseFunction() {
        $function = array( 'name' => false, 
                           'args' => array() 
                         );
                         
        // Parse function name
        preg_match('/^;\s*([A-Za-z0-9_-]+)\s*/', substr( self::$parseStr, self::$lastPos, self::$snippetEndPos ), $match );
        if( !$match ) {
            return false;
        }
        self::$lastPos += strlen( $match[0] );

        $function['name'] = $match[1];
        
        if( self::$parseStr[ self::$lastPos ] == ';' ) {
            return $function;
        }
        elseif( self::$parseStr[ self::$lastPos ] != '(' ) {
            return false;
        }
        
        
        // Parse function arguments
        do {
            self::$lastPos++;
            
            $arg = null;
            preg_match(  '/\s*(?|'
            			.'("){1}((?:[^"\\\\]|\\\\.)*)"'
            			.'|(\'){1}((?:[^\'\\\\]|\\\\.)*)\''
            			.'|(”){1}((?:[^”\\\\]|\\\\.)*)”'
            			.'|([A-Za-z0-9._-]+)'
            			.')\s*/', substr( self::$parseStr, self::$lastPos ), $match );
            if( !$match ) {
                return false;
            }
            
            if( isset($match[2]) ) {
                $arg = $match[2];
                $arg = str_replace( "\\". $match[1], $match[1], $arg );
            }
            else {
                $arg = $match[1];
            }
            self::$lastPos += strlen( $match[0] );
            
            if( self::$lastPos >= self::$snippetEndPos ) {
                self::$snippetEndPos = strpos( self::$parseStr, '}', self::$lastPos );
            }
            
            $function['args'][] = $arg;
        } while( self::$parseStr[ self::$lastPos ] == ',' );
        
        if( !preg_match('/\s*\)\s*/', substr( self::$parseStr, self::$lastPos, self::$snippetEndPos ), $match ) ) {
            return false;
        }
        self::$lastPos += strlen( $match[0] );
        
        return $function;
    }
    
    
    
}


