<?php
abstract class FPageFragment {
    
/*
 * frameworkers-foundation
 * 
 * FPageFragment.class.php
 * Created on January 08, 2010
 *
 * Copyright 2008-2010 Frameworkers.org. 
 * http://www.frameworkers.org
 */

/*
 * Class: FPageFragment
 * Provides reusable view fragments
 * 
 */ 
    
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
    
    // return the rendered fragment content
    public function render($argv = array()) {
        
    }
}
?>