<?php

/**

  Base class

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

if ( ! class_exists( 'KLogger', false ) ) {
    include_once dirname(__FILE__).'/./debug/KLogger.php';
}

if ( ! class_exists( 'wbBase', false ) ) {

    class wbBase {

        // ----- Debugging -----
        protected        $debugLevel      = KLOGGER::OFF;
        #protected        $debugLevel      = KLogger::DEBUG;

        // array to store config options
        protected        $_config         = array( 'debug' => false );

        // array to store already loaded files
        protected        $_loaded         = array();

        // accessor to KLogger
        protected        $logObj          = NULL;

        protected        $_url            = NULL;

        private static   $defaultDebugDir = '/debug/log';

        protected        $lang            = NULL; // accessor to wbI18n

        private   static $dirh            = NULL; // directory helper
		private   static $arrayh          = NULL; // array helper

        /**
         * constructor
         **/
        public function __construct ( $options = array() ) {

            if ( is_array( $options ) ) {
                $this->config( $options );
            }

            // get current working directory
            $callstack = debug_backtrace();
            $this->_config['workdir']
                = ( isset( $callstack[0] ) && isset( $callstack[0]['file'] ) )
                ? realpath( dirname( $callstack[0]['file'] ) )
                : realpath( dirname(__FILE__) );

            // init log directory
            if ( property_exists( get_class($this), 'debugDir' ) ) {
                if ( empty( $this->debugDir ) ) {
                    $this->debugDir = $this->sanitizePath( realpath( dirname(__FILE__) ) );
                }
            }
            else {
                $this->debugDir = $this->sanitizePath( realpath( dirname(__FILE__) ).self::$defaultDebugDir );
            }

            // allow to enable logging on object creation
            if ( isset( $this->_config['debug'] ) && $this->_config['debug'] === true ) {
                $this->debugLevel = KLogger::DEBUG;
            }

            if ( isset( $this->_config['path'] ) ) {
				// check if it's a full path
				if ( @is_dir( $this->sanitizePath( $this->_config['path'] ) ) ) {
				    $this->setPath( $this->_config['path'] );
				}
				// check if it's a relative path
				elseif ( @is_dir( $this->sanitizePath( $this->_config['workdir'].'/'.$this->_config['path'] ) ) ) {
                	$this->setPath( $this->_config['workdir'].'/'.$this->_config['path'] );
				}
				else {
				    $this->log()->LogDebug( 'Invalid "path" option - path [['.$this->_config['workdir'].']'.$this->_config['path'].'] does not exist!' );
				}
            }

            // create language object
            if ( isset ( $options['lang'] ) && is_object($options['lang']) ) {
                $this->lang = $options['lang'];
                $this->lang->addFile(
                    $this->lang->getLang().'.php',
                    realpath( dirname(__FILE__) ).'/'.get_class($this).'/languages'
                );
            }

        }   // end function __construct()

        /**
      	 * Prevent cloning of the object (Singleton)
      	 */
      	final private function __clone() {}

        /**
         * wrapper to wbI18n::translate; used to uncouple the template parser
         * from the wbI18n class (auto translation will not work then!)
      	 *
      	 * @access public
      	 * @param  string  $msg  - message to "translate"
      	 * @param  array   $attr - additional attributes
      	 *
      	 * @return string
      	 **/
        public function translate( $msg, $attr = array() ) {
            if ( ! is_object ( $this->lang ) ) {
                if ( file_exists( dirname( __FILE__ ).'/class.wbI18n.php' ) ) {
                    require_once dirname( __FILE__ ).'/class.wbI18n.php';
                    $this->lang = new wbI18n();
                    $this->lang->addFile(
                        $this->lang->getLang().'.php',
                        realpath( dirname(__FILE__) ).'/'.get_class($this).'/languages'
                    );
                }
            }
            // still no object?
            if ( ! is_object ( $this->lang ) ) {
                return $msg;
            }
            return $this->lang->translate( $msg, $attr );
        }   // end function translate()

        /**
         *
         **/
        public function setLangHandler( $lang ) {
            if ( is_object($lang) ) {
                $this->lang = $lang;
            }
        }   // end function setLangHandler()


      	/**
      	 * Accessor to KLogger class; this makes using the class significant faster!
      	 *
      	 * @access public
      	 * @return object
      	 *
      	 **/
      	public function log () {
            if ( $this->debugLevel < 6 ) {
                if ( ! is_object( $this->logObj ) ) {
                    $this->logObj
                        = new KLogger(
                              $this->debugDir.'/'.get_class($this).'.log',
                              $this->debugLevel,
                              true
#						      false
                          );
                }
                return $this->logObj;
            }
            return $this;
      	}   // end function log ()

      	/**
      	 * Fake KLogger's LogDebug / LogWarn functions if debug level is "off"; 
      	 * just does nothing
      	 *
      	 * @param ignored
      	 *
      	 **/
      	public function LogDebug () {
            return;
        }   // end function LogDebug ()
        public function LogWarn () {
            return;
        }   // end function LogWarn ()
        public function LogError () {
            return;
        }   // end function LogError ()
        
        /**
         *
         *
         *
         *
         **/
		public function warn( $level, $message ) {
	        $logfile = fopen( $this->debugDir.'/'.get_class($this).'.warn', 'a' );
	        if ( $logfile ) {
		        fputs($logfile,
					implode(
						' : ',
						array(
							sprintf( '%10s', $level ),
							date("d.m.Y, H:i:s",time()),
		              		$_SERVER['REMOTE_ADDR'],
		              		$message,
		              		$_SERVER['REQUEST_METHOD'],
		              		$_SERVER['PHP_SELF'],
		              		$_SERVER['HTTP_USER_AGENT'],
		              		( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '' )
						)
					) . "\n"
				);
		        fclose($logfile);
			}
		}   // end function warn()

      	/**
      	 * set path
      	 *
      	 * @access public
      	 * @param  string   $path  - new path (must exist!)
      	 * @return void
      	 *
      	 **/
        public function setPath ( $path ) {
			$path = $this->sanitizePath( $path );
			$this->log()->LogDebug( 'setting path ['.$path.']' );
            if ( file_exists( $path ) ) {
                $this->_config['path'] = $path;
            }
            else {
                $this->log()->LogDebug( 'path does not exist!' );
                $this->printError( 'path does not exist: '.$path );
            }
        }   // end function setPath ()

        /**
         *
         *
         *
         *
         **/
        public function getPath() {
            return $this->_config['path'];
        }

        /**
         * this is an alias for setFile()
         *
         * @access public
         * @param  string  $file - file to load
         * @param  string  $path - ignored
         * @param  string  $var  - var name (default: $FORMS)
         *
         **/
        public function addFile( $file, $path = NULL, $var = NULL ) {
            return $this->setFile( $file, $path, $var );
        }   // end function addFile()

      	/**
      	 * set current file
      	 *
      	 * @access public
      	 * @param  string   $file  - filename (must exist in current path!)
      	 * @param  string   $path  - optional path to load from
      	 * @param  string   $var   - var name (ignored here; overload method to use)
      	 * @return void
      	 *
      	 **/
        public function setFile ( $file, $path = NULL, $var = NULL ) {

            if ( isset( $var ) ) {
                $set->_config['var'] = $var;
            }

            // array of locations to search for $file
            $try = array(
                       realpath( $this->_config['path']         .'/'.$file ),
                       realpath( $this->_config['fallback_path'].'/'.$file ),
                       realpath( $this->_config['workdir']      .'/'.$file ),
                       $file,
                   );

            // add $path to search array if set
            if ( isset ( $path ) && file_exists( $path ) ) {
                array_unshift( $try, realpath( $path.'/'.$file ) );
            }

            // remove doubles
            $try = array_unique($try);

            $this->log()->LogDebug( 'scanning paths:', $try );

            foreach ( $try as $filename ) {
            
				if ( $filename == '' )
				{
					continue;
				}

                $this->log()->LogDebug(
                    'trying to find: -'.$filename.'-'
                );

                if ( @file_exists( $filename ) ) {

                    $this->log()->LogDebug( 'found!' );

                    // store current file name
                    $this->_config['current_filename'] = $file;

                    // store current real file name (incl. path)
                    $this->_config['current_file']     = $filename;

                    // load file and store contents
                    $fileContents = $this->slurp( $filename );
                    $this->_loaded[ $file ] = $fileContents;
                    #$this->log()->LogDebug( 'file contents:', $fileContents );

                    break;

                }
            }

        }   // end function setFile ()

        /**
         * set config values
         *
         * @access public
         * @param  string   $option
         * @param  string   $value
         * @return void
         *
         **/
        public function config( $option, $value = NULL ) {

           if ( is_array( $option ) ) {
                foreach( $option as $key => $value ) {
                    $this->_config[$key] = $value;
				}
            }
            else {
                $this->_config[$option] = $value;
            }

        }   // end function config()

      	/**
      	 *
      	 *
      	 *
      	 *
      	 **/
        public function selfURL( $url = NULL ) {
            if ( $url ) {
                $this->_url = $url;
            }
            if ( empty( self::$_url ) ) {
				if ( ! isset($_SERVER['REQUEST_URI']) ) {
					$serverrequri = $_SERVER['PHP_SELF'];
				}
				else {
					$serverrequri = $_SERVER['REQUEST_URI'];
				}
				$s          = empty($_SERVER["HTTPS"])
							? ''
							: (
								  ($_SERVER["HTTPS"] == "on")
								? "s"
								: ""
							  );
				$protocol   = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")).$s;
				$port 	    = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
               	$this->_url = $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
            }
            return $this->_url;
        }   // end function selfURL()

        /**
         *
         *
         *
         *
         **/
        function getURI ( $base_url = NULL, $remove_params = array(), $add_params = array() ) {

            if ( empty( $base_url ) ) {
                $base_url = $this->selfURL();
            }

            if ( empty( $base_url ) ) {
                return NULL;
            }

            $paramstring = NULL;
            $path        = $base_url;
            $params      = array();

            // to prevent datatype errors
            if ( ! is_array( $remove_params ) ) {
                $remove_params = array();
            }
            if ( ! is_array( $add_params ) ) {
                $add_params = array();
            }

            //
            $remove_all = ( isset( $remove_params[0] ) && $remove_params[0] == '*' )
                        ? true
                        : false;

            // get params from $base_url
            if ( strstr( $base_url, '?' ) ) {
                list ( $path, $paramstring ) = explode( '?', $base_url );
            }

#echo "BASE URL: $base_url<br />\n",
#     "PATH:     $path<br />\n",
#     "PARAMS:   $paramstring<br />\n";

            $aParam = preg_split( "/[;&]/", $paramstring );
            if ( is_array( $aParam ) ) {
                foreach ( $aParam as $item ) {
                    if ( strstr( $item, '=' ) ) {
                        list ( $key, $value ) = explode( '=', $item );
                        if ( ! $remove_all && ! in_array( $key, $remove_params ) ) {
                            $params[$key] = $value;
                        }
                    }
                }
            }

            $params = array_merge( $params, $add_params );

            $carr = array();
            while ( list( $key, $value ) = each( $params ) ) {
                $carr[] = "$key=$value";
            }

            preg_match(
                "#(http(?:s)?://([^/].*?)+)/(.*)#",
                $base_url,
                $matches
            );

            // server name
           	$servername = isset( $matches[1] )
                        ? $matches[1]
                        : $_SERVER['HTTP_HOST'];

            // remove leading /
            if ( $servername ) {
                $path = preg_replace( "#^/+#", '', $path );
            }

            $URI = $path
                 . (
                       count($carr)
                     ? '?' . implode( '&', $carr )
                     : NULL
                   );
#echo "getURI() URI before match servername: -$URI-<br />";

            // make sure that we have a server name
            if ( ! preg_match( "#^http(?:s)?://#", $URI ) ) {
                $URI = 'http://'.$servername.'/'.$URI;
            }

#echo "getURI() URI: -$URI-<br />";

            return $URI;

        }   // end function getURI ()

        /**
         * this function now resides in class.wbDirectory.php; this is for
         * backward compatibility
         * params $recurse and $haltonerror are no longer supported
         **/
        function scanDirectory( $dir, $remove_prefix = NULL, $with_files = false, $files_only = false, $recurse = true, $haltonerror = false ) {
            if( ! is_object(self::$dirh) ) {
                include_once dirname(__FILE__).'/class.wbDirectory.php';
                self::$dirh = new wbDirectory();
            }
			return self::$dirh->scanDirectory($dir, $with_files, $files_only, $remove_prefix );
        }   // end function scanDirectory()
        
        /**
         * sanitize URL (remove '/./', '/../', '//')
         *
         *
         *
         **/
        function sanitizeURI( $href )
        {
            // href="http://..." ==> href isn't relative
            $rel_parsed = parse_url($href);
            $path       = $rel_parsed['path'];

            // bla/./bloo ==> bla/bloo
            $path       = preg_replace('~/\./~', '/', $path);
            
            // remove trailing
            $path       = preg_replace('~/$~', '', $path );

            // resolve /../
            // loop through all the parts, popping whenever there's a .., pushing otherwise.
            $parts      = array();
            foreach ( explode('/', preg_replace('~/+~', '/', $path)) as $part )
            {
                if ($part === ".." || $part == '')
                {
                    array_pop($parts);
                }
                elseif ($part!="")
                {
                    $parts[] = $part;
                }
            }

            return
            (
                  array_key_exists( 'scheme', $rel_parsed )
                ? $rel_parsed['scheme'] . '://' . $rel_parsed['host'] . ( isset($rel_parsed['port']) ? ':'.$rel_parsed['port'] : NULL )
                : ""
            ) . "/" . implode("/", $parts);

        }   // end function sanitizeURI()
        
        /**
         * sanitize path (remove '/./', '/../', '//')
         *
         *
         *
         **/
        function sanitizePath( $path ) {
            if( ! is_object(self::$dirh) ) {
                include_once dirname(__FILE__).'/class.wbDirectory.php';
                self::$dirh = new wbDirectory();
                }
			return self::$dirh->sanitizePath($path);
        }   // end function sanitizePath()
        
	    /**
	     *
         *
         *
         *
         **/
        public function slurp ( $file ) {

            $text = NULL;

            // try to find the file
            if ( ! file_exists( $file ) ) {
                if (
                     isset( $this->_config['workdir'] )
                     &&
                     file_exists( $this->_config['workdir'].'/'.$file )
                ) {
                    $file = $this->_config['workdir'].'/'.$file;
                }
            }

            // read the file
            if ( file_exists( $file ) ) {
                $text = implode( '', file( $file ) );
            }
            else {
                $this->log()->LogDebug( "FILE $file NOT FOUND!<br />");
            }

            return $text;

        }   // end function slurp()

        /**
         * transform an array to an array of strings:
         * '<key>' = '<value>'
         *
         * @param array
         * @return array
         **/
        function array_collapse($arr) {
            $carr = array();
            while ( list( $key, $value ) = each( $arr ) ) {
                $carr[] = "$key=\"$value\"";
            }
            return $carr;
        }   // end function array_collapse()

        /**
         * Recursive array search
         *
         * @param  string  $Needle     value to search for
         * @param  array   $Haystack   array to search
         * @param  string  $NeedleKey  array key to retrieve
         * @param  boolean $Strict
         * @param  array   $Path
         *
         * @return mixed   $Path       array - path to array value if $Needle is found
         *                 false       if $Needle is not found
         **/
        function ArraySearchRecursive( $Needle, $Haystack, $NeedleKey="", $Strict=false, $Path=array() ) {
			if( ! is_object(self::$arrayh) ) {
                include_once dirname(__FILE__).'/class.wbArray.php';
                self::$arrayh = new wbArray();
            }
			return self::$arrayh->ArraySearchRecursive($Needle, $Haystack, $NeedleKey, $Strict, $Path);
        }   // end function ArraySearchRecursive()

        /**
         * sort an array
         *
         *
         *
         **/
        function ArraySort ( $array, $index, $order='asc', $natsort=FALSE, $case_sensitive=FALSE ) {
            if( ! is_object(self::$arrayh) ) {
                include_once dirname(__FILE__).'/class.wbArray.php';
                self::$arrayh = new wbArray();
                 }
			return self::$arrayh->ArraySort ( $array, $index, $order, $natsort, $case_sensitive );
        }   // function ArraySort


        /**
         *
         *
         *
         *
         **/
        public function printError( $msg = NULL, $args = NULL ) {

            $caller       = debug_backtrace();

            $caller_class = isset( $caller[1]['class'] )
                          ? $caller[1]['class']
                          : NULL;

            echo "<div id=\"wberror\">\n",
                 "  <h1>$caller_class Fatal Error</h1><br /><br />\n",
                 "  <div style=\"color: #FF0000; font-weight: bold; font-size: 1.2em;\">\n",
                 "  $msg\n";

            if ( $args ) {

                $dump = print_r( $args, 1 );
                $dump = preg_replace( "/\r?\n/", "\n          ", $dump );

                echo "<br />\n";
                echo "<pre>\n";
                echo "          ", $dump;
                echo "</pre>\n";
            }

            // remove path info from file
            $file = basename( $caller[1]['file'] );

            echo "<br /><br /><span style=\"font-size: smaller;\">[ ",
                 $file, ' : ',
                 $caller[1]['line'], ' : ',
                 $caller[1]['function'],
                 " ]</span><br />\n";

            if ( $this->debugLevel < 6 ) {
                echo "<h2>Debug backtrace:</h2>\n",
                     "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
                print_r( debug_backtrace() );
                echo "</textarea>";
            }

            echo "  </div>\n</div><!-- id=\"wberror\" -->\n";

        }   // end function printError()

        /**
         * create a random string
         *
         *
         *
         **/
        protected function generateRandomString( $length = 10 ) {
            for(
                   $code_length = $length, $newcode = '';
                   strlen($newcode) < $code_length;
                   $newcode .= chr(!rand(0, 2) ? rand(48, 57) : (!rand(0, 1) ? rand(65, 90) : rand(97, 122)))
            );
            return $newcode;
        }   // end function generateRandomString()

    }
   // end class wbBase

}

?>