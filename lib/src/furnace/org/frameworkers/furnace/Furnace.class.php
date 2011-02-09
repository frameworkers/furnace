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
use org\frameworkers\furnace\request\Context;
use org\frameworkers\furnace\response\ResponseTypes;

/**
 * Furnace is the main class for the Furnace framework. 
 * 
 * @author  Andrew Hart <andrew.hart@frameworkers.org>
 * @version SVN: $Id$
 */
class Furnace extends Object {
	
	public $config;
	
	public function __construct() {
		
	}
	
	public function processRequest( $url ) {

		// Filterable
		return $this->_filter(__METHOD__, array($url), function($self, $params) {

			// Create a context for this request
			$context = Context::CreateFromUrl( $params[0] );
			
			// Create a response from the context
			$response = ResponseTypes::CreateResponse( $context );

			// Send any appropriate headers
			header('Content-Type: ' . ResponseTypes::MimeFor($context->type));
			
			// Send the response body
			echo $response;
			
		});
		
	}
}