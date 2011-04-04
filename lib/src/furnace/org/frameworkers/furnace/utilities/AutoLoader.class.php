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
use org\frameworkers\furnace\extension\Extension;
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
		spl_autoload_register(array($this,'extensions'));
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
		
		$path = FURNACE_LIB_PATH . '/' . $class . ".class.php";
		
		if (file_exists($path)) {
			require_once($path);
		}
	}
	
	protected function extensions($class) {
		// swap '\' for '/' to handle linux paths. This should
		// eventually be made conditonal upon detecting a 
		// linux install.
		$class = str_replace('\\','/',$class);
		
		$exts  = Extension::Lookup('*');
		foreach ($exts as $pathBase) {
			$path = $pathBase . '/lib/' . $class . '.class.php';
			if (file_exists($path)) {
				require_once($path);
				return;

			// *Collection classes are co-located with their parents
			// * which needs to change, since it is non-standard!	
			} else if (substr($class,-10) == 'Collection' && $class[10]) {
				$path = str_replace('Collection.class.php','.class.php',$path);
				if (file_exists($path)) {
					require_once($path);
					return;
				}
			}	
		}
	}
}