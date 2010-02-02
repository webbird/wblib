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

class wbFormBuilder extends wbBase {

    // ----- Debugging -----
    #protected      $debugLevel      = KLOGGER::OFF;
    protected      $debugLevel      = KLOGGER::DEBUG;
    
    protected      $_config
        = array(
              // default path to search inc.forms.php
              'path'            => '/forms',
              // default forms definition file name
              'file'            => 'inc.forms.php',
              // default variable name
              'var'             => 'FORMS',
              // default save button name
              'save_key'        => 'save',
              # form defaults (<form> tag)
              'method'          => 'post',
              'action'          => '',
              # CSS classes
              'label_css'       => 'fblabel',
              'fb_form_class'   => 'fbform',
              'fb_left_class'   => 'fbleft',
              'fb_header_class' => 'fbheader',
              'fb_info_class'   => 'fbinfo',
              'fb_req_class'    => 'fbrequired',
              'fb_table_class'  => 'fbtable',
              'fb_error_class'  => 'fberror',
              'fb_info_class'   => 'fbinfo',

              # output as table or fieldset
              'output_as'       => 'table',
          );

    // store buttons we've already seen
    protected      $_buttons        = array();
    
    // wbTemplate object handler
    private        $tpl;
    
    // wbValidate object handler
    private        $val;
    
    // store the name of the form we currently work with
    private        $_current_form   = NULL;
    
    // variable to store forms
    // 'formname' => array of elements
    private static $_forms;
    
    // variable to store form errors
    private        $_errors         = array();
    
    // variable to store info messages
    private        $_info           = array();
    
    // variable to store validated form data
    private        $_valid          = array();
    
    // variable to store if a form is already checked
    private        $_checked        = array();
    
    /**
     * constructor
     **/
    function __construct ( $options = array() ) {
    
        parent::__construct();
    
        $this->log()->LogDebug( 'constructor options:', $options );
        
        // store config data
        foreach ( $this->_config as $key => $value ) {
            if ( isset ( $options[$key] ) ) {
                $this->_config[$key] = $options[$key];
            }
        }

        $this->__init();

        // get current working directory
        $callstack = debug_backtrace();
        $this->_config['workdir']
            = ( isset( $callstack[0] ) && isset( $callstack[0]['file'] ) )
            ? realpath( dirname( $callstack[0]['file'] ) )
            : realpath( dirname(__FILE__) );
            
        // create validator object
        $this->val  = new wbValidate();
        
        // create template object
        $this->tpl  = new wbTemplate();
        $this->tpl->setPath( realpath( dirname(__FILE__) ).'/wbFormBuilder/templates' );
        
        $this->setFile( $this->_config['file'] );
        
        $this->log()->LogDebug( 'object data', $this );

    }   // end function __construct ()
    
    /**
     *
     *
     * @access public
     * @param  string  $file - file to load
     *
     **/
    public function setFile( $file, $path = NULL, $var = NULL ) {
    
        $this->log()->LogDebug( 'reading file: '.$file );
        
        parent::setFile( $file, $path, $var );
        
        if ( isset( $this->_config['current_file'] ) ) {
        
            // compile
            include_once $this->_config['current_file'];

            eval( "\$ref = & \$".$this->_config['var'].";" );

            // add the forms to internal cache
            foreach ( $ref as $formname => $elements ) {

                // analyze data and store config
                self::$_forms[ $formname ] = $this->__registerForm( $formname, $elements );

                $save_key = isset( self::$_forms[ $formname ]['config']['save_key'] )
                          ? self::$_forms[ $formname ]['config']['save_key']
                          : $this->_config['save_key'];
                          
                // save key set? this means the form is sent by user
                if ( isset( $save_key ) ) {

                    $skey = $this->val->param( $save_key );

                    if ( ! empty( $skey ) ) {
                        $this->log()->LogDebug( 'save key is set, autocheck form data' );
                        $this->checkFormData( $formname );
                    }
                }

            }

        }
        
    }   // end function setFile()
    
    /**
     * save button field name
     *
     * @access public
     * @param  string   $key - field name
     * @return void
     *
     **/
    public function setSaveKey( $formname, $key ) {
        $formname = $this->__formName( $formname );
        $this->_config['save_key'][$formname] = $key;
    }   // end function setSaveKey()
    
