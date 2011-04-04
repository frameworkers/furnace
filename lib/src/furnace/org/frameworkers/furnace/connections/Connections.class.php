<?php
namespace org\frameworkers\furnace\connections;
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
 * Provides a data structure for organizing the application's connections
 * to various services
 */

class Connections {
	
	protected static $definitions;
	
	protected static $instances;
	
	public static function Add($label,$provider,$connectionInformation) {
		
		// Add this connection information to the `$definitions` array.
		self::$definitions[$label] = array(
			    "label"    => $label,
				"provider" => $provider,
				"extra"    => $connectionInformation
		);
	}
	
	public static function Get($label = 'default') {
		// If this is the first time this connection is being requested,
		// instantiate the connection:
		if (!isset(self::$instances[$label])) {
			$conn = self::$definitions[$label];
			if ($conn) {
				$provider = $conn['provider'];
				self::$instances[$label] = $provider::Create($conn['extra']);
			} else { die("no connection defined with label `{$label}`");}
		}
		
		// In any event, return the requested instance
		return self::$instances[$label];
	}
} 
