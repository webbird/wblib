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
include_once dirname(__FILE__).'/../class.wbValidate.php';
include_once dirname(__FILE__).'/../class.wbSeq.php';
include_once dirname(__FILE__).'/../debug/KLogger.php';

class wbDBBase extends PDO {

    // ----- Debugging -----
    protected      $debugLevel      = KLogger::OFF;
    private static $defaultDebugDir = '/../debug/log';

    protected $dsn                  = NULL;
    protected $host                 = "localhost";
    protected $port                 = 80;
    protected $user                 = "root";
    protected $pass                 = NULL;
    protected $dbname               = "mydb";
    protected $pdo_driver           = 'mysql';
    protected $prefix               = NULL;
    protected $timeout              = 5;
    protected $errors               = array();
    protected $lasterror            = NULL;
    protected $lastInsertID         = NULL;
    protected $_lastStatement 		= NULL;
    private   $val;
    private   $seq;
    
// ----- Known options for constructor -----
    protected $_options = array(
        array(
            'name' => 'dsn',
            'type' => 'PCRE_STRING',
        ),
        array(
            'name' => 'host',
            'type' => 'PCRE_STRING',
        ),
        array(
            'name' => 'port',
            'type' => 'PCRE_INT',
        ),
        array(
            'name' => 'user',
            'type' => 'PCRE_STRING',
        ),
        array(
            'name' => 'pass',
            'type' => 'PCRE_PLAIN',
        ),
        array(
            'name' => 'dbname',
            'type' => 'PCRE_STRING',
        ),
        array(
            'name' => 'timeout',
            'type' => 'PCRE_INT',
        ),
        array(
            'name' => 'prefix',
            'type' => 'PCRE_STRING',
        ),
    );

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
        'and'  => 'AND',
        'AND'  => 'AND',
        'OR'   => 'OR',
        'or'   => 'OR',
        '&&'   => 'AND',
        '\|\|' => 'OR',
        '||'   => 'OR',
    );

    /**
     * catch unknown exceptions on object construction
     **/
    public static function exception_handler( $exception ) {
        // Output the exception details
        if ( is_object( $this ) && is_object($this->log) )
        {
            $this->warn(
                '[wbDatabase Exception] '. $exception->getMessage()
            );
        }
        die( '[wbDatabase Exception] '. $exception->getMessage() );
    }   // end function exception_handler()

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

        // create logger instance
        if ( property_exists( get_class($this), 'debugDir' ) ) {
            if ( empty( $this->debugDir ) ) {
                $this->debugDir = realpath( dirname(__FILE__) );
            }
        }
        else {
            $this->debugDir = realpath( dirname(__FILE__) ).self::$defaultDebugDir;
        }
        
        if ( isset($options['debug']) && $options['debug'] === true ) {
            $this->debugLevel = KLogger::DEBUG;
		}

        $this->log
            = new KLogger(
                  $this->debugDir.'/'.get_class($this).'.log' ,
                  $this->debugLevel,
                  true
              );
        $this->val
            = new wbValidate();
            
		$this->seq
		    = new wbSeq();

        $this->__initialize($options);
        
        $this->log->LogDebug(
            'connection dsn: '.$this->dsn
        );
        
        // get driver options
        $driver_options = $this->getDriverOptions();

        // Temporarily change the PHP exception handler while we ...
        set_exception_handler(array(__CLASS__, 'exception_handler'));

        // ... create a PDO object
        if ( $this->pass == '' ) {
            parent::__construct( $this->dsn, $this->user, $driver_options );
        }
        else {
        parent::__construct( $this->dsn, $this->user, $this->pass, $driver_options );
        }

        // Change the exception handler back to whatever it was before
        restore_exception_handler();

        //$this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
        
    }   // end function __construct()
    
