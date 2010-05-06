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

// this class inherits from wbBase
require_once dirname( __FILE__ ).'/class.wbBase.php';

class wbTemplate extends wbBase {

    // ----- Debugging -----
    #protected $debugLevel     = KLOGGER::OFF;
    protected $debugLevel     = KLOGGER::DEBUG;

    //
    protected $_config
        = array (
              'unknowns'       => 'remove',
              'path'           => '/templates',
              'file'           => 'index.tpl',
              'cachedir'       => '/wbTemplate/compiled',
              'start_tag'      => '{{',
              'end_tag'        => '}}',
              'remove_blanks'  => true,
          );

    // accessor to wbI18n
    private   $_lang           = NULL;

    // array to store fillings
    protected $_fillings       = array();

    // array to store the current stack when parsing the template
    private   $_stack          = array();

    // how deep we are in the tree...
    private   $_depth          = 0;

    // data container
    private   $_data           = array();

    // vars to for nested loops
    private   $_loops_path     = array();
    private   $_loop_vars      = array();
    private   $_var_index      = 0;
    
    // data array to transfer the template data to the compiled template
    public    $data            = array();
    
    
    /**
     * constructor
     **/
    function __construct ( $options = array() ) {
        $this->_loop_vars = array_merge( array( '0' ), range( 'a', 'z' ) );
        parent::__construct( $options );
    }   // end function __construct()

/*******************************************************************************
 * Configuration helpers
 ******************************************************************************/

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
                $this->_config['unknowns'] = $behaviour;
                break;

            default:
                $this->printError( 'Unsupported behaviour: '.$behaviour );
        	      break;
        }

    }   // end function setBehaviour()

    /**
  	 * alias for setFile()
  	 **/
    public function setTemplate ( $file ) {
        return $this->setFile( $file );
    }   // end function setTemplate()

/*******************************************************************************
 *
 ******************************************************************************/

    /**
     *
     *
     *
     *
     **/
    public function setVal( $var, $value = NULL ) {

        if ( ! is_array( $var ) && isset( $value ) ) {
           $this->_fillings[ $var ] = $value;
           return;
        }

        if ( is_array( $var ) ) {
            foreach ( $var as $k => $v ) {
                $this->_fillings[ $k ] = $v;
            }
        }

    }  // end function setVal()


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
    public function getTemplate ( $file, $attr = array(), $cache = false ) {
        $this->log()->LogDebug( 'setting template file: '.$file );
        $this->setFile( $file );
        return $this->parseTemplate( $attr, $cache );
    }   // end function getTemplate()

    /**
     * parse template
     *
     * Returns parsed template (set with setFile()) or prints error and exits
     * if an error occurs (no template file given or template not loaded)
     *
     * @access public
     * @param  array   $contents  - replacements
     * @param  boolean $cache     - enable/disable cache (default: disabled)
     * @return mixed
     *
     **/
    public function parseTemplate( $contents, $cache = false ) {

        if (
               isset( $this->_config['current_filename'] )
               &&
               isset( $this->_loaded[ $this->_config['current_filename'] ] )
        ) {
            $text = $this->parseString(
                        $this->_loaded[ $this->_config['current_filename'] ],
                        $contents,
                        $cache
                    );
            return $text;

        }
        else {
        
            if ( isset( $this->_config['current_filename'] ) )
            {
                $this->printError(
                    $this->translate(
                        'template {{ template }} not loaded',
                        array( 'template' => $this->_config['current_filename'] )
                    )
                );
            }
            $this->printError(
                $this->translate( 'template file not set or template not loaded' )
            );
        }
        
    }   // end function parseTemplate()

    /**
     * alias for parseTemplate(), parse and print with one call
     *
     * See parseTemplate() for details
     *
     **/
    public function printTemplate( $contents, $cache = false ) {
        echo $this->parseTemplate( $contents, $cache );
    }   // end function printTemplate()

    /**
     * parse the template contents
     *
     * @access public
     * @param  string  $string   - string to parse
     * @param  array   $fillings - fillings array
     * @param  boolean $cache    - enable/disable cache (default: disabled)
     *
     **/
    public function parseString( $string = "", $fillings = array(), $cache = false ) {

        if ( empty( $string ) ) {
            $this->printError( 'missing string to parse!' );
        }

        $parsed = $this->__compile( $string, $fillings, $cache );
        
        return $parsed;

    }   // end function parseString()

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

	  }   // end function capture()
	  
	  /**
	   *
	   *
	   *
	   *
	   **/
    public function handleMissing( $var, $text = NULL ) {

        if ( $this->_config['unknowns']     == 'remove' ) {
            return '';
        }
        elseif ( $this->_config['unknowns'] == 'comment' ) {
            return '<!-- unresolved template param: '.$var.' -->'."\n";

        }
        else { # fail
            $this->printError(
                'Unresolved placeholder found in template '
              . $this->_config['file']
              . '<br />'
              . $text
            );
        }

    }   // end function handleMissing()
    

