<?php

/**

  (Form) Validation helper class

  Copyright (C) 2009, Bianka Martinovic
  Contact me: blackbird(at)webbird.de, http://www.webbird.de/

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or (at
  your option) any later version.

  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
  General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, see <http://www.gnu.org/licenses/>.

**/

require_once dirname( __FILE__ ).'/class.wbBase.php';

class wbValidate extends wbBase {

    // ----- Debugging -----
    protected      $debugLevel      = KLOGGER::OFF;
    #protected      $debugLevel      = KLOGGER::DEBUG;

    private static $_tainted  = array();
    private static $_server   = array();
    private static $_valid    = array();
    private static $_initialized = false;
    protected      $_errors   = array();
    protected      $_config
        = array(
              'debug'           => false,
          );

    /**
     * constructor
     **/
    function __construct ( $options = array() ) {
        parent::__construct( $options );
        $this->__init();
        $this->import();
    }   // end function __construct()

    /**
     * read the form data and unset dangerous global vars
     *
     * @access public
     * @param  boolean  $weak - do not unset globals
     *
     **/
    public function import( $weak = true ) {
    
        // initialize only once (singleton)
        #if ( self::$_initialized ) {
        #    $this->log()->LogDebug( 'already initialized' );
        #    return;
        #}
        
        $this->log()->LogDebug( 'getting form data' );

        // read request data into $tainted array
        self::$_tainted = array_merge(
                            $_REQUEST,
                            $_GET,
                            $_POST
                          );
        self::$_server  = $_SERVER;
        
        if ( is_array( $_SERVER ) ) {

            if ( ! $this->isValidUri( $_SERVER['SCRIPT_NAME'] ) ) {
                $this->selfURL( 'http://'
                              . ( isset( $_SERVER['HTTP_HOST'] )   ? $_SERVER['HTTP_HOST']   : 'localhost' )
                              . ( isset( $_SERVER['SCRIPT_NAME'] ) ? $_SERVER['SCRIPT_NAME'] : ''          )
                              );
            }
            else {
                $this->selfURL( $_SERVER['SCRIPT_NAME'] );
            }
            
        }

        $this->log()->LogDebug( 'FORM DATA:', self::$_tainted );

        // delete request data from global arrays
        if ( ! $weak ) {
            unset( $_REQUEST );
            unset( $_POST    );
            unset( $_GET     );
            unset( $_SERVER  );
            // from this point, request data can ONLY
            // be accessed using this class!
        }
        
        self::$_initialized = true;
        
    }   // end function import()
    
    /**
     *
     *
     *
     *
     **/
    public function delete( $varname ) {
    
        $this->log()->LogDebug( 'deleting var ['.$varname.']' );

        // value already validated?
        if ( isset( self::$_valid[ $varname ] ) ) {
            unset( self::$_valid[ $varname ] );
        }
        
        // value available?
        if ( isset( self::$_tainted[ $varname ] ) ) {
            unset( self::$_tainted[ $varname ] );
        }
    
    }   // end function delete()
    
    /**
     * Retrieve validated params by a given prefix
     *
     * Note: Validates all params as string!
     *
     * @access public
     * @param  string $prefix  - prefix to search for
     * @param  array  $options - more options
     * @return array
     *
     * Options:
     *     remove_prefix
     *        remove the given prefix from the var name in result array
     *        boolean; default true
     *
     **/
    public function by_prefix( $prefix, $options = array() ) {

        $remove_prefix = true;
        if ( isset( $options['remove_prefix'] ) && $options['remove_prefix'] === false ) {
            $remove_prefix = false;
        }
        
        $values = array();
        
        // search vars in tainted array
        foreach ( self::$_tainted as $var => $ignore ) {
            if ( preg_match( "#^$prefix#i", $var ) ) {
                $value = $this->param( $var );
                if( $value ) {
                    $var = ( $remove_prefix ? str_ireplace( $prefix, '', $var ) : $var );
                    $values[$var] = $value;
                }
            }
        }
        
        // search vars in validated array
        foreach ( self::$_valid as $var => $value ) {
            if ( preg_match( "#^$prefix#i", $var ) ) {
                $var = ( $remove_prefix ? str_ireplace( $prefix, '', $var ) : $var );
                $values[$var] = $value;
            }
        }

        return $values;
        
    }   // end function by_prefix()
    