    /**
     *
     *
     *
     *
     **/
    public function setError( $formname, $error ) {
        $formname = $this->__formName( $formname );
        $this->_errors[$formname][] = $error;
    }   // end function setError()
    
    /**
     *
     *
     *
     *
     **/
    public function setInfo( $formname, $msg ) {
        $formname = $this->__formName( $formname );
        $this->_info[$formname][] = $msg;
    }   // end function setInfo()
    
    /**
     * convenience method; calls getForm() for $this->_current_form
     *
     * @access public
     * @param  array   $formdata
     * @return HTML
     *
     **/
    public function getCurrentForm( $formdata ) {
        return $this->getForm( $this->_current_form, $formdata );
    }   // end function getCurrentForm()
    
    /**
     * convenience method; calls echo $this->getForm()
     *
     * @access public
     * @param  string  $formname - name of the form to show
     * @param  array   $formdata
     * @return HTML
     *
     **/
    public function printForm( $formname = '', $formdata = array() ) {
        echo $this->getForm( $formname = '', $formdata = array() );
    }   // function printForm()
    
    /**
     * generate the form from config data
     *
     * @access public
     * @param  string   $formname - form to show (default $_current_form)
     * @param  array    $formdata - current form data (prefill form)
     * @return string   generated form (HTML)
     *
     **/
    public function getForm ( $formname = '', $formdata = array() ) {
    
        $formname = $this->__formName( $formname );
    
        $this->log()->LogDebug(
            'creating form ['.$formname.'], Form data:', $formdata
        );

        // is there a form with the given name?
        if ( isset( self::$_forms[ $formname ] ) ) {

            // save key set? this means the form is sent by user
            if ( $this->val->param( $this->_config['save_key'] ) ) {
                $this->log()->LogDebug( 'save key is set, autocheck form data' );
                $this->checkFormData( $formname );
            }
            
            return $this->__generateForm( $formname, $formdata );
            
        }
        else {
        
            $existing_forms_info = NULL;
            if ( is_array( self::$_forms ) && count( self::$_forms ) > 0 ) {
                $existing_forms_info
                    = $this->translate(
                          '<br /><br />Forms defined:<br />{{ forms }}<br /><br />',
                          array(
                              'forms' => implode( '<br />', array_keys( self::$_forms ) )
                          )
                      );
            }
            $this->printError(
                $this->translate(
                    'No such form: [{{ formname }}]; did you forget to load it?',
                    array(
                        'formname' => $formname
                    )
                )
              . $existing_forms_info
            );
        }

    }   // end function getForm ()
    
    /**
     * create a form element
     *
     * This is used by __generateForm() to create the form elements
     *
     * @access public
     * @param  array   $element - element definition
     * @return string  HTML
     *
     **/
    public function createElement( $element ) {
    
        if ( method_exists( $this, $element['type'] ) ) {
            $field = $this->{$element['type']}( $element );
        }
        else {
            $field = $this->input( $element );
        }
        
        $label = NULL;
        
        if ( isset ( $element['label'] ) ) {
            $label = '<label for="'.$element['name'].'" '
                   . 'class="'.$this->_config['label_css'].'">'
                   . $this->translate( $element['label'] )
                   . '</label>';
        }
        
        return array(
                'label' => $label,
                'field' => $field,
                'req'   => ( ( isset( $element['required'] ) && $element['required'] ) ? '*' : NULL ),
                'error' => (
                               isset( $this->_errors[ $this->_current_form ][ $element['name'] ] )
                             ? $this->_errors[ $this->_current_form ][ $element['name'] ]
                             : NULL
                           ),

            );

        
        return $label.'<br />'.$field;
        
    }   // end function createElement()
    
