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
    protected      $debugLevel      = KLOGGER::OFF;
    #protected      $debugLevel      = KLOGGER::DEBUG;
    
    // name of the current form
    private        $_current_form   = NULL;

    // store buttons we've already seen
    protected      $_buttons        = array();

    // wbTemplate object handler
    private        $tpl;

    // wbValidate object handler
    private        $val;

    // variable to store forms
    // 'formname' => array of elements
    private static $_forms;

    protected      $_config
        = array(
              // default path to search inc.forms.php
              'path'            => '/forms',
              // default forms definition file name
              'file'            => 'inc.forms.php',
              // default variable name
              'var'             => 'FORMS',
              # form defaults (<form> tag)
              'method'          => 'post',
              'action'          => '',
              'enctype'         => '',
              'save_key'        => 'save',
              # use CSS file
              'fb_css_file'     => '',
              # CSS classes
              'fb_label_css'    => 'fblabel',
              'fb_form_class'   => 'fbform',
              'fb_left_class'   => 'fbleft',
              'fb_header_class' => 'fbheader',
              'fb_info_class'   => 'fbinfo',
              'fb_req_class'    => 'fbrequired',
              'fb_table_class'  => 'fbtable',
              'fb_error_class'  => 'fberror',
              'fb_info_class'   => 'fbinfo',
              'fb_buttonpane_class' => 'fbbuttonpane',
              'fb_button_class' => 'fbbutton',

              # output as table or fieldset
              'output_as'       => 'table',
          );
    
    /**
     * constructor
     **/
    function __construct ( $options = array() ) {

        // wbBase adds given $options to $this->_config
        parent::__construct( $options );

        // define known attributes
        $this->__init();
        
        // get current working directory of calling script; this is used
        // to autoload inc.forms.php
        $callstack = debug_backtrace();
        $this->_config['workdir']
            = ( isset( $callstack[0] ) && isset( $callstack[0]['file'] ) )
            ? realpath( dirname( $callstack[0]['file'] ) )
            : realpath( dirname(__FILE__) );
        
        // load config file
        $this->setFile( $this->_config['file'] );
        
        // create validator object
        $this->val  = new wbValidate();
        
        // create template object
        $this->tpl  = new wbTemplate();
        $this->tpl->setPath( realpath( dirname(__FILE__) ).'/wbFormBuilder/templates' );

    }   // end __construct()
    
    /**
     * set current form to use
     *
     * @access public
     * @param  string   $formname
     * @return void
     *
     **/
    public function setForm( $formname ) {
    
        $this->log()->LogDebug( 'setting current form: '.$formname );
        $this->_current_form = $this->__validateFormName($formname);

        // let's see if the form is already registered
        if ( ! isset( self::$_forms[$formname] ) ) {
        
            // do we have a config file?
            if ( ! isset( $this->_config['current_file'] ) ) {
                $this->printError(
                    'Unable to register form ['.$formname.']; '
                  . 'there seems to be no config file. Use setFile() to add one.'
                );
            }
            // config file found, let's see if we find the form there
            else {
            
                $ref = NULL;
                @eval( "\$ref = & \$".$this->_config['var'].";" );
                
                if ( ! isset( $ref ) || ! is_array( $ref ) ) {
                    include_once $this->_config['current_file'];
                    eval( "\$ref = & \$".$this->_config['var'].";" );
                    if ( ! isset( $ref[$formname] ) || ! is_array( $ref[$formname] ) ) {
                        $this->printError(
                            'Unable to register form ['.$formname.']; '
                          . 'the config file seems to be invalid.'
                        );
                    }
                }
                
                // now let's register the elements for later use
                // analyze data and store config
                foreach ( $ref as $name => $def ) {
                    self::$_forms[ $name ] = $this->__registerForm( $name, $def );
                }

                // let's see if the form is already sent
                if ( $this->val->param( $formname.'_submit', 'PCRE_INT' ) ) {
                    $this->log()->LogDebug( 'auto-checking form '.$formname );
                    $this->checkForm( $formname );
                }

            }

        }
        else {
            $this->log()->LogDebug( "Form $formname already registered" );
        }
        
    }   // end function setForm()
    
    /**
     * merge the contents of another form to the current one
     *
     *
     *
     **/
    public function addForm( $formname = '', $name ) {
    
        $formname = $this->__validateFormName( $formname );
        
        $this->log()->LogDebug(
            "adding elements of form [$name] to form [$formname]<br />"
        );

        // add the header as legend
        if ( isset( self::$_forms[ $name ]['config']['header'] ) ) {
            $this->insertBefore(
                $name,
                'quertz',
                array(
                    'type'  => 'legend',
                    'name'  => $name . '_header',
                    'label' => self::$_forms[ $name ]['config']['header']
                )
            );
        }
        
        $buttons = array();
        $remove  = array();

        // remove buttons from original form, storing them into an array
        foreach ( self::$_forms[ $formname ]['elements'] as $i => $element ) {
            if (
                 ! strcasecmp( $element['type'], 'submit' )
                 ||
                 ! strcasecmp( $element['type'], 'reset' )
            ) {
                $buttons[] = $element;
                $remove[]  = $i;
            }
        }
        foreach ( array_reverse( $remove ) as $i ) {
            array_splice(
                self::$_forms[ $formname ]['elements'],
                $i,
                1
            );
        }
        
        // remove buttons from added form
        foreach ( self::$_forms[ $name ]['elements'] as $i => $element ) {
            if (
                 ! strcasecmp( $element['type'], 'submit' )
                 ||
                 ! strcasecmp( $element['type'], 'reset' )
            ) {
                array_splice(
                    self::$_forms[ $name ]['elements'],
                    $i,
                    1
                );
            }
        }

        // merge elements, re-adding the buttons of the original form
        self::$_forms[ $formname ]['elements']
            = array_merge(
                  self::$_forms[ $formname ]['elements'],
                  self::$_forms[ $name ]['elements'],
                  // push buttons to form again
                  $buttons
              );
              
    }   // end function addForm()
    
  	/**
  	 * create an element and store it in internal element array(s)
  	 * (_elements or _hidden)
  	 *
  	 * @access public
  	 * @return void
  	 *
  	 **/
  	public function addElement( $formname = '', $element ) {
  	
        $formname = $this->__validateFormName( $formname );

  	    $this->log()->LogDebug(
            'adding element to form ['.$formname.']',
            $element
        );

        self::$_forms[ $formname ]['elements'][] = $this->__registerElement( $formname, $element );
        
        return NULL;

    }   // end function addElement()
    
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
  	
        $formname = $this->__validateFormName( $formname );

  	    $this->log()->LogDebug(
            'adding new element into form ['.$formname.'], before ['.$name.']',
            $element
        );

        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            array_splice( self::$_forms[ $formname ]['elements'], $path[0], 0, $element );
        }
        // element not found; add to top
        else {
            array_unshift( self::$_forms[ $formname ]['elements'], $element );
        }

        return true;

    }   // end function insertBefore()
    
    /**
     *
     *
     *
     *
     **/
    public function removeElement( $formname = '', $name ) {

        $formname = $this->__validateFormName( $formname );
        
        $this->log()->LogDebug(
            "removing element [$name] from form [$formname]"
        );

        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            array_splice( self::$_forms[ $formname ]['elements'], $path[0], 1 );
        }
        else {
            $this->log()->LogDebug( "element not found!" );
        }

    }   // end function removeElement()
    
    /**
     * set action-attribute
     *
     * @access public
     * @param  string  $action - URL
     * @return void
     *
     **/
    public function setAction( $action ) {
        $action = $this->getURI( $action );
        if ( ! $this->val->isValidURI( $action ) ) {
            $this->printError( 'Invalid form action: ['.$action.']' );
        }
        else {
            $this->_config['action'] = $action;
        }
        return NULL;
    }   // end function setAction()
    
    /**
     * add info text
     *
     * @access public
     * @param  string  $formname
     * @param  string  $msg
     * @return void
     *
     **/
    public function setInfo( $formname, $msg ) {
        $formname = $this->__validateFormName( $formname );
        $this->log()->LogDebug( 'adding info to form ['.$formname.']:', $msg );
        $this->_info[$formname][] = $this->translate( $msg );
        return NULL;
    }   // end function setInfo()
    
    /**
     * add error message
     *
     * @access public
     * @param  string  $formname
     * @param  string  $msg
     * @return void
     *
     **/
    public function setError( $formname, $msg ) {
        $formname = $this->__validateFormName( $formname );
        $this->_errors[$formname][] = $this->translate( $msg );
    }   // end function setError()
    
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
            'setting new value for form ['.$formname.'], element ['.$name.']',
            $value
        );

        $formname = $this->__validateFormName( $formname );

        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

        // element found
        if ( is_array( $path ) ) {

            $this->log()->LogDebug(
                'found element ['.$name.']',
                $value
            );

            $key = 'value';
            if (
                 self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'select'
                 ||
                 self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'multiselect'
            ) {
            
                $this->log()->LogDebug(
                    'element ['.$name.'] is a select field'
                );
                
                if ( is_array( $value ) ) {
                    $key = 'options';
                }
                else {
                    $key = 'content';
                }

                $this->log()->LogDebug(
                    'key to overwrite: '.$key
                );

            }

            self::$_forms[ $formname ]['elements'][ $path[0] ][$key] = $value;
            return true;
        }
        else {
            $this->log()->LogDebug(
                'element not found!',
                self::$_forms[ $formname ]['elements']
            );
        }

        return false;

    }  // end function setVal()
    
    /**
     *
     *
     *
     *
     **/
    public function setSelected( $formname = '', $name, $value = NULL ) {
    
        $formname = $this->__validateFormName( $formname );
        
        $this->log()->LogDebug(
            'Searching for form element ['.$name.'] in form ['.$formname.']'
        );
        
        // find given element
        $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

        // element found
        if ( is_array( $path ) ) {
            if (
                 self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] != 'select'
                 &&
                 self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] != 'multiselect'
            ) {
                $this->log()->LogDebug(
                    'element ['.$name.'] found, but not of type "select"!'
                );
                return false;
            }
            self::$_forms[ $formname ]['elements'][ $path[0] ]['value'] = $value;