    /**
     * retrieve validated form param
     *
     * @access public
     * @param  string   $varname  - form field to retrieve
     * @param  string   $constant - predefined RegExp to use
     * @param  array    $options  - more options (like default value)
     *
     **/
    public function param ( $varname, $constant = 'PCRE_STRING', $options = array() ) {

        $this->log()->LogDebug( 'var ['.$varname.']', $options );
        
        if ( empty ( $constant ) ) {
            $constant = 'PCRE_STRING';
        }

        // value already validated?
        if ( isset( self::$_valid[ $varname ] ) ) {
            $this->log()->LogDebug( 'returning already validated var '.$varname, self::$_valid[ $varname ] );
            return self::$_valid[ $varname ];
        }

        // value available?
        if ( ! isset( self::$_tainted[ $varname ] ) ) {
            $this->log()->LogDebug( 'no data found for var '.$varname );
            return isset( $options['default'] ) ? $options['default'] : NULL;
        }

        // so we have a tainted value; let's check it
        $this->log()->LogDebug( 'checking var '.$varname.' with constant '.$constant );
        
        if ( $this->validate( $constant, self::$_tainted[ $varname ], $options ) ) {

            // cache validated value
            self::$_valid[ $varname ] = self::$_tainted[ $varname ];

            // delete tainted value
            unset( self::$_tainted[ $varname ] );

            // strip?
            if ( isset( $options['stripped'] ) ) {
                self::$_valid[ $varname ] = $this->__strip( self::$_valid[ $varname ] );
            }
            return self::$_valid[ $varname ];

        }
        else {
            $this->_errors[ $varname ] = 'invalid';
            return isset( $options['default'] ) ? $options['default'] : NULL;
        }

    }   // end function param()
    

    /**
     *
     *
     *
     *
     **/
    public function isValid( $varname, $constant = 'PCRE_STRING', $options = array() ) {
        // already checked and has errors
        if ( isset( $this->_errors[ $varname ] ) ) {
            return false;
        }
        // already checked and valid
        if ( isset( self::$_valid[ $varname ] ) ) {
            return true;
        }
        // not checked
        if ( isset( self::$_tainted[ $varname ] ) ) {
            $var = $this->param( $varname, $constant, $options );
            if ( $var ) {
                return true;
            }
            return false;
        }
        return false;
    }   // end function isValid()
    
    /**
     *
     *
     *
     *
     **/
    public function getValid( $constant, $value, $options = array() ) {
        if( $this->validate( $constant, $value, $options ) ) {
            return $value;
        }
        return NULL;
    }   // end function getValid()
    
