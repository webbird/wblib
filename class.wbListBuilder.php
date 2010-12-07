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

class wbListBuilder extends wbBase {

    // ----- Debugging -----
    protected      $debugLevel     = KLOGGER::OFF;
    #protected      $debugLevel     = KLOGGER::DEBUG;
    
    private        $id             = 0;
    private        $_path          = array();
    protected      $_config        = array(
    
        'create_level_css'      => true,
    
        'css_prefix'            => '',
        'current_li_class'      => 'current_item',
        'first_li_class'        => 'first_item',
        'has_child_li_class'    => 'has_child',
// these options make no sense if href is already set by caller...
//        'href_class'            => 'link',
//        'current_href_css'    => 'current_link',
        'item_close'            => '</li>',
        'item_open'             => '<li class="%%">',
        'last_li_class'         => 'last_item',
        'li_class'              => 'item',
        'list_close'            => '</ul>',
        'list_open'             => '<ul id="%%id%%" class="%%">',
        'trail_li_class'        => 'trail_item',
        'ul_class'              => 'list',
        'space'                 => '    ',
        'more_info'             => '<span class="more_info">%%</span>',
        
        'ul_id_prefix'          => 'ul_',
        'unique_id'             => true,
        
        '__children_key'        => 'children',
        '__current_key'         => 'current',
        '__hidden_key'          => 'hidden',
        '__href_key'            => 'href',
        '__id_key'              => 'id',
        '__level_key'           => 'level',
        '__parent_key'          => 'parent',
        '__title_key'           => 'title',
        '__more_info_key'       => '',
        
    );
    
    /**
     * constructor
     **/
    function __construct ( $options = array() ) {
        parent::__construct($options);
    }   // end function __construct()
    
