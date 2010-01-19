<?php

/**

  Internationalization class

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

class wbI18n extends wbBase {

    // ----- Debugging -----
    protected      $debugLevel    = KLOGGER::OFF;

    // default language file path; override with setPath()
    private        $_langPath     = '/languages';
    
    // array to store language strings
    private static $_lang         = array();
    
    // default language
    private static $_current_lang = 'EN';

    /**
     * constructor
     **/
    public function __construct( $lang ) {
        parent::__construct();
        self::$_current_lang = $lang;
        $this->init();
    }   // end function __construct()

		public function __destruct () {} // end function __destruct()
		
		/**
		 *
		 *
		 *
		 *
		 **/
		public function init( $var = NULL ) {
		
        $this->log()->LogDebug( 'init()' );

        $caller = debug_backtrace();
        
        if ( file_exists( dirname($caller[1]['file']).'/languages' ) ) {
            $this->_langPath = dirname($caller[1]['file']).'/languages';
        }
        elseif ( file_exists( dirname($caller[1]['file']).'/../languages' ) ) {
            $this->_langPath = dirname($caller[1]['file']).'/../languages';
        }

        $lang_files
            = array(
                  self::$_current_lang => self::$_current_lang.'.php',
                  'EN'                 => 'EN.php'
              );

        $this->log()->LogDebug( 'language files to search for: ', $lang_files );

        foreach ( $lang_files as $l => $file ) {
            if ( $this->addFile( $file, $var ) ) { break; }
        }

		}   // end function init()
		
		/**
		 *
		 *
		 *
		 *
		 **/
    public function addFile ( $file, $path = NULL, $var = NULL ) {
    
        global $LANG;
        
        $lang_var = & $LANG;
        
        if ( isset( $var ) ) {
            eval ( 'global $'.$var.';' );
            eval ( "\$lang_var = & \$$var;" );
        }
        
        if ( empty( $path ) ) {
            $path = $this->_langPath;
        }

        $file = $path.'/'.$file;

        if( file_exists( $file ) ) {

            $this->log()->LogDebug( 'found language file: ', $file );

        	  require_once( $file );

        	  if ( isset( $LANG ) ) {
            	  self::$_lang = array_merge( self::$_lang, $lang_var );
                if ( preg_match( "/(\w+)\.php/", $file, $matches ) ) {
            	      self::$_current_lang = $matches[1];
                }
            	  $this->log()->LogDebug( 'loaded language file: ', $file );
                return true;
            }
            else {
                $this->printError( 'invalid lang file: ', $file );
            }

        }

    }   // end function addFile ()
		
  	/**
  	 * set language file path
  	 *
  	 * @access public
  	 * @param  string   $path  - language file path (must exist!)
  	 * @return void
  	 *
  	 **/
    public function setPath ( $path, $var = NULL ) {
    
        if ( file_exists( $path ) ) {

            $this->log()->LogDebug( 'setting language path to: ', $path );

            $this->_langPath = $path;
            $this->init( $var );

        }
        else {
            $this->printError( 'language file path does not exist: '.$path );
        }

    }   // end function setPath ()
    
		/**
		 * get current language shortcut
		 *
		 * @access public
		 * @return string
		 *
		 **/
		public function getLang() {
        return self::$_current_lang;
    }   // end function getLang()

    /**
     * try to find the given message in the language array
     *
     * Will return the original string (but with placeholders replaced) if
     * string is not found in language array.
     *
     * @access public
     * @param  string   $msg  - message to search for
     * @param  array    $attr - attributes to replace in string
     * @return string
     *
     **/
    public function translate( $msg, $attr = array() ) {

        if ( empty( $msg ) || is_bool( $msg ) ) {
            return NULL;
        }
    
        if ( array_key_exists( $msg, self::$_lang ) ) {
            $msg = self::$_lang[$msg];
        }
        
        foreach( $attr as $key => $value ) {
            $msg = str_replace( "{{ ".$key." }}", $value, $msg );
        }
        
        return $msg;
        
    }   // end function translate()
    
    /**
     * dump language array (strings beginning with $prefix)
     *
     * @access public
     * @param  string   $prefix
     * @return array
     *
     **/
    public function dump ( $prefix = NULL ) {
        if ( $prefix ) {
            $dump = array();
            foreach ( self::$_lang as $k => $v ) {
                if ( preg_match( "/^$prefix/", $k ) ) {
                    $dump[$k] = $v;
                }
            }
            return $dump;
        }
        else {
            return self::$_lang;
        }
    }   // end function dump()

}

?>