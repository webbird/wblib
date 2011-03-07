<?php

/* SEQ_LIB : Security library
 * Version 0.7.1; 07.09.2009
 *
 * Main Configuration is in a separate file.
 *
 * This software is open-source. License: GNUv3
 * Autor: Erich Kachel; info@erich-kachel.de
 * http://www.erich-kachel.de/seq_lib
 * http://code.google.com/p/sseq-lib/
*/

$_SEQ_DEBUG = 0; /* 1: show; 0: hide */

restore_error_handler();

/**
 * Error reporting is disabled to avoid informative output.
 */
if ($_SEQ_DEBUG || $_SEQ_ERRORS) {
    #error_reporting(E_USER_ERROR | E_USER_WARNING);
    error_reporting(E_ALL^E_NOTICE);
    set_error_handler('seq_error_handler_');
} else {
    error_reporting(0);
}

define('_SEQ_TOKEN_NAME','_sseqtoken');

SEQ_APP_SALT_();

/**
 * @private
 * Generates unique SALT value to be used with all MD5 hashes.
 * Salt is valid until salt file is removed (normally never)
 */
function SEQ_APP_SALT_() {
    global $_SEQ_BASEDIR;
    if (!file_exists($_SEQ_BASEDIR . 'seq_lib/app_salt.php')) {
        $application_salt = "<?php\n" . 'define("_SEQ_APP_SALT", "' . md5(uniqid(rand(), TRUE)) . '");' . "\n?>";
        $saltfile = $_SEQ_BASEDIR . "seq_lib/app_salt.php";
        $fh = fopen($saltfile, 'w');
        fwrite($fh, $application_salt);
        fclose($fh);
    }
    if (file_exists($_SEQ_BASEDIR . 'seq_lib/app_salt.php')) {
        include_once($_SEQ_BASEDIR . 'seq_lib/app_salt.php');
    } else {
        // SALT could not be created! WEAK SECURITY! USE default salt
        define("_SEQ_APP_SALT", "2b0b29725c9e1c40b5d8882632bccd4a");
    }
}

/**
 * @private
 * Main function to handle session lifetime and security.
 */
function SEQ_HANDLE_SESSION_() {
    global $_SEQ_SECURE_SESSION;
    
    if (!$_SEQ_SECURE_SESSION) { return false; }

    SEQ_SECURE_SESSION();
}

/**
 * Sets additional security to session data and session cookie.
 * Has to be called after the application fully initiates its session.
*/
function SEQ_SECURE_SESSION() {
    global $_SEQ_SESSIONLIFETIME, $_SEQ_SESSIONABSOLUTELIFETIME,
           $_SEQ_SESSIONREFRESH, $_SEQ_SESSION_HEADERSCHECK, $_SEQ_SECURE_SESSION,
           $_SEQ_SECURE_COOKIES, $HTTP_SESSION_VARS, $_SEQ_SESSION_NAME;
           
    if (!$_SEQ_SECURE_SESSION) { return false; }

    if (!isset($_SESSION) && !isset($HTTP_SESSION_VARS)) {
        seq_log_('SEQ_SECURE_SESSION: no SESSION found at execution time. Call SEQ_SECURE_SESSION after session start.', '');
        return false;
    }
    
    $seq_sessid = session_id();
    
    // get session data // what if these two differ?!
    $SESSIONDATA = '';
    if (ini_get('register_long_arrays') && isset($HTTP_SESSION_VARS)) {
        $SESSIONDATA = $HTTP_SESSION_VARS;
    } else {
        $SESSIONDATA = $_SESSION;
    }
    
    if (!isset($SESSIONDATA['SEQ'])) {
        $SESSIONDATA['SEQ'] = array();
    }

    if (!isset($SESSIONDATA['SEQ']['session_touchtime'])) {
        $session_data = $SESSIONDATA;
        if ($_SEQ_SECURE_COOKIES) {
            if (function_exists('ini_set')) {
                ini_set('session.cookie_lifetime', $_SEQ_SESSIONLIFETIME);
                ini_set('session.cookie_httponly', true);
            }
            if (function_exists('session_set_cookie_params')) {
                $cookie_data_ = session_get_cookie_params();
                session_set_cookie_params($_SEQ_SESSIONLIFETIME,
                                          $cookie_data_['path'],
                                          $cookie_data_['domain'],
                                          $cookie_data_['secure'], true);
            }
        }          
        session_regenerate_id(true);

        $SESSIONDATA = $session_data;
        $SESSIONDATA['SEQ']['session_touchtime'] = time();
        $SESSIONDATA['SEQ']['session_creationtime'] = time();

        if ($_SEQ_SESSION_HEADERSCHECK) {
            $SESSIONDATA['SEQ']['agent_key'] = seq_useragent_fingerprint_();
        }
       
    } else if ($_SEQ_SESSIONREFRESH == 0 || isset($SESSIONDATA['SEQ']['session_touchtime'])) {

        if (isset($SESSIONDATA['SEQ']['session_creationtime']) &&
           (time() - $SESSIONDATA['SEQ']['session_creationtime']) > $_SEQ_SESSIONABSOLUTELIFETIME)
        {
            seq_log_('SESSION TERMINATED: absolute sessionlifetime expired', '');
            SEQ_TERMINATE_SESSION_();
        }

        if (isset($SESSIONDATA['SEQ']['agent_key'])) {
            if ($SESSIONDATA['SEQ']['agent_key'] != seq_useragent_fingerprint_()) {
                seq_log_('SESSION TERMINATED: AGENT FINGERPRINT CHANGED', '');
                SEQ_TERMINATE_SESSION_();
            }
        }
        
        $session_age = time() - $SESSIONDATA['SEQ']['session_touchtime'];
        if ($_SEQ_SESSIONREFRESH == 0 || $session_age > $_SEQ_SESSIONREFRESH) {
            $session_data = $SESSIONDATA;

            if (!headers_sent()) {
                session_regenerate_id(true);
            }
            $SESSIONDATA = $session_data;
        }
    }
 
    $SESSIONDATA['SEQ']['session_touchtime'] = time();
    
    if (ini_get('register_long_arrays') && isset($HTTP_SESSION_VARS)) {
        $HTTP_SESSION_VARS = $SESSIONDATA;
    }
    
    $_SESSION = $SESSIONDATA;
    
}

