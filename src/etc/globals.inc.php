<?php

/* GLOBAL DEFINITIONS *******************************************************/
define(FF_ROOT_DIR,    dirname(dirname(__FILE__)));
define(FF_CONFIG_FILE, FF_ROOT_DIR . '/app/config/app.yml');
define(FF_LIB_DIR,     FF_ROOT_DIR . '/lib');
define(FF_LOG_DIR,     FF_ROOT_DIR . '/app/data/logs');

/* GLOBAL DATA STRUCTURES ***************************************************/

 $_datasources = array("production"=>array(),"debug"=>array());
 $_logmgr      = false;    // Initialized in {FF_ROOT_DIR}/etc/app.php


/* GLOBAL UTILITY FUNCTIONS  ************************************************/

 
 // FUNCTION: _db()
 //  Provides shorthand notation for accessing the database, and also
 //  insulates against API changes that will probably come as a result
 //  of a future need to support additional database connection mechanisms.
 // 

 
 function _db($which='default') {
    global $_datasources;
 
    if (_furnace()->config['debug_level'] > 0) {
 	    if (!isset($_datasources['debug'][$which])) {
 	        $_datasources['debug'][$which] = new FDatabase(_furnace()->config['datasources']['debug'][$which]);
 	    } 
 	    
 	    return $_datasources['debug'][$which];
 	    
 	} else {
 		if (!isset($_datasources['production'][$which])) {
 	        $_datasources['production'][$which] = new FDatabase(_furnace()->config['datasources']['production'][$which]);
 	    } 
 	    
 	    return $_datasources['production'][$which];
 	}
 }
 
 // FUNCTION: _model()
 //  Provides shorthand notation for accessing the application's 
 //  data model.
 function _model() {
     return $GLOBALS['_model'];
 }
 
 // FUNCTION: _user()
 //  Provides shorthand notation for accessing the currently logged
 //  in user.
 //
 function _user() {
 	return FSessionManager::checkLogin();
 }
 
 // FUNCTION: _err()
 function _err($productionMessage,$debugMessage='',$isFatal = false,$handler='unknown') {
 	// display the error to the user
 	// if debugging, display the debug message, if defined, else the production message
 	// if not debugging, display the production message
 	if (0 == _furnace()->config['debug_level']) {
 		// Production Mode
 		if ('' != $productionMessage) {
 			// Display the production message
 			$_SESSION['flashes'][] = array(
				'message' => $productionMessage,
				'cssClass'=> 'error',
				'title'   => 'Error:'
			);	
 		}
 	} else {
 		// Debug Mode
		$_SESSION['flashes'][] = array(
			'message' => ((_furnace()->config['debug_level'] > 0) 
					? (('' == $debugMessage)
						? $productionMessage 
						: $debugMessage) 
					: $productionMessage),
			'cssClass'=> 'error',
			'title'   => 'Error:'
		);
 	}
 	// log the error (and email?) if fatal
 	
 	
 	if ($isFatal) {
 		die("FATAL ERROR");
 	}
 }
 
 // FUNCTION: _account()
 //  Provides shorthand notation for accessing the currently logged in
 //  user's account information (un,pw,etc.)
 //
 function _account($failPage = '/') {
 	if (false !== ($user = FSessionManager::checkLogin())) {
 		return $user->getFAccount();
 	} else {
 		header("Location: {$failPage}");
 		exit;
 	}
 }
 
// FUNCTION: _furnace()
//  Provides shorthand notation for accessing the furnace object.
 
function _furnace() {
 	return $GLOBALS['furnace'];	
}

// FUNCTION: _model()
//  Provides shorthand notation for accessing the application model
//function _model() {
//	return $GLOBALS['fApplicationModel'];
//}
 
// FUNCTION _storeUserInput($data)
//   Stores the requested $data in the session variable _userform
function _storeUserInput($data) {
	unset($_SESSION['_userform']);
	$_SESSION['_userform'] = $data;
}
	
// FUNCTION _readUserInput($field)
//   Reads the $field variable from the session variable _userform
function _readUserInput($field) {
    $data = isset($_SESSION['_userform'][$field])
        ? $_SESSION['_userform'][$field]
        : null;
    if ($data) {
        unset($_SESSION['_userform'][$field]);
    }
	return $data;
}

// FUNCTION _log(which)
//   Returns the appropriate logger
function _log($which = 'default') {
    global $_logmgr;
    return $_logmgr->getLog($which);
}


define(FF_INFO,true);
define(FF_DEBUG,true);	
define(FF_WARN,true);
define(FF_ERR,true);

$dev_messages = array();

function info($msg) {
	global $dev_messages;
	$dev_messages[] =  sprintf("%.5f I: %s ",microtime(true),$msg);
}
function debug($msg) {
	global $dev_messages;
	$dev_messages[] =  sprintf("%.5f D: %s ",microtime(true),$msg);
}
function warn($msg) {
	global $dev_messages;
	$dev_messages[] =  sprintf("%.5f W: %s ",microtime(true),$msg);
}
function err($msg) {
	global $dev_messages;
	$dev_messages[] =  sprintf("%.5f E: %s ",microtime(true),$msg);
}
function dump($var) {
	global $dev_messages;
	$dev_messages[] =  sprintf("%.5f D: ",microtime(true)).print_r($var,true);
}
function dev_messages() {
    global $dev_messages;
    echo "<ul><li>" . implode('</li><li>',$dev_messages) .'</li></ul>';
}

?>