<?php

define("REQUEST_START",microtime(true));     // Request statistics

/*
 * The Front controller for this application
 */
// Furnace library location
define("FURNACE_LIB_PATH",dirname(dirname(__FILE__))
	."/lib/src/furnace");
	
// Furnace application location
define("FURNACE_APP_PATH",dirname(__FILE__));

// Initialize the Furnace Auto-loader
require_once(FURNACE_LIB_PATH . 
	'/org/frameworkers/furnace/utilities/AutoLoader.class.php');
AutoLoader::init();

// Furnace global application object
function app() {
	static $furnace;
	
	if (null == $furnace) {
		$furnace = new \org\frameworkers\furnace\Furnace();
	}
	
	return $furnace;
}

// Load the application configuration
require(FURNACE_APP_PATH . "/config/application.config.php");

// Load the application authentication configuration
include(FURNACE_APP_PATH . "/config/authentication.config.php");

// Load the application connection configuration
include(FURNACE_APP_PATH . "/config/connections.config.php");

// Load the application route definitions
include(FURNACE_APP_PATH . "/config/routes.config.php");

// Load the application content-type definitions
include(FURNACE_APP_PATH . "/config/types.config.php");

// Determine Application URL base
$urlbase = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']);
$urlbase = str_replace('/index.php','',$urlbase);

org\frameworkers\furnace\config\Config::Set('applicationUrlBase', '/' . $urlbase);

// Handle the request
app()->processRequest($_SERVER['REQUEST_URI']);

