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
	
	// Variable: controllerName
	// The controller name as extracted from the request
	private $controllerName;
	
	// Variable: controllerClassName
	// The name of the controller class
	private $controllerClassName;
	
	// Variable: controller
	// An instance of the controller object actually invoked
	private $controller;
	
	// Variable: viewName 
	// The view name as extracted from the request
	private $viewName;
	
	// Variable: viewArguments
	// The arguments that will be passed to the view function
	// in the controller
	private $viewArguments;
	
	// Variable: templatePath
	// The path to the view's template file
	private $templatePath;
	
	public function __construct($controllerDir,$defaultControllerName) {
		// Assign local variables
		$this->defaultControllerName = $defaultControllerName;
		$this->controllerDirectory   = $controllerDir;
		
		// Start the session
		session_start();
	}
	
	private function processRequestURI($req_uri) {
		// Save the raw REQUEST_URI
		$this->requestURI =& $req_uri;
		
		// Route the current request to a controller/view pair
		$route = FRouter::Route($req_uri);
		
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
		$this->processRequestURI($req);
		// Check that the controller file exists
		if (file_exists("{$this->controllerPath}")) {
			// Require the controller file
			require_once("{$this->controllerPath}");
			//TODO: check that the class is defined within the file
			// Create an instance of the controller
			$this->controller = new $this->controllerClassName();
			// Check that the appropriate function has been defined in the class
			if (!is_callable(array($this->controller,$this->viewName))) {
				$this->fatal(
					"The function {$this->controllerClassName}::{$this->viewName} "
					."has not been implemented yet."
				);
				return true;
			}
			// Set the template path for the view. Do not check for existence
			// at this point because it is unclear whether or not the view will
			// actually display anything (it may just be an action). This is just a 
			// priming step. The existence check is handled in dispatchRequest.
			$this->templatePath = $this->controllerDirectory
				."/../views/"
				.$this->controllerName
				."/"
				.$this->viewName
				.".html";
			
			// If everything above this passed, return true
			return true;
		} else {
			$this->fatal(
				"The controller file 'app/controllers/{$this->controllerClassName}.php' "
				."does not exist yet.");
			return true;
		}
	}
	
	public function dispatchRequest() {
		// Process the view
		call_user_func_array(
			array($this->controller,$this->viewName),
			$this->viewArguments
		);
		if (!file_exists($this->templatePath)) {
			$this->fatal(
				"The view 'app/views/{$this->controllerName}/{$this->viewName}.html "
				. "does not exist yet.");
		}
		$this->controller->setTemplate($this->templatePath);
		
		// Return the generated content
		return $this->controller->render(false,false);
	}	
	
	public function dispatchError() {
		// use $this->state to determine which error to return
		
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
	
	private function fatal($debug_message) {
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$this->controllerClassName = "_furnaceController";
			$this->viewName            = "debug";
			$this->viewArguments       = array($debug_message,$this->requestURI);
			require_once("{$this->controllerDirectory}/{$this->controllerClassName}.php");
			$this->controller = new $this->controllerClassName();
			$this->templatePath = $this->controllerDirectory . "/../views/_furnace/debug.html";
		} else {
			$this->controllerClassName = "_errorController";
			$this->viewName   = "http404";
			$this->viewArguments = array($this->requestURI);
			require_once("{$this->controllerDirectory}/{$this->controllerClassName}.php");
			$this->controller = new $this->controllerClassName();
			$this->templatePath = $this->controllerDirectory . "/../views/_error/404.html";
		}
	}
 }
?>