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

if ( ! class_exists( 'wbSeq', false ) ) {

    class wbSeq extends wbBase {

        // ----- Debugging -----
        protected        $debugLevel      = KLogger::WARN;

        // array to store config options
        protected        $_config         = array( 'debug' => false );
        
        /**
	     * constructor
	     **/
	    function __construct ( $options = array() ) {
	        parent::__construct( $options );
	        $this->__init();
	    }   // end function __construct()
    
        /**
         *
         *
         *
         *
         **/
		public function detectIntrusion( $value ) {
			// check for SQL injection
			foreach(
				array( 'PCRE_SQL_QUOTES', 'PCRE_SQL_TYPICAL', 'PCRE_SQL_UNION', 'PCRE_SQL_STORED' )
				as $constant
			) {
				if ( preg_match( constant( $constant ), $value ) ) {
	                $this->log()->LogWarn( 'found injection code!', $value );
	                return true;
	            }
			}
			// check for XSS
			foreach(
			    array( 'XSS_IMG_JS', 'XSS_ANGLED' )
			    as $constant
			) {
			    if ( preg_match( constant( $constant ), $value ) ) {
	                $this->log()->LogWarn( 'found XSS code!', $value );
	                return true;
	            }
			}
			return false;
		}   // end function detectIntrusion()
		
		
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

	}

}