    /**
     * check form data sent by user
     *
     * @access public
     * @param  string   $formname - name of the form to check
     * @return array    $errors   - all errors found in form data
     *
     **/
    public function checkFormData( $formname ) {
    
        $formname = $this->__formName( $formname );
        $errors   = array();
        
        if ( isset( $this->_checked[ $formname ] ) && $this->_checked[ $formname ] === true ) {
            $this->log()->LogDebug(
                'form '.$formname.' already checked, returning errors',
                $this->_errors[ $formname ]
            );
            return $this->_errors[ $formname ];
        }
        
        $this->log()->LogDebug(
            'checking form: '.$formname
        );
        
        foreach ( self::$_forms[$formname]['elements'] as $element ) {
        
            $this->log()->LogDebug(
                'checking field ['.$element['name'].']'
            );
        
            // get form value
            $allow = 'string';
            if ( isset( $element['allow'] ) ) {
                $allow = $element['allow'];
            }
            
            // check validity
            $value = NULL;
            $value = $this->val->param(
                         $element['name'],
                         $this->_config['_allowed'][ $allow ],
                         array(
                             'default' => ( isset( $element['value'] ) ? $element['value'] : NULL )
                         )
                     );
            
            $this->log()->LogDebug(
                'got value: '
              . ( ( isset( $value ) && strlen( $value ) > 0 ) ? '['.$value.']' : '---none---' )
            );
            
            // check required fields
            if (
                 isset( $element['required'] )
                 &&
                 ( ! isset( $value ) || strlen( $value ) == 0 )
            ) {
                $errors[ $element['name'] ]
                    = isset( $element['missing'] )
                    ? $this->translate( $element['missing'] )
                    : $this->translate( 'Please insert a value' );
                continue;
            }
            
            $this->_valid[ $formname ][ $element['name'] ] = $value;
            
        }

        $this->_errors[ $formname ]  = $errors;
        $this->_checked[ $formname ] = true;

        $this->log()->LogDebug( 'valid data:', $this->_valid  );
        $this->log()->LogDebug( 'errors: '   , $this->_errors );
        
        return $errors;
        
    }   // end function checkFormData()
    
    /**
     * convenience method; check if form has errors
     *
     * @access public
     * @param  string   $formname
     * @return boolean
     *
     **/
    public function hasErrors ( $formname ) {

        $formname = $this->__formName( $formname );
        
        if (
             isset( $this->_errors[$formname] )
             &&
             is_array( $this->_errors[$formname] )
             &&
             count ( $this->_errors[$formname] ) > 0
        ) {
            return true;
        }
        
        return false;
    
    }   // end function hasErrors()
    
    /**
     * convenience method; check if form is valid
     *
     * @access public
     * @param  string   $formname
     * @return boolean
     *
     **/
    public function isValid ( $formname ) {

        $formname = $this->__formName( $formname );

        if (
             isset( $this->_errors[$formname] )
             &&
             is_array( $this->_errors[$formname] )
             &&
             count ( $this->_errors[$formname] ) > 0
        ) {
            return false;
        }

        return true;

    }   // end function isValid()
    
    /**
     * convenience method; check if form is already checked
     *
     * @access public
     * @param  string   $formname
     * @return boolean
     *
     **/
    public function isChecked ( $formname ) {

        $formname = $this->__formName( $formname );

        $return = isset( $this->_checked[ $formname ] )
                ? $this->_checked[ $formname ]
                : false;

        $this->log()->LogDebug( $formname.' checked?', $return );

        return $return;

    }   // end function isValid()
    
    /**
     * retrieve validated form data
     *
     * @access public
     * @param  string   $formname
     * @return array    ( 'fieldname' => 'value', ... )
     *
     **/
    public function getValid( $formname ) {
    
        $formname = $this->__formName( $formname );
        return $this->_valid[ $formname ];
        
    }   // end function getValid()
    
/*******************************************************************************
 * Handle already registered elements
 ******************************************************************************/
 
    /**
  	 * Insert an element at a given position
  	 *
  	 * Can be used to insert a new element before an already
  	 * existing element. If the element is not found, the new element
  	 * is added to the top of the form.
  	 *
  	 * @access public
  	 * @param  string   $name    - name of the element to add before
  	 * @param  array    $element - complete element definition
  	 * @return void
  	 *
  	 **/
  	public function insertBefore( $formname = NULL, $name, $element ) {

  	    $this->log()->LogDebug(
            'adding new element before ['.$name.']', $element
        );
        
        $formname = $this->__formName( $formname );

        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            array_splice( self::$_forms[ $formname ]['elements'], $path[0], 0, array( $element) );
        }
        // element not found; add to top
        else {
            array_unshift( self::$_forms[ $formname ]['elements'], array( $element) );
        }