/*******************************************************************************
 * Private methods
 ******************************************************************************/

    /**
     *
     *
     *
     *
     **/
	  private function __compile( $string, $fillings, $cache = false )
    {
    
        $checksum   = md5_file( $this->_config['current_file'] );
        $cache_file = $this->_config['current_filename'].'_'.$checksum;

        if ( $cache )
        {
	  
            // do we have a cached file?
            if ( file_exists( $this->_config['workdir'].'/'.$this->_config['cachedir'].'/'.$cache_file ) )
            {
                $this->log()->LogDebug( "loading cached template" );
                ob_start();
                include $this->_config['workdir'].'/'.$this->_config['cachedir'].'/'.$cache_file;
                return ob_get_clean();
            }
            else { // clean cache; original template file was changed
                $this->__cleanCache( $this->_config['current_filename'] );
            }
            
        }
	  
        $O     = $this->_config['start_tag'];
        $C     = $this->_config['end_tag'];

        // let's see if we have globally stored contents
        if ( count( $this->_fillings ) > 0 ) {
            $this->log()->LogDebug( 'adding fillings to pre-stored contents' );
            $fillings = array_merge ( $fillings, $this->_fillings );
        }
        
        $this->log()->LogDebug( 'string:', $string );
        $this->log()->LogDebug( 'fillings:', $fillings );

        // get all included files
        $string = $this->__getIncludes ( $string );
        // ----- now we have a complete template to parse -----
        
        // remove simple comments
        $string = preg_replace(
                      "/".$this->_config['start_tag'].
                        "\s*?:comment.*?".
                        $this->_config['end_tag'].
                        "/seix",
                      '',
                      $string
                  );

        // remove comment blocks
        $string = preg_replace(
                      "/<!--\s*?BEGIN\s*?template\s*?comment\s*?-->(.+?)<!--\s*?END\s*?template\s*?comment\s*?-->/seix",
                      '',
                      $string
                  );

        // extract loops and if statements
        $string = $this->__extractBlocks( $string );
        
        // handle translations
        $trans_regexp = "#$O\s*:(lang|translate)\s*([^$C].*?)\s*$C#eim";
        $string       = preg_replace( $trans_regexp, "\$this->translate( '\\2' )", $string );

        // matches normal vars
        $vars_regexp = "$O\s*([^:]\w+)\s*$C";

        while(
                preg_match( "/$vars_regexp/im",
                            $string,
                            $vars )
        ) {
        
            $code   = '<?php '."\n".
                      '    if ( isset( $fillings["'.$vars[1].'"] ) ) :'."\n".
                      '        echo $fillings["'.$vars[1].'"];'."\n".
                      '    else:'."\n".
                      '        echo "'.$this->handleMissing( $vars[1], "no data for var -".$vars[1]."-" ).'";'."\n".
                      '    endif;'."\n".
                      '?>';

            $string = str_replace(
                          $vars[0],
                          $code,
                          $string
                      );

        }
        
        // store compiled template
        $fh = fopen( $this->_config['workdir'].'/'.$this->_config['cachedir'].'/'.$cache_file, 'w' );
        fwrite( $fh, '<'.'?php'."\n" );
        fwrite( $fh, '    $fillings = $this->data;'."\n".'?'.'>'."\n" );
        fwrite( $fh, $string );
        fwrite( $fh, "\n" );
        fclose( $fh );

        $this->data = $fillings;

        ob_start();
        include $this->_config['workdir'].'/'.$this->_config['cachedir'].'/'.$cache_file;
        $output = ob_get_clean();

        if ( ! $cache )
        {
            unlink( $this->_config['workdir'].'/'.$this->_config['cachedir'].'/'.$cache_file );
        }
        
        return $output;
        
	  }   // end function __compile()

    /**
     * handle {{ :include <File> }} directive
     *
     * loads all included template files and adds them to the parent template
     *
     * @access private
     * @param  string   $string - template contents
     * @return string
     *
     **/
    private function __getIncludes( $string ) {

        $this->log()->LogDebug( '[__getIncludes] current string:', $string );

        // define regexp
        $regexp = "/"
                . $this->_config['start_tag']
                . "\s*?:include\s*?(.*?)\s*?"
                . $this->_config['end_tag']
                . "/i";

        while( preg_match( $regexp, $string, $matches ) ) {

            $file    = $this->_config['path'].'/'.trim( $matches[1] );
            $content = NULL;

            // see if the file exists
            if ( file_exists( $file ) ) {
                $this->log()->LogDebug( 'found file: ', $file );
                $content = $this->slurp( $file );
            }
            else {
                $file = basename( $file );
                $this->printError( "Template error: Included file -$file- does not exist!" );
            }

            // replace the include placeholder with the file contents
            // and start over
            $string = str_replace(
                          $matches[0],
                          $content,
                          $string
                      );


        }

        return $string;

    }   // end function __getIncludes()

    /**
     *
     *
     *
     *
     **/
    private function __extractBlocks( $string ) {

    		// reset all needed counters and stacks
    		$this->_depth	    =	0;
    		$this->_stack	    =	array();
    		$this->_data	    =	array();

        // $1 - complete tag
        // $2 - only set if closing tag matches
        // $3 - var name
        // $4 - content
        $regexp = '('                            # capture
                .    $this->_config['start_tag'] # {{ open
                .       "\s*"                    # optional space
                .       ':(if|loop)(end)?'       # optional          $3
                .       "\s*"                    # optional space
                .       '('                      # capture...
                .         "[^"
                .            $this->_config['end_tag']
                .         "]*"                   # anything but closing
                .       ')'                      # end capture
                .    $this->_config['end_tag']   # }} close
                . ')';

        $tokens = preg_split(
                      "/$regexp/im",
                      $string,
                      -1,
                      PREG_SPLIT_DELIM_CAPTURE
                  );

        // see how many tokens we have found, and if there are
    		// none, we just return the string.
    		$cnt = count( $tokens );
    		if( $cnt <= 1 ) {
            $this->log()->LogDebug( 'No loops found' );
    		    return $string;
        }

        // this is the string before the first match
        $this->__cData( $tokens[0] );

        // extract blocks
        $i = 1;
        while( $i < $cnt ) {

            // each tag has a complete set of data.
			      $fullTag	  = $tokens[$i++]; # tag with brackets
			      $type       = $tokens[$i++]; # if or loop
			      $isClosing	= ( strtolower( $tokens[$i++] ) === 'end' ) ? true : false;
			      $key        = trim($tokens[$i++]); # variable name
			      $_cData		  = $tokens[$i++]; # text until next token

#echo "current match:<br />Full -$fullTag-<br />Type -$type-<br />isClosing -$isClosing-<br />Var -$key-<br />",( isset( $_cdata ) ? "Content -$_cdata-<br />" : '' ),"<br />";

			      if ( ! $isClosing ) {
                // begin recording a loop
                $this->__startBlock( $key, $type, $fullTag );
            }
            else {

                // end recording
                list( $fullmatch, $replacement )
                    = $this->__endBlock( $fullTag );

                $this->__cData( $replacement );

                // replace the FIRST OCCURENCE of that match
                
                // get the start point of the first match
                $pos    = strpos( $string, $fullmatch );

                // replace the first occurence
                $string = substr_replace( $string, $replacement, $pos, strlen( $fullmatch ) );
                
                #$string    = str_replace(
                #                 $fullmatch,
                #                 $replacement,
                #                 $string,
                #                 &$count
                #             );

            }

            // put text onto stack
            $this->__cData( $_cData );

        }

        // the stack should be empty now
        if ( count ( $this->_stack ) > 0 ) {
            $this->printError( "Template error: Stack is not empty after block extraction!" );
        }
        
        $this->log()->LogDebug( 'remaining string:', $string );

        return $string;

    }   // end sub __extractBlocks()

    /**
     *
     *
     *
     *
     **/
    private function __startBlock ( $key, $type, $fullTag ) {

        $this->_depth++;
        
        // reset the data stack
		    $this->_data[ $this->_depth ] = '';

        $el = array(
    			  'fulltag'   => $fullTag,
    			  'key'       => $key,
    			  'type'      => $type,
    			  'path'      => array(),
    		);

        if ( $type == 'loop' )
        {
            // push the current loop key to the path
            array_push( $this->_loops_path, $key );
            
            // increment the var index
            $this->_var_index++;

        }

        $el['path'] = $this->_loops_path;

        // push the element to the stack
        array_push( $this->_stack, $el );

        $this->log()->LogDebug(
            'started a new block:',
            $el
        );

        $this->log()->LogDebug( 'opened block of type: '.$type );

        return true;

    }   // end function __startBlock()

    /**
     *
     *
     *
     *
     **/
    private function __endBlock( $fullTag ) {
    
        $O     = $this->_config['start_tag'];
        $C     = $this->_config['end_tag'];
        
        // remove the element from the stack
        $el   =	array_pop( $this->_stack );

        // retrieve the level data
		    $el['cdata'] =	$this->_data[ $this->_depth-- ];

        $this->log()->LogDebug(
            'ending block:',
            $el
        );
        
        $this->log()->LogDebug( 'closing block:', $el );
        
        $data = $el['cdata'];
        
        // matches normal vars
        $vars_regexp = "$O\s*([^:]\w+)\s*$C";

        switch ( $el['type'] ) {
        
            // ----- handle loops -----
            case 'loop':

                $var = '$fillings';
                
                // the last element of the path is the current loop itself
                array_pop( $el['path'] );

                if ( count( $el['path'] > 0 ) )
                {
                    $i = 1;
                    
                    foreach ( $el['path'] as $index => $item )
                    {
                        $var .= '["'.$item.'"][$'.$this->_loop_vars[$i].']';
                        $i++;
                    }
                    
                    $index = count($el['path'])+1;
                    
                }
                else {
                    $index = 1;
                }
                
                $var .= '["'.$el['key'].'"]';
                $i    = '$'.$this->_loop_vars[ $this->_var_index ];
                
                $code = '<?php if ( isset( '.$var.' ) ): '."\n"
                      . '    for ( '.$i.'=0; '.$i.'<count('.$var.'); '.$i.'++ ): ?>'."\n";
                      
                // replace vars in cdata
                while(
                        preg_match( "/$vars_regexp/im",
                                    $data,
                                    $vars )
                ) {
                    $this_var    = $var.'['.$i.']["'.$vars[1].'"]';
                    $data        = str_replace(
                                       $vars[0],
                                       '<?php if ( isset( '.$this_var.' ) ): echo '.$this_var.'; else: echo "' . $this->handleMissing( $vars[1], "no data for var -".$vars[1]."-" ) . '"; endif; ?>',
                                       $data
                                   );
                }
                
                $this->_var_index--;

                array_pop( $this->_loops_path );

                return array(
                           $el['fulltag'].$el['cdata'].$fullTag,
                           $code.$data.'<?php endfor; endif; ?>'
                       );
                break;

            // ---- handle ifs -----
            case 'if':

                $var  = '$fillings';

                if ( count( $el['path'] ) > 0 )
                {
                    $i = 1;
                    foreach ( $el['path'] as $index => $item )
                    {
                        $var .= '["'.$item.'"][$'.$this->_loop_vars[ $i ].']';
                        $i++;
                    }
                    $index = count($el['path'])+1;
                }
                $var .= '["'.$el['key'].'"]';
                
                // handle else
                $data = preg_replace( "/$O\s*:else\s*$C/im", '<?php else: ?>', $data );

                $code = '<?php if ( isset( '.$var.' ) ): ?>'."\n".$data.'<?php endif; ?>';

                // create a placeholder
                return array(
                           $el['fulltag'].$el['cdata'].$fullTag,
                           $code."\n"
                       );

                break;
                
            default:
                $this->printError( 'unknown block type: '.$el['type'] );
                break;
                
        }

    }   // end function __endBlock()
    
    /**
     *
     *
     *
     *
     **/
    private function __cleanCache( $file )
    {
    
        $dir = opendir( $this->_config['workdir'].'/'.$this->_config['cachedir'] );
        
        if ( $dir )
        {
            while ( ( $filename = readdir($dir) ) !== false )
            {
                if ( substr( $filename, 0, strlen($file) ) == $file )
                {
                    unlink( $this->_config['workdir'].'/'.$this->_config['cachedir'].'/'.$filename );
                }
            }
        }
        
    }   // end function __cleanCache()

    /**
     *
     *
     *
     *
     **/
    private function __cData( $data ) {

    	  if( ! isset( $this->_data[$this->_depth] ) ) {
    		    $this->_data[$this->_depth]	=	'';
    	  }

    	  $this->_data[$this->_depth]	.=	$data;

    }   // end function __cData()


}

?>