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

// prevent this file from being accessed directly
if(!defined('WB_PATH')) die(header('Location: index.php'));

include_once WB_PATH.'/framework/class.database.php';

class wbDatabase {

    private   $db;
    public    $utf8   = true;
    private   $_error = NULL;
    
    function __construct() {
        $this->db        = new database();
        if ( $this->utf8 ) {
            $this->execute( 'SET NAMES utf8;' );
        }
    }

    /***********************************************************************
     * Throw an error and die
     *
     * @param  string    $msg              error message to print
     *
     **/
    function FatalError ( $msg = '' ) {

        global $trace;

        if ( empty( $msg ) ) {
            $msg = 'FATAL ERROR';
        }

        echo "<h1>wbDatabase Error:</h1>\n<h2>- $msg -</h2>\n";

        if ( $trace ) {
            echo "<br /><br /><strong>Debug backtrace:</strong>\n<pre>";
            print_r( debug_backtrace() );
            echo "</pre>\n\n";
        }

        die;

    }   // end function FatalError

    /**
     * execute SQL statement
     *
     * @param  string  $sql  SQL statement to execute
     * @return array
     *
     **/
    function execute ( $sql ) {

        $result = $this->db->query($sql);
        $this->_error = mysql_error();

        if ( $result ) {
            if ( @$result->numRows() > 0 ) {
                return $this->fetchAll($result);
            }
            return true;
        }
        else {
            $this->FatalError(
                "<strong>[execute] DB ERROR:</strong><br />\n"
              . $this->db->get_error()
            );
        }

        return true;

    }   // end function execute ()

    /**
     * get last insert ID using mysql_insert_id()
     *
     *
     *
     **/
    function lastInsertID() {
        return mysql_insert_id();
    }   // end function _EM_lastInsertID()

    /**
     * fetch all data from a query
     *
     * @param  ?      $result  mysql result
     * @return array  $all     array of fetched rows
     *
     **/
    function fetchAll($result) {

        $all = array();

        while ( ( $row = mysql_fetch_assoc( $result->result ) ) ) {
            $all[] = $row;
        }

        return $all;

    }   // end function fetchAll()
    
    function isError() {
        return (!empty($this->_error)) ? true : false;
    }
    
    // Return the error
    function getError() {
    	  return $this->_error;
    }
    
}