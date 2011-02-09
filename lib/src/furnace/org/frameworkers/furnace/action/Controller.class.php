<?php
namespace org\frameworkers\furnace\action;

use org\frameworkers\furnace\core\Object;
use org\frameworkers\furnace\utilities\HTTP;

class Controller extends Object {	
	
	public $context;
	
	public $response;
	
	public function __construct( &$context, &$response ) {
		$this->context  = $context;
		$this->response = $response;
	}
	
	public function set($key,$value,$zone = 'content') {
		$this->response->set($key,$value,$zone);
	}
	
	public function assert($condition,$failureMessage = 'Missing expected request parameters',$httpCode = HTTP::HTTP_400) {
		if ( true !== $condition) {
			$this->response->flash("Error: {$failureMessage}");
			header("HTTP/1.1: {$httpCode}");
			die($httpCode . ": " . $failureMessage);
		}
	}
	
}