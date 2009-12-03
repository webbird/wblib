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
require_once dirname( __FILE__ ).'/class.wbValidate.php';

class wbListBuilder extends wbBase {

    protected $_debug         = false;
    
    private   $id             = 1;
    private   $settings       = array(
    
        'create_level_css'    => true,
    
        'css_prefix'          => '',
        'current_href_css'    => 'current_link',
        'current_li_css'      => 'current_item',
        'first_li_css'        => 'first_item',
        'has_child_li_css'    => '',
        'href_css'            => 'link',
        'item_close'          => '</li>',
        'item_open'           => '<li class="%%">',
        'last_li_css'         => 'last_item',
        'li_css'              => 'item',
        'list_close'          => '</ul>',
        'list_open'           => '<ul id="%%id%%" class="%%">',
        'trail_li_css'        => 'trail_item',
        'ul_css'              => 'list',
        
        '__children_key'      => 'children',
        '__current_key'       => 'current',
        '__hidden_key'        => 'hidden',
        '__href_key'          => 'href',
        '__id_key'            => 'id',
        '__level_key'         => 'level',
        '__parent_key'        => 'parent',
        '__title_key'         => 'title',
        
    );
    
    /**
     * constructor
     **/
    function __construct ( $options = array() ) {

    }   // end function __construct()
    
    /**
     *
     *
     *
     *
     **/
    public function set ( $settings = array() ) {

        foreach ( $settings as $set => $value ) {
            if (
                 isset( $this->settings[ $set ] )
                 &&
                 wbValidate::staticValidate( 'PCRE_STRING', $value )
            ) {
                $this->settings[ $set ] = $value;
            }
        }

    }   // end function set ()
    
    /**
     *
     *
     *
     *
     **/
    public function buildRecursion ( $items, $min = 0 ) {

        if ( ! is_array( $items ) ) {
            $this->debug( 'no items to build recursion' );
            return;
        }

        $tree     = array();

        foreach ( $items as $node ) {

            // find parent
            if ( $node[ $this->settings['__level_key'] ] > $min ) {

                $path = $this->ArraySearchRecursive(
                            $node[ $this->settings['__parent_key'] ],
                            $tree,
                            $this->settings['__id_key']
                        );

                // add node to parent
                if ( is_array( $path ) ) {
                    array_pop($path);
                    eval( '$ref =& $tree[\''.implode( '\'][\'', $path ).'\'];' );
                    $ref[ $this->settings['__children_key'] ][] = $node;
                }
                else {
                    $tree[] = $node;
                }

                continue;

            }

            $tree[] = $node;

        }


        $this->debug( 'returning tree: ', $tree );

        return $tree;

    }   // end function buildRecursion ()

    /**
     *
     *
     *
     *
     **/
    public function buildList ( $tree, $space = NULL, $as_array = NULL ) {

        $this->debug(
            'building list from tree:',
            $tree
        );

        if ( ! is_array( $tree ) ) {
            $this->debug( 'no list items to show' );
            return;
        }

        $output   = array();
        $isfirst  = 1;

        $output[] = $this->listStart($space);

        // for each item in the tree...
        while ( $item = array_shift( $tree ) ) {

            // skip hidden
            if (
                 isset( $this->settings['__hidden_key'] )
                 &&
                 isset( $item[ $this->settings['__hidden_key'] ] )
                 &&
                 $item[ $this->settings['__hidden_key'] ]
            ) {
                $this->debug(
                    'skipping hidden item: '
                  . $item[ $this->settings['__title_key'] ]
                );
                continue;
            }

            // spare some typing
            $id     = $item[ $this->settings['__id_key'] ];
            $level  = $item[ $this->settings['__level_key'] ];

            // indent nicely
            $space  = str_repeat( '    ', ($level+1) );

            $li_css = $this->getListItemCSS( $item );

            // first element?
            if ( $isfirst ) {
                $li_css  .= ' ' . $this->settings['first_li_css'];
                $isfirst  = 0;
            }
            
            $text     = isset( $item[ $this->settings['__href_key'] ] )
                      ? $item[ $this->settings['__href_key'] ]
                      : $item[ $this->settings['__title_key'] ];

            // start list element
            $output[] = $this->itemStart( $li_css, $space )
                      . $text;

            // children to show?
            if ( $this->hasChildren( $item ) ) {
                // recursion
                $output[] = $this->buildList(
                                $item[ $this->settings['__children_key'] ],
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
    function listStart( $space = NULL ) {
    
        $class = $this->settings['ul_css'];
        
        // special CSS class for each level?
        if (
               (
                   isset( $this->settings['create_level_css'] )
                   &&
                   $this->settings['create_level_css'] == 1
               )
               &&
               (
                      isset( $this->settings['ul_css'] )
                   &&
                   ! empty ( $this->settings['ul_css'] )
               )
        ) {
            $class  .= ' '
                    .  $this->settings['ul_css']
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
                       $this->settings['list_open']
                   );

        return $output;

    }   // end function listStart()

    /**
     *
     *
     *
     *
     **/
    function listEnd( $space = NULL ) {
        return $space . $this->settings['list_close'];
    }   // end function listEnd()

    /**
     *
     *
     *
     *
     **/
    function itemStart( $css, $space = NULL ) {
        $start = str_replace( '%%', $css, $this->settings['item_open'] );
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
        return $space . $this->settings['item_close'];
    }   // end function _EM_itemEnd()

    /**
     *
     *
     *
     *
     **/
    function getListItemCSS ( $item ) {

        $li_css = $this->settings['li_css'];

        // special CSS class for each level?
        if (
               (
                   isset( $this->settings['create_level_css'] )
                   &&
                   $this->settings['create_level_css'] == 1
               )
               &&
               (
                      isset( $this->settings['li_css'] )
                   &&
                   ! empty ( $this->settings['li_css'] )
               )
        ) {
            $li_css .= ' '
                    .  $this->settings['li_css']
                    .  '_'
                    .  $item[ $this->settings['__level_key'] ];
        }

        // markup for current page
        if ( isset( $item[ $this->settings['__current_key'] ] ) ) {
            $li_css .= ' ' . $this->settings['current_li_css'];
        }

        // markup for pages on current trail
        if ( isset( $item['em_on_current_trail'] ) ) {
            $li_css .= ' ' . $this->settings['trail_li_css'];
        }

        // markup for pages that have children
        if (
               isset( $item[ $this->settings['__children_key'] ] )
               &&
               count( $item[ $this->settings['__children_key'] ] ) > 0
               &&
               isset( $this->settings['has_child_li_css'] )
        ) {
            $li_css .= ' ' . $this->settings['has_child_li_css'];
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
    
        $this->id++;
        return 'ul_'.$this->id;
    
    }   // end function _getID()
    
    /**
     * Check if an item has children
     *
     * @param  array   $item
     * @return boolean
     *
     **/
    function hasChildren($item) {
        if (
             isset( $item[ $this->settings['__children_key'] ] )
             &&
             count( $item[ $this->settings['__children_key'] ] ) > 0
        ) {
            return true;
        }
        return false;
    }   // end function hasChildren()

}