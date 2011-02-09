<?php
namespace org\frameworkers\furnace\persistance\cache;
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
abstract class Cache {
	
	protected static $inMemory = array();
	
	static function set($key,$value,$duration = 0) {
		self::$inMemory[$key] = $value;
	}
	
	static function get($key) {
		if (isset(self::$inMemory[$key])) {
			return self::$inMemory[$key];
		} else {
			return false;
		}
	}
	
	static function delete($key) {
		unset(self::$inMemory[$key]);
	}
	
	static function clear() {
		self::$inMemory = array();
	}
	
	static function stats() {
		$stats = array(
			'length' => count(self::$inMemory),
			'data'   => self::$inMemory	
		);
		return json_encode($stats);
	}
}
