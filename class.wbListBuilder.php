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

require_once dirname( __FILE__ ).'/class.wbBase.php';

if ( ! class_exists( 'wbListBuilder', false ) ) {

    class wbListBuilder extends wbBase {

        // ----- Debugging -----
        protected      $debugLevel     = KLOGGER::OFF;
        #protected      $debugLevel     = KLOGGER::DEBUG;

        private        $open_nodes     = array();
        private        $id             = 0;
        private        $depth          = 0;
        private        $last_id        = 0;
        private        $list_last      = 0;
        private        $last_ul_id     = NULL;
        private        $_path          = array();
        protected      $_config        = array(
            '__children_key'        => 'children',
            '__current_key'         => 'current',
            '__hidden_key'          => 'hidden',
            '__href_key'            => 'href',
            '__id_key'              => 'id',
            '__level_key'           => 'level',
            '__more_info_key'       => '',
            '__nodes_cookie_name'   => NULL,
            '__parent_key'          => 'parent',
            '__title_key'           => 'title',
            'create_level_css'      => true,

            'css_prefix'            => '',
            'current_li_class'      => 'current_item',
            'deep_recursion_level'  => 25,
            'first_li_class'        => 'first_item',
            'has_child_li_class'    => 'has_child',
            'is_open_li_class'      => 'is_open',
            'item_close'            => '</li>',
            'item_open'             => '<li id="%%id%%" class="%%{{lastcss}}">',
            'last_li_class'         => 'last_item',
            'li_class'              => 'item',
            'li_id_prefix'          => 'li_',
            'list_close'            => '</ul>',
            'list_open'             => '<ul id="%%id%%" class="%%">',
            'more_info'             => '<span class="more_info">%%</span>',
            'space'                 => '    ',
            'trail_li_class'        => 'trail_item',
            'ul_class'              => 'list',
            'ul_id_prefix'          => 'ul_',
            'unique_id'             => true,
        );

        /**
         * constructor
         **/
        function __construct ( $options = array() ) {
            parent::__construct($options);
            if ( isset( $this->_config['__nodes_cookie_name'] ) ) {
                // get open nodes
                $this->open_nodes
                    = isset( $_COOKIE[$this->_config['__nodes_cookie_name']] )
                    ? explode( ',', $_COOKIE[$this->_config['__nodes_cookie_name']] )
                    : array();
                $this->log()->LogDebug(
                    'open nodes from cookie key '.$this->_config['__nodes_cookie_name'],
                    $this->open_nodes
                );
            }
        }   // end function __construct()
        
        /**
         *
         *
         *
         *
         **/
        public function buildBreadcrumb ( $tree, $current, $as_array = NULL ) {

            $this->log()->LogDebug(
                'building breadcrumb from tree:',
                $tree
            );

            if ( ! empty($tree) && ! is_array( $tree ) ) {
                $this->log()->LogDebug( 'no breadcrumb items to show' );
                return;
            }

            $trailpages = $this->__getTrail( $tree, $current );

            if ( $as_array )
            {
                return $trailpages;
            }

            return $this->buildListIter( $trailpages );

        }   // end function buildBreadcrumb()

        /**
         * This is for downward compatibility only
         **/
        public function buildListIter( $list, $options = array() ) {
            $this->log()->LogDebug(
                'This function is marked as deprecated! Use buildList() instead!'
            );
            return $this->buildList($list,$options);
        }   // end function buildListIter()
        
        /**
         *
         * This function creates a nested list from a flat array using an
         * iterative loop
         *
         * @access public
         * @param  array  $list    - flat array
         * @param  int    $root_id - id of the root element
         * @param  array  $options - additional options
         * @return string HTML
         *
         * Based on code found here:
         * http://codjng.blogspot.com/2010/10/how-to-build-unlimited-level-of-menu.html
         *
         **/
        public function buildList( $list, $options = array() ) {

            if ( empty($list) || ! is_array( $list ) ) {
                $this->log()->LogDebug( 'no list items to show' );
                return;
            }

            $hidden     = ( isset($this->_config['__hidden_key']) ? $this->_config['__hidden_key'] : '' );
            $p_key      = $this->_config['__parent_key'];
            $root_id    = isset( $options['root_id'] ) ? $options['root_id'] : 0;

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
            $loop      = !empty( $children[$root_id] );
            
            // spare some typing
            $ul_id     = ( isset($options['ul_id'])      ? $options['ul_id']    : NULL );
            $space     = ( isset($options['space'])      ? $options['space']    : "\t" );
            $maxlevel  = ( isset($options['maxlevel']) ) ? $options['maxlevel'] : 999;
            $id_key    = $this->_config['__id_key'];
            $level_key = $this->_config['__level_key'];
            $href_key  = $this->_config['__href_key'];
            $title_key = $this->_config['__title_key'];
            $m_key     = '';
            $m_before  = NULL;
            $m_after   = NULL;

            // always open root node
            if ( isset($list[$root_id]) && count($this->open_nodes) && ! in_array( $list[$root_id], $this->open_nodes ) ) {
                array_unshift( $this->open_nodes, $list[$root_id][$id_key] );
            }

            if ( isset($this->_config['__more_info_key']) ) {
                $m_key = $this->_config['__more_info_key'];
                list( $m_before, $m_after ) = explode( '%%', $this->_config['more_info'] );
            }

            // initializing $parent as the root
            $parent       = $root_id;
            $parent_stack = array();
            $out          = array();
            $isfirst      = 1;

            while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $root_id ) ) )
            {
                if ( $option === false ) // no more children
                {
                    // move to next parent
                    $parent = array_pop( $parent_stack );
                    // close list item
                    $out[]  = str_repeat( "\t", ( count( $parent_stack ) + 1 ) * 2 )     . $this->listEnd();
                    $out[]  = str_repeat( "\t", ( count( $parent_stack ) + 1 ) * 2 - 1 ) . $this->itemEnd();
                }
                // current item has children (i.e. id is in $children array)
                elseif ( ! empty( $children[ $option['value'][$id_key] ] ) )
                {
                    $this->log()->LogDebug( 'element has children', $option['value'] );
                    $tab    = str_repeat( $space, ( count( $parent_stack ) + 1 ) * 2 - 1 );
                    $li_css = $this->getListItemCSS( $option['value'], $isfirst, $this->last_id, true );
                    $text   = $option['value'][$title_key];
                    
                    if ( isset( $option['value'][$href_key] ) ) {
                        $text = $option['value'][$href_key];
                    }
                    if ( isset( $option['value'][ $m_key ] ) ) {
                        $text .= $m_before
                              .  $option['value'][$m_key]
                              .  $m_after;
                    }
                    // only add children if in open_nodes array and level <= $maxlevel
                    if (
                         ( !isset($option['value'][$level_key]) || $option['value'][$level_key] <= $maxlevel                )
                         &&
                         ( !count($this->open_nodes)            || in_array( $option['value'][$id_key], $this->open_nodes ) )
                    ) {
                        $this->log()->LogDebug( 'showing children for element '.$option['value'][$title_key] );
                        // are we going to show next level?
                        $first_child = $children[$option['value'][$id_key]][0];
                        if ( $first_child[$level_key] <= $maxlevel ) {
                            $li_css .= ' ' . $this->_config['is_open_li_class'];
                        }
                        // HTML for menu item containing children (open)
                        $out[] = $tab.$this->itemStart( $li_css, $space )
                               . "<span>$text</span>";
                        // open sub list
                        $out[] = $tab . "\t" . $this->listStart( $space, $ul_id, $option['value'][$level_key] );
                        array_push( $parent_stack, $option['value'][$p_key] );
                        $parent = $option['value'][$id_key];
                    }
                    else {
                        $this->log()->LogDebug( 'skipping children for element '.$option['value'][$title_key] );
                        $this->log()->LogDebug( $option['value'][$level_key] .' <= ' . $maxlevel . ' || ' . $option['value'][$id_key] . ' ! in_array ', $this->open_nodes );
                        // HTML for menu item containing children (open)
                        $out[] = sprintf(
                            '%1$s'.$this->itemStart( $li_css, $space ).'%2$s',
                            $tab,   // %1$s = tabulation
                            $text   // %2$s = title
                        );
                    }
                    // get id for last child
                    end($children[ $option['value'][$id_key] ]);
                    $key = key($children[ $option['value'][$id_key] ]);
                    reset($children[ $option['value'][$id_key] ]);
                    $this->last_id = $children[ $option['value'][$id_key] ][$key][$id_key];
                    $this->log()->LogDebug( 'last child id: '.$this->last_id );
                }
                // handle leaf
                else {

                     $this->log()->LogDebug( 'handling leaf '.$option['value'][$title_key] );

                    // only add leaf if level <= maxlevel
                    if ( !isset($option['value'][$level_key]) || $option['value'][$level_key] <= $maxlevel )
                    {
                        $li_css = $this->getListItemCSS( $option['value'], $isfirst, $this->last_id );
                        $text   = $option['value'][$title_key];
                        if ( isset( $option['value'][$href_key] ) ) {
                            $text = $option['value'][$href_key];
                        }
                        if ( isset( $option['value'][ $m_key ] ) ) {
                            $text .= $m_before
                                  .  $option['value'][$m_key]
                                  .  $m_after;
                        }
                        // HTML for menu item with no children (aka "leaf")
                        $out[] = sprintf(
                            '%1$s'.$this->itemStart( $li_css, $space ).'%2$s'.$this->itemEnd(),
                            str_repeat( "\t", ( count( $parent_stack ) + 1 ) * 2 - 1 ),   // %1$s = tabulation
                            $text   // %2$s = title
                        );
                    }
                    else {
                        $this->log()->LogDebug( 'leaf not shown because level ['.$option['value'][$level_key]." <= $maxlevel" );
                    }
                }
                $isfirst = 0;
            }
            
            if ( isset( $this->_config['last_li_class'] ) && ! empty($this->_config['last_li_class']) ) {
                // get the very last element
                $last   = array_splice( $out, -1, 1 );
                // add last item css
                $last   = str_ireplace( '{{lastcss}}', ' '.$this->_config['last_li_class'], $last );
                $out[]  = $last[0];
            }
            
            $output = $this->listStart( $space, $ul_id )
                    . implode( "\r\n", $out )
                    . $this->listEnd();
            $output = str_ireplace( '{{lastcss}}', '', $output );
            
            return $output;

        }   // end function buildList()

        /**
         * Build a list from a nested (multi-dimensional) array; this is much
         * slower than using the iterative approach. Still here for backward
         * compatibility, but marked as deprecated.
         *
         * @access public
         * @param  array  $tree - multi-dimensional array
         * @param  string $space
         * @param  array  $options
         * @return string HTML
         *
         **/
        public function buildListRecursive ( $tree, $space = NULL, $options = array() ) {
        
            $this->log()->LogDebug(
                'This function is marked as deprecated! Use buildList() instead!'
            );

            if ( empty($tree) || ! is_array( $tree ) ) {
                $this->log()->LogDebug( 'no list items to show' );
                return;
            }
            if ( $this->depth >= $this->_config['deep_recursion_level'] ) {
                $this->log()->LogDebug( 'ERROR: DEEP RECURSION!' );
                $this->printError( 'buildDropDown() ERROR: DEEP RECURSION! Recursion level '.$this->depth );
                return;
            }

            $this->depth++;

            $this->log()->LogDebug(
                'building list from tree:',
                $tree
            );

            $output   = array();
            $isfirst  = 1;

            // get the id of the last element in the tree; needed to add
            // the last_li_class
            end( $tree );
            $this->log()->LogDebug( '', $tree[ key( $tree ) ] );
            $last_element_id = $tree[ key( $tree ) ][ $this->_config['__id_key'] ];
            reset( $tree );
            $this->log()->LogDebug( 'last element ID: '.$last_element_id );

            $ul_id     = ( isset($options['ul_id']) ? $options['ul_id'] : NULL );
            $output[]  = $this->listStart( $space, $ul_id );

            // spare some typing
            $id_key    = $this->_config['__id_key'];
            $level_key = $this->_config['__level_key'];
            $href_key  = $this->_config['__href_key'];
            $title_key = $this->_config['__title_key'];

            // for each item in the tree...
            foreach ( $tree as $parent => $item ) {

                // skip hidden
                if (
                     isset( $this->_config['__hidden_key'] )
                     &&
                     isset( $item[ $this->_config['__hidden_key'] ] )
                     &&
                     $item[ $this->_config['__hidden_key'] ]
                ) {
                    $this->log()->LogDebug(
                        'skipping hidden item: '
                      . $item[ $title_key ]
                    );
                    continue;
                }

                // spare some typing
                $id     = $item[ $id_key ];
                $level  = $item[ $level_key ];

                // indent nicely
                $space  = str_repeat( $this->_config['space'], ($level+1) );

                $li_css = $this->getListItemCSS( $item );

                // first element?
                if ( $isfirst ) {
                    $li_css  .= ' ' . $this->_config['css_prefix'] . $this->_config['first_li_class'];
                    $isfirst  = 0;
                }

                // last element?
                if ( $last_element_id === $item[ $id_key ] ) {
                    $li_css  .= ' ' . $this->_config['css_prefix'] . $this->_config['last_li_class'];
                }

                $text = NULL;

                if ( isset( $item[ $href_key ] ) ) {
                    $text = $item[ $href_key ];
                }
                else {
                    $text = $item[ $title_key ];
                }

                if (
                     isset( $this->_config['__more_info_key'] )
                     &&
                     isset( $item[ $this->_config['__more_info_key'] ] )
                ) {
                    $text .= str_replace( '%%', $item[ $this->_config['__more_info_key'] ], $this->_config['more_info'] );
                }

                // start list element
                $output[] = $this->itemStart( $li_css, $space )
                          . $space
                          . $text;

                // children to show?
                #if ( $this->hasChildren( $item ) ) {
                if (
                     isset( $item[ $this->_config['__children_key'] ] )
                     &&
                     count( $item[ $this->_config['__children_key'] ] ) > 0
                ) {
                    // recursion
                    $output[] = $this->buildList(
                                    $item[ $this->_config['__children_key'] ],
                                    $space,
                                    $options
                                );
                }

                $output[] = $this->itemEnd($space);

            }

            $output[] = $this->listEnd();
            
            $this->depth--;

            if ( isset($options['as_array']) ) {
                return $output;
            }

            return str_ireplace( '{{lastcss}}', '', implode( "\n", $output ) );

        }   // end function buildListRecursive()

        /**
         * This is for backward compatibility only
         **/
        public function buildDropDownIter( $list, $options = array() ) {
            $this->log()->LogDebug(
                'This function is marked as deprecated! Use buildDropDown() instead!'
            );
            return $this->buildDropDown( $list, $options );
        }   // end function buildDropDownIter()
        
        /**
         *
         *
         *
         *
         **/
        public function buildDropDown ( $list, $options = array() ) {

            $this->log()->LogDebug(
                'building dropdown from tree:',
                $list
            );
            $this->log()->LogDebug(
                'options', $options
			);

            if ( isset( $options['open_nodes'] ) ) {
                $this->open_nodes = $options['open_nodes'];
            }
            $this->log()->LogDebug( 'open nodes:', $this->open_nodes );

            if ( empty($list) || ! is_array( $list ) || count($list) == 0 ) {
                $this->log()->LogDebug( 'no list items to show' );
                return;
            }

            $space    = isset( $options['space'] )
                      ? $options['space']
                      : $this->_config['space'];
                      
            if ( isset ( $options['selected'] ) && ! empty( $options['selected'] ) ) {
                if ( ! is_array( $options['selected'] ) ) {
                    $options['selected'] = array( $options['selected'] );
                }
                $this->log()->LogDebug(
                    'selected item(s):',
                    $options['selected']
                );
            }

            $output    = array();
            $hidden    = ( isset($this->_config['__hidden_key']) ? $this->_config['__hidden_key'] : '' );
            $p_key     = $this->_config['__parent_key'];
            $id_key    = $this->_config['__id_key'];
            $title_key = $this->_config['__title_key'];
            $root_id   = isset( $options['root_id'] ) ? $options['root_id'] : 0;

            // create a list of children for each item
            foreach ( $list as $item ) {
                if ( isset($item[$hidden]) ) {
                    continue;
                }
                $children[$item[$p_key]][] = $item;
            }
            
            $this->log()->LogDebug( 'children array:', $children );

            // loop will be false if the root has no children (i.e., an empty menu!)
            $loop         = !empty( $children[$root_id] );
            if ( ! $loop ) { return $this->buildDropDownRecursive( $list, $options ); }
            
            // initializing $parent as the root
            $parent       = $root_id;
            $parent_stack = array();
            
            while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $root_id ) ) )
            {
                if ( $option === false ) // no more children
                {
                    $parent = array_pop( $parent_stack );
                }
                // current page has children (i.e. page_id is in $children array)
                elseif ( ! empty( $children[ $option['value'][$id_key] ] ) )
                {
                    $level  = isset( $option['value'][ $this->_config['__level_key'] ] )
                            ? $option['value'][ $this->_config['__level_key'] ]
                            : 0;
                    $tab    = str_repeat( $space, $level );
                    $text   = $option['value'][$title_key];
                    // mark selected
                    $sel    = NULL;
                    if (     isset   ( $options['selected'] )
                         && is_array( $options['selected'] )
                         && in_array( $option['value'][ $id_key ], $options['selected'] )
                    ) {
                        $sel = ' selected="selected"';
                    }
                    $output[] = '<option value="'
                              . $option['value'][ $id_key ]
                              . '"'.$sel.'>'
                              . $tab
                              . ' '
                              . $text
                              . '</option>';

                    array_push( $parent_stack, $option['value'][$p_key] );
                    $parent = $option['value'][$id_key];
                }
                // handle leaf
                else {
                    $level  = isset( $option['value'][ $this->_config['__level_key'] ] )
                            ? $option['value'][ $this->_config['__level_key'] ]
                            : 0;
                    $tab    = str_repeat( $space, $level );
                    $text   = $option['value'][$title_key];
                    // mark selected
                    $sel    = NULL;
                    if (    isset   ( $options['selected'] )
                         && is_array( $options['selected'] )
                         && in_array( $option['value'][ $id_key ], $options['selected'] )
                    ) {
                        $sel = ' selected="selected"';
                    }
                    $output[] = '<option value="'
                              . $option['value'][ $id_key ]
                              . '"'.$sel.'>'
                              . $tab
                              . ' '
                              . $text
                              . '</option>';
                }

            }
            
            $this->log()->LogDebug( 'output:', $output );
            
            return join( "\n", $output );

        }   // end function buildDropDown ()

        /**
         * deprecated
         *
         *
         *
         **/
        public function buildDropDownRecursive ( $tree, $options = array() ) {
        
            $this->log()->LogDebug(
                'This function is marked as deprecated! Use buildDropDown() instead!'
            );

            $this->log()->LogDebug(
                'building dropdown from tree:',
                $tree
            );
            
            if ( $this->depth >= $this->_config['deep_recursion_level'] ) {
                $this->log()->LogDebug( 'ERROR: DEEP RECURSION!' );
                $this->printError( 'buildDropDown() ERROR: DEEP RECURSION! Recursion level '.$this->depth.' >= '.$this->_config['deep_recursion_level'] );
                return;
            }
            
            $this->depth++;

            if ( empty($tree) || ! is_array( $tree ) || count($tree) == 0 ) {
                $this->log()->LogDebug( 'no list items to show' );
                return;
            }

            $space    = isset( $options['space'] )
                      ? $options['space']
                      : $this->_config['space'];

            $output   = array();

            // for each item in the tree...
            while ( $item = array_shift( $tree ) ) {

                // skip hidden
                if (
                     isset( $this->_config['__hidden_key'] )
                     &&
                     isset( $item[ $this->_config['__hidden_key'] ] )
                     &&
                     $item[ $this->_config['__hidden_key'] ]
                ) {
                    $this->log()->LogDebug(
                        'skipping hidden item: '
                      . $item[ $this->_config['__title_key'] ]
                    );
                    continue;
                }

                // spare some typing
                $id     = $item[ $this->_config['__id_key'] ];
                $level  = isset( $item[ $this->_config['__level_key'] ] )
                        ? $item[ $this->_config['__level_key'] ]
                        : 0;

                // indent nicely
                $prt_space = str_repeat( $space, $level );

                // mark selected
                $sel    = NULL;
                if ( isset ( $options['selected'] ) && ! empty( $options['selected'] ) ) {
                    if ( ! is_array( $options['selected'] ) ) {
                        $options['selected'] = array( $options['selected'] );
                    }
                foreach ( $options['selected'] as $i => $id ) {
                        if ( $id === $item[ $this->_config['__id_key'] ] ) {
                            $sel = ' selected="selected"';
                        }
                    }
                }

                $output[] = '<option value="'
                          . $item[ $this->_config['__id_key'] ]
                          . '"'.$sel.'>'
                          . $prt_space
                          . ' '
                          . $item[ $this->_config['__title_key'] ]
                          . '</option>';

                // children to show?
                #if ( $this->hasChildren( $item ) ) {
                if (
                     isset( $item[ $this->_config['__children_key'] ] )
                     &&
                     count( $item[ $this->_config['__children_key'] ] ) > 0
                ) {
                    // recursion
                    $output[] = $this->buildDropDown(
                                    $item[ $this->_config['__children_key'] ],
                                    $options
                                );
                }

            }

            $select = implode( "\n", $output );
            
            $this->depth--;

            return $select;

        }   // end function buildDropDownRecursive ()
        
        /**
         *
         *
         *
         *
         **/
        public function buildRecursion ( &$items, $min = -1 ) {

            if ( ! empty( $items ) && ! is_array( $items ) ) {
                $this->log()->LogDebug( 'no items to build recursion' );
                return;
            }

            $this->log()->LogDebug( 'building recursion from items:', $items );
            $this->log()->LogDebug( 'config: ', $this->_config );

            // if there's only one item, no recursion to do
            if ( ! ( count( $items ) > 1 ) ) {
                return $items;
            }

            $tree    = array();
            $root_id = -1;

            // spare some typing...
            $ik      = $this->_config['__id_key'];
            $pk      = $this->_config['__parent_key'];
            $ck      = $this->_config['__children_key'];
            $lk      = $this->_config['__level_key'];

            // make sure that the $items array is indexed by the __id_key
            $arr     = array();

            foreach ( $items as $index => $item ){
                $arr[ $item[$ik] ] = $item;
            }

            $items = $arr;

            //
            // this creates an array of parents with their associated children
            //
            // -----------------------------------------------
            // REQUIRES that the array index is the parent ID!
            // -----------------------------------------------
            //
            // http://www.tommylacroix.com/2008/09/10/php-design-pattern-building-a-tree/
            //
            foreach ( $items as $id => &$node ) {

                // skip nodes with depth < min level
                if (
                     isset( $node[ $lk ] )
                     &&
                     $node[ $lk ] <= $min
                ) {
                    $this->log()->LogDebug( 'skipping node (level <= min level ['.$min.']', $node );
                    continue;
                }

                // avoid error messages on missing parent key
                if ( ! isset( $node[$pk] ) )
                {
                    $this->log()->LogDebug( 'adding missing parent key to node', $node );
                    $node[$pk] = null;
                }

                // root node
                if ( $node[$pk] === null && $root_id < 0 )
                {
                    $this->log()->LogDebug( 'found root node', $node );
                    $tree[$id] = &$node;
                    $root_id   = $id;
                }
                // sub node
                else {
                    // avoid warnings on missing children key
                    if ( ! isset( $items[$node[$pk]][$ck]) )
                    {
                        $items[ $node[$pk] ][ $ck ] = array();
                    }
                    $items[ $node[$pk] ][ $ck ][] = &$node;
                }

            }

    // ---- ????? -----
            if ( ! empty($tree) && is_array($tree) && count( $tree ) > 0 )
            {
                $tree[$root_id][$ck]['__is_recursive'] = 1;
                $tree = $tree[$root_id][$ck];
            }
            else {
                $this->log()->LogDebug( '---ERROR!--- $tree is empty!' );
            }

            $this->log()->LogDebug( 'returning tree: ', $tree );

            return $tree;

        }   // end function buildRecursion ()

        /**
         *
         *
         *
         *
         **/
        function listStart( $space = NULL, $ul_id = NULL, $level = NULL ) {

            $class = $this->_config['ul_class'];

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
                          $this->_config['list_open']
                      );

            // remove empty id-attribute
            $output = str_replace( ' id=""', '', $output );

            return $output;

        }   // end function listStart()

        /**
         *
         *
         *
         *
         **/
        function listEnd( $space = NULL ) {
            return $space . $this->_config['list_close'];
        }   // end function listEnd()

        /**
         *
         *
         *
         *
         **/
        function itemStart( $css, $space = NULL ) {
            $id      = NULL;
            if ( $this->_config['unique_id'] !== false ) {
                $id  = $this->_config['li_id_prefix'].$this->__getID();
            }
            $start = str_replace(
                array( '%%id%%', '%%' ),
                array( $id     , $css ),
                $this->_config['item_open']
            );
            // remove empty id-attribute
            $start = str_replace( ' id=""', '', $start );
            return $space
                 . $start
                 . "\n";
        }   // end function itemStart()

        /**
         *
         *
         *
         *
         **/
        function itemEnd( $space = NULL ) {
            return $space . $this->_config['item_close'];
        }   // end function itemEnd()

        /**
         *
         *
         *
         *
         **/
        function getListItemCSS ( $item, $isfirst = false, $last_id = NULL, $has_children = false, $for = 'li' ) {

            $li_css = $this->_config['css_prefix']
                    . $this->_config[$for.'_class'];

            // special CSS class for each level?
            if (
                   (
                       isset( $this->_config['create_level_css'] )
                       &&
                       $this->_config['create_level_css'] == 1
                   )
                   &&
                   (
                          isset( $this->_config[$for.'_class'] )
                       &&
                       ! empty ( $this->_config[$for.'_class'] )
                   )
            ) {
                $li_css .= ' '
                        .  $this->_config['css_prefix']
                        .  $this->_config[$for.'_class']
                        .  (
                                isset( $item[ $this->_config['__level_key'] ] )
                              ? '_'.$item[ $this->_config['__level_key'] ]
                              : ''
                           );
            }

            // markup for current page
            if ( isset( $item[ $this->_config['__current_key'] ] ) ) {
                $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['current_'.$for.'_class'];
            }

// ----- TODO -----
            // markup for pages on current trail
            if ( isset( $item['em_on_current_trail'] ) ) {
                $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['trail_'.$for.'_class'];
            }
// ----- TODO -----

            // markup for pages that have children
            if ( $has_children ) {
                $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['has_child_'.$for.'_class'];
            }
            elseif (
                  isset( $this->_config['has_child_'.$for.'_class'] )
               && isset( $item[ $this->_config['__children_key'] ] )
            ) {
                if (
                     (    is_array( $item[ $this->_config['__children_key'] ] )
                       && count( $item[ $this->_config['__children_key'] ] ) > 0
                     )
                     || $item[ $this->_config['__children_key'] ] > 0
                ) {
                    $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['has_child_'.$for.'_class'];
                }
            }
            
            // markup for first element
            if ( $isfirst ) {
                $li_css  .= ' ' . $this->_config['css_prefix'] . $this->_config['first_'.$for.'_class'];
            }

            // markup for last element
            if ( $last_id === $item[ $this->_config['__id_key'] ] ) {
                $li_css  .= ' ' . $this->_config['css_prefix'] . $this->_config['last_'.$for.'_class'];
            }

            return $li_css;

        }   // end function getListItemCSS()
        
        /**
         *
         *
         *
         *
         **/
        public function getLastULID() {
            return $this->last_ul_id;
        }   // end function getLastULID()

        /**
         * get id for list start;
         * returns NULL if 'unique_id' is set to false;
         * returns a unique id if 'unique_id' is set to true;
         * returns the value of 'unique_id' in any other case
         *
         * @access private
         * @return string
         *
         **/
        protected function __getID() {
            if ( $this->_config['unique_id'] === false ) {
                return NULL;
            }
            if ( $this->_config['unique_id'] !== true ) {
                return $this->_config['unique_id'];
            }
            $this->id++;
            return $this->id;
        }   // end function __getID()

        /**
         *
         *
         *
         *
         **/
        private function __getTrail( $tree, $current )
        {

            $trail = array();
            $i=0;

            $this->log()->LogDebug( 'getting trail from tree: ', $tree );

            // get the current node
            $path = $this->ArraySearchRecursive(
                        $current,
                        $tree,
                        $this->_config['__id_key']
                    );

            if ( ! empty($path) && ! is_array( $path ) ) {
                $this->log()->LogDebug(
                    'current item ['.$current.'] not found in tree, return undef'
                );
                return NULL;
            }

            $this->log()->LogDebug( 'path for current item ['.$current.']: ', $path );

            array_pop($path);
            eval( '$node = $tree[\''.implode( '\'][\'', $path ).'\'];' );
            $trail[] = $node;

            if ( $node[ $this->_config['__parent_key'] ] !== $current ) {

                $path = $this->ArraySearchRecursive(
                            $node[ $this->_config['__parent_key'] ],
                            $tree,
                            $this->_config['__id_key']
                        );

                if ( ! empty($path) && is_array( $path ) && count( $path ) > 0 ) {

                    array_pop( $path );
                    eval( '$parent =& $tree[\''.implode( '\'][\'', $path ).'\'];' );

                    $trail[] =  $parent;

                    // while we have parents...
                    while (
                           ! empty( $parent )
                        && is_array( $parent )
                        && isset( $parent[ $this->_config['__parent_key'] ] )
                        && $parent[ $this->_config['__parent_key'] ] > 0
                    ) {
                        $path = $this->ArraySearchRecursive(
                                    $parent[ $this->_config['__parent_key'] ],
                                    $tree,
                                    $this->_config['__id_key']
                                );

                        array_pop( $path );
                        eval( '$parent =& $tree[\''.implode( '\'][\'', $path ).'\'];' );

                        $trail[] = $parent;

                        // avoid deep recursion
                        if ( $i > 15 ) {
                            $this->log->LogDebug(
                                'reached 15 recursions without finding root; break to avoid deep recursion'
                            );
                            break;
                        }

                        $i++;

                    }

                }

            }

            return array_reverse( $trail );

        }   // end function __getTrail()

        /**
         * Check if an item has children
         *
         * @param  array   $item
         * @return boolean
         *
         **/
        function hasChildren($item) {
            if (
                 isset( $item[ $this->_config['__children_key'] ] )
                 &&
                 count( $item[ $this->_config['__children_key'] ] ) > 0
            ) {
                return true;
            }
            return false;
        }   // end function hasChildren()

    }
    
}

?>