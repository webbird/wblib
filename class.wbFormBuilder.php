<?php

/**

  FormBuilder helper class

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
require_once dirname( __FILE__ ).'/class.wbTemplate.php';
require_once dirname( __FILE__ ).'/class.wbI18n.php';

class wbFormBuilder extends wbBase {

    // ----- Debugging -----
    protected      $debugLevel      = KLOGGER::OFF;
    #protected      $debugLevel      = KLOGGER::DEBUG;

    protected      $_settings       = array();

    // store lang handle (I18n object)
    private        $lang;

    // store validator handle
    private        $val             = NULL;

    // default form layout type
    private        $_type           = 'table';

    // array to store complete options array
    private static $_options        = array();

    // current language; can be set using setLanguage()
    private        $_current_lang   = 'EN';

    // array to store errors
    private        $_errors         = array();

    // array to store form elements
    private        $_elements       = array();

    // array to store hidden fields
    private        $_hidden         = array();

    // array to store form buttons
    private        $_buttons        = array();

    // array to store required fields (for validation)
    private        $_required       = array();

    // array to store optional fields
    private        $_optional       = array();

    // array to store checks
    private        $_checks         = array();

    // array to store fields that should have equal values to other fields
    private        $_equals         = array();
    
    // array to store readonly fields
    private        $_readonly       = array();

    // array to store valid form items
    private        $_valid          = array();

    // prefix used for random field name generation
    private        $_name_prefix    = 'random_fieldname_';

    // validate form field attributes yes/no; can be set using setValidation()
    private        $_validate       = true;

    // <form> element defaults
    private        $_form_defaults  = array( 'method' => 'post', 'action' => '' );

    // CSS for errors; can be set using setErrorStyle()
    private        $_error_style    = 'color: #ff0000; border: 1px solid #ff0000;';

    // default CSS classes; can be set using setCSSClasses()
    private        $_form_css       = array(
                                          'fb_header_class'   => 'fbheader',
                                          'fb_info_class'     => 'fbinfo',
                                          'fb_button_class'   => 'fbbuttons',
                                          'fb_left_class'     => 'fbleft',
                                          'fb_right_class'    => 'fbright',
                                          'fb_error_class'    => 'fberror',
                                          'fb_req_class'      => 'fbrequired',
                                          'fb_fieldset_class' => 'fbfieldset',
                                          'fb_legend_class'   => 'fblegend',
                                          'fb_button_class'   => 'fbbutton',
                                      );

    /**
     * HTML wrapper; defaults are set by constructor an can be overwritten
     * using setFormTemplate() and setElementTemplate()
     **/
		private        $_form_template;
		private        $_form_header_template;
		private        $_form_legend_template;
    private        $_element_template;
    private        $_form_infotext_template;

    // accessor to template class
    private        $_tpl;

    private static $_knownAttrs;
    private static $_registered_elements;
    private static $_is_loaded      = array();

    // map 'allow'ed to PCRE_ constants
    private static $_allowed        = array(
                                          'number'   => 'PCRE_INT',
                                          'string'   => 'PCRE_STRING',
                                          'password' => 'PCRE_PASSWORD',
                                          'email'    => 'PCRE_EMAIL',
                                          'url'      => 'PCRE_URI',
                                          'plain'    => 'PCRE_PLAIN',
                                          'boolean'  => 1,
                                      );

    /***************************************************************************
     attributes allowed in (nearly) all html tags
    ***************************************************************************/
    private static $_common
        = array(
              'class' => 'PCRE_STRING',
              'id'    => 'PCRE_ALPHANUM_EXT',
              'style' => 'PCRE_STYLE',
              'title' => 'PCRE_STRING',
              'dir',
              'lang'
          );

    /***************************************************************************
     attribute to add always
     Format:
         'attribute' => '<Default>'
    ***************************************************************************/
    private static $_always_add
        = array(
              'value' => '',
              'name'  => '&$this->__generateRandomName()',
              'id'    => '&$options["name"]',
          );

    private static $_form
        = array(
              'method'       => array( 'post', 'get' ),
              'action'       => 'PCRE_PLAIN',
              'enctype'      => 1
          );

    /***************************************************************************
     known attributes for <input>
     Format:
         'key' => 1              - known
         'key' => ...def...      - allowed values

     deprecated attributes:
         align

    ***************************************************************************/
    private static $_input
        = array(
              'accept' 	      => 1,
              'alt' 	        => 'PCRE_UTF8_STRING',
              'checked'       => array( 'checked'  ),
              'disabled'      => array( 'disabled' ),
              'name'          => 'PCRE_STRING',
              'onblur'        => 1,
              'onchange'      => 'PCRE_PLAIN',
              'onfocus'       => 1,
              'onselect'      => 1,
              'readonly'      => array( 'readonly' ),
              'size'          => 'PCRE_INT',
#              'tabindex'      => $CM::Utils::HTML::Tagset::Number,
              'type'          => array(
                                     'text',
                                     'password',
                                     'checkbox',
                                     'radio',
                                     'submit',
                                     'reset',
                                     'file',
                                     'hidden',
                                     'image',
                                     'button'
                                 ),
              'value'         => 'PCRE_STRING',

// type="image"
              'ismap'         => array( 'ismap'    ),
              'src'           => 1,
              'usemap'        => 1,
          );


    /**
     * constructor
     *
     * @param array $options
     *
     *   known keys:
     *
     *     'include' => file to load form from
     *     'lang'    => language to use ('EN', 'DE', ...)
     *     'asTable' => true/false
     *
     **/
    function __construct ( $options = array() ) {
    
        parent::__construct();

        self::$_input[ 'accesskey' ] = array_merge( range(0,9), range('a','z') );
        self::$_input[ 'maxlength' ] = array( range(0,999) );

        self::$_knownAttrs = array_merge(
                                 self::$_common,
                                 self::$_input,
                                 self::$_form
                             );

        $this->_settings = $options;

        // create validator object
        $this->val  = new wbValidate();

        // get current working directory
        $callstack = debug_backtrace();
        $this->workdir
                   = ( isset( $callstack[0] ) && isset( $callstack[0]['file'] ) )
                   ? dirname( $callstack[0]['file'] )
                   : dirname(__FILE__);

        // save accessor to language class
        if ( isset( $this->_settings['lang'] ) && is_object( $this->_settings['lang'] ) ) {
            $this->lang          = $this->_settings['lang'];
            $this->_current_lang = $this->lang->getLang();
        }
        elseif ( isset( $this->_settings['uselang'] ) ) {
            $this->lang          = new wbI18n( $this->_settings['uselang'] );
            $this->_current_lang = $this->lang->getLang();
            $this->lang->setPath( $this->workdir.'/languages' );
        }
        else {
            $this->lang          = new wbI18n('EN');
        }

        $this->lang->addFile(
            $this->_current_lang.'.php',
            $this->workdir.'/languages'
        );
        
        // add wblib lang file
        $this->lang->addFile(
            $this->_current_lang.'.php',
            dirname(__FILE__).'/languages'
        );

        // create accessor to template class
        $this->_tpl = new wbTemplate( array( 'lang' => $this->lang ) );
        $this->_tpl->setBehaviour( 'remove' );
        $this->_tpl->setPath( dirname(__FILE__).'/templates' );

        if ( isset( $this->_settings['include'] ) ) {
            $this->loadFile( $this->_settings );
        }

        if (
             isset( $this->_settings['asTable'] )
             &&
             $this->_settings['asTable'] === false
        ) {
            $this->_type = 'fieldset';
        }

        $this->setFormType( $this->_type );

    }   // end function __construct()


    /**
     * include file with form settings
     *
     *
     *
     **/
    public function loadFile( $options ) {

        $this->log()->LogDebug( 'loadFile() called with options:', $options );

        if ( isset( $options['include'] ) ) {
        
            $loaded = false;
        
            foreach (
                array(
                    $options['include'],
                    $this->workdir.'/'.$options['include']
                ) as $file
            ) {

                if ( file_exists( $file ) ) {

                    if ( isset( self::$_is_loaded[$file] ) ) {
                        $this->log()->LogDebug( 'file already loaded: ', $file );
                        return;
                    }

                    $this->log()->LogDebug( 'loading config file: ', $file );
                    include_once $file;
                    self::$_is_loaded[ $file ] = true;
                    $loaded                    = true;

                    break;
                    
                }
                
            }
            
            if ( ! $loaded ) {
                $this->printError( 'include file does not exist: '.$options['include'] );
            }


        }
        else {
            $this->log()->LogDebug( 'include file not set' );
            return;
        }

        $VAR = isset( $options['varname'] )
             ? $options['varname']
             : 'FORM';

        eval( "self::\$_options = & \$".$VAR.";" );

        foreach ( self::$_options as $area => $area_items ) {
        
            if ( ! empty( $area ) ) {

                $this->__registerElement(
                    array(
                        'type' => 'legend',
                        'content' => $this->lang->translate( $area )
                    )
                );
                
            }

            foreach ( $area_items as $item ) {
                $this->__registerElement($item);
            }

        }

        $this->log()->LogDebug( 'options:', self::$_registered_elements    );

    }   // end function loadFile()
    
    
    /**
     * set element to 'readonly'
     **/
    public function setReadonly( $name ) {
        $this->_readonly[ $name ] = 1;
    }   // function setReadonly()
    
    /**
     * set element to 'writable'
     **/
    public function unsetReadonly( $name ) {
        $this->log()->LogDebug( 'unset readonly flag for field ['.$name.']' );
        unset( $this->_readonly[ $name ] );
    }   // function setReadonly()

    /**
     * set element to 'required'
     **/
    public function setRequired( $name ) {
        $this->log()->LogDebug( 'set required flag for field ['.$name.']' );
        unset( $this->_optional[$name] );
        $this->_required[ $name ] = 1;
    }   // function setReadonly()

    /**
     * set element to 'optional'
     **/
    public function setOptional( $name ) {
        $this->log()->LogDebug( 'set optional flag for field ['.$name.']' );
        unset( $this->_readonly[ $name ] );
        $this->_optional[$name] = 1;
    }   // function setReadonly()

    /**
     *
     *
     *
     *
     **/
    public function getValid() {
        return $this->_valid;
    }   // end function getValid()

    /**
     *
     *
     *
     *
     **/
    public function getOptionsForArea( $area ) {
        return self::$_options[ $area ];
    }

    /**
  	 * create the element and return it instead of adding it to internal
  	 * element array(s)
  	 *
  	 *
  	 *
  	 **/
  	public function getElement( $options ) {

        $elem = $this->createElement( $options );

        return $this->_tpl->parseString(
                      '{{ content }}{{ error }}',
                      array_merge(
                          $elem,
                          $this->_form_css,
                          array( 'error' => $this->getError( $elem['name'] ) )
                      )
                );

  	}   // end function getElement()


  	/**
  	 * create the element and return it instead of adding it to internal
  	 * element array(s)
  	 *
  	 *
  	 *
  	 **/
  	public function createElement( $options ) {

        $this->log()->LogDebug(
            'createElement() called with args:',
            $options
        );

        $name  = isset( $options['name'] )
               ? $options['name']
               : $this->__generateRandomName();

        $label = isset( $options['label'] )
               ? '<label class="fblabel" for="'.$name.'">'.$options['label'].'</label>'
               : '';

        $options['style']
               = isset( $options['style'] )
               ? $options['style']
               : '';

        if ( ! isset( $options['type'] ) ) {
            // default type is 'text' (single line input field)
            $options['type'] = 'text';
        }

        $this->__registerElement( $options );

        // just shorter...
        $type = $options['type'];

        $this->log()->LogDebug( 'trying to create field ['.$name.'] of type '.$type );

        if ( method_exists( $this, $type ) ) {
            $elem = $this->$type( $options );
        }
        else {
            $elem = array(
                        'name'    => $name,
                        'label'   => $label,
                        'value'   => isset( $options['value'] )
                                  ?  $options['value']
                                  :  NULL,
                        'content' => $this->__createInput( $options )
                    );
        }

        return $elem;

  	}   // end function createElement()

  	/**
  	 * create an element and store it in internal element array(s)
  	 * (_elements or _hidden)
  	 *
  	 * @access public
  	 * @return void
  	 *
  	 **/
  	public function addElement( $options ) {

  	    $this->log()->LogDebug(
            'called with args:',
            $options
        );

        // add to _elements array by default
        $add_to = & $this->_elements;
        if ( isset( $options['type'] ) ) {
            if ( $options['type'] == 'hidden' ) {
                $add_to = & $this->_hidden;
            }
        }

        $add_to[] = $this->createElement( $options );

        return NULL;

    }   // end function addElement()
    
    /**
  	 * add a list of elements at once
  	 *
  	 * @param array $options
  	 * @param array $current - current settings (array)
  	 *
  	 *
  	 **/
    public function addElements( $options, $current = array() ) {

        self::$_options = & $options;

        foreach ( self::$_options as $name => $item ) {

            $value = isset( $current[ $name ] )
                   ? $current[ $name ]
                   : NULL;

            $item = array_merge(
                        $item,
                        array(
                            'name'  => $name,
                            'value' => $value
                        )
                    );

            $this->__registerElement( $item );

        }

    }   // end function addElements()
    
    /**
  	 * Insert an element at a given position
  	 *
  	 * Can be used to insert a new element before or after an already
  	 * existing element. If the element is not found, the new element
  	 * is added to the end of the form. (Same as addElement())
  	 *
  	 * @access public
  	 * @param  array   $position
  	 *                 array( 'before' => '<element name>' )    OR
  	 *                 array( 'after'  => '<element name>' )
  	 * @param  array   $options
  	 *                 see addElement for details
  	 * @return void
  	 *
  	 **/
  	public function insertElement( $position, $options ) {

  	    $this->log()->LogDebug(
            'called with args:',
            array_merge(
                $position,
                $options
            )
        );

        // create the element without adding it to _elements array
        $elem = $this->createElement( $options );

        $find = isset( $position['after'] )
              ? $position['after']
              : NULL;

        $find = ( ! $find && isset( $position['before'] ) )
              ? $position['before']
              : $find;

        // find given element
        $path = $this->ArraySearchRecursive( $find, $this->_elements );

        // element found
        if ( is_array( $path ) ) {
            if ( isset( $position['after'] ) ) {
                array_splice( $this->_elements, ( $path[0] + 1 ), 0, array( $elem ) );
            }
            else {
                array_splice( $this->_elements, $path[0], 0, array( $elem ) );
            }
        }
        // element not found; add to end
        else {
            array_push( $this->_elements, array( $elem ) );
        }

        return true;

    }   // end function insertElement()


    /**
  	 * Replace an element
  	 *
  	 * Can be used to replace an existing element
  	 *
  	 * @access public
  	 * @param  string  $name       element to replace
  	 * @param  array   $options
  	 *                 see addElement for details
  	 * @return void
  	 *
  	 **/
  	public function replaceElement( $name, $options ) {

  	    $this->log()->LogDebug(
            'called with args: name: '.$name,
            $options
        );

        // create the element without adding it to _elements array
        $elem = $this->createElement( $options );

        // find given element
        $path = $this->ArraySearchRecursive( $name, $this->_elements );

        // element found
        if ( is_array( $path ) ) {
            array_splice( $this->_elements, $path[0], 1, array( $elem ) );
        }
        // element not found; add to end
        else {
            array_push( $this->_elements, array( $elem ) );
        }

        return true;

    }   // end function replaceElement()
    
    /**
  	 * Remove an element
  	 *
  	 * Can be used to remove an existing element
  	 *
  	 * @access public
  	 * @param  string  $name       element to replace
  	 * @return void
  	 *
  	 **/
  	public function removeElement( $name ) {

  	    $this->log()->LogDebug(
            'called with args: name: '.$name,
            $options
        );

        // find given element
        $path = $this->ArraySearchRecursive( $name, $this->_elements );

        // element found
        if ( is_array( $path ) ) {
            array_splice( $this->_elements, $path[0], 1, array( $elem ) );
        }

        return true;

    }   // end function removeElement()
    
    /**
     * change/set element value at runtime
     *
     *
     *
     **/
    public function setElementValue( $name, $value ) {
    
        $this->log()->LogDebug(
            ' called with args: name: -'.$name.'- value: -'.$value.'-'
        );
        
        if ( isset( self::$_registered_elements[ $name ] ) ) {
            $elem    = & self::$_registered_elements[ $name ];
            $arrName = '_elements';
            if ( $elem['type'] == 'hidden' ) {
                $arrName = '_hidden';
            }
        }
        
        // find given element
        $path = $this->ArraySearchRecursive( $name, $this->{$arrName} );
        
        // element found
        if ( is_array( $path ) ) {
            // set value
            $this->{$arrName}[ $path[0] ]['value']
                = $value;
            $elem['value']
                = $value;
            $this->{$arrName}[ $path[0] ]['content']
                = $this->__createInput( $elem );
                
            $this->log()->LogDebug(
                'setting $this->'.$arrName.'['.$path[0].'] to value '.$value
            );
        }

        return true;
    
    }   // end function setElementValue()


    /**
     * add form buttons
     *
     *
     *
     **/
    public function addButtons( $options = array() ) {

        $this->log()->LogDebug( 'addButtons()', $options );

        foreach ( $options as $opt ) {

            $opt['name']  = isset( $opt['name'] )
                          ? $opt['name']
                          : $opt['type'];

            $opt['class'] = isset( $opt['class'] )
                          ? $opt['class']
                          : $this->_form_css[ 'fb_button_class' ];

            $this->_buttons[] = $this->__createInput( $opt );

        }

    }   // end sub addButtons()

    /**
     * de-/activate attribute validation
     *
     *
     *
     **/
    public function setValidation( $bool = true ) {
        $this->_validate = $bool;
    }

    /**
     *
     *
     *
     *
     **/
    public function setLanguage( $lang ) {
        $this->_current_lang = $lang;
        $this->lang  = new wbI18n( $options['lang'] );
    }   // end function setLanguage()

    /**
     *
     *
     *
     *
     **/
    public function setPrefix( $prefix ) {

# ---------- TODO: validate $prefix -----

        $this->_name_prefix = $prefix;
    }   // end function setPrefix()
    
    
