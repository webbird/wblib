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

class wbBase {

    protected        $_debug          = false;
    private static   $_url            = NULL;
    
    /**
     * constructor
     **/
    public function __construct () {}

    /**
  	 * Prevent cloning of the object (Singleton)
  	 */
  	final private function __clone() {}
  	
  	/**
  	 *
  	 *
  	 *
  	 *
  	 **/
    public function selfURL( $url = NULL ) {
        if ( $url ) {
            self::$_url = $url;
        }
        return self::$_url;
    }   // end function selfURL()
  	
  	/**
     *
     *
     *
     *
     **/
    public function slurp ( $file ) {
        // read the file
        $text = implode( '', file( $file ) );
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

        if( ! is_array($Haystack) ) {
            return false;
        }

        foreach($Haystack as $Key => $Val) {

            if( is_array($Val)
                &&
                $SubPath = $this->ArraySearchRecursive($Needle,$Val,$NeedleKey,$Strict,$Path)
            ) {
                $Path=array_merge($Path,Array($Key),$SubPath);
                return $Path;
            }
            elseif(
                ( ! $Strict && $Val  == $Needle && $Key == ( strlen($NeedleKey) > 0 ? $NeedleKey : $Key ) )
                ||
                (   $Strict && $Val === $Needle && $Key == ( strlen($NeedleKey) > 0 ? $NeedleKey : $Key ) )
            ) {
                $Path[]=$Key;
                return $Path;
            }

        }

        return false;

    }   // end function _EM_ArraySearchRecursive()
    
    /**
     *
     *
     *
     *
     **/
    public function printError( $msg = NULL, $args = NULL ) {
    
        $caller = debug_backtrace();

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
        
        echo "<br />",
             $caller[1]['file'], ' : ',
             $caller[1]['line'], ' : ',
             $caller[1]['function'], "<br />\n";

        if ( $this->_debug ) {
            echo "<h2>Debug backtrace:</h2>\n",
                 "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
            print_r( debug_backtrace() );
            echo "</textarea>";
        }

        echo "  </div>\n</div><!-- id=\"wberror\" -->\n";

    }   // end function printError()


    /**
     * for debugging
     *
     * @param   string   $text    - debug message
     * @param   mixed    $args    - additional info (string|array)
     *
     **/
    protected function debug ( $text = '', $args = NULL ) {

        if ( empty( $text ) ) {
            return;
        }

        if ( $this->_debug ) {

            echo '[debug] ',
                 $text;

            if ( $args ) {

                $dump = print_r( $args, 1 );
                $dump = preg_replace( "/\r?\n/", "\n          ", $dump );

                echo "<br />\n";
                echo "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
                print_r( $dump );
                echo "</textarea>";
            }

            echo "<br />\n";

        }

    }   // end function debug()

}

// end class wbBase
?>