/**
 * @private
 * Terminates current session and unsets all session content.
 */
function SEQ_TERMINATE_SESSION_($redir_exit = true) {
    global $_SEQ_ONERROR_REDIRECT_TO, $_SEQ_SECURE_COOKIES, $HTTP_COOKIE_VARS, $_SEQ_SESSION_NAME, $_SEQ_SESSIONLIFETIME;
    
    $seq_sessname = $_SEQ_SESSION_NAME ? $_SEQ_SESSION_NAME : session_name();
    
    // expire cookie
    if ($_SEQ_SECURE_COOKIES && ($_COOKIE || $HTTP_COOKIE_VARS)
        && isset($_COOKIE[$seq_sessname]) && !headers_sent())
    {
        // could we be too early to know 'path' or 'domain' settings?
        $cookie_data_ = session_get_cookie_params();
        setcookie($seq_sessname, '', time()-$_SEQ_SESSIONLIFETIME, $cookie_data_['path'], $cookie_data_['domain']);
        
        if (isset($_SESSION)) {
            $_COOKIE = array();
        }
        if (isset($HTTP_COOKIE_VARS)) {
            $HTTP_COOKIE_VARS = array();
        }
    }
    
    // unset session variables
    if (isset($_SESSION)) {
        $_SESSION = array();
    }
    if (isset($HTTP_SESSION_VARS)) {
        $HTTP_SESSION_VARS = array();
    }
    session_unset();
    //session_write_close();
    
    if ($redir_exit) {
        // redirect to location OR 
        seq_terminate_('redirect');
        die;
    }
}

/**
 * Checks a Token against CSRF-Attacks.
 * Gets Token out of GET/POST-request and checks for validity.
 * If specific name given, Token will only be valid for that named action.
 */
function SEQ_CHECK_TOKEN($originname_ = '') {
    global $_SEQ_TOKENLIFETIME, $HTTP_SESSION_VARS;

    $tokenname = SEQ_CREATE_TOKEN_NAME_($originname_);

    $tokenArray = $_SESSION['SEQ']['SEQ_TOKEN'];
    
    if (!isset($tokenArray) || !is_array($tokenArray)) {
        seq_log_('SEQ_CHECK_TOKEN: no SESSION found at execution time. Call SEQ_CHECK_TOKEN after session start.', '');
        return false;
    }
    
    $tokenvalue = _QB_HTTPVARS2ARRAY($tokenname, 'pg');
    
    if (strlen($tokenvalue) == 32) {

        if (isset($tokenArray[$tokenname]) && isset($tokenArray[$tokenname]['token']) &&
            $tokenArray[$tokenname]['token'] == $tokenvalue)
        {
            
            $token_age = time() - $tokenArray[$tokenname]['time'];
            if ($token_age > $_SEQ_TOKENLIFETIME) {
                seq_debug_($token_age . ">" . $_SEQ_TOKENLIFETIME);
                seq_log_('SEQ_CHECK_TOKEN: CSRF token expired', $token_age - $_SEQ_TOKENLIFETIME);
                SEQ_TERMINATE_SESSION_();                
            }
            
            if ($tokenArray[$tokenname]['once']) {
                unset($_SESSION['SEQ']['SEQ_TOKEN'][$tokenname]); // no replay
            }

            // SESSION OK
            
        } else {
            seq_log_('SEQ_CHECK_TOKEN: wrong CSRF token', '');
            SEQ_TERMINATE_SESSION_();
        }
    } else {
        seq_log_('SEQ_CHECK_TOKEN: CSRF token required', $tokenvalue);
        SEQ_TERMINATE_SESSION_();
    }
}

/**
 * @private
 * Generates Token value.
 */
function SEQ_CREATE_TOKEN_VALUE_($originname_ = '', $once_ = false) {
    global $_SEQ_TOKENLIFETIME, $HTTP_SESSION_VARS;

    $tokenname = SEQ_CREATE_TOKEN_NAME_($originname_);

    if (!isset($_SESSION['SEQ'])) {
        $_SESSION['SEQ'] = array();
        $_SESSION['SEQ']['SEQ_TOKEN'] = array();
    }

    if (!isset($_SESSION['SEQ']['SEQ_TOKEN'][$tokenname])) {
        $token = md5(uniqid(rand(), true));        
        $_SESSION['SEQ']['SEQ_TOKEN'][$tokenname] = array('token' => $token, 'time' => time(), 'once' => $once_ ? true : false);
    } else {
        // set single use token
        $_SESSION['SEQ']['SEQ_TOKEN'][$tokenname]['once'] = $once_ ? true : false;
        $token = $_SESSION['SEQ']['SEQ_TOKEN'][$tokenname]['token'];
    }
 
    return $token;
}

/**
 * @private
 * Generates Token name.
 */
function SEQ_CREATE_TOKEN_NAME_($originname_ = '') {
    global $_SEQ_TOKENLIFETIME, $_SEQ_SESSION_HEADERSCHECK;

    $header_hash = '';
    if ($_SEQ_SESSION_HEADERSCHECK) {
        $header_hash = seq_useragent_fingerprint_();
    }
    $originname = $originname_ ? md5($originname_ . $header_hash . session_id() . _SEQ_APP_SALT) : md5($header_hash . session_id() . _SEQ_APP_SALT);
    $tokenname = 'SEQ_TOKEN_' . $originname;

    return $tokenname;
}

/**
 * Generate a Token against CSRF-Attacks.
 * Generates a Token to be inserted into a Form.
 * If specific name given, Token will only be valid for that named action.
 */
function SEQ_FTOKEN($formname_ = '', $once_ = false) {
    return '<input type="hidden" name="' . SEQ_CREATE_TOKEN_NAME_($formname_) .
    '" value="' . SEQ_CREATE_TOKEN_VALUE_($formname_, $once_) . '" />' . "\n";
}

/**
 * Generate a Token against CSRF-Attacks.
 * Generates a Token to be inserted into a Link.
 * If specific name given, Token will only be valid for that named action.
 */
function SEQ_LTOKEN($linkname_ = '', $once_ = false) {
    return SEQ_CREATE_TOKEN_NAME_($linkname_) . '=' . SEQ_CREATE_TOKEN_VALUE_($linkname_, $once_);
}

