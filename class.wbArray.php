<?php

/**

  Array helper class

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

if(!class_exists('wbBase',false))
    include dirname(__FILE__).'/class.wbBase.php';

if(!class_exists('wbArray',false))
{
    class wbArray extends wbBase {

        private static $Needle = NULL;
        private static $Key    = NULL;

        private static function filter_callback($v)
        {
            return !isset($v[self::$Key]) || $v[self::$Key] !== self::$Needle;
        }

        /**
         * removes an element from an array
         *
         * @access public
         * @param  string $Needle
         * @param  array  $Haystack
         * @param  mixed  $NeedleKey
         **/
        public function ArrayRemove( $Needle, &$Haystack, $NeedleKey="" )
        {
            if( ! is_array( $Haystack ) ) {
                return false;
            }
            reset($Haystack);
            self::$Needle = $Needle;
            self::$Key    = $NeedleKey;
            $Haystack     = array_filter($Haystack, 'self::filter_callback');
        }   // end function ArrayRemove()

    	/**
         *
         *
         *
         *
         **/
        public function ArrayUniqueRecursive( $array, $case_sensitive = false ) {
    		$set = array();
    		$out = array();
    		foreach ( $array as $key => $val ) {
    			if ( is_array($val) ) {
    			    $out[$key] = $this->ArrayUniqueRecursive($val,$case_sensitive);
    			}
    			else {
    			    $seen_val = ( ( $case_sensitive === true ) ? $val : strtolower($val) );
    			    if( ! isset($set[$seen_val]) ) {
						$out[$key] = $val;
					}
					$set[$seen_val] = 1;
            	}
    		}
    		return $out;
   		}   // end function ArrayUniqueRecursive()

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
        public function ArraySearchRecursive( $Needle, $Haystack, $NeedleKey="", $Strict=false, $Path=array() ) {

            if( ! is_array( $Haystack ) ) {
                $this->log()->LogDebug( 'Haystack is not an array', $Haystack );
                return false;
            }

            $this->log()->LogDebug( 'searching for ['.$Needle.'] in stack: ', $Haystack );

            reset($Haystack);

            foreach ( $Haystack as $Key => $Val ) {

                $this->log()->LogDebug( 'current key: '.$Key, $Val );

                if (
                    is_array( $Val )
                    &&
                    $SubPath = $this->ArraySearchRecursive($Needle,$Val,$NeedleKey,$Strict,$Path)
                ) {
                    $Path=array_merge($Path,Array($Key),$SubPath);
                    return $Path;
                }
                elseif (
                    ( ! $Strict && $Val  == $Needle && $Key == ( strlen($NeedleKey) > 0 ? $NeedleKey : $Key ) )
                    ||
                    (   $Strict && $Val === $Needle && $Key == ( strlen($NeedleKey) > 0 ? $NeedleKey : $Key ) )
                ) {
                    $this->log()->LogDebug( "found needle [$Needle], key [$NeedleKey], value [$Val]" );
                    $Path[]=$Key;
                    return $Path;
                }

            }

            $this->log()->LogDebug( "needle [$Needle] not found" );

            return false;

        }   // end function ArraySearchRecursive()
        
        /**
         * sort an array
         *
         *
         *
         **/
        public function ArraySort ( $array, $index, $order='asc', $natsort=FALSE, $case_sensitive=FALSE ){
            if( is_array($array) && count($array)>0 ) {
                 foreach(array_keys($array) as $key) {
                     $temp[$key]=$array[$key][$index];
                 }
                 if(!$natsort) {
                     ($order=='asc')? asort($temp) : arsort($temp);
                 }
                 else {
                     ($case_sensitive)? natsort($temp) : natcasesort($temp);
                     if($order!='asc') {
                         $temp=array_reverse($temp,TRUE);
                     }
                 }

                 foreach(array_keys($temp) as $key) {
                     (is_numeric($key))? $sorted[]=$array[$key] : $sorted[$key]=$array[$key];
                 }
                 return $sorted;
            }
            return $array;
        }   // function ArraySort
        
        /**
         *
         *
         *
         *
         **/
        public function ArrayFindKeyRecursive ( $key, $array ) {
            if ( array_key_exists( $key, $array ) ) {
                return $array[$key];
            }
            foreach ( $array as $k => $value ) {
                if ( is_array( $value ) ) {
                    // do sub-search
                    $found = $this->ArrayFindKeyRecursive( $key, $array[$k] );
                    if ( $found ) {
                        return $found;
                    }
                    else {
                        return false;
                    }
                }
            }
            return false;
        }   // end function ArrayFindKeyRecursive ()
    }
}


?>