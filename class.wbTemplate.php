<?php

/**

  Template class

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

class wbTemplate extends wbBase {

    // ----- Debugging -----
    protected $debugLevel     = KLOGGER::OFF;
    #protected $debugLevel     = KLOGGER::DEBUG;
    
    // optional lang handler
    private   $_lang          = NULL;

    // what to do if there are placeholders with no replacement
    private   $_on_unknown    = 'remove';

    // default template path; override with setPath()
    private   $_templatePath  = '/templates';
    
    // template file; set with setTemplate()/setFile()
    private   $_templateFile  = '';
    
    // start tag
    private   $_start_tag     = '{{';
    
    // end tag
    private   $_end_tag       = '}}';
    
    //
    private   $_remove_blanks = true;
    
    // array to store already loaded templates
    private   $_loaded        = array();
    
    // array to store regexp
    private   $_regexp        = array();

    /**
     * constructor
     **/
    function __construct ( $options = array() ) {
    
        parent::__construct();

        if ( isset ( $options['start_tag'] ) ) {
            $this->_start_tag = $options['start_tag'];
        }
        if ( isset ( $options['end_tag'] ) ) {
            $this->_end_tag = $options['end_tag'];
        }
        
        if ( isset ( $options['lang'] ) && is_object($options['lang']) ) {
            $this->_lang = $options['lang'];
        }
        
        $this->__defineRegExp();
        
        foreach ( $this->_regexp as $key => $value ) {
            $this->_regexp[ $key ] = str_replace(
                         array(
                             '%%start%%',
                             '%%end%%'
                         ),
                         array(
                             $this->_start_tag,
                             $this->_end_tag
                         ),
                         $value
                      );
        }
        
    }   // end function __construct()
    
    /**
     *
     *
     *
     *
     **/
    private function __defineRegExp () {
    
        $loop_open  = '%%start%%\s*:loop(?!end)\s+([^%%end%%]*?)\s*%%end%%';
        $loop_close = '%%start%%\s*:loopend\s*%%end%%';
    
        $this->_regexp = array(
        
            // ----- {{ :include <file> }} -----
            'include'
                => "/%%start%%\s*?:include\s*?(.*?)\s*?%%end%%/i",
                
            // ----- multiline comments -----
            'html_comment'
                => "/<!--\s*?BEGIN\s*?template\s*?comment\s*?-->(.+?)<!--\s*?END\s*?template\s*?comment\s*?-->/seix",
            
            // ----- simple comments -----
            'simple_comment'
                => "/%%start%%\s*?:comment.*?%%end%%/seix",
                
            // ----- if-else -----
            'if'
                => "/
# {{ :if anything }}
%%start%% \s*? :if(?!end) \b([^%%end%%]*) \s*? %%end%%
# not followed by another opening (innermost)
(?! %%start%% \s*? :if(?!end) \b[^%%end%%]* \s*? %%end%% )
(
  (?: [\S\s] (?! %%start%% \s*? :if(?!end) \b[^%%end%%]* \s*? %%end%% ) )
  *?
)
# {{ :ifend }} (innermost)
%%start%% \s*? :ifend \s*? %%end%%
/seix",

            'if_subpat'
                => "/%%start%% \s*? :else \s*? %%end%%/seix",
                
            // ----- {{ :lang <string> }} -----
            'lang'
                => "/%%start%%\s*?:(?:lang|translate)\s*?(.*?)%%end%%/seix",
                
            // ----- simple placeholders -----
            'var'
                => '/%%start%%\s*([^%%start%%].*?)\s*%%end%%/e',
                
            // all
            'all'
                => '/%%start%%\s*?(.*?)%%end%%/',

            // ----- loops - this finds all "root level" loops -----
            'loop'
                => "/%%start%% \s*? :loop(?!end) \b([^%%end%%]*) \s*? %%end%% (?! %%start%% \s*? :loop(?!end) \b[^%%end%%]* \s*? %%end%% ) ( (?: [\S\s] (?! %%start%% \s*? :loop(?!end) \b[^%%end%%]* \s*? %%end%% ) ) *? ) %%start%% \s*? :loopend \s*? %%end%%/seix",

        );

    }   // end function __defineRegExp()
    
    /**
     * set behaviour for unknown params
     *
     * @access public
     * @param  string   $behaviour
     *     valid:
     *         'fail'     print error and exit
     *         'remove'   simply remove
     *         'comment'  wrap into comment <!-- -->
     * @return void
     *
     **/
    public function setBehaviour ( $behaviour = 'fail' ) {

        switch ( $behaviour ) {
        
            case 'fail':
            case 'remove':
            case 'comment':
                $this->_on_unknown = $behaviour;
                break;
            
            default:
                $this->printError( 'Unsupported behaviour: '.$behaviour );
        	      break;
        }
        
    }   // end function setBehaviour()
    
  	/**
  	 * set template path
  	 *
  	 * @access public
  	 * @param  string   $path  - template path (must exist!)
  	 * @return void
  	 *
  	 **/
    public function setPath ( $path ) {
        if ( file_exists( $path ) ) {
            $this->_templatePath = $path;
        }
        else {
            $this->printError( 'template path does not exist: '.$path );
        }
    }   // end function setPath ()
    
  	/**
  	 * set current template file
  	 *
  	 * @access public
  	 * @param  string   $file  - filename (must exist in template path!)
  	 * @return void
  	 *
  	 **/
    public function setFile ( $file ) {
        if ( file_exists( $this->_templatePath.'/'.$file ) ) {
            $this->_templateFile = $file;
            $fileContents = $this->slurp( $this->_templatePath.'/'.$file );
            $this->_loaded[ $file ] = $fileContents;
        }
        else {
            $this->printError( 'template file does not exist: '.$this->_templatePath.'/'.$file );
        }
    }   // end function setFile ()
    
    /**
  	 * alias for setFile()
  	 **/
    public function setTemplate ( $file ) {
        return $this->setFile( $file );
    }   // end function setTemplate()
    
    /**
  	 * get template contents
  	 *
  	 * This is a shortcut function for
  	 *
  	 *     $tpl->setTemplate( $file );
  	 *     $tpl->renderTemplate( $array );
  	 *
  	 * @access public
  	 * @param  string   $file  - file to parse
  	 * @param  array    $attr  - replacements
  	 *
  	 **/
    public function getTemplate ( $file, $attr = array() ) {
        $this->setFile( $file );
        return $this->parseTemplate( $attr );
    }   // end function getTemplate()
    
    /**
     * parse template
     *
     * Returns parsed template (set with setFile()) or prints error and exits
     * if an error occurs (no template file given or template not loaded)
     *
     * @access public
     * @param  array   $contents  - replacements
     * @return mixed
     *
     **/
    public function parseTemplate( $contents ) {
    
        if (
               isset( $this->_templateFile )
               &&
               isset( $this->_loaded[ $this->_templateFile ] )
        ) {

            $text = $this->parseString(
                        $this->_loaded[ $this->_templateFile ],
                        $contents
                    );
            return $text;

        }
        else {
            $this->printError( 'template file not set or template not loaded' );
        }
    }   // end function parseTemplate()
    
    /**
     * alias for parseTemplate(), but prints template, too
     *
     * See parseTemplate() for details
     *
     **/
    public function printTemplate( $contents ) {
        echo $this->parseTemplate( $contents );
    }   // end function printTemplate()

    /**
     * parse the template contents
     *
     *
     *
     **/
    public function parseString( $string = "", $aArray ) {

        $this->log()->LogDebug( 'string to parse: ',   $string );
        $this->log()->LogDebug( 'replacement array: ', $aArray );

        if ( empty( $string ) ) {
            $this->printError( 'missing string to parse!' );
        }

        // ----- remove comments -----
        $string = $this->__handleComments( $string );

        $this->log()->LogDebug( 'string after __handleCommments(): ',   $string   );

        // ----- handle includes -----
        $string = $this->__handleIncludes( $string, $aArray );
        
        $this->log()->LogDebug( 'string after __handleIncludes(): ',    $string   );

        // ----- handle language strings -----
        $string = $this->__handleLangStrings( $string );

        $this->log()->LogDebug( 'string after __handleLangStrings(): ', $string   );

        // ----- handle loops -----
        $string = $this->__handleLoops( $string, $aArray );
        
        $this->log()->LogDebug( 'string after __handleLoops(): ',       $string   );
        $this->log()->LogDebug( 'Array after __handleLoops(): ',        $aArray   );

        // extract {{ :if }} ... {{ :ifend }}
        $string = $this->__handleIf( $string, $aArray );
        
        $this->log()->LogDebug( 'string after __handleIf(): ',          $string   );

        // other
        $string = preg_replace(
                    $this->_regexp['var'],
                    'isset( $aArray[trim("$1")] ) ? $aArray[trim("$1")] : "$0"',
                    $string
                );

        $this->log()->LogDebug( 'remaining string: ', $string );

        // remove multiple blank lines ("holes")
        if ( $this->_remove_blanks ) {
            $string = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]{2,}/s", '', $string );
        }

        // handle unknown
        return $this->__handleUnknowns( str_replace( '#####', "\n", $string ) );

    }   //  function parseString()
    
    /**
     *
     *
     *
     *
     **/

   	public function capture ( $capture = NULL, $obj = NULL ) {
   	
        if ( $capture ) {
        
            ob_start();
			      $res = eval ($capture);
			      $result = ob_get_contents();
		        ob_end_clean();
		        return $result;
        }
        
		    return NULL;
	  }
    
    /**
     * handle {{ :include <File> }} directive
     *
     * @access private
     * @param  string   $string - template contents
     * @param  array    $aArray - replacements array
     *
     **/
    private function __handleIncludes( $string, $aArray ) {
    
        $this->log()->LogDebug( '[__handleIncludes] current string:', $string );
    
        if (
            preg_match_all(
                $this->_regexp[ 'include' ],
                $string,
                $cond,
                PREG_SET_ORDER
            )
        ) {

            // for all includes...
            foreach ( $cond as $item ) {

                $file = $this->_templatePath.'/'.trim( $item[1] );

                if ( file_exists( $file ) ) {
                
                    $this->log()->LogDebug( 'found file: ', $file );
                    
                    $in      = $this->slurp( $file );
                    $replace = $this->parseString( $in, $aArray );
                    
                    $this->log()->LogDebug( '[__handleIncludes] replacing ['.$item[0].']', array( $replace, $string ) );

                    $string  = str_replace(
                                   $item[0],
                                   $replace,
                                   $string
                               );
                               
                }
                else {
                
                    $this->log()->LogDebug( '[__handleIncludes] file ['.$file.'] not found, removing ['.$item[0].']' );
                    
                    $string = str_replace(
                                  $item[0],
                                  '',
                                  $string
                              );
                }
            }
            
        }
        
        return $string;
        
    }   // end function __handleIncludes()
    
    /**
     *
     *
     *
     *
     **/
    private function __handleUnknowns ( $aStr ) {

        if ( $this->_on_unknown     == 'remove' ) {

            // remove empty
            $aStr = preg_replace(
                        $this->_regexp['all'],
                        '',
                        $aStr
                    );

        }
        elseif ( $this->_on_unknown == 'comment' ) {

            // replace empty with comment
            $aStr = preg_replace(
                        $this->_regexp['all'],
                        '<!-- unresolved template param: $1 -->',
                        $aStr
                    );

        }
        elseif ( $this->_on_unknown == 'fail' ) {

            if ( preg_match( "/{{\s(.*)\s}}/", $aStr, $unknowns ) ) {

                $this->printError(
                    'Unresolved placeholders found in template '. $this->_templateFile,
                    $unknowns
                );

            }

        }
        
        return $aStr;
        
    }   // end function __handleUnknowns()
    
    /**
     * handle if statements
     *
     * @access private
     * @param  string   $string    - template contents
     * @param  array    $aArray    - replacements array
     *
     * @return string
     *
     **/
    private function __handleIf ( $string, $aArray ) {

        # $this->log()->LogDebug( 'current string:', $string );

        while ( preg_match( $this->_regexp[ 'if' ], $string, $matches ) ) {

            # $this->log()->LogDebug( 'handle match: ', $matches );

            $matches[1] = trim( $matches[1] );
            $replace    = '';
            $conditions = array();

            // handle {{ :else }}
            $parts        = preg_split(
                                "/{{\s*?:else\s*?}}/seix",
                                $matches[2],
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE
                            );

            // handle 'AND' && / 'OR'  in condition
            $cond_matches = preg_split(
                                "/\s+(AND|OR|\|\||\&\&)\s+/",
                                $matches[1],
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE
                            );

            # $this->log()->LogDebug( 'cond_matches: ', $cond_matches );

            $item = trim( array_shift( $cond_matches ) );
            $conditions[] = "isset( \$aArray['$item'] ) && ! empty( \$aArray['$item'] )";

            if ( count( $cond_matches ) > 0 ) {

                // count must be uneven
                // note: the first element is shifted from the array (see
                // above), so we check for "even" here, meaning "uneven"
                if ( count( $cond_matches ) % 2 != 0 ) {
                    $this->printError(
                        "Uneven number of elements in conditional statement expected:<br />\n"
                      . $matches[1]
                    );
                }

                // split into chunks
                while(
                        count( $cond_matches ) > 0
                        &&
                        list( $op, $item ) = array_splice( $cond_matches, 0, 2 )
                ) {

                    $item = trim($item);

                    $conditions[]
                        = "$op\nisset( \$aArray['$item'] ) && ! empty( \$aArray['$item'] )";

                }

            }

            $cond_string = 'if ( ' . implode( "\n", $conditions ) . ' ) { return true; }';

            // param found in param array
            if ( eval( $cond_string ) ) {
                $replace = $parts[0];
            }
            else {

                // handle else
                if ( isset( $parts[1] ) ) {
                    $replace = $parts[1];
                }

            }
            
            # $this->log()->LogDebug( 'replacing ... with ... in string ...', array( $matches[0], $replace, $string ) );

            ### remove from string
            $string = str_replace( $matches[0], $replace, $string );
            
            # $this->log()->LogDebug( 'remaining string:', $string );

        }
        
        # $this->log()->LogDebug( 'remaining string:', $string );
        
        return $string;

    }   // end function __handleIf()
    

    /**
     * handle loop statements
     *
     * @access private
     * @param  string   $string    - template contents
     * @param  array    $aArray    - replacements array
     * @return void
     *
     **/
    private function __handleLoops ( $string, &$aArray ) {

        # $this->log()->LogDebug( 'current string:', $string );
        
        $loops = array();

        // first, find all loops and subloops and extract them
        while ( preg_match( $this->_regexp[ 'loop' ], $string, $matches ) ) {

            // this is the name of the array key
            $condname         = trim( $matches[1] );
            
            // store loop contents
            $loops[$condname] = $matches[2];
            
            // remove loop from string; replace with "normal" template var
            $string = str_replace(
                $matches[0],
                "{{ __loop__{$condname} }}",
                $string
            );

        }
        
        # $this->log()->LogDebug( 'found loops: ', $loops );
        # $this->log()->LogDebug( 'current string: ', $string );

        // now, if we have any loops...
        if ( count( $loops ) > 0 ) {
        
            $code = '  ( isset( $loop[trim("$1")] ) && ! is_array( $loop[trim("$1")] ) )'
              . '? $loop[trim("$1")] '
              . ': ( '
              .     '  ( strlen("$1") > 8 && ! substr_compare( "$1", "__loop__", 0, 8 ) ) '
              .     '? $this->recurse( $loops[substr("$1",8)], $loops, $loop, false )'
              .     ': "$0"'
              . '  )';

            #$string = $this->recurse( $string, $loops, $aArray );
            foreach ( $loops as $key => $val ) {

                if ( isset( $aArray[ $key ] ) && is_array( $aArray[ $key ] ) ) {

                    $out  = '';

                    // parse loop
                    foreach ( $aArray[ $key ] as $loop ) {

                        if ( is_array( $loop ) ) {

                            # $this->log()->LogDebug( 'current loop data: ', $loop );
                            
                            // handle {{ :if }} inside {{ :loop }}
                            $val = $this->__handleIf( $loops[$key], $loop );

                            // replace vars in current (remaining) line
                            $out .= preg_replace(
                                            $this->_regexp['var'],
                                            $code,
                                            $val
                                        );

                        }

                    }
                    
                    // remove array key
                    $aArray[ $key ] = 1;

                    $string = str_replace(
                                  "{{ __loop__{$key} }}",
                                  $out,
                                  $string
                              );

                }

            }

        }

        return $string;
        
    }   // end sub __handleLoops()
    
    /***
     *
     *
     *
     *
     **/
    function recurse ( $string, $loops, $aArray, $return_string = true ) {
    
        $out = NULL;
    
        if ( ! is_array( $loops ) ) {
            return $string;
        }

        $code = '  isset( $loop[trim("$1")] ) '
              . '? $loop[trim("$1")] '
              . ': ( '
              .     '  ( strlen("$1") > 8 && ! substr_compare( "$1", "__loop__", 0, 8 ) ) '
              .     '? $this->recurse( $loops[substr("$1",8)], $loops, $loop, false )'
              .     ': "$0"'
              . '  )';

        foreach ( $aArray as $key => $value ) {

            if ( isset ( $loops[$key] ) ) {
            
                $this->log()->LogDebug( 'found loop for key '.$key );
                $this->log()->LogDebug( 'aArray data: ', $aArray[ $key ] );
            
                if ( is_array( $aArray[ $key ] ) ) {

                    $out  = '';

                    // parse loop
                    foreach ( $aArray[ $key ] as $loop ) {
                    
                        if ( is_array( $loop ) ) {

                            $this->log()->LogDebug( 'current loop data: ', $loop );
                            
                            // handle {{ :if }} inside {{ :loop }}
                            #$text = $this->__handleIf( $loops[$key], $loop );
                            $text = $loops[$key];

                            // replace vars in current (remaining) line
                            $out .= preg_replace(
                                            $this->_regexp['var'],
                                            $code,
                                            $text
                                        );

                        }

                    }
                    
                    if ( $return_string ) {
                        return str_replace(
                            "{{ __loop__{$key} }}",
                            $out,
                            $string
                        );
                    }

                }
                
            }
            

            
        }

        return $out;

    }
    
    /**
     * handle {{ :lang <Text> }} directives
     *
     * @access private
     * @param  string   $string - template contents
     *
     * @return string   $string - parsed string
     *
     **/
    private function __handleLangStrings( $string ) {
    
        $this->log()->LogDebug( 'current string:', $string );

        while ( preg_match( $this->_regexp[ 'lang' ], $string, $matches ) ) {
            $text = trim( $matches[1] );
            if ( is_object( $this->_lang ) ) {
                $text = $this->_lang->translate( $text );
            }
            $string = str_replace( $matches[0], $text, $string );
        }
        
        return $string;
        
    }   // end function __handleLangStrings()
    
    /**
     *
     *
     *
     *
     **/
    private function __handleComments( $string ) {
    
        // remove simple comments
        $string = preg_replace( $this->_regexp['simple_comment'], '', $string );

        // remove comment blocks
        $string = preg_replace( $this->_regexp['html_comment'], '', $string );
        
        return $string;
    
    }   // end function __handleComments()

}