/*******************************************************************************
*                           SPECIAL FORM FIELDS                                *
*******************************************************************************/


    /**
     * infotext element; this is just text
     **/
    public function infotext ( $options ) {
        return array(
            'name'    => '__infotext__',
            'content' => $this->lang->translate( $options['content'] )
        );
    }   // end function infotext ()
    
    /**
     * legend element
     *
     * <th>     for table output
     * <legend> for fieldset output
     *
     **/
    public function legend ( $options ) {

        return array(
            'name'    => '__legend__',
            'content' => $this->lang->translate( $options['content'] )
        );

    }   // end function legend ()

    /**
     * password element
     *
     * <input type="password" />
     *
     **/
    public function password ( $options ) {

        $options['type'] = 'password';

        $label   = isset( $options['label'] )
                 ? '<label class="fblabel" for="'.$options['name'].'">'.$this->lang->translate( $options['label'] ).'</label>'
                 : '';

        return array(
            'name'    => $options['name'],
            'label'   => $label,
            'content' => $this->__createInput( $options )
        );

    }   // end function password ()

  	/**
  	 * create checkbox panel
  	 *
  	 **/
    public function checkbox( $options ) {
        return $this->__handle_checkbox_radio( 'checkbox', $options );
    }   // end function checkbox()
    
    /**
     * create radio panel
     **/
    public function radio( $options ) {
        return $this->__handle_checkbox_radio( 'radio', $options );
    }   // end function radio()
    
  	/**
  	 * create a selectbox
  	 *
  	 * @param   string   name           field name
  	 * @param   string   label          label
  	 *                                  (text to show)
  	 * @param   string   value          current value
  	 *                                  (checked)
  	 * @param   array    options        options to show
  	 *
  	 * any other options given are validated using __validateOptions
  	 *
  	 **/
    public function select( $options ) {

        $this->log()->LogDebug( 'select()', $options );

        $output  = NULL;

        $name    = isset( $options['name'] )
                 ? $options['name']
                 : $this->__generateRandomName();

        $label   = isset( $options['label'] )
                 ? '<label class="fblabel" for="'.$options['name'].'">'.$this->lang->translate( $options['label'] ).'</label>'
                 : '';

        $opt     = ( isset( $options['options'] ) && is_array( $options['options'] ) )
                 ? $options['options']
                 : array();

        // filter additional options for <select ...>
        $select  = array_diff_key(
                       $options,
                       array( 'name' => 1, 'label' => 1, 'options' => 1, 'type' => 1, 'value' => 1, 'allow' => 1 )
                   );

        $output = "<select name=\"$name\" id=\"$name\" "
                . $this->__validateOptions( $select )
                . ">\n";

        if ( is_array( $opt ) && count( $opt ) > 0 ) {
        
            $isIndexed = array_values($opt) === $opt;

            foreach ( $opt as $key => $value ) {

                if ( $isIndexed ) { $key = $value; }

                $selected = (
                                isset( $options['value'] )
                                &&
                                ! strcasecmp( $options['value'], $key )
                            )
                          ? 'selected="selected"'
                          : NULL;

                $output .= "  <option value=\"$key\" $selected>$value</option>\n";
            }
            
        }
        // options where given as preprocessed HTML;
        // no checks are done here for this
        elseif ( isset( $options['content'] ) ) {
            $output .= $options['content'];
        }

        $output .= "</select>\n";

        return array( 'name' => $options['name'], 'label' => $label, 'content' => $output );

    }   // end function checkbox()

    /**
     * textarea
     *
     * <textarea></textarea>
     *
     **/
    public function textarea ( $options ) {

        $label   = isset( $options['label'] )
                 ? '<label class="fblabel" for="'.$options['name'].'">'.$this->lang->translate( $options['label'] ).'</label>'
                 : '';

        $rows    = ( isset ( $options['rows'] ) && is_numeric( $options['rows'] ) )
                 ? $options['rows']
                 : 10;

        $cols    = ( isset ( $options['cols'] ) && is_numeric( $options['cols'] ) )
                 ? $options['cols']
                 : 100;

        $style   = NULL;
        if ( isset( $this->_errors[ $options['name'] ] ) ) {
            $style = ' style="'.$this->_error_style.'"';
        }
        
        $content = "<textarea rows=\"$rows\" cols=\"$cols\" name=\"".$options['name']."\"{$style}>"
                 . $options['value']
                 . '</textarea>';

        return array(
            'name'    => $options['name'],
            'label'   => $label,
            'content' => $content
        );

    }   // end function textarea()
    
    
