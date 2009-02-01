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
 
 /* ENVIRONMENT SETUP ****************************************************/
 // Create an array to store database queries (for benchmarking).
 $queries = array();

 // Compute the project root directory based on the location of this file.
 $rootdir = dirname(dirname(__FILE__));
 $fueldir = $rootdir . "/lib/fuel/app";

 // Load the application configuration files
 require_once($rootdir . "/app/config/project.config.php");
 require_once($rootdir . "/app/config/database.config.php");
 	
 /* BENCHMARKING SETUP ***************************************************/
 if ($GLOBALS['fconfig_debug_level'] == 2) {
 	$bm_start= microtime(true);
 }
 	
 /* FOUNDATION SETUP *****************************************************/	
 set_include_path(
 	get_include_path() . PATH_SEPARATOR . 
 		$rootdir . '/lib/furnace/foundation');
 require_once('foundation.bootstrap.php');
 
 /* FACADE SETUP *********************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR .
 		$rootdir . '/lib/furnace/facade');
 require_once('facade.bootstrap.php'); 
 
 /* TOOLS SETUP **********************************************************/
 require_once($rootdir . "/lib/yaml/spyc-0.2.5.php5");
 require_once($rootdir . "/lib/yaml/FYamlParser.class.php");
  
 /* CUSTOM CONTROLLER BASE CLASS SETUP ***********************************/
 require_once($fueldir . '/controllers/_base/Controller.class.php');
 
 /* INCLUDE MODEL DATA ***************************************************/
 @include_once($rootdir . "/app/model/objects/compiled.php");
 
 if ($GLOBALS['fconfig_debug_level'] == 2) {
 	$bm_setup_end = microtime(true);
 }
 /* INIT ENTRANCE ********************************************************/
 $entrance = new Entrance( $fueldir."/controllers","_default" );
 
 /* INIT REQUEST *********************************************************/
 if ($GLOBALS['fconfig_debug_level'] == 2) {
 	$bm_requests = array();
 	$bm_current_request = null;
 }
 
 _start_request($_SERVER['REQUEST_URI']);
 
 if ($GLOBALS['fconfig_debug_level'] == 2) {
 	$bm_end = microtime(true);
 }
 
 /* BENCHMARKING REPORTING ***********************************************/
 if ($GLOBALS['fconfig_debug_level'] == 2) {
 	$setupTime   = $bm_setup_end - $bm_start;
 	echo "Page loaded in " . ($bm_end - $bm_start) . " seconds\r\n";
 	echo "\r\n<table class=\"ff_benchmark\">\r\n";
 	echo "\r\n<caption>".count($GLOBALS['queries']) ." Queries:</caption>\r\n";
 	$queryTime = 0;
 	foreach ($GLOBALS['queries'] as $q) {
 		echo "<tr><td>{$q['sql']}</td><td>{$q['delay']}</td></tr>\r\n";	
 		$queryTime += $q['delay'];
 	}		
 	echo "</table>\r\n";
 	echo "Total Setup Time: {$setupTime}<br/>\r\n";
 	foreach ($GLOBALS['bm_requests'] as $r) {
 		echo "--- {$r['uri']} : " . ($r['stop'] - $r['start']) . "<br/>\r\n";	
 	}
 	echo "-- Total Query Delay: {$queryTime}<br/>\r\n";
 }
 exit();
 
 /* GLOBAL UTILITY FUNCTIONS ****************************************************/
 function _start_request($request_uri) {
 	global $rootdir;
 	global $fueldir;
 	
 	/* BENCHMARKING ******************************************************/
 	if ($GLOBALS['fconfig_debug_level'] == 2) {
 		$bm_request_start = microtime(true);
 		if (is_array($GLOBALS['bm_current_request'])) {
 			$GLOBALS['bm_current_request']['stop'] = $bm_request_start;
 			$GLOBALS['bm_requests'][] = $GLOBALS['bm_current_request'];
 		}
 		$GLOBALS['bm_current_request'] = array('uri'=>$request_uri,'start'=>$bm_request_start,'stop'=>0);
 	}

	/* PROCESS REQUEST AND STORE CONTENTS ********************************/
	if ($GLOBALS['entrance']->validRequest($request_uri)) {
		$contents = $GLOBALS['entrance']->dispatchRequest();
	} else {
		die("Invalid request made.");
	}

	/* CREATE LAYOUT OBJECT, RENDER AND SEND CONTENT *********************/
	$layout     = $GLOBALS['entrance']->getController()->getLayout();
	$layoutPath = $fueldir."/layouts/{$layout}.html";
	if (file_exists($layoutPath)) {
		$pageLayout = new FPageLayout($layoutPath); 	
		$pageLayout->register('PageTitleFromView',  $GLOBALS['entrance']->getController()->getTitle());
		$pageLayout->register('JavascriptsFromView',$GLOBALS['entrance']->getController()->getJavascripts());
		$pageLayout->register('StylesheetsFromView',$GLOBALS['entrance']->getController()->getStylesheets());
		$pageLayout->register('MessagesFromView', _read_flashes());
		$pageLayout->register('ContentFromView',$contents);

		$pageLayout->render();
	} else {
		die("unknown layout '{$layout}' specified."); 	
	}
	
	/* BENCHMARKING ******************************************************/
	if ($GLOBALS['fconfig_debug_level'] == 2) {
 		$bm_request_stop = microtime(true);
 		if (is_array($GLOBALS['bm_current_request'])) {
 			$GLOBALS['bm_current_request']['stop'] = $bm_request_stop;
 			$GLOBALS['bm_requests'][] = $GLOBALS['bm_current_request'];
 		}
 		$GLOBALS['bm_current_request'] = null;
 	}
 }
 
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

?>