        return true;

    }   // end function insertBefore()
    
    /**
  	 * Insert an element at a given position
  	 *
  	 * Can be used to insert a new element after an already
  	 * existing element. If the element is not found, the new element
  	 * is added to the end of the form.
  	 *
  	 * @access public
  	 * @param  string   $name    - name of the element to add after
  	 * @param  array    $element - complete element definition
  	 * @return void
  	 *
  	 **/
  	public function insertAfter( $formname = NULL, $name, $element ) {

  	    $this->log()->LogDebug(
            'adding new element after ['.$name.']', $element
        );

        $formname = $this->__formName( $formname );

        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            array_splice( self::$_forms[ $formname ]['elements'], ( $path[0] + 1 ), 0, array( $element) );
        }
        // element not found; add to top
        else {
            array_push( self::$_forms[ $formname ]['elements'], array( $element) );
        }

        return true;

    }   // end function insertAfter()


    /**
  	 * Replace an element
  	 *
  	 * Can be used to replace an existing element
  	 *
  	 * @access public
  	 * @param  string  $name     - element to replace
  	 * @param  array   $element  - complete element definition
  	 * @return void
  	 *
  	 **/
  	public function replaceElement( $formname = NULL, $name, $element ) {
  	
        $formname = $this->__formName( $formname );

  	    $this->log()->LogDebug(
            'replacing element named: '.$name,
            $element
        );
        
        // try to find the element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            self::$_forms[ $formname ]['elements'][ $path[0] ] = $element;
        }
        // element not found; add to end
        else {
            self::$_forms[ $formname ]['elements'][] = $element;
        }
        
        return true;

    }   // end function replaceElement()
    
    /**
  	 * Remove an element
  	 *
  	 * Can be used to remove an existing element
  	 *
  	 * @access public
  	 * @param  string  $name       element to remove
  	 * @return void
  	 *
  	 **/
  	public function removeElement( $formname = NULL, $name ) {
  	
  	    $formname = $this->__formName( $formname );

  	    $this->log()->LogDebug(
            'removing element named: '.$name
        );

        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            array_splice( self::$_forms[ $formname ]['elements'], $path[0], 1 );
        }

        return true;

    }   // end function removeElement()
    
    /**
     * set value of an already existing element
     *
     * @access public
     * @param  string   $name  - element name
     * @param  string   $value - new value
     *
     **/
    public function setVal( $formname = '', $name, $value = NULL ) {
    
        $this->log()->LogDebug(
            'setting new value for element ['.$name.']',
            $value
        );

        $formname = $this->__formName( $formname );
        
        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );
        
        // element found
        if ( is_array( $path ) ) {
            self::$_forms[ $formname ]['elements'][ $path[0] ]['value'] = $value;
            return true;
        }
        
        return false;

    }  // end function setVal()
    