/*******************************************************************************
 * ACCESSOR FUNCTIONS
 ******************************************************************************/

    /**
     * Accessor to last error
     *
     * @access public
     * @param  boolean $fullstack - return the full error stack; default false
     * @return string
     *
     **/
    public function getError( $fullstack = false ) {
        if ( $fullstack ) {
            return $this->errors;
		}
        return $this->lasterror;
    }   // end function getError()

    /**
     * Accessor to PDO::lastInsertId
     *
     * @access public
     * @return mixed
     *
     **/
    public function getLastInsertID() {
        return $this->lastInsertID;
    }   // end function getLastInsertID()
    
    /**
     * Accessor to last executed statement; useful for debugging
     *
     * @access public
     * @return string
     *
     **/
	public function getLastStatement() {
	    return $this->_lastStatement;
	}   // end function getLastStatement()
    
    /**
     * Function prototype; override this in your driver
     *
     * @access public
     * @return array
     *
     **/
    public function getDriverOptions() {
        return array();
    }   // end function getDriverOptions()


    /**
     * Create valid DSN and store it for later use
     *
     * @access public
     * @return void
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
	public function showTables() {
	    $data   = $this->query('SHOW TABLES');
	    $tables = array();
		while( $result = $data->fetch() ) {
     		$tables[] = $result[0];
		}
		return $tables;
	}   // end function showTables()
    
    /**
     * Check if last action set lasterror
     *
     * @access public
     * @return boolean
     *
     **/
    public function isError() {
        return isset( $this->lasterror ) ? true : false;
    }   // end function isError()

/*******************************************************************************
 * CONVENIENCE FUNCTIONS
 ******************************************************************************/

    /**
     * Get the max value of a given field
     *
     * @access public
     * @param  string   $fieldname - field to check
     * @param  array    $options   - additional options (where-Statement, for example)
     * @return mixed
     *
     **/
    public function max( $fieldname, $options = array() ) {
        $data = $this->search(
            array_merge(
                $options,
                array(
                    'limit'  => 1,
                    'fields' => "max($fieldname) as maximum",
                )
            )
        );
        if ( isset( $data ) && is_array( $data ) && count( $data ) > 0 ) {
            return $data[0]['maximum'];
        }
        return NULL;
    }   // end function max()
    
    /**
     * Get the min value of a given field
     *
     * @access public
     * @param  string   $fieldname - field to check
     * @param  array    $options   - additional options (where-Statement, for example)
     * @return mixed
     *
     **/
    public function min( $fieldname, $options = array() ) {
        $data = $this->search(
            array_merge(
                $options,
                array(
                    'limit'  => 1,
                    'fields' => "min($fieldname) as minimum",
                )
            )
        );
        if ( isset( $data ) && is_array( $data ) && count( $data ) > 0 ) {
            return $data[0]['minimum'];
        }
        return NULL;
    }   // end function min()