/**
 * @private
 * Generates Useragent fingerprint
 */
function seq_useragent_fingerprint_() {
    /* With IE 6.0 HTTP_ACCEPT changes between requests. Not usefull! */
    $fingerprint = $_SERVER['HTTP_USER_AGENT']._SEQ_APP_SALT;
    seq_debug_($fingerprint);
    return md5($fingerprint);
}

/**
 * @private
 * tries to detect whether input was already backslashed or not.
 * Removes slashes only if input was backslashed.
 */
function seq_remove_slashes_($string_ = '') {
    $orig = $string_;
    $stripped = stripslashes($orig);
    if ($orig != $stripped) {
        $escaped = addslashes($stripped);
        if ($orig == $escaped) {
            $sec_value = stripslashes($escaped);
        } else {
            $sec_value = $orig;
        }
    } else {
        $sec_value = $orig;
    }
    return $sec_value;
}

function uniord__($c) {
    $h = ord($c{0});
    if ($h <= 0x7F) {
        return $h;
    } else if ($h < 0xC2) {
        return false;
    } else if ($h <= 0xDF) {
        return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
    } else if ($h <= 0xEF) {
        return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
                                 | (ord($c{2}) & 0x3F);
    } else if ($h <= 0xF4) {
        return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
                                 | (ord($c{2}) & 0x3F) << 6
                                 | (ord($c{3}) & 0x3F);
    } else {
        return false;
    }
}

/**
 * Secures input string against XSS-attacks.
 * Return value can be send to browser securely.
 * supports single & multi byte UTF-8
 */
function SEQ_OUTPUT($string_ = '') {
    $string = mb_convert_encoding($string_, "UTF-8", "7bit, UTF-7, UTF-8, UTF-16, ISO-8859-1, ASCII");
    $string = seq_remove_slashes_($string);
    seq_check_intrusion_($string);

    $output = '';

    for ($i = 0; $i < mb_strlen($string); $i++)  {
        if (preg_match('/([a-zA-Z0-9_.-])/', $string[$i])) {
            $output .= $string[$i];
            continue;
        }
        $byte = ord($string[$i]);
        if ($byte <= 127)  {
            $length = 1;
            $output .= sprintf("&#x%04s;", dechex(uniord__(mb_substr($string, $i, $length))));
        } else if ($byte >= 194 && $byte <= 223)  {
            $length = 2;
            $output .= sprintf("&#x%04s;", dechex(uniord__(mb_substr($string, $i, $length))));
        } else if ($byte >= 224 && $byte <= 239)  {
            $length = 3;
            $output .= sprintf("&#x%04s;", dechex(uniord__(mb_substr($string, $i, $length))));
        } else if ($byte >= 240 && $byte <= 244)  {
            $length = 4;
            $output .= sprintf("&#x%04s;", dechex(uniord__(mb_substr($string, $i, $length))));
        }
    }

    return $output;
}

function seq_debug_($string_ = '') {
    global $_SEQ_DEBUG;
    if ($_SEQ_DEBUG) {
        echo "<br>------" .  $string_ . "<br>";
    }
}

/**
 * @Public
 * Check string type
 * returns empty string if type or length dont match
 * returns input string if all OK
 */
function SEQ_TYPE($string_ = '', $type_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '' /*for logging*/, $source_ = ' SRC'/*for logging*/) {
    return seq_check_type_($string_, $type_, $minvalue_, $maxvalue_, $varname_, $source_);
}

/**
 * @Private
 * Check string type
 * returns empty string if type or length dont match
 * returns input string if all OK
 */
function seq_check_type_($string_ = '', $type_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '' /*for logging*/, $source_ = ''/*for logging*/) {
    $string = $string_;
    seq_check_intrusion_($string, $source_);

    switch(strtoupper(trim($type_))) {
    case 'NUM' :
    case 'INT' :
        if (!SEQ_ISNUM($string, $minvalue_, $maxvalue_, $varname_, $source_)) {
            return '';
        }
        break;
    case 'STR' :
        if (!SEQ_ISSTR($string, $minvalue_, $maxvalue_, $varname_, $source_)) {
            return '';
        }
        break;
    default:
        if (!SEQ_ISBETWEEN($string, $minvalue_, $maxvalue_, $varname_, $source_)) {
            return '';
        }
        break;
    }
    return $string;
}

/**
 * @public
 * Prepares input for usage within MYSQL-query
 * Type, min-max Length
 */
function SEQ_MYSQL($string_ = '', $type_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '', $source_ = '') {

    $string_ = seq_remove_slashes_($string_);

    $orig = $string_;

    seq_check_intrusion_($orig, $source_);

    if ($type_ != '' && $orig != '') {
        $orig = seq_check_type_($orig, $type_, $minvalue_, $maxvalue_, $varname_, $source_);
    }
    
    /* automatically choose best function to escape input */
    if (!(mysql_error())) {
        $P_ESCAPE_FUNC = create_function('$match_','return mysql_real_escape_string($match_);');
        $sec_value = $P_ESCAPE_FUNC($orig);
    }
    /* fallback if mysql is not available yet */
    if (mysql_error()) {
        $P_ESCAPE_FUNC = create_function('$match_','return mysql_escape_string($match_);');
        $sec_value = $P_ESCAPE_FUNC($orig);
    }
    
    seq_debug_($sec_value);
    return $sec_value;
}

/**
 * Input must be a number and between given values
 * 
 */
function SEQ_ISNUM($string_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '', $source_ = '') {
    seq_check_intrusion_($string_, $source_);

    $minvallist = split(',', $minvalue_);
    if (strlen($string_) == 0) {
        for ($t=0; $t < count($minvallist); $t++) {
            if (strtoupper(trim($minvallist[$t])) == 'NULL') {
                // if zero value allowed, then ok
                return true;
            }
        }
    }
    
    $typ_numeric = is_numeric($string_);
    if ($typ_numeric) {
        for ($t=0; $t < count($minvallist); $t++) {
            $minvalue = trim($minvallist[$t]);
            if (isset($minvalue) && $minvalue != '' && strtoupper($minvalue) != 'NULL' && $string_ < $minvalue) {
                seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': INT below MIN (' . $minvalue . ')', $string_, $source_);
                seq_reaction_(true /* from filter */);
                return false;
            }
        }
        $maxvalue_ = trim($maxvalue_);
        if (isset($maxvalue_) && $maxvalue_ != '' && $string_ > $maxvalue_) {
            seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': INT beneath MAX (' . $maxvalue_ . ')', $string_, $source_);
            seq_reaction_(true /* from filter */);
            return false;
        }
        return true;
    }
    seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': INT param not INT', $string_, $source_);
    seq_reaction_(true /* from filter */);
    return false;
}