echo "$name<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
print_r( self::$_forms[ $formname ]['elements'][ $path[0] ] );
echo "</textarea>";
        }
        else {
            $this->log()->LogDebug(
                'element not found!',
                self::$_forms[ $formname ]['elements']
            );
        }
        
    }
    
    /**
     *
     *
     *
     *
     **/
    public function setHeader( $formname = '', $header ) {
        $formname = $this->__validateFormName( $formname );
        self::$_forms[ $formname ]['config']['header'] = $header;
    }   // end function setHeader()


/*******************************************************************************
 * 
 ******************************************************************************/
 
    /**
     * shortcut
     **/
    public function printForm( $formname = '', $formdata = array() ) {
        echo $this->getForm( $formname, $formdata );
    }

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
    
        $formname = $this->__validateFormName( $formname );

        $this->log()->LogDebug(
            'creating form ['.$formname.'], Form data:', $formdata
        );

        // use correct template
        $template = $this->_config['output_as'].'.tpl';

        // make sure the form is loaded
        $this->setForm( $formname );
        
        // render elements
        $elements = $this->__renderFormElements( $formname, $formdata );

        // do we have required elements?
        $req_info = NULL;
        if ( $elements['req_count'] > 0 ) {
            // add note
            $req_info = $this->translate(
                            'Required items are marked with {{ marker }}',
                            array( 'marker' => '*' )
                        );
        }

        // ----- check if we have a submit button -----
        if (
             ! isset ( $this->_buttons[$formname] )
             ||
             count( $this->_buttons[$formname] ) == 0
        ) {

            $this->_buttons[$formname][0] = array(
                'field' => $this->input(
                               array(
                                   'type'  => 'submit',
                                   'value' => $this->translate( 'Submit' ),
                                   'name'  => $this->_config['save_key'],
                                   'class' => $this->_config['fb_button_class']
                               )
                           )
            );

        }

        // ----- render form elements -----
        $this->tpl->setBehaviour( 'comment' );
        $form = $this->tpl->getTemplate(
                    $template,
                    array_merge(
                        $this->_config,
                        self::$_forms[ $formname ]['config'],
                        array(
                            // form elements
                            'elements' => $elements['fields'],
                            // buttons
                            'buttons'  => $this->_buttons[$formname],
                            // info text if there are required fields
                            'req_info' => $req_info,
                        )
                    )
                );

        // ----- render the form -----
        $output =
            $this->tpl->getTemplate(
               'form.tpl',
               array_merge(
                   self::$_forms[ $formname ]['config'],
                   $this->_config,
                   array(
                       // FormBuilder CSS
                       'cssfile'    => $this->_config['fb_css_file'],
                       // form attributes
                       'attributes' => $this->__validateAttributes(
                                           array(
                                               'type'     => 'form',
                                               'class'    => $this->_config['fb_form_class'],
                                               'enctype'  => $this->_config['enctype'],
                                               'method'   => $this->_config['method'],
                                               'name'     => $formname,
                                               'action'   => $this->_config['action'],
                                           )
                                       ),
                       // hidden fields are included outside table/fieldset
                       'hidden'   => $elements['hidden'],
                       // non-hidden form elements
                       'contents' => $form,
                       // errors
                       'errors'   => ( isset( $this->_errors[ $formname ] ) && is_array( $this->_errors[ $formname ] ) )
                                  ?  implode( "<br />\n", $this->_errors[ $formname ] )
                                  :  NULL,
                       // info messages
                       'info'     => ( isset( $this->_info[ $formname ] ) && is_array( $this->_info[ $formname ] ) )
                                  ?  implode( "<br />\n", $this->_info[ $formname ] )
                                  :  NULL,
                   )
               )
            );

        return $output;
        
    }   //end function getForm()
    
    /**
     * retrieve validated form data
     *
     * @access public
     * @param  string   $formname
     * @return array    ( 'fieldname' => 'value', ... )
     *
     **/
    public function getData( $formname = '' ) {
        $formname = $this->__validateFormName( $formname );
        return $this->_valid[ $formname ];
    }   // end function getData()
    
    /**
     * check form data sent by user
     *
     * @access public
     * @param  string   $formname - name of the form to check
     * @return array    $errors   - all errors found in form data
     *
     **/
    public function checkForm( $formname = '' ) {

        $formname = $this->__validateFormName( $formname );
        $errors   = array();

        if ( isset( $this->_checked[ $formname ] ) && $this->_checked[ $formname ] === true ) {
            $this->log()->LogDebug(
                'form '.$formname.' already checked, returning registered errors',
                $this->_errors[ $formname ]
            );
            return $this->_errors[ $formname ];
        }

        $this->log()->LogDebug(
            'checking form: '.$formname
        );

        // ----- for all registered elements of this form... -----
        foreach ( self::$_forms[$formname]['elements'] as $element ) {

            $this->log()->LogDebug(
                'checking field ['.$element['name'].']'
            );

            $allow = 'string';
            if ( isset( $element['allow'] ) ) {
                $allow = $element['allow'];
            }

            // check validity
            $value = NULL;
            if ( ! is_array( $allow ) ) {
                $value = $this->val->param(
                             $element['name'],
                             $this->_config['_allowed'][ $allow ],
                             array(
                                 'default' => ( isset( $element['default'] ) ? $element['default'] : NULL )
                             )
                         );
            }
            // allow can be a list of allowed values
            else {
                foreach ( $allow as $allowed_value ) {
                    if ( ! strcasecmp( $allowed_value, $this->val->param( $element['name'] ) ) ) {
                        $value = $allowed_value;
                        break;
                    }
                }
            }

            $this->log()->LogDebug(
                'got value: '
              . ( ( isset( $value ) && strlen( $value ) > 0 ) ? '['.$value.']' : '---none---' )
            );

            // check required fields
            if (
                 ( isset( $element['required'] )
                   &&
                   $element['required'] === true
                 )
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

    }   // end function checkForm()
    
    /**
     * convenience method; check if form is valid
     *
     * @access public
     * @param  string   $formname
     * @return boolean
     *
     **/
    public function isValid ( $formname = '' ) {

        $formname = $this->__validateFormName( $formname );

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
    public function isChecked ( $formname = '' ) {

        $formname = $this->__validateFormName( $formname );

        $return = isset( $this->_checked[ $formname ] )
                ? $this->_checked[ $formname ]
                : false;

        $this->log()->LogDebug( $formname.' checked?', $return );

        return $return;

    }   // end function isChecked()



/*******************************************************************************
 * create (render) fields
 ******************************************************************************/
 
     /**
      *
      *
      *
      *
      **/
     public function legend ( $element ) {
     
         $this->log()->LogDebug( 'creating legend field:', $element );
         
         $text = $element['label'];
         unset( $element['label'] );
         
         return
            $this->tpl->getTemplate(
                'legend.tpl',
                array(
                    'attributes' => $this->__validateAttributes( $element ),
                    'value'      => $text
                )
            );
            
     }   // end function legend()

     /**
     * create <input /> field
     *
     * @access public
     * @param  array   $element - element definition
     * @return string  HTML (rendered <input />)
     *
     **/
    public function input ( $element ) {

        $this->log()->LogDebug( 'creating input field:', $element );
        
        if ( ! isset( $element['type'] ) ) {
            $element['type'] = 'text';
        }

        $template = 'input.tpl';
        if ( file_exists( $this->tpl->getPath() .'/'.$element['type'].'.tpl' ) ) {
            $template = $element['type'].'.tpl';
        }
        $this->log()->LogDebug( 'element template: '.$template );

        // is it a button?
        if ( ! strcasecmp( $element[ 'type' ], 'submit' ) ) {
            $this->log()->LogDebug( 'creating button' );
            $element['value'] = $this->lang->translate( $element['value'] );
            $this->_buttons[ $this->_current_form ][]
                = array(
                      'field' => '<input type="submit" '
                              .  'name="'.$element['name'].'" '
                              .  'id="'.$element['name'].'" '
                              .  'value="'.$element['value'].'" '
                              .  'class="'.$this->_config['fb_button_class'].'" />'
                  );
            #$element['name'];
        }

        // is it a checkbox?
        if ( ! strcasecmp( $element[ 'type' ], 'checkbox' ) ) {
            $this->log()->LogDebug( 'creating checkbox' );
            if ( isset( $element[ 'onchecked' ] ) ) {
                if (
                     isset( $element['value'] )
                     &&
                     ! strcasecmp( $element['value'], $element[ 'onchecked' ] )
                ) {
                    $element['checked'] = 'checked';
                }
                $element['value'] = $element[ 'onchecked' ];
            }
        }

        // quote value
        if ( isset( $element['value'] ) ) {
            $element['value'] = htmlentities($element['value']);
        }
        
        // get attributes
        $attributes = $this->__validateAttributes( $element );
        
        $output =
            $this->tpl->getTemplate(
                $template,
                array(
                    'attributes' => $attributes
                )
            );

        $this->log()->LogDebug( 'returning rendered input field:', $output );
        
        return $output;

    }   // end function input ()
    
    /**
     *
     *
     *
     *
     **/
    public function multiselect( $element ) {
         $element['multiple'] = 'multiple';
         if ( substr_compare( $element['name'], '[]', -2, 2 ) ) {
             $element['name'] .= '[]';
         }
         return $this->select( $element );
    }
    
    /**
     * create a select box (dropdown)
     *
     * @access private
     * @param  array    $element - element definition
     * @return HTML
     *
     **/
    public function select ( $element ) {

        $this->log()->LogDebug( 'creating select field:', $element );

        if (
             isset( $element['options'] )
             &&
             is_array( $element['options'] )
             &&
             count( $element['options'] ) > 0
        ) {

            $opt       = array();
            $found_val = false;
            $isIndexed = array_values( $element['options'] ) === $element['options'];

            foreach ( $element['options'] as $key => $value ) {

                if ( $isIndexed ) { $key = $value; }
                
                $selected = NULL;

                if ( isset( $element['value'] ) ) {
                    if ( ! is_array( $element['value'] ) ) {
                        $element['value'] = array( $element['value'] );
                    }

                    foreach ( $element['value'] as $i => $v ) {
                        if ( ! strcasecmp( $v, $key )
                        ) {
                            $selected  = 'selected="selected"';
                            $found_val = true;
                        }
                    }
                }

                $opt[] = array(
                                 'key'      => $key,
                                 'value'    => $this->lang->translate( $value ),
                                 'selected' => $selected
                             );

            }
            
            $this->log()->LogDebug( 'options:', $opt );
            
            // select default value?
            if ( ! $found_val && isset( $element['default'] ) ) {
                $opt[ $element['default'] ]['selected'] = 'selected="selected"';
            }

        }

        $output = $this->tpl->getTemplate(
                   'select.tpl',
                   array(
                       'attributes' => $this->__validateAttributes( $element ),
                       'options'    => ( isset ( $opt ) ? $opt : NULL ),
                       'content'    => ( isset ( $element['content'] ) ? $element['content'] : '' ),
                   )
               );

        $this->log()->LogDebug( 'returning rendered select field:', $output );

        return $output;

    }   // end function select()
    
    /**
     * create a radio
     *
     * @access private
     * @param  array    $element - element definition
     * @return HTML
     *
     **/
    public function radio ( $element ) {

        $this->log()->LogDebug( 'creating radio field:', $element );

        if (
             isset( $element['options'] )
             &&
             is_array( $element['options'] )
             &&
             count( $element['options'] ) > 0
        ) {
        
            $opt       = array();
            $isIndexed = array_values( $element['options'] ) === $element['options'];
            $found_val = false;

            foreach ( $element['options'] as $key => $value ) {

                if ( $isIndexed ) { $key = $value; }
                
                $checked = NULL;

                if (
                     (
                       isset( $element['value'] )
                       &&
                       ( ! empty( $element['value'] ) || strlen( $element['value'] > 0 ) )
                     )
                     &&
                     ! strcasecmp( $element['value'], $key )
                ) {
                    $checked   = 'checked';
                    $found_val = true;
                }
                
                $opt[$key] = array(
                                 'text'
                                     => $this->lang->translate( $value ),
                                 'attributes'
                                     => $this->__validateAttributes(
                                            array_merge(
                                                $element,
                                                array(
                                                    'checked' => $checked,
                                                    'value'   => $key,
                                                )
                                            )
                                        ),
                             );

            }
            
            if ( ! $found_val && isset( $element['default'] ) ) {
                $opt[ $element['default'] ]['checked'] = 'checked';
            }

            unset( $element['options'] );

        }

        return $this->tpl->getTemplate(
                   'radio.tpl',
                   array(
                       'options'    => ( isset ( $opt ) ? $opt : array() ),
                       'content'    => ( isset ( $element['content'] ) ? $element['content'] : '' ),
                   )
               );

    }   // end function radio()
    
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
                       'attributes' => $this->__validateAttributes( $element ),
                       'style'      => $style,
                       'value'      => ( isset ( $element['value'] ) ? $element['value'] : '' ),
                   )
               );

    }   // end function textarea ()

    
