<?php
namespace org\frameworkers\furnace\response;

use org\frameworkers\furnace\core\StaticObject;
use org\frameworkers\furnace\config\Config;

abstract class Response extends StaticObject {
	
	public $context;
	
	protected $local_data  = array('content' => array());
	
	protected $javascripts = array();
	
	protected $stylesheets = array();
	
	abstract public function __construct (&$context);
		
	public static function Create( $context ) {
		// Instantiate the appropriate subclass
		if (ResponseTypes::TypeExists($context->responseType)) {
			
			// Determine which class should handle responses of this type
			$responseClass = ResponseTypes::ClassFor($context->responseType);
			
			// Create an instance of the response class
			$response      = new $responseClass( $context );
			
			// Create an instance of the controller class for this request
			$controllerFilePath  = Config::Get('applicationControllersDirectory')
				. "/".ucwords($context->controllerClassName) . ".class.php";
			require_once($controllerFilePath);
			$controllerClassName = $context->controllerClassName;
			$controllerClass     = new $controllerClassName( $context, $response );
			
			// Run the handler for this request
			self::RunHandler($controllerClass,
				$context->handlerName,
				$context->arguments);
				
			// Return the rendered response for this request
			return $response->render();
			
		} else {
			die("unknown response type {$context->responseType}");
		}
	}
	
	protected static function RunHandler($c,$h,$a) {
		$argc = count($a);
		
		try {
			switch ($argc) {
				case 0: $c->$h(); break;
				case 1: $c->$h($a[0]); break;
				case 2: $c->$h($a[0],$a[1]); break;
				case 3: $c->$h($a[0],$a[1],$a[2]); break;
				case 4: $c->$h($a[0],$a[1],$a[2],$a[3]); break;
				case 5: $c->$h($a[0],$a[1],$a[2],$a[3],$a[4]); break;
				default:
					call_user_func_array(array($c,$h),$a);
			}
		}	
		catch (\Exception $e) {
			die ('An exception occurred: ' . $e->getMessage() . "<br/><pre>{$e->getTraceAsString()}</pre>");	
		}
	}
	
	public static function Redirect( $newURL ) {
		if ($newURL[0] == '/' && Config::Get('applicationUrlBase') != '/') {
			$newURL = Config::Get('applicationUrlBase') . $newURL;
		}
		header('Location: ' . $newURL);
		exit();
	}
	
	public function abort($detail = "No additional details provided") {
		if (Config::Get('debugMode') > 0) {
			echo "<h1>The response was aborted due to an error:</h1>";
			echo "<hr/>{$detail}<hr/>";
			echo "<pre>";
			echo debug_print_backtrace();
			echo "</pre>";
		} else {
			echo "<h5>An error has occurred.</h5>";	
		}		
		exit();		
	}
}