    /**
     * 
     *
     * @param   string   $constant   - constant name
     * @param   string   $varname    - variable to validate
     *
     **/
    public function validate( $constant, $value, $options = array() ) {

        if ( empty( $value ) && strlen( $value ) == 0 ) {
            $callstack = debug_backtrace();
            $this->log()->LogDebug(
                'no value to check, caller: ',
                $callstack[1]
            );
        }

        $this->log()->LogDebug(
            'validating value (see below) with constant ['.$constant.']:',
            $value
        );

        if ( ! is_array( $value ) ) {

            // check length
            if (
                   isset( $options['min_length'] )
                   &&
                   $options['min_length'] > 0
                   &&
                   strlen( $value ) < $options['min_length']
            ) {
                $this->log()->LogDebug( 'invalid (too short)' );
                return false;
            }

            if (
                   isset( $options['max_length'] )
                   &&
                   $options['max_length'] > 0
                   &&
                   strlen( $value ) > $options['max_length']
            ) {
                $this->log()->LogDebug( 'invalid (too long)' );
                return false;
            }

if ( ! is_scalar($constant) ) {
echo "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
print_r( $constant );
print_r( debug_backtrace() );
echo "</textarea>";
}
            // check pattern; returns 0 (false) or 1 (true)
            if ( preg_match( constant( $constant ), $value ) ) {
                $this->log()->LogDebug( 'valid value:', $value );
                return true;
            }
            else {
                $this->log()->LogDebug( 'invalid (doesn\'t match regexp)' );
                return false;
            }

        }

        // check array
        else {
            $this->log()->LogDebug( 'validating array' );
            $valid = array();
            foreach ( $value as $item ) {
                $this->log()->LogDebug( 'checking value ['.$item.']' );
                $is_valid = preg_match( constant( $constant ), $item );
                if ( $is_valid ) {
                    $valid[] = $is_valid;
                }
            }
            if ( count( $valid ) === count( $value ) ) {
                return true;
            }
            else {
                return false;
            }
        }

    }   // end function validate()

/*******************************************************************************
    Easy date and time checking
*******************************************************************************/

    /**
     *
     *
     *
     *
     **/
    public function getValidYear( $varname = 'year', $options = array() ) {
        $year = $this->getValid(
                    $varname,
                    array_merge(
                           array(
                               'type'       => 'integer',
                               'min_length' => 2,
                               'max_length' => 4,
                               'default'    => date('Y')
                           ),
                           $options
                       )
                );
        return strftime("%Y", strtotime("01/01/$year"));
        
    }   // end function getValidYear()
    
    /**
     * validate month
     *
     * If no valid form data is found, the current month is returned by default
     *
     * @access public
     *
     * @param  string  $varname - form var to validate (default: 'month')
     * @param  array   $options
     *
     * @return integer $month
     *
     **/
    public function getValidMonth( $varname = 'month', $options = array() ) {
    
        $month = $this->getValid(
                     $varname,
                     array_merge(
                        array(
                            'type'       => 'integer',
                            'max_length' => 2,
                        ),
                        $options
                     )
                 );

        if ( $month && $month > 0 && $month <= 12 ) {
            return strftime("%m", strtotime("$month/01/2009"));
        }

        //return date( "m" );
        return NULL;

    }   // end function getValidMonth()
    
    /**
     *
     *
     *
     *
     **/
    public function getValidDay( $varname = 'day', $options = array() ) {
    
        $day = $this->getValid(
                     $varname,
                     array_merge(
                        array(
                            'type'       => 'integer',
                            'max_length' => 2,
                        ),
                        $options
                     )
                 );
                 
        if ( $day && $day > 0 && $day <= 31 ) {
            return strftime("%d", strtotime("01/$day/2009"));
        }
        
        //return date( "d" );
        return NULL;
        
    }   // end function getValidDay()

    /**
     *
     *
     *
     *
     **/
    public function getValidQuarter( $varname = 'quarter', $options = array() ) {

        $quarter = $this->getValid(
                       $varname,
                       array_merge(
                           array(
                               'type'       => 'integer',
                               'max_length' => 1,
                           ),
                           $options
                       )
                   );

        if ( $quarter && $quarter > 0 && $quarter <= 4 ) {
            return $quarter;
        }

        return NULL;
    }   // end function getValidQuarter()

    /**
     *
     *
     *
     *
     **/
    public function getValidWeeknumber( $varname = 'week', $options = array() ) {

        $week = $this->getValid(
                       $varname,
                       array_merge(
                           array(
                               'type'       => 'integer',
                               'max_length' => 2,
                           ),
                           $options
                       )
                   );

        if ( $week && $week > 0 && $week <= 53 ) {
            return $week;
        }

        return NULL;
        
    }   // end function getValidWeeknumber()


/*******************************************************************************
    Convenience methods
*******************************************************************************/