/*******************************************************************************
*                    FORM GENERATION/INITIALIZATION                            *
*******************************************************************************/

  	/**
  	 * generate a form
  	 *
  	 * This function requires a file name to include (using loadFile() to
  	 * load the contents and call _registerElement() for each element),
  	 * or _registerElement() called otherwise (i.e. using generateFromArray())
  	 *
  	 * @param array $options
  	 *
  	 *   known array keys:
  	 *
  	 *     include - file to include
  	 *     current - current settings (array)
  	 *
  	 *
  	 **/
    public function generateForm( $options = array() ) {

        if ( isset( $options['include'] ) ) {
            $this->loadFile( $options );
        }

        $current = isset( $options['current'] )
                 ? $options['current']
                 : array();

        $lang    = isset( $this->_current_lang )
                 ? $this->_current_lang
                 : 'EN';

        // create form elements from options array
        foreach( self::$_registered_elements as $item ) {

            $name  = isset( $item['name'] )
                   ? $item['name']
                   : $this->__generateRandomName();

            $value = isset( $current[ $name ] )
                   ? $current[ $name ]
                   : ( isset( $item['value'] ) ? $item['value'] : NULL );

            $label = NULL;

            if ( isset( $item['label'] ) ) {
                $label = $this->lang->translate( $item['label'] );
            }

            $element = array_merge(
                           $item,
                           array(
                               'label' => $label,
                               'value' => $value
                           )
                       );

            $this->addElement( $element );
        }

    }   // end function generateForm()

    /**
     * check form data (required fields, valid contents etc)
     *
     *
     *
     **/
    public function checkFormData( $options = array() ) {

        $this->log()->LogDebug( 'checkFormData()', $options );

        if ( isset( $options['include'] ) ) {
            $this->loadFile( $options );
        }

        // check equals
        if ( is_array( $this->_equals ) && count( $this->_equals > 0 ) ) {
            $this->__checkEquals();
        }

        // check required
        if ( is_array( $this->_required ) && count( $this->_required > 0 ) ) {
            $this->__checkRequired();
        }

        // validate
        foreach( array_merge( $this->_optional, $this->_required ) as $field => $i ) {

            $this->log()->LogDebug( 'checking field '.$field );

            if ( ! isset( $_POST[$field] ) ) {
                continue;
            }

            $this->__checkField( $field );

        }

    		$this->log()->LogDebug( 'errors: ', $this->_errors );
        
    		if ( count( $this->_errors ) == 0 ) {
            return true;
        }

        return false;

    }   // end function checkFormData()

    /**
     * create the form (without printing)
     *
     * Usage: echo $form->getForm( $options );
     *
     *        or
     *
     *        $form->printForm( $options );
     *
     * @return string   - generated form (HTML)
     *
     **/
		public function getForm ( $options = array() ) {
		
        $elements = array();
        $hidden   = array();

        // check equals (both fields must exist)
        if ( is_array( $this->_equals ) ) {
            foreach ( $this->_equals as $i => $field ) {
                if ( ! isset ( $this->_required[$field] ) && ! isset ( $this->_optional[$field] ) ) {
                    $this->PrintError( "MISSING FIELD FOR EQUAL CHECK: $field<br />" );
                }
            }
        }

        $this->log()->LogDebug( '_elements: ', $this->_elements );
        $this->log()->LogDebug( '_hidden: '  , $this->_hidden   );
        $this->log()->LogDebug( '_required: ', $this->_required );
        $this->log()->LogDebug( '_checks: '  , $this->_checks   );
        $this->log()->LogDebug( '_equals: '  , $this->_equals   );
        $this->log()->LogDebug( '_readonly: ', $this->_readonly );

        foreach ( $this->_hidden as $elem ) {
            $hidden[] = $elem['content'];
        }
        
        // there may be a key 'more_data' with some additional template data;
        // this will only work with custom form templates!
        $more_data = array();
        if ( isset( $options['more_data'] ) ) {
            $more_data = $options['more_data'];
        }

        // render form elements and add them to $elements array
        foreach ( $this->_elements as $elem ) {
        
            switch ( $elem['name'] ) {
            
                case '__infotext__':
                
                    $elements[] = $this->_tpl->parseString(
                              $this->_form_infotext_template,
                              array_merge(
                                  $this->_form_css,
                                  $elem
                              )
                        );
                        
                    break;
            
                case '__legend__':
                    if ( ! empty( $elem['content'] ) ) {

                        if ( $this->_type !== 'table' ) {
                            $elements[] = '</fieldset><fieldset class="{{ fb_fieldset_class }}">';
                        }

                        $elements[] = $this->_tpl->parseString(
                              $this->_form_legend_template,
                              array_merge(
                                  $this->_form_css,
                                  $elem
                              )
                        );

                    }
                    break;

                default:
                
                    $req = NULL;
                    
                    if ( isset( $this->_required[ $elem['name'] ] ) ) {
                        if ( ! array_key_exists( $elem['name'], $this->_readonly ) ) {
                            $req = '*';
                        }
                        else {
                            $elem['content']
                                = '<input type="hidden" name="'
                                . $elem['name']
                                . '" value="'
                                . $elem['value']
                                . '" />'.$elem['value'];
                        }
                    }
                               

                    $elements[] = $this->_tpl->parseString(
                          $this->_element_template,
                          array_merge(
                              $elem,
                              $this->_form_css,
                              array(
                                  'error' => $this->getError( $elem['name'] ),
                                  'req'   => $req
                              )
                          )
                    );
            	      break;

            }

        }

        // table attributes ('class', 'style', ...)
# ----- TODO: validate -----
        $tableattrs  = ( isset( $options['table'] ) && is_array( $options['table'] ) )
                     ? $options['table']
                     : array( 'class' => 'fbtable' );

        // remove special attributes
        $options     = array_diff_key(
                           $options,
                           array( 'table' => 1, 'more_data' => 1 )
                       );

        // mix in form defaults to fill missing attributes
        $attribs     = array_merge(
                           $this->_form_defaults,
                           $options
                       );

        if ( empty ( $attribs['action'] ) ) {
            $attribs['action'] = $this->selfURL();
        }

        // create default buttons (submit/reset) if no are defined yet
        if (
             count( $this->_buttons ) == 0
             &&
             ! isset( $this->_settings['no_default_buttons'] )
        ) {

            $this->addButtons(
                array(
                    array( 'type' => 'submit', 'value' => $this->lang->translate('Submit') ),
                    array( 'type' => 'reset',  'value' => $this->lang->translate('Reset')  )
                )
            );

        }

        $reqinfo = NULL;
        if ( count( $this->_required ) > 0 ) {
            $reqinfo = $this->lang->translate(
                           'Required items are marked with {{ marker }}',
                           array( 'marker' => '*' )
                       );
        }
        
        // render the form
			  $temp = $this->_tpl->parseString(
                    $this->_form_template,
                    array_merge(
                        $this->_form_css,
                        $more_data,
                        array (
                            'header'
                                => $this->__getFormHeader( $options ),
    	                      'hidden'
                                => implode( "\n", $hidden   ),
                	  			  'content'
                                => implode( "\n", $elements ),
                            'formattribs'
                                => $this->__validateOptions( $attribs ),
                            'tableattrs'
                                => $this->__validateOptions( $tableattrs ),
                            'buttons'
                                => implode( ' ' , $this->_buttons ),
                            'info'
                                => ( isset($options['info']) ? $this->lang->translate( $options['info'] ) : NULL ),
                            'infoclass'
                                => ( isset($options['infoclass']) ? $options['infoclass'] : 'fbhide' ),
                            'required_info'
                                => $reqinfo,
                            'toplink'
                                => ( isset($options['toplink']) ? $options['toplink'] : NULL ),
                		  	)
                    )
        			  );

        // flush vars
        unset( $elements );
        unset( $hidden   );

        $this->reset();

        return $temp;

		}   // end function getForm()

		/**
		 * print the form; alias for echo $form->getForm();
		 **/
    public function printForm( $options = array() ) {
        echo $this->getForm( $options );
    }   // end function printForm()


		/**
		 * returns the last error recorded for given field
		 *
		 * @access public
		 * @param  string  $field - field name
		 * @return boolean
		 *
		 **/
    public function getError( $field ) {
        if ( isset( $this->_errors[$field] ) ) {
            return $this->_errors[$field];
        }
        return false;
    }

    /**
     *
     *
     *
     *
     **/
    public function reset() {
        $this->_elements = array();
        $this->_hidden   = array();
        $this->_buttons  = array();
        $this->_required = array();
        $this->_optional = array();
        $this->_checks   = array();
        $this->_equal    = array();
        $this->_valid   = array();

    }   // end function reset()
    
    
    
    

