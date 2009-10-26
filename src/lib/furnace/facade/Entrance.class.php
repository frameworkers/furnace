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
	
	// Variable: controllerPath
	// The full path to the controller file
	private $controllerPath;
	
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
		
		// Route the current request to a controller/view pair
		$route = FRouter::Route($processedRequest);
		
		$this->controllerName      = $route['controller'];
		// Append 'Controller' to get the controllerClassName
		$this->controllerClassName = $route['controller'] . "Controller";
		$this->controllerPath      = $this->controllerDirectory .
			(( !empty($route['prefix']) )
				? (rtrim("/",$route['prefix']) . "/")
				: "/") .
			$this->controllerClassName .
			".php";
		$this->viewName            = $route['view'];
		$this->viewArguments       = $route['parameters'];
	}
	
	public function validRequest($req) {
		$this->state = $this->processRequestURI($req);
		// Check that the controller file exists
		if (file_exists("{$this->controllerPath}")) {
			// Require the controller file
			require_once("{$this->controllerPath}");
			//TODO: check that the class is defined within the file
			// Create an instance of the controller
			$this->controller = new $this->controllerClassName();
			// Check that the appropriate function has been defined in the class
			if (!is_callable(array($this->controller,$this->viewName))) {
				_err('',
					 "The function <pre>{$this->controllerClassName}::{$this->viewName}</pre> has not been implemented yet."
					 ."<br/>Insert the following code: <pre>public function {$this->viewName}() {}</pre>"
					 ."<br/>Into the file: <pre>/app/controllers/{$this->controllerClassName}.php</pre>",
					 true,
					 'http404');
			}
			// Check that the template file exists
			$this->templatePath = $this->controllerDirectory
				."/../views/"
				.$this->controllerName
				."/"
				.$this->viewName
				.".html";
			
			// If everything above this passed, return true
			return true;
		} else {
			_err('',
					 "The controller file <pre>/app/controllers/{$this->controllerClassName}.php</pre> does not exist yet."
					 ."<br/>Insert the following code: <pre>class {$this->controllerClassName} {}</pre>"
					 ."<br/>Into the file: <pre>/app/controllers/{$this->controllerClassName}.php</pre>",
					 true,
					 'http404');
		}
	}
	
	public function dispatchRequest() {
		// Process the view
		call_user_func_array(
			array($this->controller,$this->viewName),
			$this->viewArguments
		);
		if (!file_exists($this->templatePath)) {
			_err('',
					 "The view <pre>/app/views/{$this->controllerName}/{$this->viewName}.html</pre> does not exist yet."
					 ."<br/>Create the file to remove this error. ",
					 true,
					 'http404');
		}
		$this->controller->setTemplate($this->templatePath);
		
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
			_err('Invalid redirect request made','',true);
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
 }
?>