<?php

/**
 * Securimage loader for wblib
 */


if ( ! class_exists('wbFormBuilderCaptcha',false) ) {
    include dirname(__FILE__).'/class.wbFormBuilderCaptcha.php';
}

$session_name = NULL;
if ( isset( $_GET['SN'] ) ) {
	$session_name = $_GET['SN'];
}
if ( ! $session_name && is_array($_COOKIE) ) {
	// this works for LEPTON and WB
	foreach( $_COOKIE as $key => $value ) {
	    if ( preg_match( '~session~i', $key ) ) {
	        $session_name = $key;
		}
	}
}

$securimage = new wbFormBuilderCaptcha( $session_name );
$securimage->showCaptcha();