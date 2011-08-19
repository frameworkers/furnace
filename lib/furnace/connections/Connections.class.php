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

namespace furnace\connections;

use furnace\utilities\Logger;
use furnace\utilities\LogLevel;

/**
 * Provides a data structure for organizing the application's connections
 * to various services
 */
class Connections {
	
	protected static $instances;
	
	public static function Add($label,$providerInstance) {
		
		// Add this connection instance to the `$instances` array.
		self::$instances[$label] = $providerInstance;
	}
	
	public static function Get($label = 'default') {

        if (!isset(self::$instances[$label])) {
            Logger::Log(LogLevel::WARN,"Request made for non-existant connection with label: {$label}");

            return null;
        }
		
		// Return the requested instance
		return self::$instances[$label];
	}
} 
