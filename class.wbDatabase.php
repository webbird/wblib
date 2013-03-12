<?php

/**

  Module developed for the Open Source Content Management System
  Website Baker (http://websitebaker.org)

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


class wbDatabase {

    public static $instances = array();
    public static $instance  = null;
    public static $driver    = 'wbMySQL';
    public        $options   = array();
    
    public static function getInstance( $connection = 'default', $options = array() ) {
        if ( !array_key_exists( $connection, self::$instances ) ) {
            self::$instances[$connection] = self::__connect($options);
        }
        return self::$instances[$connection];
    }   // end function getInstance()

    // private to make sure that constructor can only be called
    // using singleton()
    private function __construct() {}
    
    public static function singleton( $options = array() ) {
        if ( ! is_object( self::$instance ) ) {
            self::$instance = self::__connect($options);
        }
        return self::$instance;
    }   // end function singleton()
    
    private static function __connect( $options ) {
        if ( isset( $options['driver'] ) ) {
            self::$driver = $options['driver'];
        }
        $driver_file = dirname(__FILE__).'/wbDatabase/class.'.self::$driver.'.php';
        include_once $driver_file;
        return new self::$driver($options);
    }   // end function __connect()

    // no cloning!
    private function __clone() {}

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are in the same order as specified in $query
     *
     * Source: http://stackoverflow.com/questions/210564/pdo-prepared-statements
     *
     * @access public
     * @param  string $query  The sql query with parameter placeholders
     * @param  array  $params The array of substitution parameters
     * @return string The interpolated query
     */
    public static function interpolateQuery($query, $params) {

        if ( ! is_array($params) ) {
            return $query;
        }

        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:'.$key.'/';
            }
            else {
                $keys[] = '/[?]/';
            }

            if (is_array($value))
                $values[$key] = implode(',', $value);

            if (is_null($value))
                $values[$key] = 'NULL';
        }
        // Walk the array to see if we can add single-quotes to strings
        array_walk($values, create_function('&$v, $k', 'if (!is_numeric($v) && $v!="NULL") $v = "\'".$v."\'";'));
        $query = preg_replace($keys, $values, $query, 1, $count);
        return $query;
    }   // end function interpolateQuery()

}