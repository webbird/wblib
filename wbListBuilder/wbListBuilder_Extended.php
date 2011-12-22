<?php

/**

  List builder class

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

require_once dirname( __FILE__ ).'/../class.wbListBuilder.php';

if ( ! class_exists( 'wbListBuilder_Extended', false ) ) {

    class wbListBuilder_Extended extends wbListBuilder {
    
        private static $specialconfig = array(
            'table_open'  => '<table class="%%">',
            'table_close' => '</table>',
            'row_open'    => '<tr class="%%{{lastcss}}">',
            'row_close'   => '</tr>',
            'cell_open'   => '<td class="%%">',
            'cell_close'  => '</td>',
            'td_keys'     => array(),
			'current_tr_class'      => 'current_item',
            'first_tr_class'        => 'first_item',
            'has_child_tr_class'    => 'has_child',
            'is_open_tr_class'      => 'is_open',
            'last_tr_class'         => 'last_item',
            'td_class'              => 'item',
        );
        
        public function __construct($options) {
            foreach( self::$specialconfig as $key => $value ) {
                if ( ! isset($options[$key]) ) {
                    $options[$key] = $value;
				}
			}
			parent::__construct($options);
        }

		public function buildTable( $list, $options = array() ) {

		    if ( empty($list) || ! is_array( $list ) ) {
                $this->log()->LogDebug( 'no list items to show' );
                return;
            }

            $hidden     = ( isset($this->_config['__hidden_key']) ? $this->_config['__hidden_key'] : '' );
            $p_key      = $this->_config['__parent_key'];
            $id_key     = $this->_config['__id_key'];
            $title_key  = $this->_config['__title_key'];
            $level_key  = $this->_config['__level_key'];
            $root_id    = ( isset($options['root_id']) ? $options['root_id'] : 0    );
            $space      = ( isset($options['space'])   ? $options['space']   : "\t" );

            if ( isset( $options['open_nodes'] ) ) {
                $this->open_nodes = $options['open_nodes'];
            }
            $this->log()->LogDebug( 'open nodes:', $this->open_nodes );
            $this->log()->LogDebug( 'list:'      , $list             );
            $this->log()->LogDebug( 'root_id: '.$root_id );

            // create a list of children for each page
            foreach ( $list as $item ) {
                if ( isset($item[$hidden]) ) {
                    continue;
                }
                $children[$item[$p_key]][] = $item;
            }
            $this->log()->LogDebug( 'children list:', $children );
            
            // loop will be false if the root has no children (i.e., an empty menu!)
            $loop         = !empty( $children[$root_id] );
            $out          = array();
            $parent_stack = array();
            $parent       = $root_id;
            $isfirst      = 1;

            while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $root_id ) ) )
            {
                if ( $option === false ) // no more children
                {
                    $parent = array_pop( $parent_stack );
                }
                // current page has children (i.e. page_id is in $children array)
                elseif ( ! empty( $children[ $option['value'][$id_key] ] ) )
                {
                    $row  = array();
                    $tab  = str_repeat( $space, ( count( $parent_stack ) + 1 ) * 2 - 1 );
                    $tab2 = str_repeat( $space, ( count( $parent_stack ) + 2 ) * 2 - 1 );
                    //getListItemCSS ( $item, $isfirst = false, $last_id = NULL, $has_children = false, $for = 'li' )
                    $css = $this->getListItemCSS( $option['value'], $isfirst, $this->last_id, true, 'tr' );
                    foreach ( $this->_config['td_keys'] as $key ) {
                        $row[] = $this->cellStart( NULL, $tab2 )
							   . $option['value'][$key]
							   . $this->cellEnd();
                    }
                    $out[] = $this->rowStart( $css, $tab )
						   . implode( "\n", $row )
						   . '</tr>'."\n";
                    array_push( $parent_stack, $option['value'][$p_key] );
                    $parent = $option['value'][$id_key];
                }
                // handle leaf
                else {
                    $row  = array();
                    $tab  = str_repeat( $space, ( count( $parent_stack ) + 1 ) * 2 - 1 );
                    $tab2 = str_repeat( $space, ( count( $parent_stack ) + 2 ) * 2 - 1 );
                    $css  = $this->getListItemCSS( $option['value'], $isfirst, $this->last_id, true, 'tr' );
                    foreach ( $this->_config['td_keys'] as $key ) {
                        $row[] = $this->cellStart( $key, $tab2 )
							   . $option['value'][$key]
							   . $this->cellEnd();
                    }
                    $out[] = $this->rowStart( $css . ' level_'.$option['value'][$level_key], $tab )
						   . implode( "\n", $row )
						   . '</tr>'."\n";
                }
                $isfirst = 0;
			}
			
			if ( isset( $this->_config['last_tr_class'] ) && ! empty($this->_config['last_tr_class']) ) {
                // get the very last element
                $last   = array_splice( $out, -1, 1 );
                // add last item css
                $last   = str_ireplace( '{{lastcss}}', ' '.$this->_config['last_tr_class'], $last );
                $out[]  = $last[0];
            }
			
			$output = (
						  ( ! isset($options['outer_table']) || $options['outer_table'] != false )
						? $this->tableStart( $space, 0 )
						: ''
					  )
                    . implode( "\r\n", $out )
                    . (
						  ( ! isset($options['outer_table']) || $options['outer_table'] != false )
					    ? '</table>'
						: ''
					  );
            $output = str_ireplace( '{{lastcss}}', '', $output );
            
			return $output;
		
		}   // end function buildTable()
		
		function tableStart( $space = NULL, $level = NULL ) {

            // special CSS class for each level?
            if (
                   isset( $this->_config['create_level_css'] )
                   &&
                   $this->_config['create_level_css'] === true
            ) {
                $suffix  = empty($level)
                         ? intval( ( strlen($space) / 4 ) )
                         : $level;

                $class  .= ' '
                        .  $this->_config['css_prefix']
                        .  $this->_config['ul_class']
                        .  '_'
                        .  $suffix;
            }

            $id      = $ul_id;
            if ( $this->_config['unique_id'] !== false ) {
                if ( empty($id) ) {
                    $id  = $this->_config['ul_id_prefix'].$this->__getID();
                }
                else {
                    $id .= '_' . $this->__getID();
                }
            }

            $this->last_ul_id = $id;

            $output = $space
                    . str_replace(
                          array(
                              '%%id%%',
                              '%%',
                          ),
                          array(
                              $id,
                              $class
                          ),
                          $this->_config['table_open']
                      );

            // remove empty id-attribute
            $output = str_replace( ' id=""', '', $output );

            return $output."\n";

        }   // end function tableStart()
		
		function rowStart( $css, $space = NULL ) {
            $id      = NULL;
            if ( $this->_config['unique_id'] !== false ) {
                $id  = $this->_config['li_id_prefix'].$this->__getID();
            }
            $start = str_replace(
                array( '%%id%%', '%%' ),
                array( $id     , $css ),
                $this->_config['row_open']
            );
            // remove empty id-attribute
            $start = str_replace( ' id=""', '', $start );
            return $space
                 . $start
                 . "\n";
		}
		
		function cellStart( $css, $space = NULL ) {
            $id      = NULL;
            if ( $this->_config['unique_id'] !== false ) {
                $id  = $this->_config['li_id_prefix'].$this->__getID();
            }
            $start = str_replace(
                array( '%%id%%', '%%' ),
                array( $id     , $css ),
                $this->_config['cell_open']
            );
            // remove empty id-attribute
            $start = str_replace( ' id=""', '', $start );
            return $space
                 . $start
                 . "\n";
        }   // end function cellStart()
        
        /**
         *
         *
         *
         *
         **/
        function cellEnd( $space = NULL ) {
            return $space . $this->_config['cell_close'];
        }   // end function cellEnd()

	}
	
}