/*******************************************************************************
*                         OUTPUT HANDLING FUNCTIONS                            *
*******************************************************************************/

    /**
     * load defaults
     **/
    public function setFormType( $type ) {

        if ( $type == 'table' ) {
            $this->setFormTemplate    ( dirname(__FILE__).'/templates/form.table.tpl'        );
            $this->setHeaderTemplate  ( dirname(__FILE__).'/templates/header.table.tpl'        );
            $this->setLegendTemplate  ( dirname(__FILE__).'/templates/legend.table.tpl'      );
            $this->setElementTemplate ( dirname(__FILE__).'/templates/element.table.tpl'     );
            $this->setInfotextTemplate( dirname(__FILE__).'/templates/infotext.table.tpl'    );
        }
        else {
            $this->setFormTemplate    ( dirname(__FILE__).'/templates/form.fieldset.tpl '    );
            $this->setHeaderTemplate  ( dirname(__FILE__).'/templates/header.fieldset.tpl'        );
            $this->setLegendTemplate  ( dirname(__FILE__).'/templates/legend.fieldset.tpl'   );
            $this->setElementTemplate ( dirname(__FILE__).'/templates/element.fieldset.tpl'  );
            $this->setInfotextTemplate( dirname(__FILE__).'/templates/infotext.fieldset.tpl' );
        }

    }   // end function setFormType()

		/**
		 * overload form template
		 **/
    public function setFormTemplate( $tpl ) {
        $text = $this->slurp( $tpl );
        if ( $text ) {
            $this->_form_template = $text;
        }
    }   // end function setFormTemplate
    
    /**
     * overload header template
     **/
    public function setHeaderTemplate( $tpl ) {
        $text = $this->slurp( $tpl );
        if ( $text ) {
            $this->_form_header_template = $text;
        }
    }

    /**
		 * overload header template
		 **/
    public function setLegendTemplate( $tpl ) {
        $this->_form_legend_template = $this->slurp( $tpl );
    }   // end function setLegendTemplate

    /**
		 * overload element template
		 **/
    public function setElementTemplate( $tpl ) {
        $this->_element_template = $this->slurp( $tpl );
    }   // function setElementTemplate
    
    /**
		 * overload infotext template
		 **/
    public function setInfotextTemplate( $tpl ) {
        $this->_form_infotext_template = $this->slurp( $tpl );
    }   // end function setInfotextTemplate

    /**
     * overload error style
     **/
    public function setErrorStyle( $style ) {
        if ( wbValidate::staticValidate( 'PCRE_STYLE', $style ) ) {
            $this->_error_style = $style;
        }
        else {
            $this->log()->LogDebug( 'sinvalid error style: ['.$style.']' );
        }
    }   // end function setErrorStyle()

    /**
     * overload form css class names
     **/
    public function setCSSClasses( $settings = array() ) {

        foreach ( $settings as $set => $value ) {
            if (
                 isset( $this->_form_css[ $set ] )
                 &&
                 wbValidate::staticValidate( 'PCRE_STRING', $value )
            ) {
                $this->_form_css[ $set ] = $value;
            }
        }

    }   // end function setCSSClasses()


