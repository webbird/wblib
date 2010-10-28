<?php

/**
  Module developed for the Open Source Content Management System Website Baker (http://websitebaker.org)
  Copyright (C) 2009, Bianka Martinovic
  Contact me: blackbird(at)webbird.de, http://www.webbird.de/

  This module is free software. You can redistribute it and/or modify it 
  under the terms of the GNU General Public License  - version 2 or later, 
  as published by the Free Software Foundation: http://www.gnu.org/licenses/gpl.html.

  This module is distributed in the hope that it will be useful, 
  but WITHOUT ANY WARRANTY; without even the implied warranty of 
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
  GNU General Public License for more details.
**/

class wbHolidays extends wbBase {

    // ----- Debugging -----
    protected      $debugLevel     = KLOGGER::OFF;
    #protected      $debugLevel     = KLOGGER::DEBUG;

    protected      $_config        = array(
        'show_holidays' => true,
        'country'       => 'de_DE',
    );

    static  $HOLIDAYS;
    
    /**
     * constructor
     *
     * @access public
     * @param  integer  $year    - year
     * @param  string   $country - country shortcut (optional; Default de_de)
     *
     **/
    function __construct ( $options = array() ) {
        parent::__construct($options);
        $h_file = dirname( __FILE__ ).'/wbHolidays/'.$this->_config['country'].'.php';
        if ( file_exists( $h_file ) ) {
            $this->log()->LogDebug( 'loading holidays for country: '.$this->_config['country'] );
            include_once $h_file;
            $this->__CalcFeastDays();
        }
        else {
            $this->log()->LogDebug( 'no holidays found for country: '.$this->_config['country'] );
            wbHolidays::$HOLIDAYS = array();
        }
    }   // end function wbHolidays ()
    
    /**
     * check if given date is a holiday
     *
     * @access public
     * @param  integer $m - month
     * @param  integer $d - day
     * @return boolean
     **/
    function isHoliday ( $m, $d ) {
        if ( $this->_config['show_holidays'] ) {
            if ( isset( wbHolidays::$HOLIDAYS[$m] ) && isset( wbHolidays::$HOLIDAYS[$m][$d] ) ) {
                return true;
            }
        }
        return false;
    }   // end function isHoliday ()
    
    /**
     *
     *
     *
     *
     **/
    function getHolidaysForMonth( $month ) {
        if ( isset( wbHolidays::$HOLIDAYS[$month] ) ) {
            return wbHolidays::$HOLIDAYS[$month];
        }
        return array();
    }   // end function getHolidaysForMonth()
    
    /**
     * get holidays for a date
     *
     * @access public
     * @param  integer $m - month
     * @param  integer $d - day
     *
     **/
    function getHolidayForDate( $m, $d ) {
        if ( $this->_config['show_holidays'] ) {
            if ( isset( wbHolidays::$HOLIDAYS[$m] ) && isset( wbHolidays::$HOLIDAYS[$m][$d] ) ) {
                return wbHolidays::$HOLIDAYS[$m][$d];
            }
        }
        return array();
    }
    
    /**
     *
     *
     *
     *
     **/
    private function __CalcFeastDays ( $y = NULL ) {
    
        // easter
        if ( function_exists("easter_days") ) {
    
            $offsets = array( -48, -47, -46, -7, -3, -2, -1, 1, 39, 49, 50, 60 );
            $short   = array(
                           'Rosenmontag',
                           'Karnevalsdienstag',
                           'Aschermittwoch',
                           'Palmsonntag',
                           'Gr&uuml;ndonnerstag',
                           'Karfreitag',
                           'Ostersamstag',
                           'Ostermontag',
                           'Christi Himmelfahrt',
                           'Pfingstsonntag',
                           'Pfingstmontag',
                           'Fronleichnam'
                       );
                       
            // # of days after 21.03. = easter sunday
            $easterSunday = easter_days( $y );

            foreach ( $offsets as $i => $offset ) {
                $time = mktime( 0, 0, 0, 3, 21 + ( $easterSunday + $offset ), $y );
                wbHolidays::$HOLIDAYS[ date( 'n', $time ) ][ date('j', $time ) ][] = $short[$i];
            }

        }
        
        # Das Datum des Buß- und Bettages ist definiert als „Der Mittwoch vor
        # dem letzten Sonntag nach Trinitatis“ (rechnerisch gleichbedeutend mit:
        # „Der letzte Mittwoch vor dem 23. November“)
        $last = strtotime("last Wednesday", mktime( 0, 0, 0, 11, 23,$y ) );
        wbHolidays::$HOLIDAYS[ date( 'n', $last ) ][ date('j', $last ) ][] = 'Bu&szlig;- und Bettag';
        
        # Volkstrauertag = 2 Sonntage vor dem 1. Advent
        # berechne 4. Advent
        $adv = strtotime( "last Sunday", mktime( 0, 0, 0, 12, 24, $y ) );
        # 6 Wochen vorher
        $last = strtotime( "-5 week", $adv );
        wbHolidays::$HOLIDAYS[ date( 'n', $last ) ][ date('j', $last ) ][] = 'Volkstrauertag';
        
        # Totensonntag = 1 Sonntag vor dem 1. Advent
        $last = strtotime( "+1 week", $last );
        wbHolidays::$HOLIDAYS[ date( 'n', $last ) ][ date('j', $last ) ][] = 'Totensonntag';
        

    }   // end function __CalcFeastDays ()

}   // --- end class wbHolidays ---

?>