/*******************************************************************************
 * INTERNAL FUNCTIONS
 ******************************************************************************/

    /**
     * define known attributes for validation (create valid XHTML)
     *
     * @access private
     * @return void
     *
     **/
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
                  'enctype' => array( 'multipart/form-data', 'text/plain', 'application/x-www-form-urlencoded' ),
              );

        // attributes allowed in nearly all input fields
        $this->_config['_common_input_attrs']
            = array(
                  'accesskey'     => 'PCRE_STRING',       # single char
                  'disabled'      => array( 'disabled' ),
                  'name'          => 'PCRE_STRING',
                  'onblur'        => 1,
                  'onclick'       => 'PCRE_PLAIN',
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
                'text', 'hidden', 'submit', 'reset', 'radio', 'checkbox', 'password'
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
        
        // multiselect
        $this->_config['_multiselect_attrs']
            = array_merge(
                  $this->_config['_input_attrs'],
                  array(
                      'multiple' 	      => array( 'multiple' ),
                  )
              );
        unset( $this->_config['_multiselect_attrs']['value'] );

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
                  'uri'      => 'PCRE_URI',
                  'plain'    => 'PCRE_PLAIN',
                  'mime'     => 'PCRE_MIME',
                  'boolean'  => 1,
              );

    }   // end function __init()
    
    /**
     * evaluate form name
     *
     * @access private
     * @param  string   $formname (optional)
     * @return string   evaluated form name; returns $this->_current_form if
     *                  $formname is empty
     *
     **/
    private function __validateFormName( $formname = '' ) {

        if ( strlen( $formname ) == 0 ) {
            $formname = $this->_current_form;
        }
        return $formname;

    }   // end function __validateFormName

    /**
     *
     *
     *
     *
     **/
    private function __validateAttributes( $element ) {

        if ( is_array( $element ) ) {

            $this->log()->LogDebug(
                'getting attributes for element of type: '.$element['type']
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
                    $this->log()->LogDebug(
                        'validating with constant ['.$known_attributes[$attr].']',
                        $value
                    );
                    $valid = $this->val->getValid(
                                 $known_attributes[$attr], #constant
                                 $value                    # value
                             );
                    $this->log()->LogDebug(
                        'value: '.$valid
                    );
                }

                if ( empty( $valid ) && strlen( $valid ) == 0 ) {
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

    }   // end function __validateAttributes()
    
    /**
     *
     *
     *
     *
     **/
    private function __registerElement( $formname = '', $element ) {
    
        $formname = $this->__validateFormName( $formname );

        // make sure we have an element name
        $element['name'] = isset( $element['name'] )
                         ? $element['name']
                         : $formname.'_'.$this->generateRandomString();

        // make sure we have a type
        $element['type'] = isset( $element['type'] )
                         ? $element['type']
                         : 'text';
                         
        return $element;
        
    }   // end function __registerElement();

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

        $elements = array();
        $config   = array();

        foreach ( $array as $index => $element ) {
        
            if ( ! is_array( $element ) ) {
                $config[$index] = $this->translate( $element );
                continue;
            }

            $elements[] = $this->__registerElement( $formname, $element );

        }

        return array( 'config' => $config, 'elements' => $elements );

    }   // end function __registerForm()
    
    /**
     *
     *
     *
     *
     **/
    private function __renderFormElements( $formname, $formdata ) {
    
        $form     = array();
        $hidden   = array();
        $required = 0;

        // ----- render form elements -----
        foreach ( self::$_forms[ $formname ]['elements'] as $element ) {

            // overload 'value' key with current data
            if ( isset( $formdata[ $element['name'] ] ) ) {
                $element['value'] = $formdata[ $element['name'] ];
            }

            // reference to currently used array
            $add_to_array = & $form;

            // hidden elements
            if ( ! strcasecmp( $element['type'], 'hidden' ) ) {
                $add_to_array = & $hidden;
            }
            
            // buttons
            if ( ! strcasecmp( $element['type'], 'submit' ) ) {
                $add_to_array = & $this->_buttons[$formname];
            }

            // mark errors
            if ( isset( $this->_errors[ $formname ][ $element['name'] ] ) ) {

                $element['class']
                    = isset( $element['class'] )
                    ? ' ' . $this->_config['fb_error_class']
                    : $this->_config['fb_error_class'];

            }
            
            // create element
            if ( method_exists( $this, $element['type'] ) ) {
                $field = $this->{$element['type']}( $element );
            }
            else {
                $field = $this->input( $element );
            }

            $label = NULL;

            if ( isset ( $element['label'] ) ) {
                $label = '<label for="'.$element['name'].'" '
                       . 'class="'.$this->_config['fb_label_css'].'">'
                       . $this->translate( $element['label'] )
                       . '</label>';
            }

            // add rendered element to referenced array
            $add_to_array[] = array(
                'label'  => $label,
                'info'   => isset ( $element['info'] )
                         ?  $this->lang->translate( $element['info'] )
                         :  NULL,
                'field'  => $field,
                'req'    => ( ( ( isset( $element['required'] ) && $element['required'] === true ) && $element['required'] ) ? '*' : NULL ),
                'header' => ( $element['type'] == 'legend' ) ? $field : NULL,
                'error'  => (
                                isset( $this->_errors[ $this->_current_form ][ $element['name'] ] )
                              ? $this->_errors[ $this->_current_form ][ $element['name'] ]
                              : NULL
                            ),
            );

            if ( isset( $element['required'] ) && $element['required'] === true ) {
                $required++;
            }

        }
        
        // ----- add unique element to check if the form is sent -----
        $hidden[] = array(
                        'label' => '',
                        'field' => $this->input(
                                       array(
                                           'type'  => 'hidden',
                                           'name'  => $formname.'_submit',
                                           'value' => 1,
                                       )
                                   ),
                    );

        return array( 'fields' => $form, 'hidden' => $hidden, 'req_count' => $required );
        
    }   // end function __renderFormElements()
    
}

?>