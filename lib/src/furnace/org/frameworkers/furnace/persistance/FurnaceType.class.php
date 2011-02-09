<?php
namespace org\frameworkers\furnace\persistance;
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
 * Furnace has a fixed set of native types that are mapped to vendor specific
 * column types by individual DataSourceProviders.
 * 
 */
abstract class FurnaceType {
	const STRING    = 'string';
	const TEXT      = 'text';
	const INTEGER   = 'integer';
	const BOOLEAN   = 'boolean';
	const FLOAT     = 'float';
	const TIME      = 'time';
	const TIMESTAMP = 'timestamp';
	const DATE      = 'date';
	const DATETIME  = 'datetime';
	const BLOB      = 'blob';
	
	private static $all;
	
	static function all() {
		if(!isset(self::$all)) {
			self::$all = array(
							self::STRING, 
							self::TEXT, 
							self::INTEGER,
							self::UINTEGER, 
							self::BOOLEAN, 
							self::FLOAT, 
							self::TIME, 
							self::TIMESTAMP, 
							self::DATETIME,
							self::DATE,
							self::BLOB);
		}
		return self::$all;
	}
}