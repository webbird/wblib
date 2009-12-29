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

// any string that doesn't have control characters (ASCII 0 - 31) - spaces allowed
define( 'PCRE_STRING'      , '/^[^\x-\x1F]+$/' );

// alphanum (letters and numbers only), no spaces allowed
define( 'PCRE_ALPHANUM'    , '/^[A-Za-z0-9]+$/'  );

// alphanum + underscore
define( 'PCRE_ALPHANUM_EXT', '/^[a-zA-Z0-9_]+$/'  );

// integer
define( 'PCRE_INT'         , "/^[0-9]+$/"      );

// CSS style definition
define( 'PCRE_STYLE'       , "/^[a-zA-Z0-9\:\;\s\#\-]*$/" );

// Email address (anchored; no consecutive dots)
// excluding addresses with consecutive dots such as john@aol...com
// Does not match email addresses using an IP address instead of a domain name.
// Does not match email addresses on new-fangled top-level domains with more than 4 letters such as .museum.
define( 'PCRE_EMAIL'       , '/^[A-Za-z0-9._%-]+@(?:[A-Za-z0-9-]+\.)+[A-Za-z]{2,4}$/' );

// Checking password complexity
// This regular expression will tests if the input consists of 6 or more letters, digits, underscores and hyphens.
// The input must contain at least one upper case letter, one lower case letter and one digit.
define( 'PCRE_PASSWORD'    , "/^\A(?=[\.,;\:&\"\'\?\!\(\)a-zA-Z0-9]*?[A-Z])(?=[\.,;\:&\"\'\?\!\(\)a-zA-Z0-9]*?[a-z])(?=[\.,;\:&\"\'\?\!\(\)a-zA-Z0-9]*?[0-9])\S{6,}\z$/" );

