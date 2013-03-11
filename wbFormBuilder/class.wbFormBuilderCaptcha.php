<?php

/**

  FormBuilder Captcha helper class

  Copyright (C) 2012, Bianka Martinovic
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


if ( ! class_exists( 'wbFormBuilderCaptcha', false ) ) {

    if ( ! class_exists( 'securimage', false ) ) {
    	// ----- including securImage -----
        require_once dirname( __FILE__ ).'/../vendors/securimage/securimage.php';
    }

	/**
	 * This class allows to set a session name before creating a SecurImage
	 * instance; also, it sets some defaults for use with wbFormBuilder
	 **/
	class wbFormBuilderCaptcha extends Securimage {
	    public $session_name = NULL;
	    public function __construct( $session_name ) {
			$this->session_name         = $session_name;
			$this->use_sqlite_db   		= true;
            $this->sqlite_database 		= dirname(__FILE__).'/../vendors/securimage/database/wbFormBuilder.sqlite';
            $this->background_directory = dirname(__FILE__).'/../vendors/securimage/backgrounds/';
			$this->text_color 		    = new Securimage_Color('#006699');
			parent::__construct();
	    }
	    public function showCaptcha() {
			$this->show();
	    }
	}
}