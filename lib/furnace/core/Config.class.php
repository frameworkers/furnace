<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
/**
 * Provides a mechanism for storing and obtaining application configuration
 * 
 */

namespace furnace\core;

class Config {
	
	protected static $configStore = array();
	protected static $stageMode        = false;
	protected static $stagedModuleName = false;
	protected static $modules     = array();
	
	public static function Set($key, $value) {
		if (empty(self::$configStore)) self::init();
		
		if (self::$stageMode) {
		    // Create the staging area for the staged module if not exists
		    if (!isset(self::$modules[self::$stagedModuleName])) {
		        self::$modules[self::$stagedModuleName] = array();
		    }
		    // Add the current key/value pair to the staging area
		    self::$modules[self::$stagedModuleName][$key] = $value;
		} else {
		    // Add the current key/value pair to the config store
		    self::$configStore[$key] = $value;
		}
	}
	
	public static function Get($key = null,$default = null) {
		if ($key == null) {
			return self::$configStore;
		} else {
		    // self::$stageMode means we are 
		    // currently staging a module's config file. In this case
		    // we need to check both the global config store and the
		    // staged values for the module. This is required
		    // because it is possible (indeed probable) that a module 
		    // config will reference its own settings in 
		    // Router::Connect(...) declarations.
		    if (self::$stageMode) {
		        // Check the global store first
		        if (isset(self::$configStore[$key])) {
		            return self::$configStore[$key];
		        }
		        // Check the module's staged config
		        if (isset(self::$modules[self::$stagedModuleName][$key])) {
		            return self::$modules[self::$stagedModuleName][$key];
		        }
		        // Nothing matched, return the default
		        return $default;
		    // Default operation
		    } else {
    			return isset(self::$configStore[$key])
    				? self::$configStore[$key]
    				: $default;
		    }
		}
	}
	
	public static function GetModules() {
    return self::$modules;
  }

	
  public static function LoadModule( $moduleName, $returnFields = array() ) {
    // Don't do anything unless this module has not previously been seen
    if (!isset(self::$modules[$moduleName])) {	
      // Get the config file for the desired module
      $configFilePath = F_MODULES_PATH . "/{$moduleName}/config.php";
      
      // Enter staging mode
      self::$stageMode = true;
      self::$stagedModuleName = $moduleName;
      
      // Process the config file
      if (file_exists($configFilePath)) {
          require_once ($configFilePath);
      }
    }
    
    // Were any values explicitly requested for return?
    $returnValues = array();
    if (!empty($returnFields)) {
      foreach ($returnFields as $f) {
        $returnValues[$f] = self::$modules[$moduleName][$f];
      }
    }
    
    // Exit staging mode
    self::$stagedModuleName = false;
    self::$stageMode = false;
    
    // Determine what to return
    if (!empty($returnFields)) {
      return $returnValues; // Return the requested config fields
    } else {
      return true;          // Just indicate success
    } 
	}
	
  public static function ApplyStagedModule( $moduleName ) {
    if (isset(self::$modules[$moduleName])) {
        self::$configStore = array_merge(
            self::$configStore, self::$modules[$moduleName]);
        return true;
    } else {
        return false;
    }
  }
	
	public static function Delete($key) {
		unset(self::$configStore[$key]);
	}
	
	public static function Clear() {
		self::$configStore = array();
	}
	
	public static function Reload($data) {
		self::$configStore = $data;
	}
	
	protected static function init() {
		
		
	}
	
}
