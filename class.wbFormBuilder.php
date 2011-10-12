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
  
  $Id$

**/

require_once dirname( __FILE__ ).'/class.wbBase.php';
require_once dirname( __FILE__ ).'/class.wbValidate.php';
require_once dirname( __FILE__ ).'/class.wbTemplate.php';
require_once dirname( __FILE__ ).'/class.wbSeq.php';

if ( ! class_exists( 'wbFormBuilder', false ) ) {

    class wbFormBuilder extends wbBase {

        // ----- Debugging -----
        protected      $debugLevel      = KLOGGER::WARN;
        #protected      $debugLevel      = KLOGGER::DEBUG;

		// auto-number legend fields if no name is given
		private        $_legend_number  = 1;

        // name of the current form
        private        $_current_form   = NULL;
        
        // store buttons we've already seen
        protected      $_buttons        = array();

        // wbTemplate object handler
        private        $tpl;

        // wbValidate object handler
        private        $val;
        
        // wbSeq object handler
        private        $seq;
        
        private static $__FORMS__   = array();

        // variable to store forms
        // 'formname' => array of elements
        private static $_forms;
        
        //
        private        $_invalid    = array();
        
        //
        private        $_js         = array();
        
        //
        private        $_flags      = array(
            'use_filetype_check' => false,
            'use_calendar'       => false,
            'use_editor'         => false,
			'__cal_lang_set'     => false,
        );

        protected      $_config
            = array(
                  'debug'           => 'false',
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
                  'id'              => '',
                  'save_key'        => 'save',
                  'cancel_key'      => 'cancel',
                  # use CSS file
                  'css_file'            => '',
                  'wblib_base_url'      => '',
                  // ----- CSS 'skin'; empty means 'green' -----
                  'skin'                => '',
                  // -----          CSS classes            -----
                  'fieldset_class'   => 'fbfieldset',
                  'legend_class'     => 'fblegend',
                  'label_class'      => 'fblabel',
                  'form_class'       => 'fbform',
                  'left_class'       => 'fbleft',
                  'header_class'     => 'fbheader',
                  'info_class'       => 'fbinfo',
                  'req_class'        => 'fbrequired',
                  'table_class'      => 'fbtable',
                  'error_class'      => 'fberror',
                  'info_class'       => 'fbinfo',
                  'buttonpane_class' => 'fbbuttonpane',
                  'button_class'     => 'fbbutton',
                  'icon_class'       => 'fbicon',
                  'infotext_class'   => 'fbinfotext',

                  # output as table or fieldset
                  'output_as'        => 'fieldset',
                  
                  'secret_field'     => 'fbformkey',
                  
                  # known mime types
				  'mimetypes'        => array(
					  'audio/mpeg' =>
						    array(
								'suffixes' => 'mp3',
								'label'    => 'MPEG Audio Stream, Layer III (MP3)',
							),
					  'application/octet-stream' =>
							array(
								'suffixes' => 'bin|dms|lha|lzh|exe|class|ani|pgp|so|dll|dmg',
								'label'    => 'Binary'
							),
					  'image/gif'                =>
					  		array(
							  	'suffixes' => 'gif',
							  	'label'    => 'GIF Images',
							),
					  'image/*'                  =>
					  		array(
							  	'suffixes' => 'gif|ief|jpeg|jpg|jpe|png|tga|tif|tiff',
							  	'label'    => 'All kinds of images'
							),
					  'application/gzip'		 =>
					  		array(
							  	'suffixes' => 'gzip',
							  	'label'    => 'GZIP compressed files',
							),
					  'image/ief'				 =>
					  		array(
							  	'suffixes' => 'ief',
							  	'label'    => 'IEF images'
							),
					  'image/jpeg'				 =>
					  		array(
							  	'suffixes' => 'jpeg|jpg|jpe',
							  	'label'    => 'JP(e)G images'
							),
					  'application/pdf'	 		 =>
					  		array(
							  	'suffixes' => 'pdf',
							  	'label'    => 'PDF files'
							),
					  'image/png'				 =>
					  		array(
							  	'suffixes' => 'png',
							  	'label'    => 'PNG images'
							),
					  'image/targa'				 =>
					  		array(
							  	'suffixes' => 'tga',
							  	'label'    => 'TGA (Targa) images'
							),
					  'image/tiff'			     =>
					  		array(
							  	'suffixes' => 'tiff|tif',
							  	'label'    => 'TIFF images'
							),
					  'application/zip'			 =>
					  		array(
							  	'suffixes' => 'zip',
							  	'label'    => 'ZIP compressed files'
							),
				  ),

              );

        /**
         * constructor
         **/
        function __construct ( $options = array() ) {
        
            // wbBase adds given $options to $this->_config
            parent::__construct( $options );

            // define known attributes
            $this->__init();
            
            // get wbSeq object
            $this->seq = new wbSeq();

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
	     *
	     *
	     *
	     *
	     **/
	    public function setFile ( $file, $path = NULL, $var = NULL )
	    {
	    
			if ( $var == '' )
			{
				$var = 'FORMS';
			}

	        $this->log()->LogDebug( 'adding file ['.$file.'], path ['.$path.'], var ['.$var.']' );

            // load file
 			parent::setFile( $file, $path, $var );
			include_once $this->_config['current_file'];
			
			$ref = NULL;
            @eval( "\$ref = & \$".$this->_config['var'].";" );
            
            if ( isset($ref) && is_array($ref) )
            {
				// add contents of current file to internal array
				self::$__FORMS__ = array_merge( self::$__FORMS__, $FORMS );
			}

	    }   // end function addFile()

        /**
         *
         *
         *
         *
         **/
        public function addButtons( $formname = NULL, $buttons, $preserve_default = true ) {

            // ----- check if we have a submit button -----
            if (
                    $preserve_default
                 && (
                         ! isset ( $this->_buttons[$formname] )
                      || count( $this->_buttons[$formname] ) == 0
                    )
            ) {

                $this->_buttons[$formname][0] = array(
                    'field' => $this->input(
                                   array(
                                       'type'  => 'submit',
                                       'label' => $this->translate( 'Submit' ),
                                       'value' => $this->translate( 'Submit' ),
                                       'name'  => $this->_config['save_key'].'_'.$formname,
                                       'class' => $this->_config['button_class']
                                                . ( ! empty( $this->_config['skin'] ) ? ' fb'.$this->_config['skin'] : '' ),
                                   )
                               )
                );
                $this->_buttons[$formname][1] = array(
                    'field' => $this->input(
                                   array(
                                       'type'  => 'submit',
                                       'label' => $this->translate( 'Cancel' ),
                                       'value' => $this->translate( 'Cancel' ),
                                       'name'  => $this->_config['cancel_key'].'_'.$formname,
                                       'class' => $this->_config['button_class']
                                                . ( ! empty( $this->_config['skin'] ) ? ' fb'.$this->_config['skin'] : '' ),
                                   )
                               )
                );

            }

            foreach( $buttons as $button ) {
                $this->_buttons[$formname][] = array(
                    'field' => $this->input(
                                   array(
                                       'type'  => 'submit',
                                       'label' => $this->translate( $button['label'] ),
                                       'value' => $this->translate( $button['label'] ),
                                       'name'  => $button['name'],
                                       'class' => $this->_config['button_class']
                                                . ( ! empty( $this->_config['skin'] ) ? ' fb'.$this->_config['skin'] : '' ),
                                   )
                               )
                );
            }

        }   // end function addButtons()

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

            if ( ! $this->hasElement( $formname, $element['name'] ) ) {
	      	    $this->log()->LogDebug(
	                'adding element to form ['.$formname.']',
	                $element
	            );
	            self::$_forms[ $formname ]['elements'][] = $this->__registerElement( $formname, $element );
            }

            return NULL;

        }   // end function addElement()

        /**
         * merge the contents of another form to the current one
         *
         * @access public
         * @param  string  $formname - name of the current form
         * @param  string  $name     - name of the added form
         * @return void
         *
         **/
        public function addForm( $formname = '', $name ) {

            $formname = $this->__validateFormName( $formname );

            // check if the form exists
            if ( ! isset( self::$_forms[$name] ) || ! is_array( self::$_forms[$name] ) ) {
                $this->printError( 'Unable to add form ['.$name.'] to form ['.$formname.'] - the form doesn\'t exist!' );
            }
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

            $this->log()->LogDebug( 'Adding submit flag for form '.$name );

            // add submit flag
            $this->addElement(
                $formname,
                array(
                    'type'  => 'hidden',
                    'name'  => $name.'_submit',
                    'value' => 1,
                )
            );

            // merge elements, re-adding the buttons of the original form
            self::$_forms[ $formname ]['elements']
                = array_merge(
                      self::$_forms[ $formname ]['elements'],
                      self::$_forms[ $name ]['elements']
                  );
                      // push buttons to form again
            if ( count($buttons) ) {
                self::$_forms[ $formname ]['elements']
                    = array_merge(
                          self::$_forms[ $formname ]['elements'],
                      $buttons
                  );
            }

        }   // end function addForm()

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

            $this->seq->config( 'secret_field', $this->_config['secret_field'] );
            if ( ! $this->seq->validateToken( $formname, false ) ) {
                $this->log()->LogDebug(
                    'validateToken returned false!'
				);
				$this->_errors[$formname][] = $this->lang->translate( 'Invalid token!' );
				return $this->_errors[ $formname ];
            }

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
            
                // skip elements that are not expected to have any data
                if ( $element['type'] == 'legend' || $element['type'] == 'infotext' ) {
                    continue;
                }

                $this->log()->LogDebug(
                    'checking field ['.$element['name'].']'
                );

                $allow = 'string';
                if ( isset( $element['allow'] ) ) {
                    $allow = $element['allow'];
                }
                
                // check captcha
                if ( $element['type'] == 'captcha' ) {
                    $securimage = new Securimage();
					$securimage->use_sqlite_db = true;
					$securimage->sqlite_database = dirname(__FILE__).'/vendors/securimage/database/securimage.sqlite';
                    if ($securimage->check($this->val->param($element['name'])) == false) {
                        $errors[ $element['name'] ]
                            = isset( $element['invalid'] )
                            ? $this->translate( $element['invalid'] )
                            : $this->translate( 'The security code entered was incorrect' );
                    }
                }

                // check validity
                $value = NULL;
                if ( ! is_array( $allow ) ) {
                    $value = $this->val->param(
                                 $element['name'],
                                 $this->_config['_allowed'][ $allow ],
                                 array(
                                     'default'  => ( isset( $element['default']  ) ? $element['default'] : NULL ),
                                     'stripped' => (
									 			 	 ( ! isset($element['stripped']) || $element['stripped'] !== false )
									 				 ? true
									 				 : NULL
									 			   )
                                 )
                             );
                    $val_errors = $this->val->getErrors($element['name']);
                    if ( $val_errors ) {
                        if ( $val_errors == 'invalid' ) {
                            $this->_invalid[$formname][$element['name']]
                                = isset( $element['invalid'] )
                                ? $this->translate( $element['invalid'] )
                                : $this->translate( 'Please insert a valid value' );
                        }
                        else {
                            $errors[$element['name']] = $val_errors;
                        }
                    }
                }
                // allow can be a list of allowed values
                else {
                    // value can be a list, too
					$check_values = $this->val->param( $element['name'] );
					if ( ! is_array( $check_values ) ) {
                        $check_values = array( $check_values );
					}
                    foreach ( $allow as $allowed_value ) {
						foreach( $check_values as $check_value ) {
	                        if ( ! strcasecmp( $allowed_value, $check_value ) ) {
	                            if ( $value ) {
	                                $value   = array( $value );
	                            	$value[] = $allowed_value;
								}
								else {
								    $value = $allowed_value;
								}
	                        }
						}
                    }
                }
                
                // encode HTML? - default is YES!
                if ( ! isset( $element['encode'] ) || $element['encode'] !== false ) {
                    $value = $this->__encodeFormData($value);
                }

                $this->log()->LogDebug(
                    'got value: '
                  . (
                        ( isset( $value ) )
                      ? ( is_array( $value ) ? print_r( $value, 1 ) : '['.$value.']' )
                      : '---none---'
                    )
                );

                // check required fields
                if (
                     ( isset( $element['required'] )
                       &&
                       $element['required'] === true
                     )
                ) {
                    if ( isset( $this->_invalid[$formname][$element['name']] ) ) {
                        $errors[ $element['name'] ] = $this->_invalid[$formname][$element['name']];
                        continue;
                    }
                    // field empty?
                    elseif ( ! isset( $value ) || ( is_string($value) && strlen( $value ) == 0 ) ) {
                        $errors[ $element['name'] ]
                            = isset( $element['missing'] )
                            ? $this->translate( $element['missing'] )
                            : $this->translate( 'Please insert a value' );
                        continue;
                    }

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
         *
         *
         *
         *
         **/
		public function getAllowedMimeTypes( $formname = '', $name ) {
		
		    $formname = $this->__validateFormName( $formname );

            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

            // element found
            if ( is_array( $path ) ) {
                $this->log()->LogDebug(
                    'found element ['.$name.']'
                );
                return self::$_forms[ $formname ][ $name ][ 'mimetypes' ];
			}
			
			return NULL;
			
		}   // end function getAllowedMimeTypes()
        
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
            if (
                    ! isset( $this->_valid[ $formname ] )
                 || ! is_array( $this->_valid[ $formname ] )
                 || ! count( $this->_valid[ $formname ] )
            ) {
                if ( ! isset( $this->_checked[ $formname ] ) || $this->_checked[ $formname ] === false ) {
                    $this->checkForm($formname);
                }
            }
            return
                  isset( $this->_valid[ $formname ] )
                ? $this->_valid[ $formname ]
                : array();
        }   // end function getData()
        
        /**
         *
         *
         *
         *
         **/
        public function getErrors( $formname = '' ) {
            $formname = $this->__validateFormName( $formname );
            return is_array( $this->_errors[$formname] )
                 ? $this->_errors[$formname]
                 : array();
        }   // end function getErrors()

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
            
            // print notice into log if the secret field name was left to default
            if ( $this->_config['secret_field'] == 'fbformkey' ) {
                $this->log()->LogWarn(
					'Please note: The "secret_field" option was left to default. You should override this to improve form protection.'
				);
            }
            $this->seq->config( 'secret_field', $this->_config['secret_field'] );

            // use correct template
            $template = 'block.'.$this->_config['output_as'].'.tpl';
            
            // set some defaults
            $this->tpl->setGlobal(
                array(
                    'fieldset_class'
                        => $this->_config['fieldset_class']
                           . ( ! empty( $this->_config['skin'] ) ? ' fb'.$this->_config['skin'] : '' ),
                    'buttonpane_class'
                        => $this->_config['buttonpane_class']
                           . ( ! empty( $this->_config['skin'] ) ? ' fb'.$this->_config['skin'] : '' ),
                    'WBLIB_BASE_URL'
                        => $this->sanitizeURI( $this->_config['wblib_base_url'] ),
                    'token'
						=> $this->seq->createToken( $formname ),
                )
            );

            // make sure the form is loaded
            $this->setForm( $formname );

            // render elements
            $elements = $this->__renderFormElements( $formname, $formdata );

            // do we have required elements?
            $req_info = NULL;
            if ( $elements['req_count'] > 0 ) {
                // add note
                $req_info = $this->translate(
                                'Required item'
                            );
            }

            // ----- check if we have a submit button -----
            if (
                 ! isset ( $this->_buttons[$formname] )
                 ||
                 count( $this->_buttons[$formname] ) == 0
            ) {
                $this->addButtons( $formname, array(), true );
            }

            // ----- render form elements -----
            if ( ! isset ( $elements['blocks'] ) || count( $elements['blocks'] ) < 1 ) {
                $elements['blocks'][] = 0;
            }

            end($elements['fields']);
            $lastitem  = key($elements['fields']);
            $blocks    = array();

            foreach ( $elements['blocks'] as $i => $offset ) {

                // calculate size; $index is the index of the element where the new block begins
                if ( isset( $elements['blocks'][$i+1] ) && $elements['blocks'][$i+1] < $lastitem ) {
                    $size = ( $elements['blocks'][$i+1] - $elements['blocks'][$i] ) + 1;
                }
                else {
                    $size = $lastitem - $offset + 1;
                }

                $begin = 0;
                if ( $offset > 0 ) {
                    $begin = $offset+1;
                }

                $blocks[] = $this->tpl->getTemplate(
                                $template,
                                array_merge(
                                    $this->_config,
                                    self::$_forms[ $formname ]['config'],
                                    array(
                                        // form elements
                                        'header'   => $elements['fields'][$begin]['field'],
                                        'elements' => array_slice( $elements['fields'], $begin, $size ),# $elements['fields'],
                                        'block_number' => $i,
                                    )
                                )
                                // enable cache
                                //,true
                            );
            }

            $form = $this->tpl->getTemplate(
                        $this->_config['output_as'].'.tpl',
                        array_merge(
                            $this->_config,
                            self::$_forms[ $formname ]['config'],
                            array(
                                'formname' => $formname,
                                // form contents
                                'form'     => implode( ' ', $blocks ),
                                // buttons
                                'buttons'  => $this->_buttons[$formname],
                                // info text if there are required fields
                                'req_info' => $req_info,
                                // errors
                                'errors'   => ( isset( $this->_errors[ $formname ] ) && is_array( $this->_errors[ $formname ] ) && count( $this->_errors[ $formname ] ) > 0 )
                                           ?  implode( "<br />\n", $this->_errors[ $formname ] )
                                           :  NULL,
                                // info messages
                                'info'     => ( isset( $this->_info[ $formname ] ) && is_array( $this->_info[ $formname ] ) && count( $this->_info[ $formname ] ) > 0 )
                                           ?  implode( "<br />\n", $this->_info[ $formname ] )
                                           :  NULL,
                            )
                        )
                        // enable cache
                        //,true
                    );

            // ----- render the form -----
            $output =
                $this->tpl->getTemplate(
                   'form.tpl',
                   array_merge(
                       self::$_forms[ $formname ]['config'],
                       $this->_config,
                       $this->_flags,
                       array(
                           // FormBuilder CSS
                           'cssfile'    => $this->_config['css_file'],
                           // form attributes
                           'attributes' => $this->__validateAttributes(
                                               array(
                                                   'type'     => 'form',
                                                   'class'    => $this->_config['form_class'],
                                                   'enctype'  => $this->_config['enctype'],
                                                   'method'   => $this->_config['method'],
                                                   'name'     => $formname,
                                                   'id'       => $this->_config['id'],
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
                           'info'     => ( isset( $this->_info[ $formname ] ) && is_array( $this->_info[ $formname ] ) && count( $this->_info[ $formname ] ) > 0  )
                                      ?  implode( "<br />\n", $this->_info[ $formname ] )
                                      :  NULL,
                           'js'       => implode( "\n", $this->_js )
                       )
                   )
                    // enable cache
                	//,true
                );

            return $output;

        }   //end function getForm()
        
        /**
         *
         *
         *
         *
         **/
		public function getMimeTypes( $by = 'keys' ) {
		    if ( isset($by) ) {
		        $ret = array();
		        if ( $by == 'keys' ) {
			        foreach ( $this->_config['mimetypes'] as $type => $item ) {
			            $ret[$type] = $this->_config['mimetypes'][$type]['label']
									. ' ( .'. str_replace( '|', ', .', $this->_config['mimetypes'][$type]['suffixes'] ) . ')';
			        }
				}
				elseif ( $by == 'suffixes' ) {
				    foreach ( $this->_config['mimetypes'] as $type => $item ) {
			            $ret = array_merge(
								   $ret,
								   explode( '|', $this->_config['mimetypes'][$type]['suffixes'] )
							   );
			        }
			        $ret = array_unique($ret);
			        asort($ret);
				}
		        return $ret;
			}
			return $this->_config['mimetypes'];
		}   // end function getMimeTypes()
        
        /**
         *
         *
         *
         *
         **/
        public function getOptionsByRegex( $formname, $regex ) {
            $formname = $this->__validateFormName( $formname );
            $elements = array();
            $seen     = array();
            foreach ( self::$_forms[ $formname ]['elements'] as $element ) {
                if( preg_match( $regex, $element['name'] ) ) {
					if ( isset( $seen[ $element['name'] ] ) ) {
						continue;
					}
                    $elements[] = $element;
                    $seen[ $element['name'] ] = 1;
                }
            }
            return $elements;
        }   // end function getOptionsByRegex()
        
        /**
         * returns the registered element for a block
         * (start element named $start -> next legend element)
         *
         * @access public
         * @param  string   $formname
         * @param  string   $start      - name of a legend element
         * @return array
         *
         **/
        public function getOptionsForArea( $formname, $start ) {
            $formname = $this->__validateFormName( $formname );
            // find given element
            $path = $this->ArraySearchRecursive( $start, self::$_forms[ $formname ]['elements'], 'name' );
            if ( ! is_array($path) || empty( $path[0] ) ) {
                $this->log()->LogDebug( 'element ['.$start.'] not found, returning empty array' );
                return array();
            }
            // find next legend (=end of block)
            $temp = array_slice( self::$_forms[ $formname ]['elements'], $path[0] );
            // remove the first element from $temp as it is a legend
            $search = array_slice( $temp, 1 );
            $this->log()->LogDebug( 'looking for ['.$start.'] in:', $search );
            // find legend
            $path   = $this->ArraySearchRecursive( 'legend', $search, 'type' );
            // no legend found, so set last element = end of block
            if ( ! is_array($path) || empty( $path[0] ) ) {
                end($temp);
                $path[0] = count($temp);
            }
            $temp = array_slice( $temp, 0, $path[0] );
            $this->log()->LogDebug( 'returning array_slice:', $temp );
            return $temp;
        }   // end function getOptionsForArea()

        /**
         * check if an element is already there
         *
         * @access public
         * @param  string   $formname - name of the form
      	 * @param  array    $name     - name of the element
      	 * @return boolean
         *
         **/
      	public function hasElement( $formname = NULL, $name ) {

            $formname = $this->__validateFormName( $formname );

      	    $this->log()->LogDebug(
                'looking for element ['.$name.'] in form ['.$formname.']'
            );

            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );

            // element found
            if ( is_array( $path ) ) {
                return true;
            }
            // element not found; add to top
            else {
                return false;
            }

            return true;

        }   // end function hasElement()

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
            $formname = $this->__validateFormName( $formname );
      	    $this->log()->LogDebug(
                'adding new element into form ['.$formname.'], after ['.$name.']',
                $element
            );
            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name' );
            // element found
            if ( is_array( $path ) ) {
                array_splice( self::$_forms[ $formname ]['elements'], ++$path[0], 0, array( $element ) );
            }
            // element not found; add to top
            else {
                array_push( self::$_forms[ $formname ]['elements'], $element );
            }
            return true;
        }   // end function insertAfter()
        
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
                array_splice( self::$_forms[ $formname ]['elements'], $path[0], 0, array( $element ) );
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
        public function isCanceled ( $formname = '' ) {
            $formname = $this->__validateFormName( $formname );
            $this->log()->LogDebug( 'checking if form ['.$formname.'] was canceled' );
            if ( $this->val->param( $this->_config['cancel_key'].'_'.$formname ) ) {
                $this->log()->LogDebug( '---> yes' );
                return true;
            }
            $this->log()->LogDebug( '---> no' );
            return false;
        }   // end function isCanceled ()
        
        /**
         *
         *
         *
         *
         **/
        public function isSent( $formname = '' ) {
            $formname = $this->__validateFormName( $formname );
            $this->log()->LogDebug( 'checking if form ['.$formname.'] was sent' );
            $this->log()->LogDebug( 'submit field ['.$formname.'_submit] value ['.$this->val->param( $formname.'_submit' ).']' );
            $this->log()->LogDebug( 'cancel field ['.$this->_config['cancel_key'].'_'.$formname.'] value ['.$this->val->param( $this->_config['cancel_key'].'_'.$formname ).']' );
            if (
                      $this->val->param( $formname.'_submit', 'PCRE_INT' )
                 && ! $this->val->param( $this->_config['cancel_key'].'_'.$formname )
            ) {
                $this->log()->LogDebug( '---> yes' );
                return true;
            }
            $this->log()->LogDebug( '---> no' );
            return false;
        }   // end function isSent()
        
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
            
            $this->log()->LogDebug( 'checking if form ['.$formname.'] is checked' );

            $return = isset( $this->_checked[ $formname ] )
                    ? $this->_checked[ $formname ]
                    : false;

            $this->log()->LogDebug( $formname.' checked?', $return );

            return $return;

        }   // end function isChecked()

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

            $this->log()->LogDebug(
                'form valid?',
                $this->_errors[$formname]
            );

            if (
                 isset( $this->_errors[$formname] )
                 &&
                 is_array( $this->_errors[$formname] )
                 &&
                 count ( $this->_errors[$formname] ) > 0
            ) {
                $this->log()->LogDebug( 'form invalid (errors found)' );
                return false;
            }

            $this->log()->LogDebug( 'form is valid' );
            return true;

        }   // end function isValid()

        /**
         * shortcut
         **/
        public function printForm( $formname = '', $formdata = array() ) {
            echo $this->getForm( $formname, $formdata );
        }   // end function printForm()

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
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

            // element found
            if ( is_array( $path ) ) {
                array_splice( self::$_forms[ $formname ]['elements'], $path[0], 1 );
                return true;
            }
            else {
                $this->log()->LogDebug( "element not found!" );
                return false;
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
        public function setAction( $formname = NULL, $action ) {
            $formname = $this->__validateFormName( $formname );
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
         * add error message
         *
         * @access public
         * @param  string  $formname
         * @param  string  $msg
         * @param  string  $element   - (optional) name of the element the message is attached to
         * @param  boolean $on_top    - (optional) add message on top (instead of bottom)
         * @return void
         *
         **/
        public function setError( $formname = NULL, $msg, $element = NULL, $on_top = false ) {
            $formname = $this->__validateFormName( $formname );
            if ( $on_top ) {
                array_unshift( $this->_errors[$formname], $this->translate( $msg ) );
            }
            else {
                if ( $element ) {
                    $this->_errors[$formname][$element] = $this->translate( $msg );
                }
                else {
                $this->_errors[$formname][] = $this->translate( $msg );
                }
            }
        }   // end function setError()
        
        /**
         * set current form to use
         *
         * @access public
         * @param  string   $formname
         * @return void
         *
         **/
        public function setForm( $formname, $autocheck = true ) {

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

/*
                    if ( ! isset( $ref ) | ! is_array( $ref ) ) {
                        $this->log()->LogDebug( 'trying to load config file ['.$this->_config['current_file'].']' );
                        include_once $this->_config['current_file'];
                        eval( "\$ref = & \$".$this->_config['var'].";" );
                        if ( ! isset( $ref[$formname] ) || ! is_array( $ref[$formname] ) ) {
                            $this->printError(
                                'Unable to register form ['.$formname.']; '
                              . 'the config file seems to be invalid.'
                            );
                        }
                    }|
*/
					if ( ! isset( self::$__FORMS__[$formname] ) || ! is_array( self::$__FORMS__[$formname] ) ) {
					    $this->printError(
                                'Unable to register form ['.$formname.']; '
                              . 'the config file seems to be invalid.'
                            );
					}
					else
					{
					    $ref = &self::$__FORMS__;
					}

                    // now let's register the elements for later use
                    // analyze data and store config
                    foreach ( $ref as $name => $def ) {
                        self::$_forms[ $name ] = $this->__registerForm( $name, $def );
                    }

                    // let's see if the form is already sent
                    if (
                              $autocheck
                         &&   $this->val->param( $formname.'_submit', 'PCRE_INT' )
                         && ! $this->val->param( $this->_config['cancel_key'].'_'.$formname )
                    ) {
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
         *
         *
         *
         *
         **/
        public function setHeader( $formname = '', $header ) {
            $formname = $this->__validateFormName( $formname );
            self::$_forms[ $formname ]['config']['formheader'] = $header;
        }   // end function setHeader()
        
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
         * set infotext of an already existing element
         *
         * @access public
         * @param  string   $name  - element name
         * @param  string   $value - new value
         *
         **/
        public function setInfotext( $formname = '', $name, $value = NULL ) {

            $formname = $this->__validateFormName( $formname );

            $this->log()->LogDebug(
                'setting new value for form ['.$formname.'], element ['.$name.']',
                $value
            );

            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

            // element found
            if ( is_array( $path ) ) {

                $this->log()->LogDebug(
                    'found element ['.$name.']'
                );

                self::$_forms[ $formname ]['elements'][ $path[0] ]['infotext'] = $value;

            }

        }   // end function setInfotext()
        
        /**
         * set allowed MIME types for file upload field
         *
         *
         *
         **/
		public function setAllowedMimeTypes( $formname = '', $name, $types ) {
		
            $formname = $this->__validateFormName( $formname );
            
            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

            // element found
            if ( is_array( $path ) ) {
                $this->log()->LogDebug(
                    'found element ['.$name.']; setting mime types to:', $types
                );
                if ( self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] != 'file' ) {
                    $this->log()->LogError( 'element of type ['.self::$_forms[ $formname ]['elements'][ $path[0] ]['type'].'] cannot have a MIME type!' );
                }
                $mimetypes = array();
                if ( ! is_array( $types ) ) {
                    // is it a serialized array? (format see $_config)
					$unserialized = @unserialize( stripslashes( $types ) );
					if ( $unserialized === false ) {
					    if ( substr_count( $types, '|' ) ) {
					        // application/pdf|image/*|...
					        $types = explode( '|', $types );
						}
						else {
						    // give up, use given value as is
							$types = array( $types );
						}
					}
					else {
					    $types =& $unserialized;
					}
                }
                if ( is_array( $types ) ) {
                    // validate
                    foreach( $types as $item ) {
						if ( preg_match( "/^\w+\//", $item ) ) {
							$mimetypes[] = $item;
						}
                    }
                }
				self::$_forms[ $formname ][ $name ][ 'mimetypes' ] = $mimetypes;
				$this->_flags['use_filetype_check'] = true;
                return true;
            }
            else {
                $this->log()->LogDebug(
                    'element not found!',
                    self::$_forms[ $formname ]['elements']
                );
            }

            return false;
            
		}   // end function setAllowedMimeTypes()
        
        /**
         * set option of an already existing element
         *
         * @access public
         * @param  string   $name   - element name
         * @param  string   $option - option to set
         * @param  string   $value  - new value
         *
         **/
        public function setOption( $formname = '', $name, $option, $value = NULL ) {

            $formname = $this->__validateFormName( $formname );

            $this->log()->LogDebug(
                'setting option ['.$option.'] for form ['.$formname.'], element ['.$name.']',
                $value
            );

            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

            // element found
            if ( is_array( $path ) ) {
                $this->log()->LogDebug(
                    'found element ['.$name.']; old option value: ['.self::$_forms[ $formname ]['elements'][ $path[0] ][$option].']'
                );
                self::$_forms[ $formname ]['elements'][ $path[0] ][$option] = $value;
                return true;
            }
            else {
                $this->log()->LogDebug(
                    'element not found!',
                    self::$_forms[ $formname ]['elements']
                );
            }

            return false;

        }  // end function setOption()

        /**
         * make a field readonly
         *
         * @access public
         * @param  string  $formname
         * @param  string  $name
         * @return void
         *
         **/
        public function setReadonly( $formname, $name ) {
            $formname = $this->__validateFormName( $formname );
            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );
            // element found
            if ( is_array( $path ) ) {
                $this->log()->LogDebug( 'found element ['.$name.']' );
                self::$_forms[ $formname ]['elements'][ $path[0] ]['readonly'] = 'readonly';
                if ( ! isset ( self::$_forms[ $formname ]['elements'][ $path[0] ]['class'] ) ) {
                    self::$_forms[ $formname ]['elements'][ $path[0] ]['class'] = 'fbdisabled';
                }
                else {
                    self::$_forms[ $formname ]['elements'][ $path[0] ]['class'] .= ' fbdisabled';
                }
            }
        }   // end function setReadonly

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
                $this->log()->LogDebug( 'setting key [value] for select field ['.$name.']' );
                self::$_forms[ $formname ]['elements'][ $path[0] ]['value'] = $value;
                $this->log()->LogDebug( 'result:', self::$_forms[ $formname ]['elements'][ $path[0] ] );
                return true;
            }
            else {
                $this->log()->LogDebug(
                    'element not found!',
                    self::$_forms[ $formname ]['elements']
                );
                return false;
            }

        }   // end function setSelected()

        /**
         *
         *
         *
         *
         **/
        public function setURI( $uri ) {
            $this->_config['wblib_base_url'] = $uri;
        }   // end function setURI()

        /**
         * convenience function
         **/
        public function setValue( $formname = '', $name, $value = NULL ) {
            return $this->setVal( $formname, $name, $value );
        }   // end function setValue()

        /**
         * set value of an already existing element
         *
         * @access public
         * @param  string   $name  - element name
         * @param  string   $value - new value
         *
         **/
        public function setVal( $formname = '', $name, $value = NULL ) {

            $formname = $this->__validateFormName( $formname );

            $this->log()->LogDebug(
                'setting new value for form ['.$formname.'], element ['.$name.']',
                $value
            );

            // find given element
            $path = $this->ArraySearchRecursive( $name, self::$_forms[ $formname ]['elements'], 'name', true );

            // element found
            if ( is_array( $path ) ) {

                $this->log()->LogDebug(
                    'found element ['.$name.']',
                    $value
                );

                // find out which key contains the value
                $key = 'value';
                if ( self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'legend' ) {
                    $key = 'text';
                }
                elseif (
                     self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'select'
                     ||
                     self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'multiselect'
                     ||
                     self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'checkbox'
                     ||
                     self::$_forms[ $formname ]['elements'][ $path[0] ]['type'] == 'radio'
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
                $this->log()->LogDebug( 'new item content', self::$_forms[ $formname ]['elements'][ $path[0] ][$key] );
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
        


/*******************************************************************************
 * create (render) fields
 ******************************************************************************/
 
        /**
         *
         **/
		public function captcha( $element ) {
		    if ( ! class_exists( 'securimage' ) ) {
		        // ----- including securImage -----
				require_once dirname( __FILE__ ).'/vendors/securimage/securimage.php';
		    }
			$uri = $this->sanitizeURI( $this->_config['wblib_base_url'] . '/wblib/vendors/securimage/securimage_show.php' );
			return
				'<img id="captcha" src="'.$uri.'" alt="CAPTCHA Image" />' .
				$this->input( array( 'name' => $element['name'], 'maxlength' => 6 ) ).
				'<a href="#" onclick="document.getElementById(\'captcha\').src = \'/securimage/securimage_show.php?\' + Math.random(); return false">'.
				'[ '.$this->lang->translate('Different Image').' ]</a>';
		}   // end function captcha()
 
        /**
         * create a file upload field
         **/
        public function file( $element ) {
            return $this->input( array_merge( $element, array( 'type' => 'file' ) ) );
        }   // end function file()

        /**
         * some simple text
         **/
        public function infotext ( $element ) {

            $this->log()->LogDebug( 'creating infotext field:', $element );

            $text   = $element['label'];
            $output =
                $this->tpl->getTemplate(
                    'infotext.'.$this->_config['output_as'].'.tpl',
                    array(
                        'attributes' => $this->__validateAttributes( $element ),
                        'label'      => //SEQ_OUTPUT(
                                        $this->lang->translate( $text )
                                        //)
                                        ,
                        'value'      => //SEQ_OUTPUT(
                                        $this->lang->translate( $element['value'] )
                                        //)
                                        ,
                        'labelclass' => $this->_config['label_class'],
                    )
                    // enable cache
                    //,true
                );

            $this->log()->LogDebug( 'returning rendered infotext: ', $output );

            return $output;

        }   // end function infotext
        
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
                if ( isset( $element['label'] ) ) {
                    $element['value'] = $this->lang->translate( $element['label'] );
                }
                if (
                        ! isset( $this->_buttons[ $this->_current_form ] )
                     ||   count( $this->_buttons[ $this->_current_form ] ) == 0
                ) {
                    if ( ! isset( $element['class'] ) ) {
                        $element['class'] = 'fbfirstbutton';
                    }
                    else {
                        $element['class'] .= ' fbfirstbutton';
                    }
                }
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

			$formname = $this->__validateFormName();
          
            // is it a file upload field?
            if ( ! strcasecmp( $element['type'], 'file' ) ) {
                if ( isset( self::$_forms[ $formname ][ $element['name'] ][ 'mimetypes' ]) && is_array(self::$_forms[ $formname ][ $element['name'] ][ 'mimetypes' ]) ) {
					$mimetypes = array();
                    foreach( self::$_forms[$formname][$element['name']]['mimetypes'] as $item ) {
						if ( isset( $this->_config['mimetypes'][$item] ) ) {
							$suffixes = explode( '|', $this->_config['mimetypes'][$item]['suffixes'] );
							$mimetypes = array_merge( $mimetypes, $suffixes );
						}
						else {
						    $this->log()->LogDebug( 'unknown MIME Type '.$item.'; please set the suffixes for this type!' );
						}
                    }
					$element['onchange'] = 'TestFileType( this.form.'.$element['name'].'.value, [ \''.implode("', '", $mimetypes).'\' ] );';
                }
            }
            
            // is it a calendar field?
            if ( ! strcasecmp( $element['type'], 'text' ) && isset( $element['calendar'] ) && $element['calendar'] === true ) {
                if ( isset( $element['class'] ) ) {
					$element['class'] .= ' datepicker';
				}
				else {
				    $element['class'] = 'datepicker';
				}
				$this->_flags['use_calendar'] = true;
				if ( isset($element['isstart']) && $element['isstart'] === true ) {
					$this->_js[] = '  setStartID( "'.$element['name'].'" );';
				}
				if ( isset($element['isend']) && $element['isend'] === true ) {
					$this->_js[] = '  setEndID( "'.$element['name'].'" );';
				}
				if ( $this->_flags['__cal_lang_set'] === false ) {
				    $this->_js[] = '  setLang( "'.strtolower( $this->lang->getLang() ).'" );';
				    $this->_flags['__cal_lang_set'] = true;
				}
            }
            
            // is it a password field?
            $pwstrength = NULL;
            if (
			       ! strcasecmp( $element['type'] , 'password' )
				&& ! strcasecmp( $element['allow'], 'password' )
				&&               $element['pwstrength']
			) {
				$pwstrength = true;
			}

            // quote value
            if ( isset($element['value']) && strlen( $element['value'] ) > 0 ) {
                $element['value'] = //SEQ_OUTPUT(
                                    $element['value']
                                    //)
                                    ;
            }

            // get attributes
            $attributes = $this->__validateAttributes( $element );

            $output =
                $this->tpl->getTemplate(
                    $template,
                    array(
                        'attributes' => $attributes,
                        'tooltip'    => ( isset ( $element['infotext'] ) ? $this->translate( $element['infotext'] ) : NULL ),
                        'pwstrength' => $pwstrength,
                        'name'       => $element['name']
                    )
                    // enable cache
                    //,true
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
        public function legend ( $element ) {

            $this->log()->LogDebug( 'creating legend field:', $element );

            $text   = isset( $element['value'] )
                    ? $element['value']
                    : (
                          isset( $element['text'] )
                        ? $element['text']
                        : NULL
                      );

            if ( ! $text ) {
                $this->log()->LogError( 'no text given for legend element!' );
            }
            
            $output =
                $this->tpl->getTemplate(
                    'legend.'.$this->_config['output_as'].'.tpl',
                    array(
                        'attributes' => $this->__validateAttributes( $element ),
                        'value'      => //SEQ_OUTPUT(
                                        $this->lang->translate( $text )
                                        //)
                                        ,
                    )
	                // enable cache
	                //,true
                );

            $this->log()->LogDebug( 'returning rendered legend: ', $output );

            return $output;

        }   // end function legend()
        
        /**
         *
         *
         *
         *
         **/
        public function multiselect( $element ) {
             $element['type']     = 'multiselect';
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
            
            if ( isset( $element['multiple'] ) ) {
                $element['type'] = 'multiselect';
                if ( substr_compare( $element['name'], '[]', -2, 2 ) ) {
                    $element['name'] .= '[]';
                }
            }

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
                            if ( ! strcasecmp( $v, $key ) ) {
                                $selected  = 'selected="selected"';
                                $found_val = true;
                            }
                        }
                    }

                    $opt[] = array(
                                     'key'      => $key,
                                     'value'    => //SEQ_OUTPUT(
                                                   $this->lang->translate( $value )
                                                   //)
                                                   ,
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
                           //'tooltip'    => ( isset ( $element['infotext'] ) ? SEQ_OUTPUT( $this->translate( $element['infotext'] ) ) : NULL ),
                           'tooltip'    => ( isset ( $element['infotext'] ) ? $this->translate( $element['infotext'] ) : NULL ),
                       )
                        // enable cache
                        //,true
                   );

            $this->log()->LogDebug( 'returning rendered select field:', $output );

            return $output;

        }   // end function select()

        /**
         * create checkbox(es)
         *
         * This works the same way like radio(s), so it's just an accessor to
         * the radio() method
         * The 'type' key in the $element array forces this to be a checkbox
         *
         * @access private
         * @param  array    $element - element definition
         * @return HTML
         *
         **/
        public function checkbox( $element ) {
            $element['type'] = 'checkbox';
            return $this->radio( $element );
        }   // end function checkbox()

        /**
         * create a radio
         *
         * @access public
         * @param  array    $element - element definition
         * @return HTML
         *
         **/
        public function radio ( $element ) {
        
            if ( ! isset( $element['type'] ) ) {
                $element['type'] = 'radio';
            }

            $this->log()->LogDebug( 'creating radio field:', $element );

            if (
                 isset( $element['options'] )
                 &&
                 is_array( $element['options'] )
                 &&
                 count( $element['options'] ) > 0
            ) {

                if ( $element['type'] == 'checkbox' && ! preg_match( '#\[\]$#', $element['name'] ) ) {
                    $element['name'] .= '[]';
                }

                $opt       = array();
                $isIndexed = array_values( $element['options'] ) === $element['options'];
                $found_val = false;
                $marked    = array();

                if ( isset($element['value']) ) {
                    if ( ! is_array( $element['value'] ) ) {
                        $element['value'] = array($element['value']);
                    }
                    $marked = $element['value'];
                }
                

                foreach ( $element['options'] as $key => $value ) {

                    if ( $isIndexed ) { $key = $value; }

                    $checked = NULL;

                    #if (
                    #     (
                    #       isset( $element['value'] )
                    #       &&
                    #       ( ! empty( $element['value'] ) || strlen( $element['value'] > 0 ) )
                    #     )
                    #     &&
                    #     ! strcasecmp( $element['value'], $key )
                    #) {
                    #    $checked   = 'checked';
                    #    $found_val = true;
                    #}
                    #if ( in_array( $value, $marked ) ) {
                    if ( in_array( $key, $marked ) ) {
                        $checked   = 'checked';
                        $found_val = true;
                    }

                    // key 'selected' allows to set a list of selected options;
                    // this is only useful for checkboxes
                    if ( $element['type'] == 'checkbox' && isset( $element['selected'] ) && is_array( $element['selected'] ) ) {
                        $this->log()->LogDebug(
                            'setting selected elements for checkbox',
                            $element['selected']
                        );
                        if ( in_array( $value, $element['selected'] ) ) {
                            $checked   = 'checked';
                            $found_val = true;
                        }
                    }

                    $opt[] = array(
                                     'text'
                                         => //SEQ_OUTPUT(
                                            $this->lang->translate( $value )
                                            //)
                                            ,
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
                                     'tooltip'
                                         //=> ( isset ( $element['infotext'] ) ? SEQ_OUTPUT( $this->translate( $element['infotext'] ) ) : NULL ),
                                         => ( isset ( $element['infotext'] ) ? $this->translate( $element['infotext'] ) : NULL ),
                           			 'break'
										 => ( isset ( $element['break'] )    ? true                						: NULL ),
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
                           //'content'    => ( isset ( $element['content'] ) ? SEQ_OUTPUT( $element['content'] ) : ''   ),
                           'content'    => ( isset ( $element['content'] ) ? $element['content'] : ''   ),
                       )
                        // enable cache
                        //,true

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

            if ( isset( $element['maxlength'] ) ) {
                $this->_js[] = "jQuery('#" . $element['name'] . "').jqEasyCounter({ "
  			                     . "maxChars: " . $element['maxlength'] . ", "
			                       . "maxCharsWarning: " . ( $element['maxlength'] - intval( ($element['maxlength']*10/100 ) ) ) . ", "
			                       . "msgText: '" . $this->lang->translate("Characters") . ": '"
			                       . " });\n";
            }
            if ( isset( $element['editor'] ) && $element['editor'] === true ) {
                $this->_js[] = "if ( typeof cleditor != 'undefined' ) { jQuery('#" . $element['name'] . "').cleditor(); }\n";
                $this->_flags['use_editor'] = true;
            }

            return $this->tpl->getTemplate(
                       'textarea.tpl',
                       array(
                           'attributes' => $this->__validateAttributes( $element ),
                           'tooltip'    => ( isset ( $element['infotext'] ) ? $this->translate( $element['infotext'] ) : NULL ),
                           'style'      => $style,
                           //'value'      => ( isset ( $element['value'] ) ? SEQ_OUTPUT($element['value']) : '' ),
                           'value'      => ( isset ( $element['value'] ) ? $element['value'] : '' ),
                       )
                        // enable cache
                        //,true

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
                      'method'  => array( 'get', 'post' ),
                      'action'  => 'PCRE_URI',
                      'id'      => 'PCRE_STRING',
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
                      'tel_de'   => 'PCRE_TEL_GERMAN',
                      'boolean'  => 'PCRE_BOOLEAN',
                  );

        }   // end function __init()
        
		/**
		 * encode HTML in form data
		 * found here:
		 * http://www.php.net/manual/de/function.htmlspecialchars.php#97991
		 *
		 * @access private
		 * @param  string   $value - form data to encode
		 * @return string
		 *
		 **/
		private function __encodeFormData( $var ) {

        	$pattern = '/&(#)?[a-zA-Z0-9]{0,};/';

            // If variable is an array
	        if ( is_array($var) ) {
	            $out = array();
	            foreach ($var as $key => $v) {
	                // Run encoding on every element of the array and return the result. Also maintains the keys.
	                $out[$key] = $this->__encodeFormData($v);
	            }
	        } else {
	            $out = $var;
	            // avoid double encoding (&amp; -> &amp;amp;)
	            while ( preg_match( $pattern,$out ) > 0 ) {
	                $out = htmlspecialchars_decode( $out, ENT_QUOTES );
	            }
	            // Trim the variable, strip all slashes, and encode it
	            $out = htmlspecialchars( stripslashes(trim($out)), ENT_QUOTES, 'UTF-8', true );

	        }

			// return result
	        return $out;
        	
		}   // end function __encodeFormData()

        /**
         *
         *
         *
         *
         **/
        private function __registerElement( $formname = '', $element ) {

            $formname = $this->__validateFormName( $formname );

            // make sure we have a type
            $element['type'] = isset( $element['type'] )
                             ? $element['type']
                             : 'text';

            // make sure we have an element name
            if ( ! isset( $element['name'] ) ) {
                if ( $element['type'] == 'legend' ) {
                	$element['name'] = 'legend_'.++$this->_legend_number;
            	}
				else {
					$element['name'] = $formname.'_'.$this->generateRandomString();
				}
			}

// ----- TODO: readonly ist normalerweise auch für weitere Elemente gültig! -----
            // set valid value for 'readonly'
            if (
                 isset( $element['readonly'] ) && $element['readonly']
                 &&
                 ( $element['type'] === 'text' || $element['type'] === 'textarea' )
            ) {
                $element['readonly'] = 'readonly';
            }

            $this->log()->LogDebug( 'registered element to form '.$formname.':', $element );

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

            $fields   = array();
            $hidden   = array();
            $blocks   = array();
            $required = 0;

            // ----- render form elements -----
            foreach ( self::$_forms[ $formname ]['elements'] as $element ) {

                // overload 'value' key with current data
                if ( isset( $formdata[ $element['name'] ] ) ) {
                    $element['value'] = $formdata[ $element['name'] ];
                }

                // reference to currently used array
                $add_to_array = & $fields;

                // hidden elements
                if ( ! strcasecmp( $element['type'], 'hidden' ) ) {
                    $add_to_array = & $hidden;
                }

                // buttons
                if ( ! strcasecmp( $element['type'], 'submit' ) ) {
                    $add_to_array = & $this->_buttons[$formname];
                }

                // mark blocks
                if ( $element['type'] == 'legend' ) {
                    end($fields);
                    $index = key($fields);
                    $index = isset( $index ) ? $index : 0;
                    $blocks[] = $index;
                }

                // mark errors
                if (
                      isset( $this->_errors[ $formname ][ $element['name'] ] )
                      ||
                      isset( $this->_invalid[ $formname ][ $element['name'] ] )
                ) {
                    $element['class']
                        = isset( $element['class'] )
                        ? ' ' . $this->_config['error_class']
                        : $this->_config['error_class'];
                }

                // create element
                if ( method_exists( $this, $element['type'] ) ) {
                    $field = $this->{$element['type']}( $element );
                }
                else {
                    $field = $this->input( $element );
                }

                $label    = NULL;

                if ( isset ( $element['label'] ) ) {
                    $label = '<label for="'.$element['name'].'" '
                           . 'class="'.$this->_config['label_class'].'">'
                           . $this->translate( $element['label'] )
                           . '</label>';
                }

                // add rendered element to referenced array
                $add_to_array[] = array(
                    'label'    => $label,
                    'type'     => $element['type'],
                    'infotext' => ( isset($infotext) ? $infotext : NULL ),
                    'name'     => $element['name'],
                    'info'     => ( isset ( $element['info'] ) && ( ! empty( $element['info'] ) ) )
                               ?  $this->lang->translate( $element['info'] )
                               :  NULL,
                    'field'    => $field,
                    'req'      => (
                                    ( ( isset( $element['required'] ) && $element['required'] === true ) && $element['required'] )
                                    ? '*'
                                    : NULL
                                  ),
                    'header'   => ( $element['type'] == 'legend' ) ? $field : NULL,
                    'error'    => (
                                      isset( $this->_errors[ $this->_current_form ][ $element['name'] ] )
                                    ? $this->_errors[ $this->_current_form ][ $element['name'] ]
                                    : (
                                          isset( $this->_invalid[ $this->_current_form ][ $element['name'] ] )
                                        ? $this->_invalid[ $this->_current_form ][ $element['name'] ]
                                        : NULL
                                      )
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

            return array( 'fields' => $fields, 'hidden' => $hidden, 'req_count' => $required, 'blocks' => $blocks );

        }   // end function __renderFormElements()
        
        /**
         *
         *
         *
         *
         **/
        private function __validateAttributes( $element ) {

            $this->log()->LogDebug( '', $element );
            
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

                $attrs       = array();
                $css_classes = array();
                $id_seen     = false;

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
                        $this->log()->LogDebug(
                            'validating attr ['.$attr.'] value ['.$value.'] against $known_attributes[$attr]',
                            $known_attributes[$attr]
                        );
                        $valid = in_array( $value, $known_attributes[$attr] )
                               ? $value
                               : NULL;

                    }
                    else {
                        $this->log()->LogDebug(
                            'validating value ['.$value.'] with constant ['.$known_attributes[$attr].']',
                            $value
                        );
                        $valid = $this->val->getValid(
                                     $known_attributes[$attr], #constant
                                     $value                    # value
                                 );
                        $this->log()->LogDebug(
                            'validated value: '.$valid
                        );
                    }

                    if ( empty( $valid ) && strlen( $valid ) == 0 ) {
                        $this->log()->LogDebug( 'Invalid value for attribute: '.$attr, $value );
                        continue;
                    }

                    // css class?
                    if ( ! strcasecmp( $attr, 'class' ) ) {
                        $css_classes = explode( ' ', $valid );
                    }
                    else {
                        $attrs[] = $attr.'="'.$valid.'"';
                    }

                    if ( ! strcasecmp( $attr, 'id' ) ) {
                        $id_seen = true;
                    }
                }

                if ( ! $id_seen ) {
                    $attrs[] = 'id="'.$element['name'].'"';
                }

                // add type specific css class to the element
                if ( ! array_key_exists( 'fb'.$element['type'], $css_classes ) ) {
                    $css_classes[] = 'fb'.$element['type'];
                }

                $attrs[] = 'class="'.implode( ' ', $css_classes ).'"';
                $attributes = implode( ' ', $attrs );
                $this->log()->LogDebug(
                    'returning validated attributes as string: '.$attributes
                );
                return $attributes;

            }
            else {
                echo "invalid call to __validateAttributes(): element is not an array!<br />";
                echo "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
                print_r( $element );
                echo "</textarea>";
                echo "<textarea cols=\"100\" rows=\"20\" style=\"width: 100%;\">";
                var_export( debug_backtrace() );
                echo "</textarea>";
                exit;
            }

        }   // end function __validateAttributes()

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
        
    }

}

?>