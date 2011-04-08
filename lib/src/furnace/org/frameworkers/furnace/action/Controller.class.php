<?php
namespace org\frameworkers\furnace\action;

use org\frameworkers\furnace\core\Object;
use org\frameworkers\furnace\utilities\HTTP;
use org\frameworkers\furnace\auth\Auth;

class Controller extends Object {	
	
	public $request;
	
	public $response;
	
	public $auth;
	
	public $user;
	
	public $clean = true; // A controller is clean until it fails an assertion
	
	public function __construct( &$request, &$response ) {
		
		// Store information about the context of this request
		$this->request  = $request;
		$this->response = $response;
		
		// Store information about the currently authenticated user
		$this->auth     = Auth::Get();
		$this->user     = ($this->auth) ? $this->auth->getEntityObject() : false;
		
		// No assertions have failed at this point
		$this->clean    = true;
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
	public function assert($condition,$failureMessage = 'Missing expected request parameters',$callback = null) {

		if ( true != $condition) {
			$this->clean = false;
			$this->response->error($failureMessage);
			if (null != $callback) {
				$callback($this->request,$this->response);
			}
		}
	}

	public function assertStrict($condition,$failureMessage = 'Missing expected request parameters',$callback = null) {

		if ( true !== $condition) {
			$this->clean = false;
			$this->response->error($failureMessage);
			if (null != $callback) {
				$callback($this->request,$this->response);
			}
		}
	}
}