/*******************************************************************************
*                            PRIVATE FUNCTIONS                                 *
*******************************************************************************/
		 
    function __handle_checkbox_radio( $type = 'checkbox', $options ) {

        $output  = NULL;

        $value
            = isset( $options['value'] )
            ? $options['value']
            : NULL;
            
        $this->log()->LogDebug( 'value: '.$value );

        // make sure that we have an array of options
        $opt
            = ( isset( $options['options'] ) && is_array( $options['options'] ) )
            ? $options['options']
            : (
                    isset( $options['options'] )
                  ? array( $options['options'] )
                  : array( $value )
              );

        $this->log()->LogDebug( 'options array: ', $opt );

        // get list of checked boxes/radio buttons
        $checked
            = ( isset( $options['checked'] ) && is_array( $options['checked'] ) )
            ? $options['checked']
            : (
                    isset( $options['checked'] )
                  ? array( $options['checked'] => true )
                  : array()
              );

        $this->log()->LogDebug( 'checked values: ', $checked );

        // get list of labels
        $labels
            = ( isset( $options['labels'] ) && is_array( $options['labels'] ) )
            ? $options['labels']
            : array();

        $this->log()->LogDebug( 'labels (from labels key):', $labels );

        // let's see if we have an indexed array
        $isIndexed = array_values($opt) === $opt;

        // now, create a box for every entry in the $opt array
        foreach ( $opt as $key => $name ) {
        
            if ( $isIndexed ) { $key = $name; }

            if ( $type == 'radio' && isset( $options['name'] ) ) {
                $name = $options['name'];
            }

            $on = isset( $options['on'] )
                ? $options['on']
                : $key;

            $label = isset( $labels[ $key ] )
                   ? $labels[ $key ]
                   : $name;

            $this->log()->LogDebug( 'adding '.$type.' with name:', $name );

            $output .= $this->__createInput(
                           array(
                               'name'    => $name,
                               'type'    => $type,
                               'checked' => (
                                              ( isset( $checked[$key] ) || isset( $checked[$name] ) )
                                            ? 'checked'
                                            : NULL
                                            ),
                               'value'   => $on,
                           )
                       )
                    .  $this->lang->translate( $label )
                    .  "<br />";

        }

        return array(
            'name'    => $options['name'],
            'content' => $output,
            'label'   => isset( $options['label'] ) ? $options['label'] : NULL
        );
    }

    /**
     *
     *
     *
     *
     **/
    private function __getFormHeader( $options = array() ) {

        if ( ! isset( $options['header'] ) || empty( $options['header'] ) ) {
            return NULL;
        }
        
        if ( ! is_array( $options['header'] ) ) {
            return
                $this->_tpl->parseString(
                      $this->_form_header_template,
                      array( 'content' => $this->lang->translate( $options['header'] ) )
                );
        }
        else {
            return $this->lang->translate(
                $options['header'][0],
                $options['header'][1]
            );
        }

    }   // end function __getFormHeader()

    /**
     *
     *
     *
     *
     **/
    private function __registerElement( $options ) {

        $name = isset( $options['name'] )
              ? $options['name']
              : $this->__generateRandomName();

        // register required elements (fields that must have a value when the
        // form is submitted)
        if ( isset( $options['required'] ) ) {
            if ( ! in_array( $name, $this->_required ) ) {
                $this->_required[$name] = 1;
            }
        }
        else {
            if ( ! in_array( $name, $this->_optional ) ) {
                $this->_optional[$name] = 1;
            }
        }

        // items that should have equal values to other items
        if ( isset( $options['equal_to'] ) ) {
            $this->_equals[$name] = $options['equal_to'];
        }

        // items that have 'allow' attribute
        if ( isset( $options['allow'] ) ) {

            if ( is_array( $options['allow'] ) ) {
                $this->_checks[$name] = $options['allow'];
            }
            elseif ( isset( self::$_allowed[$options['allow']] ) ) {
                $this->_checks[$name] = self::$_allowed[$options['allow']];
            }
            else {
                $this->_checks[$name] = $options['allow'];
            }

        }

        if ( ! isset( $options['type'] ) ) {
            $options['type'] = 'text';
        }

        self::$_registered_elements[$name] = $options;

    }   // end function __registerElement()

  	/**
  	 *
  	 *
  	 *
  	 *
  	 **/
    private function __createInput( $options ) {

        if ( isset( $options['readonly'] ) && $options['readonly'] ) {
            $this->setReadonly( $options['name'] );
        }

        foreach ( self::$_always_add as $attr => $default ) {
            if ( ! isset( $options[$attr] ) ) {
                if ( $v = preg_replace( "/^\&/", '', $default ) ) {
                    eval( "\$default = $v;" );
                }
                $options[$attr] = $default;
            }
        }

        // mark required elements
        if ( isset( $options['required'] ) ) {

            $class = isset( $options['class'] )
                   ? $options['class']
                   : '';

            $options['class'] = $class . ' ' . $this->_form_css['fb_req_class'];

        }

        // remove special attrs
        $options = array_diff_key(
                       $options,
                       array(
                           'on'       => 1,
                           'options'  => 1,
                           'label'    => 1,
                           'required' => 1,
                           'allow'    => 1,
                       )
                   );

        if ( isset( $this->_errors[ $options['name'] ] ) ) {
            $options['style'] .= $this->_error_style;
        }

        $return  = '<input '
                 . $this->__validateOptions( $options )
                 . ' /> ';
                 
        $this->log()->LogDebug( 'created element:', $return );

        return $return;

    }   // end function __createInput()

  	/**
  	 *
  	 *
  	 *
  	 *
  	 **/
    private function __validateOptions( $options ) {

        if ( is_array( $options ) && count( $options ) > 0 ) {

            $output = array();

            $this->log()->LogDebug(
                'called with args:',
                $options
            );

            if ( $this->_validate ) {

                foreach ( $options as $key => $value ) {

                    $value = ( ! empty( $value ) )
                           ? htmlentities( $this->lang->translate( $value ) )
                           : NULL;

                    if ( array_key_exists( $key, self::$_knownAttrs ) ) {

                        $this->log()->LogDebug( 'found common attribute: '.$key );
                        $is_valid = false;

                        if ( is_array( self::$_knownAttrs[ $key ] ) ) {
                            if ( in_array( $value, self::$_knownAttrs[ $key ] ) ) {
                                $is_valid = true;
                            }
                        }
                        // validate
                        elseif ( strpos( self::$_knownAttrs[ $key ], 'PCRE_' ) !== false ) {
                            $is_valid = wbValidate::staticValidate( self::$_knownAttrs[ $key ], $value );
                        }

                        if ( $is_valid ) {
                            $this->log()->LogDebug( 'valid key '.$key );
                            $output[] = "$key=\"$value\"";
                        }
                        else {
                            $this->log()->LogDebug( 'key '.$key.' has invalid content:', $value );
                        }

                    }
                    else {
                        $this->log()->LogDebug( 'invalid key '.$key );
                    }

                }

            }
            else {

                foreach ( $options as $key => $value ) {
                    $output[] = "$key=\"$value\"";
                }

            }

            return implode( ' ', $output );

        }

    }   // end function __validateOptions()

    /**
     *
     *
     *
     *
     **/
    private function __generateRandomName() {
        for(
               $code_length = 10, $newcode = '';
               strlen($newcode) < $code_length;
               $newcode .= chr(!rand(0, 2) ? rand(48, 57) : (!rand(0, 1) ? rand(65, 90) : rand(97, 122)))
        );
        return $this->_name_prefix.$newcode;
    }

    /**
     *
     *
     *
     *
     **/
    private function __checkField( $field ) {

        $is_error = NULL;

        $this->log()->LogDebug( '__checkField(): '.$field );

        if ( ! isset( $_POST[$field] ) || empty( $_POST[$field] ) ) {
            $this->log()->LogDebug( 'no form data found' );
            return;
        }

        if ( isset( $this->_checks[$field] ) ) {

            if ( is_array( $this->_checks[$field] ) ) {

                $this->log()->LogDebug( 'array of valid values defined' );

                if ( ! in_array( $_POST[$field], $this->_checks[$field] ) ) {
                    $is_error = isset( self::$_registered_elements[$field]['invalid'] )
                              ? $this->lang->translate( self::$_registered_elements[$field]['invalid'] )
                              : $this->lang->translate( 'invalid' );

                }
                else {
                    $this->_valid[$field] = $_POST[$field];
                }

            }
            else {

                if ( ! wbValidate::staticValidate( $this->_checks[$field], $_POST[$field] ) ) {
                    $is_error = isset( self::$_registered_elements[$field]['invalid'] )
                              ? $this->lang->translate( self::$_registered_elements[$field]['invalid'] )
                              : $this->lang->translate( 'invalid' );

                }
                else {
                    $this->_valid[$field] = $_POST[$field];
                }

            }

        }
        else {
            $this->log()->LogDebug( 'No checks defined for field: '.$field );
        }

        if ( $is_error ) {
            $this->_errors[ $field ] = $is_error;
            return false;
        }

        return true;

    }   // end function __checkField()

    /**
     *
     *
     *
     *
     **/
    private function __checkEquals() {

        // check equals (both fields must exist)
        if ( is_array( $this->_equals ) ) {
            foreach ( $this->_equals as $i => $field ) {
                if ( ! isset ( $this->_required[$field] ) && ! isset ( $this->_optional[$field] ) ) {
                    $this->PrintError( "MISSING FIELD FOR EQUAL CHECK: $field<br />" );
                }
            }
        }

# ----- TODO: $_GET berücksichtigen ---

        foreach ( $this->_equals as $field1 => $field2 ) {

            if (
                   (
                       isset( $_POST[$field1] )
                       &&
                       ! empty( $_POST[$field1] )
                   )
                   &&
                   (
                       isset( $_POST[$field2] )
                       &&
                       ! empty( $_POST[$field2] )
                   )
            ) {

                if ( strcasecmp( $_POST[$field1], $_POST[$field2] ) ) {

                    $this->_errors[$field1]
                        = isset( self::$_registered_elements[$field1]['notequal'] )
                        ? $this->lang->translate( self::$_registered_elements[$field1]['notequal'] )
                        : $this->lang->translate( 'fields not equal!' );

                }

            }

        }

    }   // end function __checkEquals()

    /**
     *
     *
     *
     *
     **/
    private function __checkRequired() {

        // check required
        foreach( $this->_required as $field => $i ) {

            $this->log()->LogDebug( 'checking required field: '.$field );

            if ( ! isset( $_POST[$field] ) || empty( $_POST[$field] ) ) {

                $this->log()->LogDebug( 'no form data found, checking default value' );

                if ( isset( self::$_registered_elements[ $field ]['default'] ) ) {
                    $this->log()->LogDebug( 'setting default value: '. self::$_registered_elements[ $field ]['default'] );
                    $_POST[$field] = self::$_registered_elements[ $field ]['default'];
                }
                elseif (
                    self::$_registered_elements[ $field ]['type'] == 'checkbox'
                    &&
                    isset( self::$_registered_elements[ $field ]['off'] )
                ) {
                    $_POST[$field] = self::$_registered_elements[ $field ]['off'];
                }
                else {
                    // missing required
                    $this->_errors[$field]
                        = isset( self::$_registered_elements[$field]['missing'] )
                        ? $this->lang->translate( self::$_registered_elements[$field]['missing'] )
                        : $this->lang->translate( 'missing' );
                }

            }

        }

    }   // end function __checkRequired()

    public function usage() {

    }   // end function usage()

}

/*

values allowed for lang

                items  => qw( aa ab af am ar as ay az ba be bg bh bi bn
                             bo br ca co cs cy da de dz el en eo es et
                             eu fa fi fj fo fr fy ga gd gl gn gu ha he
                             hi hr hu hy ia id ie ik is it iu iw ja ji
                             jv ka kk kl km kn ko ks ku ky la ln lo lt
                             lv mg mi mk ml mn mo mr ms mt my na ne nl
                             no oc om or pa pl ps pt qu rm rn ro ru rw
                             sa sd sg sh si sk sl sm sn so sq sr ss st
                             su sv sw ta te tg th ti tk tl tn to tr ts
                             tt tw ug uk ur uz vi vo wo xh yi yo za zh zu
                           )



    public function _text( $options ) {

        $label   = isset( $options['label'] )
                 ? '<label for="'.$options['name'].'">'.$options['label'].'</label>'
                 : '';

        // remove special attrs
        $options = array_diff_key(
                       $options,
                       array('label'=>1)
                   );

        return array( 'label' => $label, 'content' => $this->__createInput( $options ) );

    }

*/

?>