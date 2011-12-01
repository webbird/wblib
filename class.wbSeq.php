<?php

/**

  Security helper class

  Copyright (C) 2011, Bianka Martinovic
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

require_once dirname( __FILE__ ).'/class.wbValidate.php';

if ( ! class_exists( 'wbSeq', false ) ) {

    class wbSeq extends wbBase {

        // ----- Debugging -----
        protected        $debugLevel      = KLogger::WARN;

        // array to store config options
        protected        $_config         = array(
			'debug'            => false,
            # CSRF protection settings; can and SHOULD be overwritten by caller
			'secret'           => '!p"/.m4fk{ay{£1R0W0O',
			'secret_time'      => 86400,
			'secret_field'     => 'fbseqkey',
		);
        
        // stores last detected issue
        // NOTE! THIS IS A SECURITY ISSUE!!! DON'T USE THIS IN PRODUCTION!!!
        // ONLY USE FOR DEBUGGING!!!
        private          $_lastIssue      = NULL;
        private          $_lastMatch      = NULL;
        
        //
        private          $val;
        
        /**
	     * constructor
	     **/
	    function __construct ( $options = array() ) {
	        parent::__construct( $options );
	        $this->__init();
	        $this->val = new wbValidate();
	    }   // end function __construct()
	    
        /**
         * create a signed token
         *
         *
         *
         **/
		public function createToken( $dynamic = 'wbSeq' ) {
		
		    if ( empty($dynamic) ) {
		        $this->log()->LogWarn( 'No dynamic token part given! Please use always a dynamic part!' );
		        $dynamic = 'wbSeq';
			}
			elseif ( $dynamic == 'wbSeq' ) {
		        $this->log()->LogWarn( 'No dynamic token part given! Please use always a dynamic part!' );
			}

			$secret   = $this->__createSecret( $dynamic );

			// create a random token
            $token    = dechex(mt_rand());

            // create a hash using the secret, the dynamic part, and the random token
            $hash     = sha1( $secret.'-'.$dynamic.'-'.$token );

            // now, at least, create the token
            return $token.'-'.$hash.'-'.time();

		}   // end function function createToken()
	    
	    /**
	     * check mail header injection
	     *
	     * @access public
	     * @param  mixed  $values
	     * @return boolean
	     *
	     **/
	    public function detectMailInjection( $values ) {
	        if ( empty( $values ) ) {
		        return false;
			}
			if ( is_scalar( $values ) ) {
			    $values = array( $values );
			}
			foreach( $values as $value ) {
				foreach(
					array( 'MAIL_INJECTION' )
					as $constant
				) {
				    if ( preg_match( constant( $constant ), $value ) ) {
		                $this->warn( 'SECURITY ISSUE', 'found mail header injection code! -> ' . $value );
		                $this->_lastMatch = $constant;
		                $this->_lastIssue = 'found mail header injection code! -> ' . $value;
		                return true;
		            }
				}
			}
			return false;
	    }   // function detectMailInjection()
	    
	    /**
	     * This method checks for typical SQL injection code
	     *
	     * @access public
         * @param  mixed $values - array of values or single value (scalar)
         * @return boolean       - returns false if no intrusion code was found
	     **/
		public function detectSQLInjection( $values ) {
			if ( empty( $values ) ) {
		        return false;
			}
			if ( is_scalar( $values ) ) {
			    $values = array( $values );
			}
			foreach( $values as $value ) {
				// check for SQL injection
				foreach(
					array( 'PCRE_SQL_TYPICAL', 'PCRE_SQL_UNION', 'PCRE_SQL_STORED' ) //'PCRE_SQL_QUOTES',
					as $constant
				) {
					if ( preg_match( constant( $constant ), $value ) ) {
		                $this->warn( 'SECURITY', 'found SQL injection! ('.$constant.') -> [' . $value . ']' );
		                $this->_lastMatch = $constant;
                  		$this->_lastIssue = 'found SQL injection! ' . "\n$constant\n" . constant( $constant ). "\n\n" . $value;
		                return true;
		            }
				}
			}
			// all checks passed
			return false;
		}   // end function detectSQLInjection()
    
        /**
         * This methods checks for angled content (<something>) and images
         * (<img...>). It should NOT be used to check content created by
         * WYSIWYG editors, because this will make the check fail in any case!
         *
         * @access public
         * @param  mixed $values - array of values or single value (scalar)
         * @return boolean       - returns false if no intrusion code was found
         **/
		public function detectIntrusion( $values ) {
		    if ( empty( $values ) ) {
		        return false;
			}
			if ( is_scalar( $values ) ) {
			    $values = array( $values );
			}
			foreach( $values as $value ) {
				// check for XSS
				foreach(
				    array( 'XSS_IMG_JS' ) //, 'XSS_ANGLED'
				    as $constant
				) {
				    if ( preg_match( constant( $constant ), $value ) ) {
		                $this->warn( 'SECURITY', 'found XSS code! ('.$constant.') -> [' . $value .']' );
		                $this->_lastMatch = $constant;
		                $this->_lastIssue = 'found XSS code! -> ' . $value;
		                return true;
		            }
				}
			}
			// all checks passed
			return false;
		}   // end function detectIntrusion()
		
		/**
		 * encode HTML in form data
		 * found here:
		 * http://www.php.net/manual/de/function.htmlspecialchars.php#97991
		 *
		 * @access public
		 * @param  string   $value - form data to encode
		 * @return string
		 *
		 **/
		public function encodeFormData( $var ) {
            // If variable is an array
	        if ( is_array($var) ) {
	            $out = array();
	            foreach ($var as $key => $v) {
	                // Run encoding on every element of the array and return the result. Also maintains the keys.
	                $out[$key] = $this->encodeFormData($v);
	            }
	        } else {
	            $out = $var;
	            // make sure it's really UTF-8
				$out = mb_convert_encoding($out, 'UTF-8', mb_detect_encoding($out));
	            // Trim the variable, strip all slashes, and encode it
	            $out = htmlspecialchars( stripslashes(trim($out)), ENT_QUOTES, 'UTF-8', true );
	            // fix double encoded
	            $out = str_ireplace( '&amp;amp;', '&amp;', $out );
	        }
			// return result
	        return $out;
		}   // end function encodeFormData()
		
		/**
		 * returns last detected issue
		 *
		 * NOTE! THIS IS A SECURITY ISSUE!!! ONLY USE FOR DEBUGGING!!!
		 *
		 * For security reasons, this only works if debug is set to true
		 *
		 * @access public
		 * @return string
		 *
		 **/
		public function getLastIssue() {
		    if ( $this->_config['debug'] !== true ) {
				return '';
			}
		    return $this->_lastIssue;
		}   // end function getLastIssue()

		/**
		 * returns last detected match (name of constant that matched)
		 *
		 * NOTE! THIS IS A SECURITY ISSUE!!! ONLY USE FOR DEBUGGING!!!
		 *
		 * For security reasons, this only works if debug is set to true
		 *
		 * @access public
		 * @return string
		 *
		 **/
		public function getLastMatch() {
		    if ( $this->_config['debug'] !== true ) {
				return '';
			}
		    return $this->_lastMatch;
		}   // end function getLastMatch()

        /**
         * validate a token
         *
         * @access public
         * @param  string  $dynamic
         * @param  boolean $terminate - terminate session, default false
         * @param  boolean $strict    - passed to __terminateSession(), default true
         *
         **/
		public function validateToken( $dynamic, $terminate = false, $strict = true ) {

            // print notice into log if the secret field name was left to default
            if ( $this->_config['secret_field'] == 'fbseqkey' ) {
                $this->log()->LogWarn(
					'Please note: The "secret_field" option was left to default. You should override this to improve protection.'
				);
            }

            $key   = $this->val->param( $this->_config['secret_field'] );
            $parts = explode( '-', $key );

            $this->log()->LogDebug( 'checking token: '.$key );

            if ( count($parts) == 3 ) {
                list( $token, $hash, $time ) = $parts;
                // check if token is expired
                if ( $time < ( time() - 30 * 60 ) ) {
                    $this->warn( 'SECURITY', 'token is expired (token time -'.$time.'- checked against -'.( time() - 30*60 ).'-' );
                    if ( $terminate ) {
                    	$this->__terminateSession( $strict, 'token expired' );
					}
                    return false;
                }
                // check the secret
                $secret = $this->__createSecret( $dynamic );
                if ( $hash != sha1( $secret.'-'.$dynamic.'-'.$token ) ) {
                    $this->warn( 'WARN', 'invalid token!' );
                    if ( $terminate ) {
                    	$this->__terminateSession( $strict, 'invalid token' );
					}
                    return false;
                }
                else {
					return true;
				}
            }

			// token should have 3 parts; if not, it's invalid
			if ( $terminate ) {
                $this->warn( 'WARN', 'invalid token ['.$key.'] - parts count != 3' );
            	$this->__terminateSession( $strict, 'invalid token ['.$key.'] - parts count != 3');
			}
            return false;

		}   // function validateToken()

	    /**
	     * define regexp
	     *
	     * @access private
	     * @return void
	     *
	     **/
	    private function __init () {
	        if ( defined( '__WBSEQ_INIT__' ) ) {
	            return;
	        }
	        include dirname(__FILE__).'/wbSeq/inc.regexp.php';
	        define( '__WBSEQ_INIT__', true );
	    }   // end sub __init()

		/**
		 *
		 *
		 *
		 *
		 **/
		private function __createSecret( $dynamic ) {

			// add some randomness to the configured secret
		    $secret      = $this->_config['secret'];
			$secrettime  = $this->_config['secret_time'];

			// secret time should not extend one day and not drop below 1 hour
			if ( ! is_numeric($secrettime) || $secrettime > 86400 || $secrettime < 36000 ) {
			    // issue a warning
			    $this->log()->LogWarn(
			        'Invalid secret time given; using the default (86400 = 1 day)'
				);
				$secrettime = 86400;
			}
			$TimeSeed    = floor( time() / $secrettime ) * $secrettime;
			$DomainSeed  = $_SERVER['SERVER_NAME'];
			$Seed        = $TimeSeed + $DomainSeed;

			// use some server specific data
			$serverdata  = ( isset( $_SERVER['SERVER_SIGNATURE'] ) )   ? $_SERVER['SERVER_SIGNATURE']     : '2';
			$serverdata .= ( isset( $_SERVER['SERVER_SOFTWARE'] ) )    ? $_SERVER['SERVER_SOFTWARE']      : '3';
			$serverdata .= ( isset( $_SERVER['SERVER_NAME'] ) ) 	   ? $_SERVER['SERVER_NAME'] 		  : '5';
			$serverdata .= ( isset( $_SERVER['SERVER_ADDR'] ) ) 	   ? $_SERVER['SERVER_ADDR'] 		  : '7';
			$serverdata .= ( isset( $_SERVER['SERVER_PORT'] ) ) 	   ? $_SERVER['SERVER_PORT'] 		  : '11';
			$serverdata .= ( isset( $_SERVER['SERVER_ADMIN'] ) )	   ? $_SERVER['SERVER_ADMIN'] 		  : '13';
			$serverdata .= PHP_VERSION;

			// add some browser data
			$browser     = ( isset($_SERVER['HTTP_USER_AGENT']) )      ? $_SERVER['HTTP_USER_AGENT']      : 'b';
			$browser    .= ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'c';
			$browser    .= ( isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : 'e';
			$browser	.= ( isset($_SERVER['HTTP_ACCEPT_CHARSET']) )  ? $_SERVER['HTTP_ACCEPT_CHARSET']  : 'g';

			// add seed to current secret
			$secret     .= md5( $Seed ) . md5( $serverdata ) . md5( $browser );

			return $secret;

		}   // end function __createSecret()
		
        /**
         *
         *
         *
         *
         **/
		private function __terminateSession( $strict = true, $reason = NULL ) {
		
		    if ( ! empty($this->_lastMatch) ) {
		    	$this->warn( 'INFO', '__terminateSession() - '. $this->_lastMatch );
			}
			if ( ! empty($reason) ) {
		    	$this->warn( 'INFO', '__terminateSession() - '. $reason );
			}

		    // unset session variables
		    if (isset($_SESSION)) {
		        $_SESSION = array();
		    }
		    if (isset($HTTP_SESSION_VARS)) {
		        $HTTP_SESSION_VARS = array();
		    }

		    // unset globals
		    unset( $_REQUEST );
            unset( $_POST    );
            unset( $_GET     );
            unset( $_SERVER  );

		    session_unset();

			if ( ! empty( $this->_config['onerror_redirect_to'] ) ) {
			    if ( ! headers_sent() ) {
		            header("Location: " . $this->_config['onerror_redirect_to'] );
		        }
				else {
		            $this->log()->LogWarn( "__terminateSession() - Unable to redirect. Undefined action." );
		        }
		        if ( $strict ) { die; }
            }
            else {
	            $this->log()->LogWarn( "__terminateSession() - Unable to redirect. Undefined action." );
	        }
	        if ( $strict ) { die; }

		}   // end function __terminateSession()
		
	}

}