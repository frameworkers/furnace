<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage utilities
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
 
namespace furnace\utilities;

class AutoLoader {

    public static $loader;

    /**
     * Initalize the static autoloader
     * 
     */
    public static function init() {
	    if (self::$loader == NULL) {
		    self::$loader = new self();
	    }
	    return self::$loader;
    }

    /**
     * Constructor
     * 
     * @access  protected
     */
    protected function __construct() {
	
	    // Put framework classes on the include path
	    set_include_path(F_LIB_PATH
		    . PATH_SEPARATOR
		    . get_include_path());
	
	    // Register autoload features
	    spl_autoload_extensions('.class.php');
	    spl_autoload_register(array($this,'framework'));
        spl_autoload_register(array($this,'modules'));
    }

    /**
     * Provide autoload for Furnace framework classes
     * 
     * @param string $class The candidate class name
     */
    protected function framework($class) {
	    // swap '\' for '/' to handle linux paths. This should
	    // eventually be made conditonal upon detecting a 
	    // linux install.
	    $class = str_replace('\\','/',$class);
	
	    $path = F_LIB_PATH ."/{$class}.class.php";
	
	    if (file_exists($path)) {
		    require_once($path);
	    }
    }

    /**
     * Provide autoload for application module classes and models
     * 
     * @param string $class The candidate class name
     */
    protected function modules($class) {
	    // swap '\' for '/' to handle linux paths. This should
	    // eventually be made conditonal upon detecting a 
	    // linux install.
	    $class = str_replace('\\','/',$class);
	
	    $path = F_APP_PATH ."/modules/{$class}.class.php";
	
	    if (file_exists($path)) {
		    require_once($path);
	    }
    }
}