/*******************************************************************************
 * CORE FUNCTIONS
 ******************************************************************************/

    /**
     * Search the DB
     *
     * @access public
     * @param  array   $options - see below
     * @return mixed   returns false if an error occured, or an array of rows
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
        
        $this->__setError( NULL );

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
                ? $this->__get_params( $options['params'] )
                : NULL;
                
        $group  = isset( $options['group_by'] )
                ? 'GROUP BY '.$options['group_by']
                : NULL;
                
		// any errors so far?
		if ( $this->isError() ) {
		    // let the caller handle the error, just return false here
		    return false;
		}

        // create the statement
        $statement = "SELECT "
                   . (
                       is_array( $fields )
                       ? implode( ', ', $fields )
                       : $fields
                     )
                   . " FROM $tables $where $group $order $limit";

		$this->_lastStatement = wbDatabase::interpolateQuery($statement,$params);
        $this->log->LogDebug( $this->_lastStatement );

        $stmt      = $this->prepare( $statement );
        
        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->__setError( 'prepare() ERROR: '.$error_info, 'fatal' );
            return false;
        }

        if ( $stmt->execute( $params ) ) {
            $this->log->LogDebug( 'returning ['.$stmt->rowCount().'] results' );
            return $stmt->fetchAll( PDO::FETCH_ASSOC );
        }
        else {
            if ( $stmt->errorInfo() ) {
                $error = '['.implode( "] [", $stmt->errorInfo() ).']';
            }
            $this->__setError( $error, 'fatal' );
            return false;
        }

    }   // end function search ()
    
    /**
     * inserts a new line; return false on error, true on success
     *
     * Use isError() and getError() to check for errors
     *
     * @access public
     * @param  array  $options
     * @return mixed
     *
     **/
    public function insert( $options ) {
    
        if ( ! isset( $options['tables'] ) || ! isset( $options['values'] ) ) {
            return NULL;
        }
        
        // reset error
        $this->__setError( NULL );
        
        $do     = isset( $options['do'] )
                ? $options['do']
                : 'INSERT';
                
        $options['__is_insert'] = true;

        $tables = $this->__map_tables( $options['tables'], $options );
        $values = array();
        $fields = NULL;

        if ( isset( $options['values'] ) ) {
            if ( ! is_array( $options['values'] ) ) {
                $options['values'] = array( $options['values'] );
            }
            foreach ( $options['values'] as $v ) {
                $values[] = '?';
            }
        }

        if ( isset( $options['fields'] ) ) {
            if ( ! is_array( $options['fields'] ) ) {
                $options['fields'] = array( $options['fields'] );
            }
            $fields = '( `'
                    . implode( '`, `', $options['fields'] )
                    . '` )';
        }

        // create the statement
        $statement = "$do INTO $tables $fields"
                   . " VALUES ( "
                   . implode( ', ', $values )
                   . " )";
                       
        $stmt      = $this->prepare( $statement );
        $params    = $this->__get_params($options['values']);
        
		// any errors so far?
		if ( $this->isError() ) {
		    // let the caller handle the error, just return false here
		    return false;
		}

        $this->_lastStatement = wbDatabase::interpolateQuery($statement,$params);
        $this->log->LogDebug( $this->_lastStatement );

        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->__setError( 'prepare() ERROR: '.$error_info, 'fatal' );
            return false;
        }
        
        if ( $stmt->execute( $params ) ) {
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
                $this->__setError( $error, 'fatal' );
                return false;
            }
        }
    }   // end function insert()
    
    /**
     * replace a row; in fact, this is only a wrapper to insert()
     **/
    public function replace( $options ) {
        $this->log->LogDebug( '', $options );
        $options['do'] = 'REPLACE';
        return $this->insert( $options );
    }   // end function replace()
    
    /**
     * update a row
     *
     *
     *
     **/
    public function update( $options ) {
    
        if ( ! isset( $options['tables'] ) || ! isset( $options['values'] ) ) {
            return NULL;
        }

        $this->__setError( NULL );
        
        $tables = $this->__map_tables( $options['tables'], $options, true );
        $where  = isset( $options['where'] )
                ? $this->__parse_where( $options['where'] )
                : NULL;
                
		// any errors so far?
		if ( $this->isError() ) {
		    // let the caller handle the error, just return false here
		    return false;
		}

        $carr = array();
        if ( isset( $options['fields'] ) && ! is_array( $options['fields'] ) ) {
            $options['fields'] = array( $options['fields'] );
        }
        foreach ( $options['fields'] as $key ) {
            $carr[] = "$key = ?";
        }
        
        // create the statement
        $statement = "UPDATE $tables SET "
                   . implode( ', ', $carr )
                   . " $where";

        return $this->__prepare_and_execute( $statement, $options );
        
    }   // end function update()
    
    /**
     *
     *
     *
     *
     **/
    public function delete( $options ) {
    
        if ( ! isset( $options['tables'] ) ) {
            return NULL;
        }
        
        $this->__setError( NULL );
        $options['__is_delete'] = true;

        $tables = $this->__map_tables( $options['tables'], $options, true );
        $where  = isset( $options['where'] )
                ? $this->__parse_where( $options['where'] )
                : NULL;
                
		// any errors so far?
		if ( $this->isError() ) {
		    // let the caller handle the error, just return false here
		    return false;
		}

        // create the statement
        $statement = "DELETE FROM $tables "
                   . " $where";
                   
        return $this->__prepare_and_execute( $statement, $options );
                   
    }   // end function delete()
    
    /**
     * Truncate table
     *
     * @access public
     * @param  array   $options
     * @return boolean
     *
     **/
    public function truncate( $options ) {

        if ( ! isset( $options['tables'] ) ) {
            return NULL;
        }

        $this->__setError( NULL );
        $options['__is_delete'] = true;
        
        $tables = $this->__map_tables( $options['tables'], $options, true );
        $where  = isset( $options['where'] )
                ? $this->__parse_where( $options['where'] )
                : NULL;

		// any errors so far?
		if ( $this->isError() ) {
		    // let the caller handle the error, just return false here
		    return false;
		}

        // create the statement
        $statement = "TRUNCATE $tables "
                   . " $where";

        return $this->__prepare_and_execute( $statement, $options );
        
    }   // end function truncate()
    
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


