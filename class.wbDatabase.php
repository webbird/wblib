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

    public static $instance = null;
    public static $driver   = 'wbMySQL';
    
    // private to make sure that constructor can only be called
    // using singleton()
    private function __construct() {}
    
    public static function singleton( $options = array() ) {
    
        if ( ! is_object( self::$instance ) ) {
        
            if ( isset( $options['driver'] ) ) {
                self::$driver = $options['driver'];
            }
        
            $driver_file = dirname(__FILE__).'/wbDatabase/class.'.self::$driver.'.php';
            
            include_once $driver_file;

            self::$instance = new self::$driver($options);
            
        }

        return self::$instance;

    }   // end function singleton()

    // no cloning!
    private function __clone() {}


}