    /**
     * 
     *
     *
     *
     **/
    public function buildRecursion ( &$items, $min = -1 ) {
    
        if ( ! is_array( $items ) ) {
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
        $root_id = 0;
        
        // spare some typing...
        $ik      = $this->_config['__id_key'];
        $pk      = $this->_config['__parent_key'];
        $ck      = $this->_config['__children_key'];
        $lk      = $this->_config['__level_key'];
        
        // make sure that the $items array is indexed by the __id_key
        $arr     = array();

        foreach ( $items as $index => $item )
        {
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
        foreach ( $items as $id => &$node )
        {

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
            if ( $node[$pk] === null && $root_id === 0 )
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
        if ( is_array($tree) && count( $tree ) > 0 )
        {
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
    public function buildList ( $tree, $space = NULL, $as_array = NULL ) {

        $this->log()->LogDebug(
            'building list from tree:',
            $tree
        );
        
        if ( ! is_array( $tree ) ) {
            $this->log()->LogDebug( 'no list items to show' );
            return;
        }

        $output   = array();
        $isfirst  = 1;
        
        // get id of last element in the tree
        #if ( count( $tree ) > 1 ) {
            end( $tree );
$this->log()->LogDebug( '', $tree[ key( $tree ) ] );
            $last_element_id = $tree[ key( $tree ) ][ $this->_config['__id_key'] ];
            reset( $tree );
        #}
        #else {
        #    $last_element_id = $tree[0][ $this->_config['__id_key'] ];
        #}
        
        $this->log()->LogDebug( 'last element ID: '.$last_element_id );

        $output[]  = $this->listStart($space);
        
        // spare some typing
        $id_key    = $this->_config['__id_key'];
        $level_key = $this->_config['__level_key'];
        $href_key  = $this->_config['__href_key'];
        $title_key = $this->_config['__title_key'];

        // for each item in the tree...
        foreach ( $tree as $parent => $item )
        {

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
            if ( $this->hasChildren( $item ) ) {
                // recursion
                $output[] = $this->buildList(
                                $item[ $this->_config['__children_key'] ],
                                $space
                            );
            }

            $output[] = $this->itemEnd($space);

        }

        $output[] = $this->listEnd();

        if ( $as_array ) {
            return $output;
        }

        return implode( "\n", $output );

    }   // end function buildList()
    
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

        if ( ! is_array( $tree ) ) {
            $this->log()->LogDebug( 'no breadcrumb items to show' );
            return;
        }

        $trailpages = $this->__getTrail( $tree, $current );

        if ( $as_array )
        {
            return $trailpages;
        }
        
        return $this->buildList( $trailpages );

    }   // end function buildBreadcrumb()
    
    /**
     *
     *
     *
     *
     **/
    public function buildDropDown ( $tree, $options = array() ) {
    
        $this->log()->LogDebug(
            'building dropdown from tree:',
            $tree
        );

        if ( ! is_array( $tree ) ) {
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
            if ( isset ( $options['selected'] ) ) {
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
            if ( $this->hasChildren( $item ) ) {
                // recursion
                $output[] = $this->buildDropDown(
                                $item[ $this->_config['__children_key'] ],
                                $options
                            );
            }

        }
        
        $select = implode( "\n", $output );
        
        return $select;
        
    }   // end function buildDropDown ()

    /**
     *
     *
     *
     *
     **/
    function listStart( $space = NULL ) {
    
        $class = $this->_config['ul_class'];
        
        // special CSS class for each level?
        if (
               (
                   isset( $this->_config['create_level_css'] )
                   &&
                   $this->_config['create_level_css'] == 1
               )
               &&
               (
                      isset( $this->_config['ul_class'] )
                   &&
                   ! empty ( $this->_config['ul_class'] )
               )
        ) {
            $class  .= ' '
                    .  $this->_config['css_prefix']
                    .  $this->_config['ul_class']
                    .  '_'
                    .  intval( ( strlen($space) / 4 ) );
        }

        $output  = $space
                 . str_replace(
                       array(
                           '%%id%%',
                           '%%',
                       ),
                       array(
                           $this->_getID(),
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
        $start = str_replace( '%%', $css, $this->_config['item_open'] );
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
    }   // end function _EM_itemEnd()

    /**
     *
     *
     *
     *
     **/
    function getListItemCSS ( $item ) {

        $li_css = $this->_config['css_prefix']
                . $this->_config['li_class'];

        // special CSS class for each level?
        if (
               (
                   isset( $this->_config['create_level_css'] )
                   &&
                   $this->_config['create_level_css'] == 1
               )
               &&
               (
                      isset( $this->_config['li_class'] )
                   &&
                   ! empty ( $this->_config['li_class'] )
               )
        ) {
            $li_css .= ' '
                    .  $this->_config['css_prefix']
                    .  $this->_config['li_class']
                    .  '_'
                    .  $item[ $this->_config['__level_key'] ];
        }

        // markup for current page
        if ( isset( $item[ $this->_config['__current_key'] ] ) ) {
            $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['current_li_class'];
        }

        // markup for pages on current trail
        if ( isset( $item['em_on_current_trail'] ) ) {
            $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['trail_li_class'];
        }

        // markup for pages that have children
        if (
               isset( $item[ $this->_config['__children_key'] ] )
               &&
               count( $item[ $this->_config['__children_key'] ] ) > 0
               &&
               isset( $this->_config['has_child_li_class'] )
        ) {
            $li_css .= ' ' . $this->_config['css_prefix'] . $this->_config['has_child_li_class'];
        }

        return $li_css;

    }   // end function getListItemCSS()
    
    /**
     *
     *
     *
     *
     **/
    private function _getID() {
    
        if ( ! $this->_config['unique_id'] ) {
            return NULL;
        }
        
        $this->id++;
        return $this->_config['ul_id_prefix'].$this->id;
    
    }   // end function _getID()
    
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
                
        if ( ! is_array( $path ) ) {
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

            if ( is_array( $path ) && count( $path ) > 0 ) {

                array_pop( $path );
                eval( '$parent =& $tree[\''.implode( '\'][\'', $path ).'\'];' );

                $trail[] =  $parent;

                // while we have parents...
                while (
                       is_array( $parent )
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