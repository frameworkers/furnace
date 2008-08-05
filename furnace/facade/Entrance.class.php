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
	
	// Variable: controllerClassName
	// The name of the controller class
	private $controllerClassName;
	
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
	
	// Variable: templatePath
	// The path to the requested view's template file
	private $templatePath;
	
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
		
		// Process subdirectory skips if required
		$processedRequest = $req_uri;
		if ($this->skip != '') {
			if (strpos($req_uri,$this->skip) >0) {
				echo (strpos($req_uri,$this->skip) == 0);
				$processedRequest = substr($req_uri,
					strpos($req_uri,$this->skip)+strlen($this->skip));
			}
		}
		
		
		// Grab the parameter string from the request
		$this->stateParams = explode("/",trim($processedRequest,"/"));
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
		
		// Append 'Controller' to get the controllerClassName
		$this->controllerClassName = $this->controllerName . "Controller";
	}
	
	public function validRequest($req) {
		$this->state = $this->processRequestURI($req);
		// Check that the controller file exists
		if (file_exists("{$this->controllerDirectory}/{$this->controllerClassName}.php")) {
			// Require the controller file
			require_once("{$this->controllerDirectory}/{$this->controllerClassName}.php");
			//TODO: check that the class is defined within the file
			// Create an instance of the controller
			$this->controller = new $this->controllerClassName();
			// Check that the appropriate function has been defined in the class
			if (!is_callable(array($this->controller,$this->viewName))) {
				$this->fatal(
					"The function {$this->controllerClassName}::{$this->viewName} "
					."has not been implemented yet."
				);
				return false;
			}
			// Check that the template file exists
			$this->templatePath = $this->controllerDirectory
				."/../views/"
				.$this->controllerName
				."/"
				.$this->viewName
				.".html";
			if (!file_exists($this->templatePath)) {
				$this->fatal(
					"The view 'app/views/{$this->controllerName}/{$this->viewName}.html "
					. "does not exist yet.");
				return false;
			}
			// If everything above this passed, return true
			return true;
		} else {
			$this->fatal(
				"The controller file 'app/controllers/{$this->controllerClassName}.php' "
				."does not exist yet.");
			return false;
		}
	}
	
	public function dispatchRequest() {

		// Handle any 'actions' before processing the view
		for ($i = 0; $i < count($this->viewArguments) - 1; $i++) {
			if ("actions" == $this->viewArguments[$i]) {
				$actionName = "_{$this->viewArguments[$i+1]}";
				$actionArgs = @array_slice($this->viewArguments,$i+2);
				call_user_func(array($this->controller,"doAction"),$actionName,$actionArgs);
				break;
			}
		}	
		
		// Process the view
		$this->controller->setTemplate($this->templatePath);
		call_user_func_array(
			array($this->controller,$this->viewName),
			$this->viewArguments
		);
		
		// Return the generated content
		return $this->controller->render(false,false);
	}	
	
	public function dispatchError() {
		// use $this->state to determine which error to return
		
	}
	
	public function handleRedirect($newController,$newView='index',$flashText='',$cssClass='') {
		// Build new REQUEST_URI
		if ($newView == 'index') {
			$request = (($this->skip != '') ? "/{$this->skip}" : "") . "/{$newController}/";
		} else {
			$request = (($this->skip != '') ? "/{$this->skip}" : "") . "/{$newController}/{$newView}";
		}
		// Execute the new request
		if ($this->validRequest($request)) {
			$this->dispatchRequest($flashText,$cssClass);
		} else {
			die("Invalid redirect request made.");
		}
	}
	
	public function getController() {
		return $this->controller;	
	}
	public function getControllerName() {
		return $this->controllerName;	
	}
	public function getControllerClassName() {
		return $this->controllerClassName;	
	}
	
	private function fatal($message) {
		if (FProject::DEBUG_LEVEL > 0) {
			die($message);
		} else {
			// Should redirect to the 'invalid request' page
			return false;
		}
	}
 }
?>