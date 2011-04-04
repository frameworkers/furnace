<?php
namespace org\frameworkers\furnace\request;

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
 * A simple wrapper for storing information about an HTTP request method
 *
 */
class RequestMethod {
	
	const GET     = "GET";
	const POST    = "POST";
	const PUT     = "PUT";
	const DELETE  = "DELETE";
	
	public static function Determine($string) {
		switch (strtoupper($string)) {
			case 'GET':    return self::GET;
			case 'POST':   return self::POST;
			case 'PUT':    return self::PUT;
			case 'DELETE': return self::DELETE;
		}
	}
}