// URL
$pattern = "#(?:http://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)(?:/(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;:@&=])*)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))*)(?:\?(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))?)?)|(?:ftp://(?:(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?&=])*)(?::(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?&=])*))?@)?(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?))(?:/(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&=])*)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&=])*))*)(?:;type=[AIDaid])?)?)|(?:news:(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;/?:&=])+@(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3})))|(?:[a-zA-Z](?:[a-zA-Z\d]|[_.+-])*)|\*))|(?:nntp://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)/(?:[a-zA-Z](?:[a-zA-Z\d]|[_.+-])*)(?:/(?:\d+))?)|(?:telnet://(?:(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?&=])*)(?::(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?&=])*))?@)?(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?))/?)|(?:gopher://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)(?:/(?:[a-zA-Z\d$\-_.+!*'(),;/?:@&=]|(?:%[a-fA-F\d]{2}))(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),;/?:@&=]|(?:%[a-fA-F\d]{2}))*)(?:%09(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;:@&=])*)(?:%09(?:(?:[a-zA-Z\d$\-_.+!*'(),;/?:@&=]|(?:%[a-fA-F\d]{2}))*))?)?)?)?)|(?:wais://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)/(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*)(?:(?:/(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*)/(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*))|\?(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))?)|(?:mailto:(?:(?:[a-zA-Z\d$\-_.+!*'(),;/?:@&=]|(?:%[a-fA-F\d]{2}))+))|(?:file://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))|localhost)?/(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&=])*)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&=])*))*))|(?:prospero://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)/(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&=])*)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&=])*))*)(?:(?:;(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&])*)=(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[?:@&])*)))*)|(?:ldap://(?:(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?))?/(?:(?:(?:(?:(?:(?:(?:[a-zA-Z\d]|%(?:3\d|[46][a-fA-F\d]|[57][Aa\d]))|(?:%20))+|(?:OID|oid)\.(?:(?:\d+)(?:\.(?:\d+))*))(?:(?:%0[Aa])?(?:%20)*)=(?:(?:%0[Aa])?(?:%20)*))?(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*))(?:(?:(?:%0[Aa])?(?:%20)*)\+(?:(?:%0[Aa])?(?:%20)*)(?:(?:(?:(?:(?:[a-zA-Z\d]|%(?:3\d|[46][a-fA-F\d]|[57][Aa\d]))|(?:%20))+|(?:OID|oid)\.(?:(?:\d+)(?:\.(?:\d+))*))(?:(?:%0[Aa])?(?:%20)*)=(?:(?:%0[Aa])?(?:%20)*))?(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*)))*)(?:(?:(?:(?:%0[Aa])?(?:%20)*)(?:[;,])(?:(?:%0[Aa])?(?:%20)*))(?:(?:(?:(?:(?:(?:[a-zA-Z\d]|%(?:3\d|[46][a-fA-F\d]|[57][Aa\d]))|(?:%20))+|(?:OID|oid)\.(?:(?:\d+)(?:\.(?:\d+))*))(?:(?:%0[Aa])?(?:%20)*)=(?:(?:%0[Aa])?(?:%20)*))?(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*))(?:(?:(?:%0[Aa])?(?:%20)*)\+(?:(?:%0[Aa])?(?:%20)*)(?:(?:(?:(?:(?:[a-zA-Z\d]|%(?:3\d|[46][a-fA-F\d]|[57][Aa\d]))|(?:%20))+|(?:OID|oid)\.(?:(?:\d+)(?:\.(?:\d+))*))(?:(?:%0[Aa])?(?:%20)*)=(?:(?:%0[Aa])?(?:%20)*))?(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))*)))*))*(?:(?:(?:%0[Aa])?(?:%20)*)(?:[;,])(?:(?:%0[Aa])?(?:%20)*))?)(?:\?(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+)(?:,(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+))*)?)(?:\?(?:base|one|sub)(?:\?(?:((?:[a-zA-Z\d$\-_.+!*'(),;/?:@&=]|(?:%[a-fA-F\d]{2}))+)))?)?)?)|(?:(?:z39\.50[rs])://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+)(?:\+(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+))*(?:\?(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+))?)?(?:;esn=(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+))?(?:;rs=(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+)(?:\+(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))+))*)?))|(?:cid:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?:@&=])*))|(?:mid:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?:@&=])*)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[;?:@&=])*))?)|(?:vemmi://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)(?:/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[/?:@&=])*)(?:(?:;(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[/?:@&])*)=(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[/?:@&])*))*))?)|(?:imap://(?:(?:(?:(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~])+)(?:(?:;[Aa][Uu][Tt][Hh]=(?:\*|(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~])+))))?)|(?:(?:;[Aa][Uu][Tt][Hh]=(?:\*|(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~])+)))(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~])+))?))@)?(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?))/(?:(?:(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~:@/])+)?;[Tt][Yy][Pp][Ee]=(?:[Ll](?:[Ii][Ss][Tt]|[Ss][Uu][Bb])))|(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~:@/])+)(?:\?(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~:@/])+))?(?:(?:;[Uu][Ii][Dd][Vv][Aa][Ll][Ii][Dd][Ii][Tt][Yy]=(?:[1-9]\d*)))?)|(?:(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~:@/])+)(?:(?:;[Uu][Ii][Dd][Vv][Aa][Ll][Ii][Dd][Ii][Tt][Yy]=(?:[1-9]\d*)))?(?:/;[Uu][Ii][Dd]=(?:[1-9]\d*))(?:(?:/;[Ss][Ee][Cc][Tt][Ii][Oo][Nn]=(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),]|(?:%[a-fA-F\d]{2}))|[&=~:@/])+)))?)))?)|(?:nfs:(?:(?://(?:(?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*(?:[a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(?:\d+))?)(?:(?:/(?:(?:(?:(?:(?:[a-zA-Z\d\$\-_.!~*'(),])|(?:%[a-fA-F\d]{2})|[:@&=+])*)(?:/(?:(?:(?:[a-zA-Z\d\$\-_.!~*'(),])|(?:%[a-fA-F\d]{2})|[:@&=+])*))*)?)))?)|(?:/(?:(?:(?:(?:(?:[a-zA-Z\d\$\-_.!~*'(),])|(?:%[a-fA-F\d]{2})|[:@&=+])*)(?:/(?:(?:(?:[a-zA-Z\d\$\-_.!~*'(),])|(?:%[a-fA-F\d]{2})|[:@&=+])*))*)?))|(?:(?:(?:(?:(?:[a-zA-Z\d\$\-_.!~*'(),])|(?:%[a-fA-F\d]{2})|[:@&=+])*)(?:/(?:(?:(?:[a-zA-Z\d\$\-_.!~*'(),])|(?:%[a-fA-F\d]{2})|[:@&=+])*))*)?)))#";
define( 'PCRE_URI'         , $pattern );

// plain text
define( 'PCRE_PLAIN'       , '/^.*?$/esx' );

#'/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i' );


require_once dirname( __FILE__ ).'/class.wbBase.php';

class wbValidate extends wbBase {

    private static $_tainted  = array();
    private static $_server   = array();
    private static $_errors   = array();
    private        $_valid    = array();

    // ----- Debugging -----
    protected      $debugLevel      = KLOGGER::OFF;

    /**
     * constructor
     **/
    function __construct () {
        parent::__construct();
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

        // read request data into $tainted array
        self::$_tainted = array_merge(
                            $_REQUEST,
                            $_GET,
                            $_POST
                          );
        self::$_server  = $_SERVER;

        if ( ! $this->isValidUri( $_SERVER['SCRIPT_NAME'] ) ) {
            $this->selfURL( 'http://'
                          . $_SERVER['HTTP_HOST']
                          . $_SERVER['SCRIPT_NAME']
                          );
        }
        else {
            $this->selfURL( $_SERVER['SCRIPT_NAME'] );
        }

        $this->log->LogDebug( 'FORM DATA:', self::$_tainted );

        // delete request data from global arrays
        if ( ! $weak ) {
            unset( $_REQUEST );
            unset( $_POST    );
            unset( $_GET     );
            unset( $_SERVER  );
            // from this point, request data can ONLY
            // be accessed using this class!
        }
        
    }   // end function import()
    
