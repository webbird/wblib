<?php

/**

  Database Abstraction interface definition
  
  mySQL Abstraction Layer

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

include_once dirname(__FILE__).'/class.wbDBBase.php';
include_once dirname(__FILE__).'/interface.wbDBDriver.php';

// decorator class

class wbSQLite extends wbDBBase implements wbDBDriver {

    public function defaults () {
        $this->port       = NULL;
        $this->dbname     = '/default.db';
        $this->pdo_driver = 'sqlite';
    }   // end function defaults ()
    
    /**
     *
     *
     *
     *
     **/
    public function getDSN() {
        // build dsn
        if ( empty( $this->dsn ) ) {
            $this->dsn = $this->pdo_driver.':'.$this->dbname;
        }
    }   // end function getDSN()

}

?>
