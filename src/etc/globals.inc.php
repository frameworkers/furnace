<?php
/* GLOBAL UTILITY FUNCTIONS  ************************************************/

 
 // FUNCTION: _db()
 //  Provides shorthand notation for accessing the database, and also
 //  insulates against API changes that will probably come as a result
 //  of a future need to support additional database connection mechanisms.
 //
 function _db($which='default') {
    $datasources = array("production"=>array(),"debug"=>array());
    
    if (_furnace()->config['debug_level'] > 0) {
 	    if (!isset($datasources['debug'][$which])) {
 	        $datasources['debug'][$which] = new FDatabase(_furnace()->config['datasources']['debug'][$which]);
 	    } 
 	    
 	    return $datasources['debug'][$which];
 	    
 	} else {
 		if (!isset($datasources['production'][$which])) {
 	        $datasources['production'][$which] = new FDatabase(_furnace()->config['datasources']['production'][$which]);
 	    } 
 	    
 	    return $datasources['production'][$which];
 	}
 }
 
 // FUNCTION: _user()
 //  Provides shorthand notation for accessing the currently logged
 //  in user.
 //
 function _user($failPage = '/') {
 	return $user = FSessionManager::checkLogin();
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
function _model() {
	return $GLOBALS['fApplicationModel'];
}
 
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
?>