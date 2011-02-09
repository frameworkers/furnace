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
use org\frameworkers\furnace\persistance\orm\pdo\DataSource;

class Connections {
	
	protected static $definitions;
	
	protected static $instances;
	
	public static function Add($label,$type,$connectionInformation) {
		
		// Add this connection information to the `$definitions` array.
		self::$definitions[$label] = array(
			    "label"=> $label,
				"type" => $type,
				"info" => $connectionInformation
		);
	}
	
	public static function Get($label = 'default') {
		// If this is the first time this connection is being requested,
		// instantiate the connection:
		if (!isset(self::$instances[$label])) {
			$conn = self::$definitions[$label];
			if ($conn) {
				switch ($conn['type']) {
					// Handle RDBMS connections by instantiating a new
					// DataSource instance with the appropriate provider
					case 'mysql':
						self::$instances[$label] = new DataSource(
							"mysql:host={$conn['info']['host']};dbname={$conn['info']['dbname']};",
							$conn['info']['username'],
							$conn['info']['password']);
						break;
					default:
						die("unable to instantiate connection to resource with label `{$label}`");
							
				}
			} else { die("no connection defined with label `{$label}`");}
		}
		
		// In any event, return the requested instance
		return self::$instances[$label];
	}
} 
