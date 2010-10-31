<?php
/**
 * IF YOU HAVE MOVED YOUR FURNACE LIBRARY, OR WISH TO
 * POINT TO A DIFFERENT VERSION, MODIFY 'FURNACE_LIB_PATH'
 * TO POINT TO THE FULL PATH TO YOUR FURNACE INSTALLATION:
 */
define("FURNACE_LIB_PATH", dirname(dirname(__FILE__)) . '/lib');

/*********************************************************
/** ** ** NO CHANGES NECESSARY BELOW THIS LINE ** ** ** **
 *********************************************************/
$GLOBALS['_furnace_stats']['furnace_start'] = microtime(true);
			    	
define("FURNACE_APP_PATH", dirname(__FILE__));
define("CLI",              !isset($_SERVER['HTTP_USER_AGENT']));

/* SET UP NAMESPACES */
use org\frameworkers\furnace         as Furnace;
use org\frameworkers\furnace\config  as Config;
use org\frameworkers\furnace\control as Control;
use org\frameworkers\furnace\logging as Logging;

/* INCLUDE REQUIRED FILES */
require('Log.php');

/* LOAD GLOBAL FUNCTIONS, STRUCTURES, AND DEFINITIONS */
include(FURNACE_LIB_PATH . '/etc/globals.inc.php');
 
/* LOAD APPLICATION CONFIGURATION */
$GLOBALS['_furnace_stats']['furnace_loadConfig_start'] = microtime(true);
$config  = new Config\FApplicationConfig(
	Furnace\Furnace::yaml(FF_CONFIG_FILE));
$GLOBALS['_furnace_stats']['furnace_loadConfig_end'] = microtime(true);
$GLOBALS['_furnace_stats']['furnace_loadConfig_time'] =  $GLOBALS['_furnace_stats']['furnace_loadConfig_end'] - $GLOBALS['_furnace_stats']['furnace_loadConfig_start'];

/* START UP THE APPLICATION LOG MANAGER */
$_logmgr = new Logging\FApplicationLogManager($config);

/* INITIALIZE FURNACE */
$furnace  = new Furnace\Furnace($config);
$furnace->loadApplicationModel();
 
/* HANDLE A REQUEST THAT ORIGINATED FROM THE COMMAND LINE */
if (CLI) {
	_log()->log("Processing command line request: " . implode(' ',$argv) );
}
 
/* HANDLE A REQUEST THAT ORIGINATED FROM */
else {
	_log()->log("Processing request: {$_SERVER['REQUEST_URI']} from {$_SERVER['REMOTE_ADDR']}");

	/* CREATE A REQUEST OBJECT */
	$GLOBALS['_furnace_stats']['furnace_createRequest_start'] = microtime(true);
	$request = new Control\FApplicationRequest($_SERVER['REQUEST_URI']);
	$GLOBALS['_furnace_stats']['furnace_createRequest_end']  = microtime(true);
	$GLOBALS['_furnace_stats']['furnace_createRequest_time'] =  $GLOBALS['_furnace_stats']['furnace_createRequest_end'] - $GLOBALS['_furnace_stats']['furnace_createRequest_start'];
	
	 
	/* PROCESS THE REQUEST OBJECT */
	$furnace->process($request);
	$GLOBALS['_furnace_stats']['furnace_process_end']  = microtime(true);
	$GLOBALS['_furnace_stats']['furnace_process_time'] =  $GLOBALS['_furnace_stats']['furnace_process_end'] - $GLOBALS['_furnace_stats']['furnace_process_start'];
 	$GLOBALS['_furnace_stats']['TOTAL_REQUEST_TIME'] = $GLOBALS['_furnace_stats']['furnace_process_end'] - $GLOBALS['_furnace_stats']['furnace_start'];
	echo "<!-- Furnace (http://furnace.frameworkers.org) rendered this page in {$GLOBALS['_furnace_stats']['TOTAL_REQUEST_TIME']} seconds -->\r\n";
	
	/* CLEAN UP */
	unset($furnace);
	
	/* EXIT */
	exit();
}
?>