/*******************************************************************************
 * INTERNAL FUNCTIONS
 ******************************************************************************/
 
    private function __init() {
    
        // attributes allowed in (nearly) all html tags
        $this->_config['_common_attrs']
            = array(
                  'class' => 'PCRE_STRING',
                  'id'    => 'PCRE_ALPHANUM_EXT',
                  'style' => 'PCRE_STYLE',
                  'title' => 'PCRE_STRING',
                  'dir',
                  'lang'
              );
              
        // <form>
        $this->_config['_form_attrs']
            = array(
                  'method' => array( 'get', 'post' ),
                  'action' => 'PCRE_URI',
              );
              
        // attributes allowed in nearly all input fields
        $this->_config['_common_input_attrs']
            = array(
                  'accesskey'     => 'PCRE_STRING',       # single char
                  'disabled'      => array( 'disabled' ),
                  'name'          => 'PCRE_STRING',
                  'onblur'        => 1,
                  'onchange'      => 'PCRE_PLAIN',
                  'onfocus'       => 1,
                  'onselect'      => 1,
                  'readonly'      => array( 'readonly' ),

              );

        // special input attrs
        $this->_config['_input_attrs']
            = array_merge(
                  $this->_config['_common_input_attrs'],
                  array(
                      'alt' 	        => 'PCRE_UTF8_STRING',
                      'maxlength'     => 'PCRE_INTEGER',
                      'size'          => 'PCRE_INT',
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
                  )
              );
              
        foreach (
            array(
                'text', 'hidden', 'submit', 'reset', 'radio', 'checkbox'
            ) as $type
        ) {
            $this->_config['_'.$type.'_attrs']  = $this->_config['_input_attrs'];
        }
        
        $this->_config['_image_attrs']
            = array_merge(
                  $this->_config['_input_attrs'],
                  array(
                      'src'           => '',
                  )
              );

        // file upload
        $this->_config['_file_attrs']
            = array_merge(
                  $this->_config['_input_attrs'],
                  array(
                      'accept' 	      => 'PCRE_MIME',
                  )
              );
        unset( $this->_config['_file_attrs']['value'] );

        // attributes for radio fields
        $this->_config['_radio_attrs']
            = array_merge(
                  $this->_config['_input_attrs'],
                  array(
                      'checked'       => array( 'checked' ),
                  )
              );
        // attributes for checkboxes
        $this->_config['_checkbox_attrs']
            = $this->_config['_radio_attrs'];

        // attributes for textarea
        $this->_config['_textarea_attrs']
            = array_merge(
                  $this->_config['_common_input_attrs'],
                  array(
                      'rows' => 'PCRE_INTEGER',
                      'cols' => 'PCRE_INTEGER',
                  )
              );

        // support "nicer" names for allowed types
        $this->_config['_allowed']
            = array(
                  'number'   => 'PCRE_INT',
                  'integer'  => 'PCRE_INT',
                  'string'   => 'PCRE_STRING',
                  'password' => 'PCRE_PASSWORD',
                  'email'    => 'PCRE_EMAIL',
                  'url'      => 'PCRE_URI',
                  'plain'    => 'PCRE_PLAIN',
                  'mime'     => 'PCRE_MIME',
                  'boolean'  => 1,
              );
              
    }   // end function __init()

    /**
     * make sure that we have all data we need (as element names)
     *
     * @access private
     * @param  string   $formname - form name
     * @param  array    $array    - array of elements
     * @return array
     *
     **/
    private function __registerForm( $formname, $array ) {
    
        $this->_current_form = $formname;
    
        $elements = array();
        $config   = array();

        foreach ( $array as $index => $element ) {
        
            if ( ! is_array( $element ) ) {
                $config[$index] = $this->translate( $element );
                continue;
            }

            // make sure we have an element name
            $element['name'] = isset( $element['name'] )
                             ? $element['name']
                             : $formname.'_'.$this->generateRandomString();

            // make sure we have a type
            $element['type'] = isset( $element['type'] )
                             ? $element['type']
                             : 'text';
                         
            $elements[] = $element;
            
        }
        
        return array( 'config' => $config, 'elements' => $elements );
        
    }   // end function __registerForm()
    
    /**
     * renders the form
     *
     *
     *
     **/
    private function __generateForm( $formname, $formdata ) {
    
        $this->_current_form = $formname;
        
        $template = $this->_config['output_as'].'.tpl';
        $form     = array();
        $hidden   = array();
        $info     = array();
        $required = 0;

        // ----- render form elements -----
        foreach ( self::$_forms[ $formname ]['elements'] as $element ) {
        
            // overload 'value' key with current data
            if ( isset( $formdata[ $element['name'] ] ) ) {
                $element['value'] = $formdata[ $element['name'] ];
            }
            
            // reference to currently used array
            $add_to_array = & $form;
            
            if ( ! strcasecmp( $element['type'], 'hidden' ) ) {
                $add_to_array = & $hidden;
            }
            
            // mark errors
            if ( isset( $this->_errors[ $formname ][ $element['name'] ] ) ) {
            
                $element['class']
                    = isset( $element['class'] )
                    ? ' ' . $this->_config['error_css']
                    : $this->_config['error_css'];
                    
                #$info[]
                #    = $this->_errors[ $formname ][ $element['name'] ];
                    
            }
            
            // add rendered element to referenced array
            $add_to_array[] = $this->createElement( $element );
            
            if ( isset( $element['required'] ) && $element['required'] === true ) {
                $required++;
            }

        }
        
        // ----- check if we have a submit button -----
        if (
             ! isset ( $this->_buttons['submit'] )
             ||
             count( $this->_buttons['submit'] ) == 0
        ) {
        
            $form[] = $this->createElement(
                          array(
                              'type'  => 'submit',
                              'value' => $this->translate( 'Submit' ),
                              'name'  => $this->_config['save_key'],
                          )
                      );
                      
        }

        // do we have required elements?
        if ( $required > 0 ) {
            // add note
            $req_info = $this->translate(
                            'Required items are marked with {{ marker }}',
                            array( 'marker' => '*' )
                        );
        }

        // ----- render form elements -----
        $form = $this->tpl->getTemplate(
                    $template,
                    array_merge(
                        $this->_config,
                        self::$_forms[ $formname ]['config'],
                        array(
                            'elements' => $form,
                            'req_info' => $req_info,
                        )
                    )
                );
                

        // ----- make sure we have a valid action -----
        $action = (
                    isset( $this->_config['action'] )
                    &&
                    $this->val->isValidUri( $this->_config['action'] )
                  )
                ? $this->_config['action']
                : $this->selfURL();

        // ----- render the form -----
        return
            $this->tpl->getTemplate(
               'form.tpl',
               array_merge(
                   self::$_forms[ $formname ]['config'],
                   $this->_config,
                   array(
                       // form attributes
                       'attributes' => $this->__getAttributes(
                                           array(
                                               'type'     => 'form',
                                               'class'    => $this->_config['fb_form_class'],
                                               'method'   => $this->_config['method'],
                                               'name'     => $formname,
                                               'action'   => $action,
                                           )
                                       ),
                       // hidden fields are included outside table/fieldset
                       'hidden'   => implode( "\n  ",
                                         array_map(
                                             create_function(
                                                 '$arr',
                                                 'return $arr["field"];'
                                             ),
                                             $hidden
                                         )
                                     ),
                       // non-hidden form elements
                       'contents' => $form,
                       // errors
                       'errors'   => is_array( $this->_errors[ $formname ] )
                                  ?  implode( "<br />\n", $this->_errors[ $formname ] )
                                  :  NULL,
                       // info messages
                       'info'     => is_array( $this->_info[ $formname ] )
                                  ?  implode( "<br />\n", $this->_info[ $formname ] )
                                  :  NULL,
                   )
               )
            );

    }   // end function __generateForm()

    /**
     *
     *
     *
     *
     **/
    private function __getAttributes( $element ) {
    
        if ( is_array( $element ) ) {
    
            $this->log()->LogDebug(
                'getting attributes for element of type '.$element['type']
            );

            $known_attrs_for = '_'.$element['type'].'_attrs';
            if ( ! isset( $this->_config[ $known_attrs_for ] ) ) {
                $known_attrs_for = '_common_input_attrs';
            }

            $known_attributes = array_merge(
                                    $this->_config[$known_attrs_for],
                                    $this->_config['_common_attrs']
                                );

            $attrs      = array();
            $id_seen    = false;
            $class_seen = false;
            
            $this->log()->LogDebug( 'known attributes:', $known_attributes );

            foreach ( $element as $attr => $value ) {

                if (
                     ! array_key_exists( $attr, $known_attributes )
                ) {
                    $this->log()->LogDebug( 'Unknown attribute: '.$attr );
                    continue;
                }

                // validate attribute
                if ( is_array( $known_attributes[$attr] ) ) {
                    $valid = in_array( $value, $known_attributes[$attr] )
                           ? $value
                           : NULL;
                }
                else {
                    $valid = $this->val->validate(
                                 $known_attributes[$attr], #constant
                                 $value                    # value
                             );
                }

                if ( ! $valid ) {
                    $this->log()->LogDebug( 'Invalid value for attribute: '.$attr, $value );
                    continue;
                }

                $attrs[] = $attr.'="'.$valid.'"';

                if ( ! strcasecmp( $attr, 'id' ) ) {
                    $id_seen = true;
                }

                // css class?
                if ( ! strcasecmp( $attr, 'class' ) ) {
                    $class_seen = true;
                }

            }

            if ( ! $id_seen ) {
                $attrs[] = 'id="'.$element['name'].'"';
            }

            if ( ! $class_seen ) {
                $attrs[] = 'class="fb'.$element['type'].'"';
            }

            $attributes = implode( ' ', $attrs );
            $this->log()->LogDebug(
                'returning validated attributes as string: '.$attributes
            );
            return $attributes;
            
        }
        else {
echo "invalid call to _getAttributes()<br />";
echo "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
var_export( debug_backtrace() );
echo "</textarea>";
exit;
        }
        
    }   // end function __getAttributes()
    
    /**
     * evaluate form name
     *
     * @access private
     * @param  string   $formname
     * @return string   evaluated form name
     *
     **/
    private function __formName( $formname = '' ) {
    
        if ( strlen( $formname ) == 0 ) {
            $formname = $this->_current_form;
        }
        else {
            $this->_current_form = $formname;
        }
        
        return $this->_current_form;
        
    }   // end function __formName
    
    
