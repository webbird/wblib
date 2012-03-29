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

require_once dirname( __FILE__ ) . '/class.wbBase.php';

class wbI18n extends wbBase
{
    // ----- Debugging -----
    protected $debugLevel = KLOGGER::OFF;
    //protected      $debugLevel    = KLOGGER::DEBUG;

    // default language
    protected $_config = array( 'defaultlang' => 'EN', 'langPath' => '/languages' );

    // array to store language strings
    private static $_lang = array();

    // default language
    private static $_current_lang = NULL;

    /**
     * constructor
     **/
    public function __construct( $lang = NULL )
    {
        $options = array();
        if ( is_array( $lang ) )
        {
            $options = $lang;
            $lang    = NULL;
        }
        parent::__construct( $options );
        if ( !isset( $lang ) )
        {
            if ( defined( 'LANGUAGE' ) )
            {
                $lang = LANGUAGE;
            }
        }
        if ( isset($lang) && $lang != '' ) {
        	self::$_current_lang = $lang;
		}
        $this->init();
    } // end function __construct()

    public function __destruct()
    {
    } // end function __destruct()

    /**
     *
     *
     *
     *
     **/
    public function init( $var = NULL )
    {
        $this->log()->LogDebug( 'init()' );

        $caller = debug_backtrace();

        if ( self::$_current_lang == '' )
        {
            $lang_files = $this->__lang_getfrombrowser();
        }
        else
        {
            $lang_files = array(
                 self::$_current_lang
            );
        }

        if ( file_exists( dirname( $caller[ 1 ][ 'file' ] ) . '/languages' ) )
        {
            //$this->_langPath = dirname($caller[1]['file']).'/languages';
            $this->_config[ 'langPath' ] = dirname( $caller[ 1 ][ 'file' ] ) . '/languages';
        }
        elseif ( file_exists( dirname( $caller[ 1 ][ 'file' ] ) . '/../languages' ) )
        {
            //$this->_langPath = dirname($caller[1]['file']).'/../languages';
            $this->_config[ 'langPath' ] = dirname( $caller[ 1 ][ 'file' ] ) . '/../languages';
        }

        // add default lang
        $lang_files[] = 'EN';

        $this->log()->LogDebug( 'language files to search for: ', $lang_files );

        foreach ( $lang_files as $l )
        {
            $file = $l . '.php';
            if ( $this->addFile( $file, $var ) )
            {
                break;
            }
        }

    } // end function init()

    /**
     *
     *
     *
     *
     **/
    public function addFile( $file, $path = NULL, $var = NULL )
    {
        $check_var = 'LANG';

        if ( isset( $var ) )
        {
            $var = str_ireplace( '$', '', $var );
            eval( 'global $' . $var . ';' );
            eval( "\$lang_var = & \$$var;" );
            $check_var = $var;
        }

        if ( empty( $path ) )
        {
            $path = $this->_config[ 'langPath' ];
        }

        $file = $this->sanitizePath( $path . '/' . $file );

        if ( file_exists( $file ) && ! $this->isLoaded($file) )
        {
            $this->log()->LogDebug( 'found language file: ', $file );
            $this->checkFile( $file, $check_var );
            return true;
        }

        $this->log()->LogDebug( 'language file does not exist: ', $file );

    } // end function addFile ()

    /**
     *
     *
     *
     *
     **/
	public function checkFile( $file, $check_var, $check_only = false )
	{
		{
			// require the language file
		    @require( $file );
			// check if the var is defined now
		    if ( isset( ${$check_var} ) )
		    {
                $isIndexed = array_values( ${$check_var} ) === ${$check_var};
                if ( $isIndexed )
                {
                    return false;
                }
		        if ( $check_only )
		        {
		        	return ${$check_var};
				}
				else
            {
	                self::$_lang = array_merge( self::$_lang, ${$check_var} );
                if ( preg_match( "/(\w+)\.php/", $file, $matches ) )
                {
                    self::$_current_lang = $matches[ 1 ];
                }
	                $this->__loaded[$file] = 1;
                $this->log()->LogDebug( 'loaded language file: ', $file );
                return true;
            }
            }
            else
            {
                $this->log()->logInfo( 'invalid lang file: ', $file );
                return false;
            }
		}
	}   // end function checkFile()

