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
namespace org\frameworkers\furnace\auth;

/**
 * Static wrapper class for the application's Authentication
 * implementation. All implementations must implement
 * org\frameworkers\furnace\interfaces\IAuthExtension.
 */
class Auth {
	
	static $authObject;

	public static function init($obj,$config) {
		self::$authObject = $obj;
		self::$authObject->init($config);
	}
	
	public static function get() {
		return self::$authObject;
	}
}