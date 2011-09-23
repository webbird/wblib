<?php

/**

  Intrusion detection

  Copyright (C) 2011, Bianka Martinovic
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
  
  -----                                                              -----
  You may add your own RegExp here, but please do NOT alter existing ones!
  -----                                                              -----

**/

// http://niiconsulting.com/innovation/snortsignatures.html

// ----- SQL Injection checks -----
// Signature 1 - detects single-quote and double-dash
define( 'PCRE_SQL_QUOTES', '/(\%27)|(\')|(%2D%2D)|(\-\-)/i' );

// Signature 2 - detects typical SQL injection attack, such as 1'or some_boolean_expression
define( 'PCRE_SQL_TYPICAL', "/\w*(\%27)|'(\s|\+)*((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i" );

//Signature 3 - detects use of union - good guarantee of an attack
define( 'PCRE_SQL_UNION', "/((\%27)|')(\s|\+)*union/i" );

//Signature 4 - detects calling of an MS SQL stored or extended procedures
define( 'PCRE_SQL_STORED', '/exec(\s|\+)+(s|x)p\w+/i' );

// ----- XSS detection -----
// Signature 1 - detects anything sent within angled brackets
define( 'XSS_ANGLED', '/((\%3C)|<)((\%2F)|\/)*[a-z0-9\%]+((\%3E)|>)/i' );

// Signature 2 - detects javascript embedding using the <img src> tag
define( 'XSS_IMG_JS', '/((\%3C)|<)((\%69)|i|(\%49))((\%6D)|m|(\%4D))((\%67)|g|(\%47))[^\n]+((\%3E)|>)/i' );

// ----- Mail injection -----
define( 'MAIL_INJECTION', "/(Content-Transfer-Encoding:|MIME-Version:|content-type:|Subject:|to:|cc:|bcc:|from:|reply-to:)/ims" );

?>