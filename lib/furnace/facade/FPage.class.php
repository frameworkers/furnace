<?php
/*
 * frameworkers
 * 
 * FPage.class.php
 * Created on June 07, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
  /*
  * Class: FPage
  * A wrapper for Tadpole that adds, among other things,
  * support for internationalization and object oriented page
  * generation. 
  * 
  * Extends: 
  * 
  *  <Tadpole>
  */
 class FPage extends Tadpole {
 	
 	// Variable: title
 	// The page title
 	protected $title;
 	
 	// Variable: layout
 	// The name of the layout to use with this controller
 	protected $layout;
 	
 	// Array: stylesheets
	// An array of stylesheet declarations to add
	protected $stylesheets;
	
	// Array: javascripts
	// An array of javascript declarations to add
	protected $javascripts;
 	
 	// Variable: charset
 	// The page character set
 	protected $charset;
	
	// Variable: contents
	protected $contents;
	
		
 	public function __construct() {
 		parent::__construct(); 
 		self  ::setLayout('default');
 		$this->javascripts = array();
 		$this->stylesheets = array();
 		
 		$this->contents    = "";
 	}

 	public function render($bEcho = true) {
 		// Compile the page contents
 		$this->contents = parent::compile($this->contents);
 		
 		// Store the page content so that it is accessible to the layout
 		$this->ref('_content_',$this->contents);
 		$this->set('_title_',  $this->title);
 		$this->set('_js_',     $this->javascripts);
 		$this->set('_css_',    $this->stylesheets);
 		$this->set('_flashes_',_furnace()->read_flashes());
 		
 		// Compile the layout to form the final page
 		$finalContent = parent::compile($this->layout);
 		
 		// Return or output the final content
 		if ($bEcho) {
 			echo $finalContent;
 		} else {
 			return $finalContent;
 		}
 	}	
 	
 	public function setLayout($layout) {
 		$this->layout = file_get_contents(
 			_furnace()->layoutBasePath . "/{$layout}.html");
 	}
 	
 	public function getLayout() {
 		return $this->layout;	
 	}
 	
 	public function getTitle() {
 		return $this->title;	
 	}
 	
 	protected function setTitle($value) {
		$this->title = $value;
	}
	
	protected function addStylesheet($path) {
		$this->stylesheets[] = $path;
	}
	
	protected function addJavascript($path) {
		$this->javascripts[] = $path;
	}
	
	public function getStylesheets() {
		return $this->stylesheets;
	}
	
	public function getJavascripts() {
		return $this->javascripts;
	}
	
	public function getContents() {
		return $this->contents;
	}
	
	public function setContents($val) {
		$this->contents = $val;
	}
	
	public function setTemplate($templatePath,$bCompileNow = false) {
		$this->contents = file_get_contents($templatePath);
		if ($bCompileNow) {
			$this->contents = parent::compile($this->contents);	
		}
	}
	
	public function flash($message,$cssClass='success',$title='') {
		$_SESSION['flashes'][] = array(
			'message' => $message,
			'cssClass'=> $cssClass,
			'title'   => $title
		);
	}

 	protected function requireLogin($failPage='/') {
		if (false == ($user = FSessionManager::checkLogin())) {
			if ("" == $failPage) {
				return false;
			} else {
				// Store the location of the current request for redirection
				// after successful login
				$_SESSION['afterLogin'] = $_SERVER['REQUEST_URI']; 
				header("Location: {$failPage}");
				exit;
			}
		} else {
			return $user;
		}
	}
 }
?>