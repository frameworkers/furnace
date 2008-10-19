<?php
/*
 * frameworkers_furnace
 * 
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
 $rootdir = dirname(dirname(dirname(__FILE__)));
 // Include the /app directory in the path
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. $rootdir . "/app");
 // Require the configuration files
 require_once("config/project.config.php");
 require_once("config/database.config.php");
 	
 /* BENCHMARKING SETUP ***************************************************/
 if (FProject::DEBUG_LEVEL == 2) {
 	$bm_start= microtime(true);
 }
 	
 /* FOUNDATION SETUP *****************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. $rootdir.'/furnace/foundation');
 require_once('foundation.bootstrap.php');
 
 /* FACADE SETUP *********************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. $rootdir.'/furnace/facade');
 require_once('facade.bootstrap.php'); 
  
 /* CUSTOM CONTROLLER BASE CLASS SETUP ***********************************/
 require_once('../../app/controllers/_base/Controller.class.php');
 
 /* INCLUDE MODEL DATA ***************************************************/
 @include_once("../../model/objects/compiled.php");
 
 if (FProject::DEBUG_LEVEL == 2) {
 	$bm_setup_end = microtime(true);
 }
 /* INIT ENTRANCE ********************************************************/
 $entrance = new Entrance(
 	$rootdir."/app/controllers","_default");
 
 /* INIT REQUEST *********************************************************/
 if (FProject::DEBUG_LEVEL == 2) {
 	$bm_requests = array();
 	$bm_current_request = null;
 }
 
 _start_request($_SERVER['REQUEST_URI']);
 
 if (FProject::DEBUG_LEVEL == 2) {
 	$bm_end = microtime(true);
 }
 
 /* BENCHMARKING REPORTING ***********************************************/
 if (FProject::DEBUG_LEVEL == 2) {
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
 	
 	/* BENCHMARKING ******************************************************/
 	if (FProject::DEBUG_LEVEL == 2) {
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
	$layoutPath = $rootdir."/app/layouts/{$layout}.html";
	if (file_exists($layoutPath)) {
		$pageLayout = new FPageLayout($layoutPath); 	
		$pageLayout->register('PageTitleFromView',  $GLOBALS['entrance']->getController()->getTitle());
		$pageLayout->register('JavascriptsFromView',$GLOBALS['entrance']->getController()->getJavascripts());
		$pageLayout->register('StylesheetsFromView',$GLOBALS['entrance']->getController()->getStylesheets());
		$pageLayout->register('MessagesFromView', _read_flashes());
		$pageLayout->register('ContentFromView',$contents);
		// Google Analytics Support
		if (FProject::DEBUG_LEVEL == 0 && '' != FProject::GOOGLE_ANALYTICS_CODE && '' != FProject::GOOGLE_ANALYTICS_SITE_BASE) {
			$pageLayout->register('doGoogleAnalytics',true);
			$pageLayout->register('googleAnalyticsCode',FProject::GOOGLE_ANALYTICS_CODE);
			$pageLayout->register('googleAnalyticsSiteBase',FProject::GOOGLE_ANALYTICS_SITE_BASE);
		}
		$pageLayout->render();
	} else {
		die("unknown layout '{$layout}' specified."); 	
	}
	
	/* BENCHMARKING ******************************************************/
	if (FProject::DEBUG_LEVEL == 2) {
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
 	return FDatabase::singleton(FDatabaseConfig::$DSN);
 }
?>