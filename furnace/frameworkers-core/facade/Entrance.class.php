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
	
	// Variable: defaultPageName
	// The controller to use when no page name is specified
	private $defaultPageName;
	
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
	
	// Variable: pageName
	// The page name as extracted from the request
	private $pageName;
	
	public function __construct($controllerDir,$defaultPageName,$skip='') {
		// Assign local variables
		$this->skip = $skip;
		$this->defaultPageName = $defaultPageName;
		$this->controllerDirectory = $controllerDir;
		$this->stateParams     = array();
		
		// Start the session
		session_start();
	}
	
	private function processRequestURI($req_uri) {
		// Save the raw REQUEST_URI
		$this->requestURI =& $req_uri;
		
		// Process subdirectory skips if required
		$request = $req_uri;
		if ($this->skip != '') {
			if (strpos($req_uri,$this->skip) >0) {
				echo (strpos($req_uri,$this->skip) == 0);
				$request = substr($req_uri,
					strpos($req_uri,$this->skip)+strlen($this->skip));
			}
		}
		
		// Grab the parameter string from the request
		$this->stateParams = explode("/",trim($request,"/"));
			
		// Extract the page name
		$this->pageName = $this->stateParams[0];

		// Specify a default page name if necessary
		if ("" == $this->pageName) {
		 	$this->pageName = $this->defaultPageName;
		}
		
		// Append 'Page' to the page name
		$this->pageName .= "Page";
	}
	
	public function validRequest($req) {
		$this->state = $this->processRequestURI($req);
		if (file_exists("{$this->controllerDirectory}/{$this->pageName}.php")) {
			return true;
		} else {
			return false;
		}
	}
	
	
	public function dispatchRequest() {
		require_once("{$this->controllerDirectory}/{$this->pageName}.php");
		// What do we send? (priority order: POST,GET)
		$args = ((count($_POST) > 0) 
			? $_POST
			: ((count($_GET) > 0)
				? $_GET
				: array()
			  )
		);
		$page = new $this->pageName($args,$this->stateParams);	
		$page->render();
	}	
	
	public function dispatchError() {
		// use $this->state to determine which error to return
		
	}
 }
?>