<?php
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
namespace org\frameworkers\furnace;
use org\frameworkers\furnace\core\Object;
use org\frameworkers\furnace\request\Request;
use org\frameworkers\furnace\response\ResponseTypes;

/**
 * Furnace is the main class for the Furnace framework. 
 * 
 * @author  Andrew Hart <andrew.hart@frameworkers.org>
 * @version SVN: $Id$
 */
class Furnace extends Object {
	
	const VERSION = '0.3.0-rc2';
	
	public function processRequest( $url ) {

		// Filterable
		return $this->_filter(__METHOD__, array($url), function($self, $params) {
			
			// Initialize a PHP session
			session_start();
			header("cache-control: private");

			// Create a request object for this request
			$request = Request::CreateFromUrl( $params[0] );
			
			// Create a response from the context
			$response = ResponseTypes::CreateResponse( $request );

			// Send any appropriate headers
			header('Content-Type: ' . ResponseTypes::MimeFor($request->responseType));
			
			// Send the response body
			echo $response;
			
		});
	}
}