/**
 * Input must be a string and between given values
 * 
 */
function SEQ_ISSTR($string_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '', $source_ = '') {
    seq_check_intrusion_($string_, $source_);

    $typ_string = is_string($string_);
    if ($typ_string) {
        $minvalue_ = trim($minvalue_);
        if (isset($minvalue_) && $minvalue_ != '' && strlen($string_) < $minvalue_) {
            seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': STR length below MIN (' . $minvalue_ . ')', $string_, $source_);
            seq_reaction_(true /* from filter */);
            return false;
        }
        $maxvalue_ = trim($maxvalue_);
        if (isset($maxvalue_) && $maxvalue_ != '' && strlen($string_) > $maxvalue_) {
            seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': STR length beneath MAX (' . $maxvalue_ . ')', $string_, $source_);
            seq_reaction_(true /* from filter */);
            return false;
        }
        return true;
    }
    seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': STR Param not STRING', $string_, $source_);
    seq_reaction_(true /* from filter */);
    return false;
}

/**
 * Length of input must be between given values
 * 
 */
function SEQ_ISBETWEEN($string_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '', $source_ = '') {
    $minvalue_ = trim($minvalue_);
    if (isset($minvalue_) && $minvalue_ != '' && strlen($string_) < $minvalue_) {
        seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': length below MIN (' . $minvalue_ . ')', $string_, $source_);
        seq_reaction_(true /* from filter */);
        return false;
    }
    $maxvalue_ = trim($maxvalue_);
    if (isset($maxvalue_) && $maxvalue_ != '' && strlen($string_) > $maxvalue_) {
        seq_log_(($varname_ ? $varname_ : 'UNKNOWN VAR') . ': length beneath MAX (' . $maxvalue_ . ')', $string_, $source_);
        seq_reaction_(true /* from filter */);
        return false;
    }
    return true;
}

/**
 * Apply urldecode on input until all occurences are decoded.
 * Handles multiple encoded inputs
 */
function SEQ_URLDECODE($string_ = '') {
    $unescaped = mb_convert_encoding($string_, "UTF-8", "auto");
    while(urldecode($unescaped) != $unescaped) {
        $unescaped = urldecode($unescaped);
    }
    return $unescaped;
}

/**
 * Tries to make sure, the file path is local.
 */
function SEQ_LOCFILE($path_ = '') {
    $path = SEQ_URLDECODE($path_);
    $path = realpath($path);
    $path_check = preg_replace('/\\\/', '/', strtolower($path));
    $docpath_check = preg_replace('/\\\/', '/', strtolower($_SERVER['DOCUMENT_ROOT']));
    seq_debug_($path_check . '###' . $docpath_check);
    if ($path && strpos($path_check, $docpath_check) !== 0) {
        seq_log_('SEQ_LOCFILE: Path not in BASEPATH', $path_check);
        seq_reaction_();
        $path = '';
    } else if (empty($path)) {
        seq_log_('SEQ_LOCFILE: Path not local or damaged', $path_);
        seq_reaction_();
    }

    return $path;
}

/**
 * Error output with XSS-prevention.
 * Can be turned off globally to supress informative errors.
 */
function SEQ_ERROR($string_ = '') {
    global $_SEQ_ERRORS;
    if ($_SEQ_ERRORS) {
        echo SEQ_OUTPUT($string_);
    }
    seq_log_('SEQ_ERROR: ', $string_);
}

function seq_error_handler_($code_ = '', $msg_ = '', $file_ = '', $line_ = '') {
    switch ($code_) {
    case E_ERROR:
        seq_log_('Script Error', "line: $line_ script: $file_ error: $code_ reason: $msg_");
        break;
    case E_WARNING:
        seq_log_('Script Warning', "line: $line_ script: $file_ error: $code_ reason: $msg_");
        break;
    case E_NOTICE:
        seq_log_('Script Notice', "line: $line_ script: $file_ error: $code_ reason: $msg_");
        break;
    default:
        break;
    }
    if( function_exists( 'xdebug_get_function_stack' ) ) {
        xdebug_get_function_stack();
    }
}

/**
 * Logfile output
 */
function seq_log_($message_ = '', $testName_ = '', $source_ = '') {
    global $_SEQ_BASEDIR, $_SEQ_LOG;

    if ($_SEQ_LOG) {
        $rootdir = $_SEQ_BASEDIR;
        $logfile = fopen($rootdir . "seq_log/log.txt","a");
        fputs($logfile, date("d.m.Y, H:i:s",time()) .
              ", " . $_SERVER['REMOTE_ADDR'] .
              ", [" . $source_ . "]" .
              ", " . $message_ .
              ", " . $testName_ .
              ", " . $_SERVER['REQUEST_METHOD'] .
              ", " . $_SERVER['PHP_SELF'] .
              ", " . $_SERVER['HTTP_USER_AGENT'] .
              ", " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') .
              "\n");
        fclose($logfile);
    }
}

/**
 * Terminates script execution
 */
function seq_terminate_($reason_ = '') {
    global $_SEQ_ONERROR_REDIRECT_TO;
    
    // better to redirect in any case? it is less informative!
    switch ($reason_) {
    case 'err':
        echo "<b>Undefined action.</b>";
        die;
        break;
    case 'redirect':
        if (!headers_sent() && !empty($_SEQ_ONERROR_REDIRECT_TO)) {
            header("Location: " . $_SEQ_ONERROR_REDIRECT_TO);
        } else {
            echo "<b>Undefined action.</b>";
        }
        die;
        break;
    default:
        echo "<b>Illegal action.</b>";
        die;
    }
}

/**
 * Executes defined reaction on detected security breach.
 */
