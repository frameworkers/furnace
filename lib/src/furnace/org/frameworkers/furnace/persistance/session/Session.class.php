<?php
namespace org\frameworkers\furnace\persistance\session;
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
 * A simple Object Oriented wrapper for the PHP Session construct
 *
 */
class Session {
	
	public static function Start() {
		
	}
	
	public static function Get($key) {
		return (isset($_SESSION[$key])) 
			? $_SESSION['key']
			: null;
	}
	
	public static function Set($key,$val) {
		$_SESSION[$key] = $val;
	}
	
	public static function Clear($key) {
		unset($_SESSION[$key]);
	}
	
	public static function End() {
		
	}
}