/*******************************************************************************
 * Create special field types
 * These functions are private
 ******************************************************************************/
 
     /**
     * create <input /> field
     *
     * @access public
     * @param  array   $element - element definition
     * @return string  HTML (rendered <input />)
     *
     **/
    private function input ( $element ) {
    
        $this->log()->LogDebug( 'creating input field:', $element );

        $template = 'input.tpl';
        if ( file_exists( $this->tpl->getPath() .'/'.$element['type'].'.tpl' ) ) {
            $template = $element['type'].'.tpl';
        }

/*
        if ( isset( $element['value'] ) && is_array( $element['value'] ) ) {
            $output = array();
            foreach ( $element['value'] as $value ) {
                $output[] = '<input '
                          . $this->__getAttributes( $element )
                          . ' />';
            }
            return implode( "<br />", $output );
        }
*/

        // is it a button?
        if ( ! strcasecmp( $element[ 'type' ], 'submit' ) ) {
            $this->_buttons['submit'][] = $element['name'];
        }

        return
            $this->tpl->getTemplate(
                $template,
                array(
                    'attributes' => $this->__getAttributes( $element )
                )
            );

    }   // end function input ()

    /**
     * textarea
     *
     * <textarea></textarea>
     *
     **/
    private function textarea ( $element ) {

        $element['rows'] = ( isset ( $element['rows'] ) && is_numeric( $element['rows'] ) )
                         ? $element['rows']
                         : 10;

        $element['cols'] = ( isset ( $element['cols'] ) && is_numeric( $element['cols'] ) )
                         ? $element['cols']
                         : 100;

        $style   = NULL;
        if ( isset( $this->_errors[ $element['name'] ] ) ) {
            $style = ' style="'.$this->_error_style.'"';
        }

        return $this->tpl->getTemplate(
                   'textarea.tpl',
                   array(
                       'attributes' => $this->__getAttributes( $element ),
                       'style'      => $style,
                       'value'      => ( isset ( $element['value'] ) ? $element['value'] : '' ),
                   )
               );
        
    }   // end function textarea ()
    
    /**
     *
     *
     *
     *
     **/
    private function select ( $element ) {

        if (
             isset( $element['options'] )
             &&
             is_array( $element['options'] )
             &&
             count( $element['options'] ) > 0
        ) {

            $isIndexed = array_values( $element['options'] ) === $element['options'];

            foreach ( $element['options'] as $key => $value ) {

                if ( $isIndexed ) { $key = $value; }

                $selected = (
                                isset( $options['value'] )
                                &&
                                ! strcasecmp( $options['value'], $key )
                            )
                          ? 'selected="selected"'
                          : NULL;

                $element['options']['selected'] = 'selected';
                
            }

        }

        return $this->tpl->getTemplate(
                   'select.tpl',
                   array(
                       'attributes' => $this->__getAttributes( $element ),
                       'value'      => ( isset ( $element['value'] ) ? $element['value'] : '' ),
                   )
               );
    
    
    }

}

?>