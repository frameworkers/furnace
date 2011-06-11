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
	
	public static function Set($key, $value) {
		if (empty(self::$configStore)) self::init();
		self::$configStore[$key] = $value;
	}
	
	public static function Get($key = null,$default = null) {
		if ($key == null) {
			return self::$configStore;
		} else {		
			return isset(self::$configStore[$key])
				? self::$configStore[$key]
				: $default;
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
