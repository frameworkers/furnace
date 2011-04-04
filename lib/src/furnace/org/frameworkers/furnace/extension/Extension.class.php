<?php
namespace org\frameworkers\furnace\extension;
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
/**
 * Provides a mechanism for storing and obtaining application extension info
 * 
 */
class Extension {
	
	protected static $registry = array();
	
	public static function Register($label, $rootDir) {
		self::$registry[$label] = $rootDir;
	}
	
	public static function Lookup($label) {
		if ($label == '*') {
			return self::$registry;
		} else {		
			return isset(self::$registry[$label])
				? self::$registry[$label]
				: null;
		}
	}
}