    /**
    * check and get value; shortcut to
    * ->param( 'var', <constant> );
    **/
    public function getString ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_STRING', $options );
    }
    public function getAlphanum ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_ALPHANUM', $options );
    }
    public function getAlphanum_ext ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_ALPHANUM_EXT', $options );
    }
    public function getInteger ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_INTEGER', $options );
    }
    public function getStyle ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_STYLE', $options );
    }
    public function getEmail ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_EMAIL', $options );
    }
    public function getPassword ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_PASSWORD', $options );
    }
    public function getPUri ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_URI', $options );
    }
    public function getPlain ( $varname, $options = array() ) {
        return $this->param( $varname, 'PCRE_PLAIN', $options );
    }
    public function getBoolean( $varname, $options = array() ) {
        if ( $this->param( $varname, 'PCRE_PLAIN', $options ) ) {
            return true;
        }
        return false;
    }

    /**
     * check a given value to be valid; returns valid value if any
     **/
    public function isValidString ( $value, $options = array() ) {
        return $this->validate( 'PCRE_STRING', $value, $options );
    }
    public function isValidAlphanum ( $value, $options = array() ) {
        return $this->validate( 'PCRE_ALPHANUM', $value, $options );
    }
    public function isValidAlphanum_ext ( $value, $options = array() ) {
        return $this->validate( 'PCRE_ALPHANUM_EXT', $value, $options );
    }
    public function isValidInteger ( $value, $options = array() ) {
        return $this->validate( 'PCRE_INT', $value, $options );
    }
    public function isValidStyle ( $value, $options = array() ) {
        return $this->validate( 'PCRE_STYLE', $value, $options );
    }
    public function isValidEmail ( $value, $options = array() ) {
        return $this->validate( 'PCRE_EMAIL', $value, $options );
    }
    public function isValidPassword ( $value, $options = array() ) {
        return $this->validate( 'PCRE_PASSWORD', $value, $options );
    }
    public function isValidUri ( $value, $options = array() ) {
        return $this->validate( 'PCRE_URI', $value, $options );
    }
    public function isValidPlain ( $value, $options = array() ) {
        return $this->validate( 'PCRE_PLAIN', $value, $options );
    }
    public function isValidMime( $value, $options = array() ) {
        return $this->validate( 'PCRE_MIME', $value, $options );
    }
    
    /**
     * define regexp
     *
     * @access private
     * @return void
     *
     **/
    private function __init () {
    
        if ( defined( '__WBV_INIT__' ) ) {
            return;
        }
    
        include dirname(__FILE__).'/wbValidate/inc.regexp.php';

        define( '__WBV_INIT__', true );

    }   // end sub __init()
    
    /**
     * strip potentially dangerous items from a string
     *
     * @access private
     * @param  string   $filter   string to filter
     *
     **/
    private function __strip ( $filter ) {

        // realign javascript href to onclick
        $filter = preg_replace("/href=(['\"]).*?javascript:(.*)?\\1/i", "onclick=' $2 '", $filter);

        //remove javascript from tags
        while( preg_match("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $filter) ) {
            $filter = preg_replace("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $filter);
        }

        // dump expressions from contibuted content
        if(0) $filter = preg_replace("/:expression\(.*?((?>[^(.*?)]+)|(?R)).*?\)\)/i", "", $filter);

        while( preg_match("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $filter) ) {
            $filter = preg_replace("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $filter);
        }

        // remove all on* events
        while( preg_match("/<(.*)?\s?on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", $filter) ) {
           $filter = preg_replace("/<(.*)?\s?on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", "<$1$3>", $filter);
        }

        // remove <script> tags
        $filter = preg_replace( "/<\/?script.*?>/i", '', $filter );

        return $filter;

    }   // end function __strip ()

}