/*******************************************************************************
 * PROTECTED / PRIVATE FUNCTIONS
 ******************************************************************************/
 
    /**
     * checks params for SQL injection code; uses __setError() to log any
     * positive matches
     *
     * @access protected
     * @param  array     $params - params to check
     * @return array
     *
     **/
    protected function __get_params( $params ) {
        foreach ( $params as $i => $param ) {
			if ( ! $this->seq->detectSQLInjection( $this->quote($param) ) ) {
				// no escaping here; we're using PDO, remember?
			    $params[$i] = $param;
			}
			else {
				$this->__setError('SQL INJECTION DETECTED!', 'fatal');
				return NULL;
			}
        }
        $this->log->LogDebug( 'PARAMS:', $params );
        return $params;
    }   // end function __get_params()

    /**
     * adds prefix to table names, handles joins
     *
     * @access protected
     * @param  mixed     $tables    - array of tables or single table name
     * @param  array     $options
     * @return string
     *
     **/
    protected function __map_tables( $tables, $options = array() ) {
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
     * prepares a statement and calls execute()
     *
     * @access protected
     * @param  string    $statement - the statement to execute
     * @param  array     $options   - any additional options
     * @return boolean
     *
     **/
    protected function __prepare_and_execute( $statement, $options ) {
    
        $this->log->LogDebug( 'preparing statement: '.$statement );

        $stmt = $this->prepare( $statement );

        if ( ! is_object( $stmt ) ) {
            $error_info = '['.implode( "] [", $this->errorInfo() ).']';
            $this->__setError( 'prepare() ERROR: '.$error_info, 'fatal' );
            return false;
        }

        $params = array();
        if ( isset ( $options['params'] ) ) {
            $params = $options['params'];
        }

        if ( isset( $options['values'] ) ) {
            if ( ! is_array( $options['values'] ) ) {
                $options['values'] = array($options['values']);
            }
            $execute_params = array_merge( $options['values'], $params );
        }
        else {
            $execute_params = $params;
        }
        
        $this->_lastStatement = wbDatabase::interpolateQuery($statement,$execute_params);
        $this->log->LogDebug( $this->_lastStatement );

        if ( $stmt->execute( $execute_params ) ) {
            $this->log->LogDebug( 'statement successful:', $statement );
            return true;
        }
        else {
            if ( $stmt->errorInfo() ) {
                $error = '['.implode( "] [", $stmt->errorInfo() ).']';
                $this->__setError( $error, 'fatal' );
            }
            return false;
        }
        
    }   // end function __prepare_and_execute()
    
    /**
     * parse where conditions
     *
     * @access protected
     * @param  mixed     $where - array or scalar
     * @return mixed     parsed WHERE statement or NULL
     *
     **/
    protected function __parse_where( $where ) {
        $this->log->LogDebug( '', $where );
        if ( is_array( $where ) ) {
            $where = implode( ' AND ', $where );
        }
        // replace conjunctions
        $string = $this->__replaceConj( $where );
        // replace operators
        $string = $this->__replaceOps( $string );
        if ( ! empty( $string ) ) {
            $this->log->LogDebug( $string );
            return ' WHERE '.$string;
        }
        return NULL;
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
            $this->__setError( '$tables count > 2 and $join is not an array' );
            return NULL;
        }
        
        if ( ! is_array( $join ) ) {
            $join = array( $join );
        }
        
        if ( count( $join ) <> ( count( $tables ) - 1 ) ) {
            $this->__setError( 'table count <> join count', 'fatal' );
            return;
        }
            
        $join_string = $this->prefix . $tables[0] . ' AS t1 ';
            
        foreach ( $join as $index => $item ) {
            $join_string .= ( isset($options['jointype']) ? $options['jointype'] : $jointype )
                         .  $this->prefix.$tables[ $index + 1 ]
                         . ' AS t'.($index+2).' ON '
                         . $item;
        }
        
        $this->log->LogDebug( 'join string before replacing ops/conj: ', $join_string );
        
        $join = $this->__replaceConj( $this->__replaceOps( $join_string ) );
        
        $this->log->LogDebug( 'returning parsed join: ', $join );
            
        return $join;
        
    }   // end function __parse_join()
    
    /**
     * Replace operators in string
     *
     * @access protected
     * @param  string    $string - string to convert
     * @return string
     *
     **/
    protected function __replaceOps( $string ) {
        $reg_exp = implode( '|', array_keys( $this->operators ) );
        reset( $this->operators );
        $this->log->LogDebug( 'replacing ('.$reg_exp.') from: ', $string );
        return preg_replace( "/(\s{1,})($reg_exp)(\s{1,})/eisx", '" ".$this->operators["\\2"]." "', $string );
    }   // end function __replaceOps()
    
    /**
     * Replace conjunctions in string
     *
     * @access protected
     * @param  string    $string - string to convert
     * @return string
     *
     **/
    protected function __replaceConj( $string ) {
         $reg_exp = implode( '|', array_keys( $this->conjunctions ) );
         $this->log->LogDebug( 'replacing ('.$reg_exp.') from: ', $string );
         return @preg_replace(
                      "/(\s{1,})($reg_exp)(\s{1,})/eisx",
                      '"\\1".$this->conjunctions["\\2"]."\\3"',
                      $string
                  );
    }   // end function __replaceConj()
    
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
        // load defaults
        $this->defaults();
        // check options
        foreach ( $this->_options as $opt ) {
            $key  = $opt['name'];
            $type = $opt['type'];
            if ( isset( $options[$key] ) && ! empty( $options[$key] ) ) {
                // check value
                if ( $this->val->validate( $type, $options[$key] ) === true ) {
                    $this->log->LogDebug( 'setting key ['.$key.'] to ['. ( $key == 'pass' ? '*****' : $options[$key] ).']' );
                    $this->$key = $options[$key];
                }
                else {
                    $this->log->LogFatal( 'Invalid value ['.$options[$key].'] for key ['.$key.'] ('.$type.')' );
                }
            }
        }
        $this->getDSN();
        return true;
    }   // end function __initialize()
    
    /**
     * put error on error stack and set $lasterror
     *
     * @access private
     * @return void
     *
     **/
    private function __setError( $error, $level = 'error' ) {
        $this->lasterror = $error;
        // push onto error stack
        if ( $error != NULL ) {
        	$this->errors[]  = $error;
		}
        $log_method = 'LogError';
        if ( isset($level) && $level == 'fatal' ) {
			$log_method = 'LogFatal';
		}
        $this->log->$log_method( $error );
    }   // end function __setError()


}   // end class wbDBBase

?>