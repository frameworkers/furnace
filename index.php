<?php
/**
 *      ___       ___          ___          ___          ___          ___          ___     
 *     /  /\     /__/\        /  /\        /__/\        /  /\        /  /\        /  /\    
 *    /  /:/_    \  \:\      /  /::\       \  \:\      /  /::\      /  /:/       /  /:/_   
 *   /  /:/ /\    \  \:\    /  /:/\:\       \  \:\    /  /:/\:\    /  /:/       /  /:/ /\  
 *  /  /:/ /:/___  \  \:\  /  /:/~/:/   _____\__\:\  /  /:/~/::\  /  /:/  ___  /  /:/ /:/_ 
 * /__/:/ /://__/\  \__\:\/__/:/ /:/___/__/::::::::\/__/:/ /:/\:\/__/:/  /  /\/__/:/ /:/ /\
 * \  \:\/:/ \  \:\ /  /:/\  \:\/:::::/\  \:\~~\~~\/\  \:\/:/__\/\  \:\ /  /:/\  \:\/:/ /:/
 *  \  \::/   \  \:\  /:/  \  \::/~~~~  \  \:\  ~~~  \  \::/      \  \:\  /:/  \  \::/ /:/ 
 *   \  \:\    \  \:\/:/    \  \:\       \  \:\       \  \:\       \  \:\/:/    \  \:\/:/  
 *    \  \:\    \  \::/      \  \:\       \  \:\       \  \:\       \  \::/      \  \::/   
 *     \__\/     \__\/        \__\/        \__\/        \__\/        \__\/        \__\/    
 *    
 *    
 * =========================================================================================
 * Frameworkers.org - Furnace - Lightweight PHP Web Application Development Framework    
 * 
 * Copyright (c) 2008-2011 Frameworkers.org
 * License: Apache Software Licence V2.0 (http://furnace.frameworkers.org/license)    
 * 
 * Author: Andrew F. Hart (andrew@frameworkers.org)
 * 
 * Web: http://furnace.frameworkers.org   
 *    
 ***/

/* =========================================================================================
 * 0. Sanitize the operational environment
 * =========================================================================================
 ***/

// 0.1 Define Constants --------------------------------------------------------------------
define('FURNACE_VERSION'      , '0.4.2');
define('F_REQUEST_START'      , microtime(true));
define('F_START_MEMORY'       , memory_get_usage());
define('F_ENV_PRODUCTION'     , 1000);
define('F_ENV_DEVELOPMENT'    , 2000);
define('F_APP_PATH'           , dirname(__FILE__));
define('F_LIB_PATH'           , F_APP_PATH . '/lib');
define('F_DATA_PATH'          , F_APP_PATH . '/data');
define('F_MODULES_PATH'       , F_APP_PATH . '/modules');
define('F_DEFAULT_MODULE_NAME', 'default');
define('F_DEFAULT_MODULE_NS'  , 'app');
define('F_DEFAULT_MODULE_PATH', F_MODULES_PATH . '/' . F_DEFAULT_MODULE_NAME);
define('F_DEFAULT_LOG_PATH'   , F_DATA_PATH . '/logs/app.log');
define('HTTP_POST'            , 'POST');
define('HTTP_GET'             , 'GET');

define('HTTP_403'             , '403');
define('HTTP_404'             , '404');
define('HTTP_500'             , '500');


// 0.2 Register our own error handler ------------------------------------------------------
//TODO: Register our own error handler


// 0.3 Begin buffering output --------------------------------------------------------------
ob_start();


/* =========================================================================================
 * 1. Bootstrap Furnace
 * =========================================================================================
 ***/

// 1.1 List of aliases used in this file ---------------------------------------------------
use \furnace\utilities\Benchmark;
use \furnace\utilities\Logger;
use \furnace\utilities\LogLevel;
use \furnace\request\Request;
use \furnace\response\ResponseChunk;
use \furnace\routing\Router;
use \furnace\core\Config;
use \furnace\core\Furnace;

// 1.2 Initialize the class autoloader -----------------------------------------------------
require_once(F_LIB_PATH . '/furnace/utilities/AutoLoader.class.php');
$autoloader = \furnace\utilities\AutoLoader::init();


/* =========================================================================================
 * 2. Load the Application
 * =========================================================================================
 ***/

// 2.1 Determine the application's Url Base ------------------------------------------------
$urlbase = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']);
$urlbase = str_replace('/index.php','',$urlbase);
define('F_URL_BASE', $urlbase);

// 2.2 Parse the application configuration settings ----------------------------------------
require(F_APP_PATH . '/config.php');

