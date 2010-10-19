<?php
/**
 * Furnace AutoLoader
 * 
 * Provides autoloading features offered by PHP 5.3+
 * 
 * @author andrew
 *
 */
class FurnaceAutoLoader {
	
	public static $loader;
	
	public static function init() {
		if (self::$loader == NULL) {
			self::$loader = new self();
		}
		return self::$loader;
	}
	
	public function __construct() {
		
		// Put framework classes on the include path
		set_include_path(get_include_path()
			. PATH_SEPARATOR
			. FURNACE_LIB_PATH
			. '/framework/classes/');
			
		// Put module classes on the include path
		set_include_path(get_include_path()
			. PATH_SEPARATOR
			. FURNACE_APP_PATH
			. '/modules/');
			
		// Put utility classes on the include path
		set_include_path(get_include_path()
			. PATH_SEPARATOR
			. FURNACE_LIB_PATH
			. '/utilities/classes/');
			
		// Put managed model classes on the include path
		set_include_path(get_include_path()
			. PATH_SEPARATOR
			. FURNACE_APP_PATH
			. '/model/');
			
		//var_dump(get_include_path());
		
		// Register autoload features
		spl_autoload_extensions('.class.php');
		spl_autoload_extensions('.block.php');
		spl_autoload_register(array($this,'model'));
		spl_autoload_register(array($this,'modules'));
		spl_autoload_register(array($this,'framework'));
		spl_autoload_register(array($this,'utilities'));
	}
	
	public function model($class) {
		// swap '\' for '/' to handle linux paths. This should
		// eventually be made conditonal upon detecting a 
		// linux install.
		$class = str_replace('\\','/',$class);
		
		
		$path = FURNACE_APP_PATH
			. '/model/' . $class . ".class.php";
		//echo "[model] loading {$class}\r\n";
		if (file_exists($path)) {
			require_once($path);
		}
	}
	
	public function modules($class) {
		// swap '\' for '/' to handle linux paths. This should
		// eventually be made conditonal upon detecting a 
		// linux install.
		$class = str_replace('\\','/',$class);
		$class = str_replace('ResultFormatter','',$class);
		//echo "[modul] loading {$class}\r\n";
		
		$path = FURNACE_APP_PATH
			. '/modules/' . $class . ".class.php";
		if (file_exists($path)) {
			require_once($path);
		}
		
		$blockPath = FURNACE_APP_PATH
			. '/modules/' . $class . ".block.php";
		if (file_exists($blockPath)) {
			require_once($blockPath);
		}
		
		// Handle an F<Object>Collection. This could be eliminated
		// (and might be faster) if all Collection,ResultFormatter, etc
		// classes were in their own files. Benchmark testing needed to
		// prove this though.
		if (substr($class,-10) == 'Collection') {
			$class = substr($class,0,-10);
			$lastF = strrpos($class,'/F');
			if ($lastF) {
				$class = substr($class,0,$lastF)
					. "/"
					. substr($class,$lastF+2);
			}
		}
		$path = FURNACE_APP_PATH
			. '/modules/' . $class . ".class.php";
		if (file_exists($path)) {
			require_once($path);
		}
	}
	
	public function framework($class) {
		// swap '\' for '/' to handle linux paths. This should
		// eventually be made conditonal upon detecting a 
		// linux install.
		$class = str_replace('\\','/',$class);
		//echo "[frame] loading {$class}\r\n";
		
		$path = FURNACE_LIB_PATH
			. '/framework/classes/' . $class . ".class.php";
		if (file_exists($path)) {
			require_once($path);
		}
	}
	
	public function utilities($class) {
		// swap '\' for '/' to handle linux paths. This should
		// eventually be made conditonal upon detecting a 
		// linux install.
		$class = str_replace('\\','/',$class);
		//echo "[utili] loading {$class}\r\n";
		
		$path = FURNACE_LIB_PATH
			. '/utilities/classes/' . $class . ".class.php";
		if (file_exists($path)) {
			require_once($path);
		}
	}
}