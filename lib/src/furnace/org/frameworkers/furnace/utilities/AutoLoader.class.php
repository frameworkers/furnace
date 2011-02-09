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
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
/**
 * AutoLoader implements autoloading features offered by PHP 5.3+
 * 
 * @author  Andrew Hart <andrew.hart@frameworkers.org>
 * @version SVN: $Id$
 */
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
		set_include_path(get_include_path()
			. PATH_SEPARATOR
			. FURNACE_LIB_PATH);
		
		// Register autoload features
		spl_autoload_extensions('.class.php');
		spl_autoload_register(array($this,'framework'));
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
		
		$pathBase = 'app/' == substr($class,0,4)
			? FURNACE_APP_PATH . '/' . substr($class,4)
			: FURNACE_LIB_PATH . '/' . $class;
		
		$path   = $pathBase .".class.php";
		
		//var_dump($path);
		
		if (file_exists($path)) {
			require_once($path);
			
		// *Collection classes are co-located with their parents	
		} else if (substr($class,-10) == 'Collection' && $class[10]) {
			$path = str_replace('Collection.class.php','.class.php',$path);
			if (file_exists($path)) {
				require_once($path);
			}
		}
	}
}