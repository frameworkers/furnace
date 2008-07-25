<?php
/*
 * frameworkers
 * 
 * Entrance.class.php
 * Created on June 23, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

 /*
  * Class: Entrance
  * A Front-Controller base class.
  */
 class Entrance {
 	
 	// Variable: skip
	// The portion of the request URI to ignore. This is 
	// useful for FrontControllers residing in subdirectories.
	private $skip;
	
	// Variable: state
	// The status of the FController after initialization
	private $state;
	
	// Variable: defaultController
	// The controller to use when no page name is specified
	private $defaultControllerName;
	
	// Variable: controllerDirectory
	// The directory where the controllers reside.
	private $controllerDirectory;
	
	// Variable: requestURI
	// The original request URI
	private $requestURI;
	
	// Variable: request
	// The filtered request
	private $request;
	
	// Variable: params
	// The parameters passed in the request
	private $stateParams;
	
	// Variable: controllerName
	// The controller name as extracted from the request
	private $controllerName;
	
	// Variable: controller
	// The controller object actually invoked
	private $controller;
	
	// Variable: viewName 
	// The view name as extracted from the request
	private $viewName;
	
	// Variable: viewArguments
	// The arguments that will be passed to the 'viewName' function
	// in the controller
	private $viewArguments;
	
	public function __construct($controllerDir,$defaultControllerName,$skip='') {
		// Assign local variables
		$this->skip = $skip;
		$this->defaultControllerName = $defaultControllerName;
		$this->controllerDirectory   = $controllerDir;
		$this->stateParams     = array();
		
		// Start the session
		session_start();
	}
	
	private function processRequestURI($req_uri) {
		// Save the raw REQUEST_URI
		$this->requestURI =& $req_uri;
		
		/*
		// Process subdirectory skips if required
		$request = $req_uri;
		if ($this->skip != '') {
			if (strpos($req_uri,$this->skip) >0) {
				echo (strpos($req_uri,$this->skip) == 0);
				$request = substr($req_uri,
					strpos($req_uri,$this->skip)+strlen($this->skip));
			}
		}
		*/
		
		// Grab the parameter string from the request
		$this->stateParams = explode("/",trim($this->requestURI,"/"));
		// Extract the controller & view names
		if (count($this->stateParams) == 1 && "" == $this->stateParams[0]) {
			// Load the default controller with the default view
			$this->controllerName = $this->defaultControllerName;
			$this->viewName       = FProject::DEFAULT_VIEW;
			$this->viewArguments  = array();	
		} else if (count($this->stateParams) == 1) {
			// Load the specified controller with the default view
			$this->controllerName = $this->stateParams[0];	
			$this->viewName       = FProject::DEFAULT_VIEW;
			$this->viewArguments  = array();
		} else {
			// Load the specified controller with the specified view
			$this->controllerName = $this->stateParams[0];
			$this->viewName       = $this->stateParams[1];	
			$this->viewArguments  = array_slice($this->stateParams,2);
		}
		
		// Append 'Controller' to the controller name
		$this->controllerName .= "Controller";
		var_dump($this->controllerName);
	}
	
	public function validRequest($req) {
		$this->state = $this->processRequestURI($req);
		echo "checking {$this->controllerDirectory}/{$this->controllerName}.php";
		if (file_exists(
			"{$this->controllerDirectory}/{$this->controllerName}.php")) {
			return true;
		} else {
			return false;
		}
	}
	
	public function dispatchRequest() {
		require_once(
			"{$this->controllerDirectory}/{$this->controllerName}.php");
		// Determine what content was submitted (priority order: POST,GET)
		$args = ((count($_POST) > 0) 
			? $_POST
			: ((count($_GET) > 0)
				? $_GET
				: array()
			  )
		);
		// Create the controller, passing any submitted data
		$this->controller = new $this->controllerName($args);
		// Invoke the method corresponding to the view name
		if (is_callable(array($this->controller,$this->viewName))) {
			call_user_func(
				array($this->controller,$this->viewName),
				$this->viewArguments
			);
		}
		// Return the generated content
		return $this->controller->render(false,false);
	}	
	
	public function dispatchError() {
		// use $this->state to determine which error to return
		
	}
	
	public function getController() {
		return $this->controller;	
	}
 }
?>