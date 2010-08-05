<?php

/**

  Database Abstraction base properties definition

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

include_once dirname(__FILE__).'/../class.wbBase.php';
include_once dirname(__FILE__).'/../debug/KLogger.php';

class wbDBBase extends PDO {

    // ----- Debugging -----
    protected    $debugLevel = KLogger::OFF;
    private static $debugDir = '/../debug/log';

    protected $dsn        = NULL;
    protected $host       = "localhost";
    protected $port       = 80;
    protected $user       = "root";
    protected $pass       = "password";
    protected $dbname     = "mydb";
    protected $pdo_driver = 'mysql';
    protected $prefix     = NULL;
    protected $timeout    = 5;
    protected $errors     = array();
    protected $lasterror  = NULL;
    protected $lastInsertID = NULL;

// ----- Operators used in WHERE-clauses -----
    protected $operators  = array(
        '='  => '=',
        'eq' => '=',
        'ne' => '<>',
        '==' => '=',
        '!=' => '<>',
        '=~' => 'REGEXP',
        '!~' => 'NOT REGEXP',
        '~~' => 'LIKE'
    );

// ----- Conjunctions used in WHERE-clauses -----
    protected $conjunctions = array(
        'and' => 'AND',
        'AND' => 'AND',
        'OR'  => 'OR',
        'or'  => 'OR',
        '&&'  => 'AND',
        '\|\|'  => 'OR'
    );

    /**
     * catch unknown exceptions on object construction
     **/
    public static function exception_handler( $exception ) {
        // Output the exception details
        if ( is_object( $this ) && is_object($this->log) )
        {
            $this->log->LogError(
                'Uncaught exception: '. $exception->getMessage()
            );
        }
        die( 'Uncaught exception: '. $exception->getMessage() );
    }

    /**
     * inheritable constructor
     *
     *
     *
     **/
    public function __construct( $options = array() ) {
    
        // must be inherited
        if ( get_class($this) === 'wbDBBase' ) {
            die( 'Invalid class: ' . get_class($this) );
        }
        
// ----- TODO: validate options -----
        $this->__initialize($options);
        
        $this->log->LogDebug(
            'connection dsn: '.$this->dsn
        );

        // Temporarily change the PHP exception handler while we ...
        set_exception_handler(array(__CLASS__, 'exception_handler'));

        // ... create a PDO object
        parent::__construct( $this->dsn, $this->user, $this->pass );

        // Change the exception handler back to whatever it was before
        restore_exception_handler();

        //$this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
        
        // create logger instance
        if ( property_exists( get_class($this), 'debugDir' ) ) {
            if ( empty( $this->debugDir ) ) {
                $this->debugDir = realpath( dirname(__FILE__) );
            }
        }
        else {
            $this->debugDir = realpath( dirname(__FILE__) ).self::$debugDir;
        }

        $this->log
            = new KLogger(
                  $this->debugDir.'/'.get_class($this).'.log' ,
                  $this->debugLevel,
                  true
              );

    }   // end function __construct()
    
    /**
     *
     *
     *
     *
     **/
    public function getDSN() {
        // build dsn
        if ( empty( $this->dsn ) ) {
            $this->dsn = $this->pdo_driver.':host='.$this->host.';dbname='.$this->dbname;
            if ( isset( $this->port ) ) {
                $this->dsn .= ';port='.$this->port;
            }
        }
    }   // end function getDSN()
    
    /**
     *
     *
     *
     *
     **/
    public function getError() {
        return $this->lasterror;
    }   // end function getError()
    
    /**
     *
     *
     *
     *
     **/
    public function getLastInsertID() {
        return $this->lastInsertID;
    }   // end function getLastInsertID()
    
    /**
     *
     *
     *
     *
     **/
    public function isError() {
        return isset( $this->lasterror ) ? true : false;
    }   // end function isError()

    /**
     * Search the DB
     *
     * @access public
     * @param  array   $options - see below
     * @return array
     *
     * Usage:
     *
     * $data = $dbh->search(
     *    'tables' => 'myTable',
     *    'fields' => array( 'id', 'content' ),
     *    'where'  => 'id == ? && content ne ?',
     *    'params' => array( '5', NULL )
     * );
     *
     **/
    public function search ( $options = array() ) {
    
        if ( ! isset( $options['tables'] ) ) {
            return NULL;
        }
        
        $this->lasterror = NULL;

        $tables = $this->__map_tables( $options['tables'], $options );

        $fields = isset( $options['fields'] )
                ? $options['fields']
                : '*';
                
        $where  = isset( $options['where'] )
                ? $this->__parse_where( $options['where'] )
                : NULL;

        $order  = isset( $options['order_by'] )
                ? 'ORDER BY '.$options['order_by']
                : NULL;
                
        $limit  = isset( $options['limit'] )
                ? 'LIMIT '.$options['limit']
                : NULL;
                
        $params = isset( $options['params'] ) && is_array( $options['params'] )
                ? array_values( $options['params'] )
                : NULL;
                
        $group  = isset( $options['group_by'] )
                ? 'GROUP BY '.$options['group_by']
                : NULL;

        // create the statement
        $statement = "SELECT "
                   . (
                       is_array( $fields )
                       ? implode( ', ', $fields )
                       : $fields
                     )
                   . " FROM $tables $where $order $group $limit";

        $this->log->LogDebug( 'executing statement: '.$statement, $params );

        $stmt      = $this->prepare( $statement );
        
        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->log->LogFatal( 'prepare() ERROR: '.$error_info );
        }

        if ( $stmt->execute( $params ) ) {
            $this->log->LogDebug( 'returning ['.$stmt->rowCount().'] results' );
            return $stmt->fetchAll( PDO::FETCH_ASSOC );
        }
        else {
            if ( $stmt->errorInfo() ) {
                $error = '['.implode( "] [", $stmt->errorInfo() ).']';
            }
            $this->log->LogFatal(
                $error
            );
            $this->lasterror = $error;
        }

    }   // end function search ()
    
    /**
     *
     *
     *
     *
     **/
    public function insert( $options ) {
    
        if ( ! isset( $options['tables'] ) || ! isset( $options['values'] ) ) {
            return NULL;
        }
        
        $this->lasterror = NULL;
        
        $do     = isset( $options['do'] )
                ? $options['do']
                : 'INSERT';
                
        $options['__is_insert'] = true;

        $tables = $this->__map_tables( $options['tables'], $options );
        $values = array();
        $fields = NULL;

        foreach ( $options['values'] as $v ) {
            $values[] = '?';
        }

        if ( isset( $options['fields'] ) && is_array( $options['fields'] ) ) {
            $fields = '( `'
                    . implode( '`, `', $options['fields'] )
                    . '` )';
        }

        // create the statement
        $statement = "$do INTO $tables $fields"
                   . " VALUES ( "
                   . implode( ', ', $values )
                   . " )";
                       
        $this->log->LogDebug( 'executing statement: '.$statement, $options['values'] );

        $stmt      = $this->prepare( $statement );

        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->log->LogFatal( 'prepare() ERROR: '.$error_info );
        }

        if ( $stmt->execute( $options['values'] ) ) {
            $this->log->LogDebug( 'statement successful:', $statement );
            // if it's an insert, save the id
            if ( $do == 'INSERT' ) {
                $this->lastInsertID = $this->lastInsertId();
            }
            return true;
        }
        else {
            if ( $stmt->errorInfo() ) {
                $error = '['.implode( "] [", $stmt->errorInfo() ).']';
                $this->errors[] = $error;
                $this->lasterror = $error;
                $this->log->LogFatal(
                    $error
                );
            }
        }

    }   // end function insert()
    
    /**
     *
     *
     *
     *
     **/
    public function replace( $options ) {
        $this->log->LogDebug( '', $options );
        $options['do'] = 'REPLACE';
        return $this->insert( $options );
    }   // end function replace()
    
    /**
     *
     *
     *
     *
     **/
    public function update( $options ) {
    
        if ( ! isset( $options['tables'] ) || ! isset( $options['values'] ) ) {
            return NULL;
        }

        $this->lasterror = NULL;
        
        $tables = $this->__map_tables( $options['tables'], $options, true );
        $where  = isset( $options['where'] )
                ? $this->__parse_where( $options['where'] )
                : NULL;
        
        $carr = array();
        foreach ( $options['fields'] as $key ) {
            $carr[] = "$key = ?";
        }
        
        // create the statement
        $statement = "UPDATE $tables SET "
                   . implode( ', ', $carr )
                   . " $where";

        $this->log->LogDebug( 'executing statement: '.$statement, $options['values'] );

        $stmt      = $this->prepare( $statement );
        
        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->log->LogFatal( 'prepare() ERROR: '.$error_info );
        }
        
        $params = array();
        if ( isset ( $options['params'] ) ) {
            $params = $options['params'];
        }

        if ( $stmt->execute( array_merge( $options['values'], $params ) ) ) {
            $this->log->LogDebug( 'statement successful:', $statement );
            return true;
        }
        else {
            if ( $stmt->errorInfo() ) {
                $error = '['.implode( "] [", $stmt->errorInfo() ).']';
                $this->errors[] = $error;
                $this->lasterror = $error;
                $this->log->LogFatal(
                    $error
                );
            }
        }
    
    }   // end function update()
    
    /**
     *
     *
     *
     *
     **/
    public function delete( $options )
    {
    
        if ( ! isset( $options['tables'] ) ) {
            return NULL;
        }
        
        $this->lasterror = NULL;
        $options['__is_delete'] = true;

        $tables = $this->__map_tables( $options['tables'], $options, true );
        $where  = isset( $options['where'] )
                ? $this->__parse_where( $options['where'] )
                : NULL;
    
        // create the statement
        $statement = "DELETE FROM $tables "
                   . " $where";
                   
        $stmt      = $this->prepare( $statement );

        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->log->LogFatal( 'prepare() ERROR: '.$error_info );
        }

        $params = array();
        if ( isset ( $options['params'] ) ) {
            $params = $options['params'];
        }

        $this->log->LogDebug( 'executing statement: '.$statement, $options['params'] );

        if ( $stmt->execute( $params ) ) {
            $this->log->LogDebug( 'statement successful:', $statement );
            return true;
        }
        else {
            if ( $stmt->errorInfo() ) {
                $error = '['.implode( "] [", $stmt->errorInfo() ).']';
                $this->errors[] = $error;
                $this->lasterror = $error;
                $this->log->LogFatal(
                    $error
                );
            }
        }
                   
    }
    
    
    /**
     *
     *
     *
     *
     **/
    protected function __map_tables( $tables, $options = array(), $is_insert = false ) {
    
        if ( is_array( $tables ) ) {
        
            // join(s) defined?
            if ( isset( $options['join'] ) ) {
                return $this->__parse_join( $tables, $options );
            }
            else {
                foreach ( $tables as $i => $t_name ) {
                    if (
                         ! empty( $this->prefix )
                         &&
                         substr_compare( $t_name, $this->prefix, 0, strlen($this->prefix), true )
                    ) {
                        $t_name = $this->prefix . $t_name;
                    }
                    $tables[$i] = $t_name . ( isset( $options['__is_delete'] ) ? '' : ' as t' . ($i+1) );
                }
                return implode( ', ', $tables );

            }

        }
        else {
            return $this->prefix . $tables . ( ( isset( $options['__is_insert'] ) || isset( $options['__is_delete'] ) ) ? NULL : ' as t1' );
        }
        
        
    }   // end function __map_tables()
    

    /**
     *
     *
     *
     *
     **/
    protected function __parse_where( $where ) {

        // replace conjunctions "'\\1'.strtoupper('\\2').'\\3'",
        $string = $this->replaceConj( $where );

        // replace operators
        $string = $this->replaceOps( $string );

        return ' WHERE '.$string;

    }   // end function __parse_where()
    
    /**
     * parse join statement
     *
     *
     *
     **/
    protected function __parse_join( $tables, $options = array() ) {
    
        $jointype = ' LEFT JOIN ';
        $join     = $options['join'];
        
        $this->log->LogDebug( 'tables: ', $tables );
        $this->log->LogDebug( 'options: ', $options );
    
        if ( ! is_array( $tables ) ) {
            $tables = array( $tables );
        }

        if ( count( $tables ) > 2 && ! is_array( $join ) ) {
            $this->log->LogError(
                '$tables count > 2 and $join is not an array'
            );
            return NULL;
        }
        
        if ( ! is_array( $join ) ) {
            $join = array( $join );
        }
        
        if ( count( $join ) <> ( count( $tables ) - 1 ) ) {
            $this->log->LogFatal(
                'table count <> join count'
            );
            return;
        }
            
        $join_string = $this->prefix . $tables[0] . ' AS t1 ';
            
        foreach ( $join as $index => $item ) {
            $join_string .= $jointype
                         .  $this->prefix.$tables[ $index + 1 ]
                         . ' AS t'.($index+2).' ON '
                         . $item;
        }
        
        $this->log->LogDebug( 'join string before replacing ops/conj: ', $join_string );
        
        $join = $this->replaceConj( $this->replaceOps( $join_string ) );
        
        $this->log->LogDebug( 'returning parsed join: ', $join );
            
        return $join;
        
    }   // end function __parse_join()
    
    /**
     *
     *
     *
     *
     **/
    protected function replaceOps( $string ) {
        $reg_exp = implode( '|', array_keys( $this->operators ) );
        reset( $this->operators );
        $this->log->LogDebug( 'replacing ('.$reg_exp.') from: ', $string );
        return preg_replace( "/(\s{1,})($reg_exp)(\s{1,})/eisx", '" ".$this->operators["\\2"]." "', $string );
    }   // end function replaceOps()
    
    /**
     *
     *
     *
     *
     **/
    protected function replaceConj( $string ) {
         $reg_exp = implode( '|', array_keys( $this->conjunctions ) );
         $this->log->LogDebug( 'replacing ('.$reg_exp.') from: ', $string );
         return preg_replace(
                      "/(\s{1,})($reg_exp)(\s{1,})/eisx",
                      '"\\1".$this->conjunctions["\\2"]."\\3"',
                      $string
                  );
    }
    
    /**
     * initialize database class:
     *
     * - create logger instance
     * - load driver defaults
     * - overwrite defaults with given options (if any)
     * - get valid DSN for DB connection
     *
     **/
    private final function __initialize($options) {

        // create logger instance
        if ( empty( $this->debugDir ) ) {
            $this->debugDir = dirname(__FILE__);
        }
        $this->log
            = new KLogger(
                  $this->debugDir.'/wbDB_'.$this->pdo_driver.'.log' ,
                  $this->debugLevel,
                  true
              );

        // load defaults
        $this->defaults();

        // check options
        foreach (
            array(
                'dsn', 'host', 'port', 'user', 'pass', 'dbname', 'timeout', 'prefix'
            ) as $key
        ) {
            if ( isset( $options[$key] ) ) {
                $this->log->LogDebug( 'setting key ['.$key.'] to ['.$options[$key].']' );
                $this->$key = $options[$key];
            }
        }

        $this->getDSN();

        return true;

    }   // end function __initialize()

}   // end class wbDBBase

?>