// 2.3 Special environment preparations depending on 'environment' -------------------------
if (Config::Get('environment') == F_ENV_DEVELOPMENT) {
	
	// Include development utilities
    require(F_LIB_PATH . '/furnace/utilities/development.php');
    
    // Turn on error reporting
    ini_set('display_errors',1);
    
} else {
	
	// Turn off error reporting
	ini_set('display_errors',0);
}


// 2.4 Ensure the data directory is writeable ----------------------------------------------
if (!is_writeable(F_DATA_PATH)) {
    Furnace::halt("Unable to write to the application data directory",
     "Furnace needs write access to the application data directory, currently "
    ."specified by F_DATA_PATH in 'index.php' to be: <br/> "
    ."<code>" . F_DATA_PATH . "</code><br/> Recursively change the permissions on this "
    ."directory so that the user Apache runs as has write permission.");
}

// 2.5 Initialize the logging subsystem ----------------------------------------------------
if (file_exists(Config::Get('env.logging.file')) 
    && !is_writeable(Config::Get('env.logging.file'))) {
    Furnace::halt("Unable to write to the application log file",
     "Furnace needs write access to the application log file, currently "
    ."specified by `env.logging.file in 'config.php' to be: <br/> "
    ."<code>" . Config::Get('env.logging.file') . "</code><br/> Ensure "
    ."that Furnace has permission to write to it.");
}
Logger::Log(LogLevel::DEBUG, "Logger Initialized");
Logger::Log(LogLevel::DEBUG, "Request URI: {$_SERVER['REQUEST_URI']}");


// 2.6 Initialize the session subsystem ----------------------------------------------------
if (Config::Get('use.sessions')) {

    session_start();

    $authProviderClassName = Config::Get('auth.provider');
    $authProviderInstance  = new $authProviderClassName();
    $authProviderInstance->init(Config::Get('auth.options'));

}

/* =========================================================================================
 * 3. Process Request
 * =========================================================================================
 ***/

$response = Furnace::Request($_SERVER['REQUEST_URI']);


/* =========================================================================================
 * 4. Send Response
 * =========================================================================================
 ***/

// 4.1 Stop output buffering
ob_end_clean();

// 4.2 Send the response
// Send any appropriate headers
//echo $response->headers();
// Send the response body
echo $response->body();


/* =========================================================================================
 * 5. Clean up
 * =========================================================================================
 ***/

Furnace::Cleanup();


/* =========================================================================================
 * 6. Globally Available Environment Information Helper Function
 * =========================================================================================
 ***/

function env($key = null,$default = null) {

    $v = Config::Get($key);
    return (null == $v) 
        ? $default
        : $v;
}


/* =========================================================================================
 * 7. Globally Available Rendering Helper Functions
 * =========================================================================================
 ***/

function text($contents) {
    return new ResponseChunk($contents);
}   

function html($contents) {
    return new ResponseChunk($contents);
}

function json($contents) {
    return new ResponseChunk(json_encode($contents));
}

function template(ResponseChunk $chunk) {
    // Using a template engine
    if (Config::Get('template.engine')) {
        $renderEngine = Config::Get('template.engine');
        $renderEngine::init();
        return new ResponseChunk($renderEngine::process($chunk));
    
    // Using straight PHP
    } else {
        ob_start();
        $_data = $chunk->data();
        echo eval('?>' . $chunk->contents());
        $result = ob_get_contents();
        ob_end_clean();
        return new ResponseChunk($result);
    }
}

function success($message) {
    return flash($message,"Success!",'success');
}
function warn($message) {
    return flash($message,"Warning:",'warn');
}
function error($message) {
    return flash($message,"An error has occurred:",'error');
}
function info($message) {
    return flash($message,"For your information:",'info');
}
function flash($message,$title,$cssClasses) {
    return new ResponseChunk(
        "<div class='f_flashMessage {$cssClasses}'><h5>{$title}</h5>{$message}</div>");
}

function img_url($path) {
    return Config::Get('app.themes.url') 
    	   . Config::Get('app.theme') . "/img/{$path}";
}

function js_url($path) {
    return Config::Get('app.themes.url') 
    	   . Config::Get('app.theme') . "/js/{$path}";
}

function css_url($path) {
    return Config::Get('app.themes.url') 
    	   . Config::Get('app.theme') . "/css/{$path}";
}

function href($url) {
    return F_URL_BASE . $url;
}

function input($type,$name,$id='',$data = null,$default = '') {
    switch (strtolower($type)) {
        case 'textarea':
            return '<textarea name="'.$name.'" id="'.$id.'">'.$data.'</textarea>';
        case 'text':
        default:
            return '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$data.'"/>';
    }
}


function load_region($name) {
    return Furnace::Response()->data($name);
}








