<?php

/**
 * IF YOU HAVE MOVED YOUR FURNACE LIBRARY, OR WISH TO
 * POINT TO A DIFFERENT VERSION, MODIFY 'FURNACE_LIB_PATH'
 * TO POINT TO THE FULL PATH TO YOUR FURNACE INSTALLATION:
 */
define("FURNACE_LIB_PATH", dirname(dirname(__FILE__)));


/*********************************************************
/** ** ** NO CHANGES NECESSARY BELOW THIS LINE ** ** ** **
 *********************************************************/

define("FURNACE_APP_PATH", dirname(__FILE__));

/* INCLUDE REQUIRED FILES */
 require(FURNACE_LIB_PATH . '/lib/furnace/Furnace.class.php');
 require(FURNACE_LIB_PATH . '/lib/furnace/config/FApplicationConfig.class.php');
 require(FURNACE_LIB_PATH . '/lib/furnace/logging/FApplicationLogManager.class.php');
 require(FURNACE_LIB_PATH . '/lib/furnace/request/FApplicationRequest.class.php');
 require(FURNACE_LIB_PATH . '/lib/yaml/spyc-0.4.1.php');
 
 
 require('Log.php');

 /* LOAD GLOBAL FUNCTIONS, STRUCTURES, AND DEFINITIONS */
 include(FURNACE_LIB_PATH . '/etc/globals.inc.php');
 
 /* LOAD APPLICATION CONFIGURATION */
 $config  = new FApplicationConfig(Furnace::yaml(FF_CONFIG_FILE));
 
 /* START UP THE APPLICATION LOG MANAGER */
 $_logmgr = new FApplicationLogManager($config);
 _log()->log("Processing request: {$_SERVER['REQUEST_URI']} from {$_SERVER['REMOTE_ADDR']}");
 
 /* CREATE A REQUEST OBJECT */
 $request = new FApplicationRequest($_SERVER['REQUEST_URI']);

 /* INITIALIZE FURNACE */
 $furnace  = new Furnace($config);
 
 /* PROCESS THE REQUEST OBJECT */
 $furnace->process($request);
 
 /* CLEAN UP */
 unset($furnace);
 
 /* EXIT */
 exit();
?>