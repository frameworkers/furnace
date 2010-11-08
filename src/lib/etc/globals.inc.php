<?php
/* FURNACE VERSION INFORMATION **********************************************/

define('FF_VERSION',     '0.3');

/* GLOBAL DEFINITIONS *******************************************************/
define('FF_ROOT_DIR',    FURNACE_LIB_PATH);
define('FF_CONFIG_FILE', FURNACE_APP_PATH . '/config/app.yml');
define('FF_LIB_DIR',     FURNACE_LIB_PATH);
define('FF_THEME_DIR',   FURNACE_APP_PATH . "/themes");

// Logging Constants (requires the PEAR Log package)
// http://pear.php.net/package/Log/
define('FF_DEBUG',       PEAR_LOG_DEBUG);
define('FF_INFO',        PEAR_LOG_INFO);
define('FF_NOTICE',      PEAR_LOG_NOTICE);
define('FF_WARNING',     PEAR_LOG_WARNING);
define('FF_ERROR',       PEAR_LOG_ERR);
define('FF_CRIT',        PEAR_LOG_CRIT);
define('FF_ALERT',       PEAR_LOG_ALERT);
define('FF_EMERG',       PEAR_LOG_EMERG);


/* CLASS AUTOLOADING ********************************************************/
require(FURNACE_LIB_PATH . 
	'/utilities/classes/org/frameworkers/furnace/AutoLoader.class.php');
FurnaceAutoLoader::init();

/* NAMESPACE SHORTCUTS ******************************************************/
use org\frameworkers\furnace\control as Control;
use org\frameworkers\furnace\auth as Auth;
use org\frameworkers\furnace\core\models\User;

/* GLOBAL DATA STRUCTURES ***************************************************/

$_datasources = array("production"=>array(),"debug"=>array());
$_logmgr      = false; // Initialized in [FF_ROOT_DIR]/etc/app.php


/* GLOBAL UTILITY FUNCTIONS  ************************************************/

/**
 * _db
 * 
 * Global function that provides shorthand notation for accessing a datasource 
 * driver and also insulates against API changes that come as a result
 * of a future need to support additional database connection mechanisms.
 * 
 * @param  string $which  The identifier (from app.yml) of the database to use
 * @return FDatasourceDriver An FDatasourceDriver object that can interact with
 *         the requested datasource
 *         
 * @global 
 */
function _db($which='default') {
    global $_datasources;
    
    // Determine whether to use the debug or production datasources
    $debugLevel = (_furnace()->config->debug_level > 0)
        ? 'debug'
        : 'production';
    
    try {
	    // If this is the first access, load the appropriate driver and init. it  
	    if (!isset($_datasources[$debugLevel][$which])) {
	        $driverClass = _furnace()->config->data['datasources'][$debugLevel][$which]['driver'];
	        $options     = _furnace()->config->data['datasources'][$debugLevel][$which]['options'];
	        
	        $_datasources[$debugLevel][$which] = new $driverClass();
	        $_datasources[$debugLevel][$which]->init($options);  
	    }
	    
	    return $_datasources[$debugLevel][$which];
    } catch (Exception $e) {
    	//TODO: Log this error
    	return false;
    }
}
 
/**
 * _model()
 * 
 * Global function that provides shorthand notation for accessing the
 * application's data model
 * 
 * @return ApplicationModel   The application's information model
 * @global
 */
function _model() {
    return $GLOBALS['_model'];
}

function _data($type) {
	if (is_numeric($type)) {
		$sql   = "SELECT * FROM `nodes` WHERE `nid`='{$type}' LIMIT 1";
		$row   = _db()->rawQuery($sql);
		$data  = $row->fetch();
		$class = substr($data['type'],strrpos($data['type'],'\\') + 1);
		return  _data($class)->unique($type);
	} else {
		return \ApplicationModel::data($type);
	}
}

 /**
  * _user
  * 
  * Provides shorthand notation for accessing the currently logged
  * in user
  * 
  * @return  FAccount  The FAccount-derived object representing the user
  * @global
  */
 function _user($id = false) {
 	if ($id) {
 		return User::Search()->unique($id);	
 	} else {
 		return Auth\Authentication::checkLogin();
 	}
 }
 
 
 /**
  * _err
  * 
  * Global function that provides a shorthand notation for sending messages
  * to the built-in 'flashes' (notices) section of the page layout.
  * 
  * @param string $productionMessage  The message to display in production
  * @param string $debugMessage       The (optional) message to display while debugging
  * @param $isFatal                   Whether or not the error prevents further action
  * @return nothing
  */
 function _err($productionMessage,$debugMessage='',$isFatal = false) {
 	// display the error to the user
 	// if debugging, display the debug message, if defined, else the production message
 	// if not debugging, display the production message
 	if (0 == _furnace()->config->debug_level) {
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
			'message' => (('' == $debugMessage)    // debug msg defined?
				? $productionMessage 
				: $debugMessage),
			'cssClass'=> 'error',
			'title'   => 'Error:'
		);
 	}
 	// log the error (and email?) if fatal
 	if ($isFatal) {
 	    $msg = ('' == $debugMessage) 
 	        ? $productionMessage
 	        : $debugMessage;
 	    _log()->log($msg,FF_ERROR);
 		die("FATAL ERROR");
 	}
 }
 
/**
 * _furnace
 * 
 * Global function that provides shorthand notation for accessing the
 * furnace object
 * 
 * @return Furnace   The global furnace object
 * @global
 */
function _furnace() {
 	return $GLOBALS['furnace'];	
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

// FUNCTION _log(which)
//   Returns the appropriate logger
function _log($which = 'default') {
    global $_logmgr;
    return $_logmgr->getLog($which);
}

