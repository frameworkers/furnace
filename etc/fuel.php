<?php
/*
 * frameworkers_furnace
 * 
 * fuel.php
 * Created on Oct 18, 2008
 * 
 * Based on and replaces:
 * AppEntrance.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /* INITIALIZE FURNACE */
 include('Furnace.class.php');
 $furnace = new Furnace(true);
 
 /* PROCESS A REQUEST  */
if (checkFuelLogin()) {
 	$furnace->process($_SERVER['REQUEST_URI']);
} else {
	$furnace->process("/fuel/login");
}
 
 /* CLEAN UP */
 unset($furnace);
 
 /* EXIT */
 exit();
 
 /* GLOBAL UTILITY FUNCTIONS ****************************************************/
 
 function _read_flashes($bReset = true) {
 	if (isset($_SESSION['flashes'])) {
 		$flashes = $_SESSION['flashes'];
 		if ($bReset) {
 			$_SESSION['flashes'] = array();	
 		}
 		return $flashes;
 	} else {
 		return array();
 	}	
 }
 // FUNCTION: _db()
 //  Provides shorthand notation for accessing the database, and also
 //  insulates against API changes that will probably come as a result
 //  of a future need to support additional database connection mechanisms.
 //
 function _db() {
 	if ($GLOBALS['fconfig_debug_level'] > 0) {
 		return FDatabase::singleton($GLOBALS['fconfig_debug_dsn']);
 	} else {
 		return FDatabase::singleton($GLOBALS['fconfig_production_dsn']);
 	}
 }

 
 // FUNCTION: _user()
 //  Provides shorthand notation for accessing the currently logged
 //  in user.
 //
 function _user($failPage = '/') {
 	if (FSessionManager::checkLogin()) {
 		return true;
 	} else {
 		_err('You must be logged in to continue...');
 		header("Location: {$failPage}");
 		exit;
 	}
 }
 
 // FUNCTION: _err()
 function _err($productionMessage,$debugMessage='',$isFatal = false,$handler='unknown') {
 	// display the error to the user
 	// if debugging, display the debug message, if defined, else the production message
 	// if not debugging, display the production message
 	if (0 == $GLOBALS['fconfig_debug_level']) {
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
			'message' => (($GLOBALS['fconfig_debug_level'] > 0) 
					? (('' == $debugMessage)
						? $productionMessage 
						: $debugMessage) 
					: $productionMessage),
			'cssClass'=> 'error',
			'title'   => 'Error:'
		);
 	}
 	// log the error (and email?) if fatal
 	
 	// redirect if fatal
 	if ($isFatal) {
 		_start_request("/_error/{$handler}");
 		exit();
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

 
 function checkFuelLogin() {
 	if (isset($_SESSION['fuel']['loggedin']) && $_SESSION['fuel']['loggedin']) {
 		return true;
 	} else {
 		return false;
 	}
 }
?>