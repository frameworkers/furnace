<?php
namespace org\frameworkers\furnace\action;

use org\frameworkers\furnace\core\Object;
use org\frameworkers\furnace\utilities\HTTP;

class Controller extends Object {	
	
	public $request;
	
	public $response;
	
	public function __construct( &$request, &$response ) {
		$this->request  = $request;
		$this->response = $response;
	}
	
	public function set($key,$value,$zone = 'content') {
		$this->response->set($key,$value,$zone);
	}
	
	/**
	 * Test for a condition and provide error headers and messages on failure
	 * @param mixed   $condition      The condition to test. Will be evaluated to boolean true or false
	 * @param string  $failureMessage The message to display if the assertion fails
	 * @param string  $httpCode       The http code to respond with if the assertion fails 
	 *                                (use \org\frameworkers\furnace\utilities\HTTP class for http code constants)
	 */
	public function assert($condition,$failureMessage = 'Missing expected request parameters',$httpCode = HTTP::HTTP_400) {
		if ( true !== $condition) {
			$this->response->error("{$httpCode}: {$failureMessage}");
			header("HTTP/1.1: {$httpCode}");
		}
	}	
}