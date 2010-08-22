<?php
/**
 * IF YOU HAVE MOVED YOUR FURNACE LIBRARY, OR WISH TO
 * POINT TO A DIFFERENT VERSION, MODIFY 'FURNACE_LIB_PATH'
 * TO POINT TO THE FULL PATH TO YOUR FURNACE INSTALLATION:
 */
define("FURNACE_LIB_PATH", '/home/andrew/Public/Frameworkers/frameworkers-furnace-trunk/src/lib');


/*********************************************************
/** ** ** NO CHANGES NECESSARY BELOW THIS LINE ** ** ** **
 *********************************************************/

define("FURNACE_APP_PATH", dirname(__FILE__));
define("CLI",              !isset($_SERVER['HTTP_USER_AGENT']));

/* INCLUDE REQUIRED FILES */
require('Log.php');

/* LOAD GLOBAL FUNCTIONS, STRUCTURES, AND DEFINITIONS */
include(FURNACE_LIB_PATH . '/etc/globals.inc.php');
 
/* LOAD APPLICATION CONFIGURATION */
$config  = new FApplicationConfig(Furnace::yaml(FF_CONFIG_FILE));
 
/* START UP THE APPLICATION LOG MANAGER */
$_logmgr = new FApplicationLogManager($config);

/* INITIALIZE FURNACE */
$furnace  = new Furnace($config);
$furnace->loadApplicationModel();
 
/* HANDLE A REQUEST THAT ORIGINATED FROM THE COMMAND LINE */
if (CLI) {
	_log()->log("Processing command line request: " . implode(' ',$argv) );
}
 
/* HANDLE A REQUEST THAT ORIGINATED FROM */
else {
	_log()->log("Processing request: {$_SERVER['REQUEST_URI']} from {$_SERVER['REMOTE_ADDR']}");

	/* CREATE A REQUEST OBJECT */
	$request = new FApplicationRequest($_SERVER['REQUEST_URI']);
	 
	/* PROCESS THE REQUEST OBJECT */
	$furnace->process($request);
 
	/* CLEAN UP */
	unset($furnace);
	
	/* EXIT */
	exit();
}
?>