function seq_reaction_($filter_ = false) {
    global $_SEQ_IDS_ONATTACK_ACTION, $_SEQ_FILTER_NOMATCH_ACTION, $_SEQ_SESSION_NAME;
    
    $action = $_SEQ_IDS_ONATTACK_ACTION;
    
    // call is comming from filter check
    if ($filter_) {
        $action = $_SEQ_FILTER_NOMATCH_ACTION;
    }
    
    $action_array = split(' ', $action);
    if (in_array('delay', $action_array)) {
        sleep(50);
    }
    if (in_array('logout', $action_array)) {
        SEQ_TERMINATE_SESSION_();
    }
    if (in_array('redirect', $action_array)) {
        if (!headers_sent() && !empty($_SEQ_ONERROR_REDIRECT_TO)) {              
            $save_session = '';
            
            // if known and found in query string, keep session id when redirect
            if ($_SERVER['QUERY_STRING']) {
                $seq_sessname = $_SEQ_SESSION_NAME ? $_SEQ_SESSION_NAME : session_name();
                $querypairs = split('&', $_SERVER['QUERY_STRING']);
                for ($t=0; $t < length($querypairs); $t++) {
                    $pairs = split('=', $querypairs[$t]);
                    if ($pairs[0] == $seq_sessname) {
                        $save_session = join($querypairs[$t]);
                    }
                }
            }
            
            header("Location: " . $_SEQ_ONERROR_REDIRECT_TO . '?' . $save_session);
        }
    }
    // do not stop script execution here. it may be a minor violation and maybe
    // there was no redirect before.
}

function seq_intrusion_sql_($string_ = '', $source_ = '') {
    $scan_value = $string_;
    $matches = false;
    /* scan for SQL-attack pattern
       http://niiconsulting.com/innovation/snortsignatures.html
    */
    if (preg_match("/(\%27)|(\')|(\')|(%2D%2D)|(\/\*)/i", $scan_value) || /*(\-\-)  deleted. no meaning for MySQL*/
                                                                 /* (\/\*) added. Comment sign for MySQL */
        preg_match("/\w*(\%27)|'(\s|\+)*((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i", $scan_value) ||
        preg_match("/((\%27)|')(\s|\+)*union/i", $scan_value)) {
        seq_log_('SQL Injection detected', $scan_value, $source_);
        $matches = true;
    }    
}

/**
 * Helper for "globals overwrite" scan
 *
 * @param string $string_
 * @param string $source_
 * @return boolean
 */ 
function seq_globals_overwrite_($string_ = '', $source_ = '') {
    $matches = false;
    $s_globalvars = array('_SERVER',
                          'HTTP_SERVER_VARS',
                          '_ENV',
                          'HTTP_ENV_VARS',
                          '_COOKIE',
                          'HTTP_COOKIE_VARS',
                          '_GET',
                          'HTTP_GET_VARS',
                          '_POST',
                          'HTTP_POST_VARS',
                          '_FILES',
                          'HTTP_POST_FILES',
                          '_REQUEST',
                          '_SESSION',
                          'HTTP_SESSION_VARS',
                          'GLOBALS');    

    /*
    security vulneration!
    http://www.securityfocus.com/archive/1/462263/30/0/threaded
    */
    if (preg_match("/^(" . implode("|", $s_globalvars) . ")/", $string_, $match_)) {
        seq_log_('Global VAR overwrite detected', $string_, $source_);
        $matches = true;
    }
    return $matches;
}

/**
 * Helper for Intrusion Detection System
 */
function seq_check_intrusion_($string_ = '', $source_ = '') {
    global $_SEQ_IDS, $_SEQ_IDS_ONATTACK_ACTION;

    if (!$_SEQ_IDS) { return false; }
    
    /* array scan is later required */
    if(is_array($string_)) {return false;}
    $scan_value = $string_;

    $matches = false;
    /* scan for SQL-attack pattern
       http://niiconsulting.com/innovation/snortsignatures.html
    */
    if (preg_match("/(\%27)|(\')|(\')|(%2D%2D)|(\/\*)/i", $scan_value) || /*(\-\-)  deleted. no meaning for MySQL*/
                                                                 /* (\/\*) added. Comment sign for MySQL */
        preg_match("/\w*(\%27)|'(\s|\+)*((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i", $scan_value) ||
        preg_match("/((\%27)|')(\s|\+)*union/i", $scan_value))
    {
        seq_log_('SQL Injection detected', $scan_value, $source_);
        $matches = true;
    }

    /* scan for XSS-attack pattern
       http://niiconsulting.com/innovation/snortsignatures.html
    */
    if (preg_match("/((\%3C)|<)((\%2F)|\/)*[a-z0-9\%]+((\%3E)|>)/i", $scan_value) ||
        preg_match("/((\%3C)|<)((\%69)|i|(\%49))((\%6D)|m|(\%4D))((\%67)|g|(\%47))[^\n]+((\%3E)|>)/i", $scan_value))
    {
        seq_log_('XSS detected', $scan_value, $source_);
        $matches = true;
    }

    /* scan for Mail-Header-attack pattern
    */
    if (preg_match("/(Content-Transfer-Encoding:|MIME-Version:|content-type:|Subject:|to:|cc:|bcc:|from:|reply-to:)/ims", $scan_value))
    {
        seq_log_('Mail-Header Injection detected', $scan_value, $source_);
        $matches = true;
    }

    /* scan for "Special chars" pattern
    */
    if (preg_match("/%0A|\\r|%0D|\\n|%00|\\0|%09|\\t|%01|%02|%03|%04|%05|%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13/i", $scan_value))
    {
        seq_log_('Special Chars detected', $scan_value, $source_);
        $matches = true;
    }

    $matches = seq_globals_overwrite_($scan_value, $source_);

    if ($matches) {
        seq_reaction_();      
    }

    return $matches;
}

/**
 * Generates data dump of incomming data.
 * Output is to be analysed to design an appropriate SANITIZE - filter
 */
