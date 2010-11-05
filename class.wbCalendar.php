<?php

/**

  Calendar class

  Copyright (C) 2010, Bianka Martinovic
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

require_once dirname( __FILE__ ).'/class.wbBase.php';
require_once dirname( __FILE__ ).'/class.wbTemplate.php';
require_once dirname( __FILE__ ).'/class.wbI18n.php';
require_once dirname( __FILE__ ).'/class.wbHolidays.php';

class wbCalendar extends wbBase {

    // ----- Debugging -----
    protected      $debugLevel     = KLOGGER::OFF;
    #protected      $debugLevel     = KLOGGER::DEBUG;
    
    protected      $_config        = array(
        'day_name_length' => 3,
        'first_day'       => 0, // sunday
        'locale'          => 'de_DE',
        'rows'            => 1,
        'show_holidays'   => true,
    );
    
    private        $_labels        = array();
    private        $_events        = array();
    
    private        $hdays;
    
    /**
     * constructor
     **/
    function __construct ( $options = array() ) {
        parent::__construct($options);
        $this->tpl = new wbTemplate();
        $this->tpl->setPath( realpath( dirname(__FILE__) ).'/wbCalendar/templates' );
        $this->lang = new wbI18n();
    }   // end function __construct()
    
    /**
     * set row label
     *
     * @access public
     * @param  integer  $row - row number (numeric)
     * @param  string   $label - text
     *
     **/
    public function setLabel( $row, $label ) {
        $this->_labels[$row]['label'] = $label;
    }   // end function setLabel
    
    /**
     * get the labels
     *
     * @access public
     * 
     **/
    public function getLabels() {
        // make sure we have a label for each row
        for ( $i=1; $i<=$this->_config['rows']; $i++ ) {
            if ( ! isset( $this->_labels[$i] ) ) {
                $this->_labels[$i]['label'] = '';
            }
            if ( ! isset( $this->_labels[$i]['label'] ) ) {
                $this->_labels[$i]['label'] = '';
            }
        }
        return $this->_labels;
    }   // end function getLabels()
    
    /**
     * set current year
     *
     * @access public
     * @param  integer  $year - year number (numeric)
     * @return NULL
     *
     **/
    public function setYear( $year ) {
        if ( ! is_numeric( $year ) ) {
            $month = date( 'Y' );
        }
        $this->_config['current_year'] = $year;
    }   // end function setMonth()
    
    /**
     * set current month
     *
     * @access public
     * @param  integer  $month - month number (1-12)
     * @return NULL
     *
     **/
    public function setMonth( $month ) {
        if ( ! is_numeric( $month ) || $month < 1 || $month > 12 ) {
            $month = date( 'n' );
        }
        $this->_config['current_month'] = $month;
    }   // end function setMonth()
    
    /**
     * set current day
     *
     * @access public
     * @param  integer  $day - day number (1-31)
     * @return NULL
     *
     **/
    public function setDay( $day ) {
    
        if ( ! is_numeric($day) || $day < 1 || $day > 31 ) {
            $day = date('d');
        }

        $month              = $this->currentMonth();
        $last_day_of_month  = strftime( "%d", mktime(0,0,0,$month+1,0,$year) );

        if ( $day > $last_day_of_month ) {
            $this->printError(
                $this->lang->translate(
                    'Invalid day ['.$day.'] (month ['.$month.'] has ['.$last_day_of_month.'] days)'
                )
            );
        }
        
        $this->_config['current_day'] = $day;
        
    }   // end function setDay()
    
    /**
     *
     *
     *
     *
     **/
    public function currentDay() {
        if ( isset( $this->_config['current_day'] ) ) {
            return $this->_config['current_day'];
        }
        else {
            return date( 'd' );
        }
    }   // end function currentMonth()
    
    /**
     *
     *
     *
     *
     **/
    public function currentMonth() {
        if ( isset( $this->_config['current_month'] ) ) {
            return $this->_config['current_month'];
        }
        else {
            return date( 'n' );
        }
    }   // end function currentMonth()
    
    /**
     *
     *
     *
     *
     **/
    public function currentYear() {
        if ( isset( $this->_config['current_year'] ) ) {
            return $this->_config['current_year'];
        }
        else {
            return date( 'Y' );
        }
    }   // end function currentYear()
    
    /**
     *
     *
     *
     *
     **/
    public function day ( $options = array() ) {
    
        $month              = isset( $options['month'] )
                            ? $options['month']
                            : $this->currentMonth();

        $year               = isset( $options['year'] )
                            ? $options['year']
                            : $this->currentYear();

        $day                = isset( $options['day'] )
                            ? $options['day']
                            : $this->currentDay();
                            
        // look for events given as function param
        if ( isset( $options['events'] ) && is_array( $options['events'] ) && count( $options['events'] ) > 0 ) {
            foreach ( $options['events'] as $i => $item ) {
                $this->addEvent( $item );
            }
        }
        
        $events = $this->getEvents( $year, $month, $day );
        $rows   = array();
        
        if ( is_array($events) && count($events) > 0 ) {
            for ( $i=0; $i<$this->_config['rows']; $i++ ) {
                $rows[$i]['events'] = $events[$i];
            }
        }
        
        $data   = array(
                      'day'    => $day,
                      'rows'   => $rows
                  );
        
        if ( $this->hdays->isHoliday( $month, $day ) ) {
            $event = $this->hdays->getHolidayForDate( $month, $day );
            $data['spanclass'] = 'wbcal_holiday';
            $data['hovertext'] = implode( "<br />\n", $event );
        }
        
        return
            $this->tpl->getTemplate(
                'table_month_day.tpl',
                $data
            );
        
    }   // end function day
    
    /**
     * generate a month sheet
     *
     *
     *
     **/
    public function month ( $options = array() ) {
    
        $month              = isset( $options['month'] )
                            ? $options['month']
                            : $this->currentMonth();
                            
        $year               = isset( $options['year'] )
                            ? $options['year']
                            : $this->currentYear();
                            
        $last_day_of_month  = strftime( "%d", mktime(0,0,0,$month+1,0,$year) );
        $first_day_of_month = strftime( "%w", mktime(0,0,0,$month,1,$year ) );
        $weeknumber         = date( 'W', mktime(0,0,1,$month,1,$year) );
        $today              = getdate();
        $line               = array();
        $count              = 0;
        $content            = NULL;
        $cols               = 8;
        $labels             = $this->getLabels();
        
        // do we have some labels?
        if ( count( $labels ) > 0 ) {
            $cols = 9;
        }
        
        // look for events given as function param
        if ( isset( $options['events'] ) && is_array( $options['events'] ) && count( $options['events'] ) > 0 ) {
            foreach ( $options['events'] as $i => $item ) {
                $this->addEvent( $item );
            }
        }
        
        // add holidays
        if ( $this->_config['show_holidays'] === true ) {
            $this->hdays = new wbHolidays( $year, $this->_config['locale'] );
            $hdays = $this->hdays->getHolidaysForMonth( $month );
        }

        // prepadding
        if ( $first_day_of_month >= 1 ) {
            for ( $i=1; $i<$first_day_of_month; $i++ ) {
                $line['days'][] =
                    array(
                        'tdclass'  => 'wbcal-pre',
                        'dayclass' => '',
                        'day'      => ''
                    );
            }
            $count = $count + $first_day_of_month - 1;
        }

        // fill weeks
        for ( $day=1; $day<=$last_day_of_month; $day++ ) {
        
            if ( $count%7 == 0 && $count > 0 ) {  // next line
                $content .= $this->tpl->getTemplate(
                                'table_month_week.tpl',
                                array_merge(
                                    array(
                                        'weeknumber' => date( 'W', mktime(0,0,1,$month,$day+1,$year)),
                                        'labels'     => $this->getLabels()
                                    ),
                                    $line
                                )
                            );
                $line = array();
            }

            $tdclass = array();
            $events  = array();

            if ( $day == $this->_config['first_day'] ) {
                $tdclass[] = 'wbcal_fd';
            }
            if ( $this->hasEvents( $day, $month, $year ) ) {
                $tdclass[] = 'wbcal_event';
            }

            $line['days'][] =
                array(
                    'tdclass'  => implode( ' ', $tdclass ),
                    'dayclass' => '',
                    'day'      => $this->day( array( 'day' => $day ) )
                );
            
            $count++;

        }
        
        // post-padding
        if ( $count%7 != 0 && $count > 0 )
        {
            $colspan = ( $cols - 1 ) - strftime( "%w", mktime(0,0,0,$month,$last_day_of_month,$year ) );
            if ( $colspan >= 1 ) {
                for( $i=1; $i<$colspan; $i++ ) {
                    $line['days'][] =
                    array(
                        'tdclass'  => 'wbcal-post',
                        'dayclass' => '',
                        'day'      => ''
                    );
                }
            }
        }
        
        // add last line
        $content .= $this->tpl->getTemplate(
                        'table_month_week.tpl',
                        array_merge(
                                    array(
                                        'weeknumber' => date( 'W', mktime(0,0,1,$month,$day+1,$year)),
                                        'labels'     => $this->getLabels()
                                    ),
                                    $line
                                )
                    );

        return
            $this->tpl->getTemplate(
                'table_month.tpl',
                array(
                    'labels'   => ( count( $labels ) > 0 ) ? 1 : NULL,
                    'cols'     => $cols,
                    'month'    => $this->getMonthName( $month ).' '.$year,
                    'daynames' => $this->_dayNames(),
                    'sheet'    => $content
                )
            );

    }   // end function month()
    
    /**
     *
     *
     *
     *
     **/
    public function getEvents( $year, $month, $day ) {
        if ( array_key_exists( $year, $this->_events ) ) {
            if ( array_key_exists( $month, $this->_events[$year] ) ) {
                if ( array_key_exists( $day, $this->_events[$year][$month] ) ) {
                    // make sure we have an entry for each row
                    //return $this->_events[$year][$month][$day];
                    for ( $i=0; $i<$this->_config['rows']; $i++ ) {
                        if ( ! isset( $this->_events[$year][$month][$day][$i] ) ) {
                            $this->_events[$year][$month][$day][$i] = NULL;
                        }
                    }
                    return $this->_events[$year][$month][$day];
                }
            }
        }
        return false;
    }   // end function getEvents()
    
    /**
     *
     *
     *
     *
     **/
    public function addEvent( $options ) {
    
        if ( ! is_array( $options ) ) {
            $this->printError(
                $this->lang->translate(
                    'Invalid call to addEvent(): Missing event data!'
                )
            );
        }
        
        $year  = isset( $options['year'] )
               ? $options['year']
               : $this->currentYear();
              
        $month = isset( $options['month'] )
               ? $options['month']
               : $this->currentMonth();
               
        $day   = isset( $options['day'] )
               ? $options['day']
               : $this->currentDay();
               
        $row   = isset( $options['row'] )
               ? $options['row']
               : 1;
        
        $this->_events[$year][$month][$day][$row][] = $options;
        
    }   // end function addEvent()
    
    /**
     *
     *
     *
     *
     **/
    public function hasEvents ( $day = NULL, $month = NULL, $year = NULL ) {
        if ( array_key_exists( $year, $this->_events ) ) {
            if ( array_key_exists( $month, $this->_events[$year] ) ) {
                if ( array_key_exists( $day, $this->_events[$year][$month] ) ) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     *
     *
     *
     *
     **/
    public function getMonthName( $month ) {

        $oldlocale = NULL;

        if ( isset( $this->_config['locale'] ) ) {
            // store old locale
            $oldlocale = setlocale(LC_TIME, NULL); #save current locale
            setlocale( LC_TIME, $this->_config['locale'] );
        }
        $month_name = gmstrftime('%B',gmmktime(0,0,0,$month,1,2000));
        
        if ( isset( $oldlocale ) ) {
            // reset locale
            setlocale(LC_TIME, $oldlocale);
        }

        return $month_name;
        
    }   // end function getMonthName()
    
    /**
     * generate all the day names according to the current locale
     *
     *
     *
     **/
    private function _dayNames() {
    
        $day_names = array();
        $oldlocale = NULL;

        if ( isset( $this->_config['locale'] ) ) {
            // store old locale
            $oldlocale = setlocale(LC_TIME, NULL); #save current locale
            setlocale( LC_TIME, $this->_config['locale'] );
        }

        // January 4, 1970 was a Sunday
        for( $n=0,$t=(3+$this->_config['first_day'])*86400; $n<7; $n++,$t+=86400 ) {
            $d = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name
            $d = $this->_config['day_name_length'] < 4
               ? substr($d,0,$this->_config['day_name_length'])
               : $d;
            $day_names[$n]['name'] = htmlentities( $d );
        }
        
        if ( isset( $oldlocale ) ) {
            // reset locale
            setlocale(LC_TIME, $oldlocale);
        }

        return $day_names;
        
    }   // function _dayNames()

}