	/**
	 *
	 *
	 *
	 *
	 **/
	public function isLoaded( $file )
	{
	    if ( isset( $this->__loaded[$file] ) )
	    {
	        return true;
        }
		return false;
	}   // end function isLoaded()

    /**
     * set language file path
     *
     * @access public
     * @param  string   $path  - language file path (must exist!)
     * @return void
     *
     **/
    public function setPath( $path, $var = NULL )
    {
        if ( file_exists( $path ) )
        {
            $this->log()->LogDebug( 'setting language path to: ', $path );

            $this->_config[ 'langPath' ] = $path;
            $this->init( $var );

        }
        else
        {
            $this->printError( 'language file path does not exist: ' . $path );
        }

    } // end function setPath ()

    /**
     * get current language shortcut
     *
     * @access public
     * @return string
     *
     **/
    public function getLang()
    {
        return self::$_current_lang;
    } // end function getLang()

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
    public function translate( $msg, $attr = array() )
    {
        if ( empty( $msg ) || is_bool( $msg ) )
        {
            return $msg;
        }

        if ( array_key_exists( $msg, self::$_lang ) )
        {
            $msg = self::$_lang[ $msg ];
        }
        foreach ( $attr as $key => $value )
        {
            $msg = str_replace( "{{ " . $key . " }}", $value, $msg );
        }

        return $msg;

    } // end function translate()

    /**
     * dump language array (strings beginning with $prefix)
     *
     * @access public
     * @param  string   $prefix
     * @return array
     *
     **/
    public function dump( $prefix = NULL )
    {
        if ( $prefix )
        {
            $dump = array();
            foreach ( self::$_lang as $k => $v )
            {
                if ( preg_match( "/^$prefix/", $k ) )
                {
                    $dump[ $k ] = $v;
                }
            }
            return $dump;
        }
        else
        {
            return self::$_lang;
        }
    } // end function dump()

    /**
     * This method is based on code you may find here:
     * http://aktuell.de.selfhtml.org/artikel/php/httpsprache/
     *
     *
     **/
    private function __lang_getfrombrowser( $strict_mode = true )
    {
        $browser_langs = array();
        $lang_variable = $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ];

        if ( empty( $lang_variable ) )
        {
            return $this->_config[ 'defaultlang' ];
        }

        $accepted_languages = preg_split( '/,\s*/', $lang_variable );
        $current_q          = 0;

        foreach ( $accepted_languages as $accepted_language )
        {
            // match valid language entries
            $res = preg_match( '/^([a-z]{1,8}(?:-[a-z]{1,8})*)(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i', $accepted_language, $matches );

            // invalid syntax
            if ( !$res )
            {
                continue;
            }

            // get language code
            $lang_code = explode( '-', $matches[ 1 ] );

            if ( isset( $matches[ 2 ] ) )
            {
                $lang_quality = (float) $matches[ 2 ];
            }
            else
            {
                $lang_quality = 1.0;
            }

            while ( count( $lang_code ) )
            {
                $browser_langs[] = array(
                     'lang' => strtoupper( join( '-', $lang_code ) ),
                    'qual' => $lang_quality
                );
                // don't use abbreviations in strict mode
                if ( $strict_mode )
                {
                    break;
                }
                array_pop( $lang_code );
            }
        }

        // order array by quality
        $langs = $this->ArraySort( $browser_langs, 'qual', 'desc', true );
        $ret   = array();
        foreach ( $langs as $lang )
        {
            $ret[] = $lang[ 'lang' ];
        }

        return $ret;

    } // end __lang_getfrombrowser()


}

?>