function SEQ_DATADUMP() {
    global $_SEQ_BASEDIR, $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_SESSION_VARS;
    
    $datafile = $_SEQ_BASEDIR . "seq_dump/app_data.txt";
    //if (file_exists($datafile)) {
        
        if (isset($_GET)) {
            foreach($_GET as $param=>$value) {
                $appdata .= '[_GET] ' . $param . '=' . $value . "\n";
            }
        }

        if (isset($HTTP_GET_VARS)) {
            foreach($HTTP_GET_VARS as $param=>$value) {
                $appdata .= '[HGET] ' . $param . '=' . $value . "\n";
            }
        }
       
        if (isset($_POST)) {
            foreach($_POST as $param=>$value) {
                $appdata .= '[_POS] ' . $param . '=' . $value . "\n";
            }
        }

        if (isset($HTTP_POST_VARS)) {
            foreach($HTTP_POST_VARS as $param=>$value) {
                $appdata .= '[HPOS] ' . $param . '=' . $value . "\n";
            }
        }
    
        if (isset($_COOKIE)) {
            foreach($_COOKIE as $param=>$value) {
                $appdata .= '[_COO] ' . $param . '=' . $value . "\n";
            }
        }           

        if (isset($HTTP_COOKIE_VARS)) {
            foreach($HTTP_COOKIE_VARS as $param=>$value) {
                $appdata .= '[HCOO] ' . $param . '=' . $value . "\n";
            }
        }

        if (isset($_SESSION)) {
            foreach($_SESSION as $param=>$value) {
                $appdata .= '[_SES] ' . $param . '=' . $value . "\n";
            }
        }
    
        if (isset($HTTP_SESSION_VARS)) {
            foreach($HTTP_SESSION_VARS as $param=>$value) {
                $appdata .= '[HSES] ' . $param . '=' . $value . "\n";
            }
        }        
                           
        if (isset($GLOBALS)) {
            foreach($GLOBALS as $param=>$value) {
                $appdata .= '[ GLO] ' . $param . '=' . (is_object($value) ? 'ARRAY' : $value) . "\n";
            }
        }
        $appdata .= "====================================================================================================\n";
        $fh = fopen($datafile, 'a');
        if ($fh) {
            fwrite($fh, $appdata);
            fclose($fh);
        }
    //}
     
}

/**
 * Helper for SANITIZE
 */
function sanitize_var_($string_ = '', $type_ = '', $minvalue_ = null, $maxvalue_ = null, $varname_ = '' /*for logging*/, $source_ = '' /*for logging*/, $sql_ = false, $xss_ = false) {

    $return = seq_check_type_($string_, $type_, $minvalue_, $maxvalue_, $varname_, $source_);
    
    if ($sql_) {
        $return = SEQ_MYSQL($return, $type_, $minvalue_, $maxvalue_, $varname_, $source_);
    }
    if ($xss_) {
        $return = SEQ_OUTPUT($return);
    }
    
    return $return;
}

/**
 * handles single or _array_ field values
 */
function sanitize_block_($value_ = '', $actions_ = array(), $varname_ = '' /*for logging*/, $source_ = '' /*for logging*/, $sql_ = false, $xss_ = false) {
    if (is_array($value_)) {
        $fieldvalue = $value_;
    } else {
        // create fake array to handle both inputs the same
        $fieldvalue = array($value_);
    }
    $returnValue = null;
    
    // here all input is an array
   foreach ($fieldvalue as $r => $value) {
        $sanitizedvalue = sanitize_var_($value, strtoupper(trim($actions_['type'])), $actions_['min'], $actions_['max'], $varname_, $source_, $sql_, $xss_);
        
        if (is_array($value_)) {
            // restore arrays
            $returnValue[$r] = $sanitizedvalue;
        } else {
            // restore nonarrays
            $returnValue = $sanitizedvalue;
        }                          
    }
    
    return $returnValue;
}

/**
 * SANITIZE checks variables in common global locations to match a defined type
 * Non matching variables are rewritten with an empty string.
 * Filter can be loaded from a file.
 */
function SEQ_SANITIZE($sanitizeList_ = '', $isFile_ = false) {
    global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS;

    SEQ_CHECK_GLOBALS_OVERWRITE_();
    
    if ($isFile_) {
        if (file_exists($sanitizeList_)) {
            $sanitizeList_ = file_get_contents($sanitizeList_, 'r');
        }
        if (!$sanitizeList_) {
            /* could not load file */
            seq_log_('SANITIZE: Could not load file. No filter definition available!', '', '');
        }
        
        $sanitizeList_ = preg_replace('/\/\/.*/', '', $sanitizeList_);
        $sanitizeList_ = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', '', $sanitizeList_);
        $sanitizeList_ = preg_replace('/\s{2,}/', ' ', $sanitizeList_);
    }
    
    $hash = array();
    if (!is_array($sanitizeList_)) {
        $lines = split('&', $sanitizeList_);
        for($l=0; $l < count($lines); $l++) {
            $line = trim($lines[$l]);        
            $params = split('#', $line);
            for($p=0; $p < count($params); $p++) {
                $params[$p] = trim($params[$p]);
            }
            if (!$params[0] || $params[0] == '') {
                continue;
            }
            $hash[$params[0]] = array('source'=>$params[1],
                                     'type'=>$params[2],
                                     'min'=>$params[3],
                                     'max'=>$params[4],
                                     'xss'=>$params[5] == 'true' ? $params[5] : 'false',
                                     'sql'=>$params[6] == 'true'? $params[6] : 'false');
        }
        $sanitizeList_ = $hash;
    }

    if ($sanitizeList_) {
        foreach($sanitizeList_ as $varname=>$actions) {

            if (!$varname) { continue; }
            $paramSource_ = $actions['source'];

            $xss = ($actions['xss'] == 'true') ? true : false;
            $sql = ($actions['sql'] == 'true') ? true : false;
            
            if (!$paramSource_) {$paramSource_ = ini_get('variables_order');}

            $source = $paramSource_;

            if (preg_match('/g/i', $source)) {
                if (isset($_GET) && isset($_GET[$varname])) {
                    $_GET[$varname] = sanitize_block_($_GET[$varname], $actions, $varname, '_GET', $sql, $xss);
                }              
                if (ini_get('register_long_arrays') && isset($HTTP_GET_VARS) && isset($HTTP_GET_VARS[$varname])) {
                    $HTTP_GET_VARS[$varname] = sanitize_block_($HTTP_GET_VARS[$varname], $actions, $varname, 'HGET', $sql, $xss);
                }
            }

            if (preg_match('/p/i', $source)) {                  
                if (isset($_POST) && isset($_POST[$varname])) {
                    $_POST[$varname] = sanitize_block_($_POST[$varname], $actions, $varname, '_POS', $sql, $xss);                  
                }
                if (ini_get('register_long_arrays') && isset($HTTP_POST_VARS) && isset($HTTP_POST_VARS[$varname])) {
                    $HTTP_POST_VARS[$varname] = sanitize_block_($HTTP_POST_VARS[$varname], $actions, $varname, 'HPOS', $sql, $xss);
                }
            }

            if (preg_match('/c/i', $source)) {
                if (isset($_COOKIE) && isset($_COOKIE[$varname])) {
                    $_COOKIE[$varname] = sanitize_block_($_COOKIE[$varname], $actions, $varname, '_COO', $sql, $xss);                  
                }                  
                if (ini_get('register_long_arrays') && isset($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS[$varname])) {
                    $HTTP_COOKIE_VARS[$varname] = sanitize_block_($HTTP_COOKIE_VARS[$varname], $actions, $varname, 'HCOO', $sql, $xss);                    
                }
            }

            if (preg_match('/s/i', $source) && isset($_SESSION)) {
                if (isset($_SESSION) && isset($_SESSION[$varname])) {
                    $_SESSION[$varname] = sanitize_block_($_SESSION[$varname], $actions, $varname, '_SES', $sql, $xss);
                }
                if (ini_get('register_long_arrays') && isset($HTTP_SESSION_VARS) && isset($HTTP_SESSION_VARS[$varname])) {
                    $HTTP_SESSION_VARS[$varname] = sanitize_block_($HTTP_SESSION_VARS[$varname], $actions, $varname, 'HSES', $sql, $xss);
                }
            }
            
            if (isset($_REQUEST) && isset($_REQUEST[$varname])) {
                $_REQUEST[$varname] = sanitize_block_($_REQUEST[$varname], $actions, $varname, '_REQ', $sql, $xss);
            }
            
            if (isset($GLOBALS) && isset($GLOBALS[$varname])) {
                $GLOBALS[$varname] = sanitize_block_($GLOBALS[$varname], $actions, $varname, ' GLO', $sql, $xss);
            }
            
            /* _ENV, _SERVER, _FILES are not checked yet. Should they? */
        }
    }
}