    /**
     *
     *
     *
     *
     **/
    public function getValid ( $varname, $options = array() ) {
    
        $this->log->LogDebug( 'var ['.$varname.']', $options );
    
        // value already validated?
        if ( isset( $this->_valid[ $varname ] ) ) {
            $this->log->LogDebug( 'returning already validated var '.$varname );
            return $this->_valid[ $varname ];
        }
        
        // value available?
        if ( ! isset( self::$_tainted[ $varname ] ) ) {
            $this->log->LogDebug( 'no data found for var '.$varname );
            return isset( $options['default'] ) ? $options['default'] : NULL;
        }
    
        // name of accessor method available?
        if ( isset( $options['type'] ) ) {

            $func_name = 'isValid'.ucfirst($options['type']);

            if ( method_exists( $this, $func_name ) ) {
        
                $this->log->LogDebug( 'checking var ['.$varname. '] with method ['.$func_name.']' );

                if ( $this->$func_name( self::$_tainted[ $varname ], $options ) ) {
                
                    $this->log->LogDebug( 'found valid value for var ['.$varname.']' );
                
                    // found valid value
                    $self->_valid[ $varname ] = self::$_tainted[ $varname ];
                    unset( self::$_tainted[ $varname ] );
                    
                    if ( isset( $options['stripped'] ) ) {
                        $self->_valid[ $varname ] = $this->__strip( $self->_valid[ $varname ] );
                    }
                    return $self->_valid[ $varname ];

                }
                else {
                    return isset( $options['default'] ) ? $options['default'] : NULL;
                }
                
            }

        }   // if ( isset( $options['type'] ) )
        
        // constant name available?
        $constant = 'PCRE_STRING';
        if ( isset( $options['constant'] ) ) {
            $constant = $options['constant'];
        }
        $this->log->LogDebug( 'checking var '.$varname.' with constant '.$constant );

        if ( self::staticValidate( $constant, self::$_tainted[$varname] ) ) {
            $self->_valid[ $varname ] =  self::$_tainted[ $varname ];
            unset(  self::$_tainted[ $varname ] );
            if ( isset( $options['stripped'] ) ) {
                $self->_valid[ $varname ] = $this->__strip( $self->_valid[ $varname ] );
            }
            return $self->_valid[ $varname ];
        }
        else {
            return isset( $options['default'] ) ? $options['default'] : NULL;
        }

    }   // end function getValid()
    
    /**
     *
     *
     *
     *
     **/
    public function get( $varname, $constant = NULL, $default = NULL, $stripped = false ) {
    
        $this->log->LogDebug( 'NOTE: This function is marked as deprecated! Please use getValid() instead!' );
        
        return $this->getValid (
                   $varname,
                   array(
                       'constant' => $constant,
                       'default'  => $default,
                       'stripped' => $stripped
                   )
               );

    }   // end function get()
    
    /**
     * OOP wrapper to staticValidate
     *
     * @param   string   $constant   - constant name
     * @param   string   $varname    - variable to validate
     * @param   integer  $min_length - optional, Default: 0
     * @param   integer  $max_length - optoinal, Default: unlimited
     *
     **/
    public function validate ( $constant, $varname, $options = array() ) {
        // value already validated?
        if ( isset( $this->_valid[ $varname ] ) ) {
            $this->log->LogDebug( 'returning already validated var ['.$varname.']' );
            return $this->_valid[ $varname ];
        }
        if ( ! isset( self::$_tainted[ $varname ] ) ) {
            $this->log->LogDebug( 'no value for var ['.$varname.']' );
            return NULL;
        }
        return self::staticValidate( $constant, self::$_tainted[ $varname ], $options );
    }

    /**
     * validate a value using a PCRE_* constant (using preg_match())
     *
     * @param   string   $constant   - constant name
     * @param   string   $value      - value to validate
     * @param   integer  $min_length - optional, Default: 0
     * @param   integer  $max_length - optoinal, Default: unlimited
     *
     **/
    static function staticValidate ( $constant, $value, $options = array() ) {

        // check length
        if (
               isset( $options['min_length'] )
               &&
               $options['min_length'] > 0
               &&
               strlen( $value ) < $options['min_length']
        ) {
            return 0;
        }
        
        if (
               isset( $options['max_length'] )
               &&
               $options['max_length'] > 0
               &&
               strlen( $value ) > $options['max_length']
        ) {
            return 0;
        }

        // check pattern; returns 0 (false) or 1 (true)
        return preg_match( constant( $constant ), $value );

    }   // end function staticValidate ()
    

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
     *
     *
     *
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
    Accessor functions for easier use
*******************************************************************************/
    public function isValidString ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_STRING', $value, $options );
    }
    public function isValidAlphanum ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_ALPHANUM', $value, $options );
    }
    public function isValidAlphanum_ext ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_ALPHANUM_EXT', $value, $options );
    }
    public function isValidInteger ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_INT', $value, $options );
    }
    public function isValidStyle ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_STYLE', $value, $options );
    }
    public function isValidEmail ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_EMAIL', $value, $options );
    }
    public function isValidPassword ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_PASSWORD', $value, $options );
    }
    public function isValidUri ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_URI', $value, $options );
    }
    public function isValidPlain ( $value, $options = array() ) {
        return self::staticValidate( 'PCRE_PLAIN', $value, $options );
    }
    
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