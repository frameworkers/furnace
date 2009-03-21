<?php
/*
 * frameworkers
 * 
 * FPageModule.class.php
 * Created on June 27, 2008
 * Modified significantly on March 16, 2009
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FPageModule
  * 
  */
class FPageModule  {
	
	protected $controller;
	
	private $installPath;
	
	public function __construct(&$controller,$installPath) {
		
		// Initialize the controller
		$this->controller  = $controller;
		
		// Initialize the installation path
		$this->installPath = $installPath; 
	}

 	protected function getView($view) {
 		return file_get_contents($this->installPath . "/views/{$view}.html");
 	}
}
?>