/**
 * Implemented light Intrusion Detection System
 */
function SEQ_CHECK_GLOBALS_OVERWRITE_($paramSource_ = '') {
    global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_REQUEST_VARS;

    $matches = false;

    if (!$paramSource_) {$paramSource_ = ini_get('variables_order');} /* what about: request_order ?*/
    $method = '';

    for($t=0; $t < strlen($paramSource_); $t++) {
        $method = $paramSource_[$t];

        $sec_value = '';

        if (strtolower($method) == 'g') {
            foreach($_GET as $name=>$value) {
                if(seq_globals_overwrite_($name, '_GET')) {
                    unset($_GET[$name]);
                }
            }
            if (ini_get('register_long_arrays') && isset($HTTP_GET_VARS)) {
                foreach($HTTP_GET_VARS as $name=>$value) {
                    if(seq_globals_overwrite_($name, 'HGET')) {
                        unset($HTTP_GET_VARS[$name]);
                    }
                }
            }
        }

        if (strtolower($method) == 'p') {
            foreach($_POST as $name=>$value) {
                if(seq_globals_overwrite_($name, '_POS')) {
                    unset($_POST[$name]);
                }
            }
            if (ini_get('register_long_arrays') && isset($HTTP_POST_VARS)) {
                foreach($HTTP_POST_VARS as $name=>$value) {
                    if(seq_globals_overwrite_($name, 'HPOS')) {
                        unset($HTTP_POST_VARS[$name]);
                    }
                }
            }
        }

        if (strtolower($method) == 'c') {
            foreach($_COOKIE as $name=>$value) {
                if(seq_globals_overwrite_($name, '_COO')) {
                    unset($_COOKIE[$name]);
                }
            }
            if (ini_get('register_long_arrays') && isset($HTTP_COOKIE_VARS)) {
                foreach($HTTP_COOKIE_VARS as $name=>$value) {
                    if(seq_globals_overwrite_($name, 'HCOO')) {
                        unset($HTTP_COOKIE_VARS[$name]);
                    }
                }
            }
        }
    }

    if (isset($_SESSION)) {
        foreach($_SESSION as $name=>$value) {
            if(seq_globals_overwrite_($name, '_SES')) {
                unset($_SESSION[$name]);
            }
        }
        if (ini_get('register_long_arrays') && isset($HTTP_SESSION_VARS)) {
            foreach($HTTP_SESSION_VARS as $name=>$value) {
                if(seq_globals_overwrite_($name, 'HSES')) {
                    unset($HTTP_SESSION_VARS[$name]);
                }
            }
        }
    }

    if (isset($_REQUEST)) {
        foreach($_REQUEST as $name=>$value) {
            if(seq_globals_overwrite_($name, '_REQ')) {
                unset($_REQUEST[$name]);
            }
        }
    }
    
    /* $GLOBALS cannot be checked because it contains all globals names! */
    /* _ENV, _SERVER, _FILES are not checked yet. Should they? */
}

/**
 * Simulates prepared statements for older MYSQL
 * Replaces placeholder with variables after checking for type and length
 */
class SEQ_SQL_SANITIZE {
    var $query = '';
    function SEQ_SQL_SANITIZE($query_ = '') {
        $this->query = $query_;
    }
    /* > 5.2.1*/
    function __toString() {
        return $this->query;
    }
    /* ALL VERSIONS */
    function READY() {
        return $this->query;
    }
    function INSERT($key_ = '', $var_ = '', $type_ = 'STR', $minvalue_ = null, $maxvalue_ = null) {
        $key = ltrim($key_, ':');
        $var = $var_;
        $var = SEQ_MYSQL($var, strtoupper(trim($type_)), $minvalue_, $maxvalue_);
        if (strtoupper(trim($type_)) == 'STR') {
            $var = "'" . $var . "'";
        }
        $this->query = preg_replace('/:' . preg_quote($key) . '/', $var, $this->query);
    }
}

/**
 * Checks variables to avoid Mail Header Injection
 * Set second param to "false" when checking mail body elsewere all line breaks
 * and carriage returns will be deleted.
 */
function SEQ_EMAIL($param_ = '', $lbcr_ = true) {
    seq_check_intrusion_($param_);

    /* replace until done */
    while ($param_ != $filtered || !isset($filtered)) {
        if (isset($filtered)) {
            $param_ = $filtered;
        }
        $filtered = preg_replace("/(Content-Transfer-Encoding:|MIME-Version:|content-type:|" .
                           "Subject:|to:|cc:|bcc:|from:|reply-to:)/ims", "", $param_);
    }
    unset($filtered);
    
    if ($lbcr_) {
        /* replace until done */
        while ($param_ != $filtered || !isset($filtered)) {
            if (isset($filtered)) {
                $param_ = $filtered;
            }        
            $filtered = preg_replace("/(%0A|\\\\r|%0D|\\\\n|%00|\\\\0|%09|\\\\t|%01|%02|%03|%04|%05|" .
                                   "%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13)/ims", "", $param_);
        }
    }
    return $param_;
}

/**
 * Checks variables to avoid HTTP Header Injection
 */
function SEQ_HEADER($param_ = '') {
    seq_check_intrusion_($param_);

    /* replace until done */
    while ($param_ != $filtered || !isset($filtered)) {
        if (isset($filtered)) {
            $param_ = $filtered;
        }
        $filtered = preg_replace("/(%0A|\\\\r|%0D|\\\\n|%00|\\\\0|%09|\\\\t|%01|%02|%03|%04|%05|" .
                           "%06|%07|%08|%09|%0B|%0C|%0E|%0F|%10|%11|%12|%13)/ims", "", $param_);
    }
    return $param_;
}

function _QB_SPECIAL_PARAM_DELIMITER() {
    /*
      osCommerce, Open Source E-Commerce Solutions
      http://www.oscommerce.com
    
      Copyright (c) 2003 osCommerce
    
      Released under the GNU General Public License
    */
    
    // set the HTTP GET parameters manually if search_engine_friendly_urls is enabled
    $params = array();
    if (strlen(getenv('PATH_INFO')) > 1) {
      $GET_array = array();
      $PHP_SELF = str_replace(getenv('PATH_INFO'), '', $PHP_SELF);
      $vars = explode('/', substr(getenv('PATH_INFO'), 1));
      for ($i=0, $n=sizeof($vars); $i<$n; $i++) {
        if (strpos($vars[$i], '[]')) {
          $GET_array[substr($vars[$i], 0, -2)][] = $vars[$i+1];
        } else {
          $params[$vars[$i]] = $vars[$i+1];
        }
        $i++;
      }

      if (sizeof($GET_array) > 0) {
        while (list($key, $value) = each($GET_array)) {
          $params[$key] = $value;
        }
      }
    }

    return $params;
}

function _QB_HTTPVARS2ARRAY(
    $var_ = '',       /* eine explizite variable abfragen */
    $selection_ = 'ps'  /* p - nur POST /// g - nur GET /// s - nur SESSION*/
    ) {

    global $_QB_VERBOSE;

    $data = null;
    if ($var_) {
        if (ini_get('register_long_arrays')) {
            if (isset($HTTP_POST_VARS) && array_key_exists($var_, $HTTP_POST_VARS) && (strpos(strtolower($selection_), 'p') > -1 || !$selection_)) {
                $data = $HTTP_POST_VARS[$var_];
            } else if (isset($HTTP_GET_VARS) && array_key_exists($var_, $HTTP_GET_VARS) && (strpos(strtolower($selection_), 'g') > -1 || !$selection_)) {
                $data = $HTTP_GET_VARS[$var_];
            } else if (isset($HTTP_SESSION_VARS) && array_key_exists($var_, $HTTP_SESSION_VARS) && (strpos(strtolower($selection_), 's') > -1 || !$selection_)) {
                $data = $HTTP_SESSION_VARS[$var_];
            }
        }
        
        if (!isset($data)) {
            if (array_key_exists($var_, $_POST) && (strpos(strtolower($selection_), 'p') > -1 || !$selection_)) {
                $data = $_POST[$var_];
            } else if (array_key_exists($var_, $_GET) && (strpos(strtolower($selection_), 'g') > -1 || !$selection_)) {
                $data = $_GET[$var_];
            } else if ($_SESSION && array_key_exists($var_, $_SESSION) && (strpos(strtolower($selection_), 's') > -1 || !$selection_)) {
                $data = $_SESSION[$var_];
            }
        }
        
        if (!isset($data) && function_exists('_QB_SPECIAL_PARAM_DELIMITER') && array_key_exists($var_, _QB_SPECIAL_PARAM_DELIMITER())) {
            $data = _QB_SPECIAL_PARAM_DELIMITER();
            $data = $data[$var_];
        }
    } else {
        //$data = array();
        if (ini_get('register_long_arrays')) {
            if(isset($HTTP_SESSION_VARS) && (strpos(strtolower($selection_), 's') > -1 || !$selection_)) {
                $data = $HTTP_SESSION_VARS;
            }
            if(isset($HTTP_GET_VARS) && (strpos(strtolower($selection_), 'g') > -1 || !$selection_)) {
                $data = $HTTP_GET_VARS;
            }
            if(isset($HTTP_POST_VARS) && (strpos(strtolower($selection_), 'p') > -1 || !$selection_)) {
                $data = $HTTP_POST_VARS;
            }          
        }
        
        if (isset($data)) {
            if(isset($_SESSION) && (strpos(strtolower($selection_), 's') > -1 || !$selection_)) {
                $data = $_SESSION;
            }
            if(isset($_GET) && (strpos(strtolower($selection_), 'g') > -1 || !$selection_)) {
                $data = $_GET;
            }
            if(isset($_POST) && (strpos(strtolower($selection_), 'p') > -1 || !$selection_)) {
                $data = $_POST;
            }
        }
        
        if (!isset($data) && function_exists('_QB_SPECIAL_PARAM_DELIMITER')) {
            $data = _QB_SPECIAL_PARAM_DELIMITER();
        }        
    }

    return $data;
}
?>
