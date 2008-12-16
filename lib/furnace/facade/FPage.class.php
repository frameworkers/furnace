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
 	
 	// Variable: langcode
	// The code for the language to be used to display strings
	protected $langcode;
	
	// Variable: string_data
	// The array of language-specific strings loaded
	protected $string_data;
 	
 	// Variable: state
	// Record the state information for this page request
	protected $state;
 	
 	public function __construct($state=array()) {
 		parent::__construct(); 
 		$this->state = $state;	
 		$this->register("session",$_SESSION);
 		$this->langcode = "en-us";
 		$this->layout   = 'default';
 		$this->javascripts = array();
 		$this->stylesheets = array();
 	}

	protected function display() {
		echo $this->getContents();	
	}
	
	public function compile() {
		parent::compile();
		/*
		if (FProject::I18N_ENABLED) {
			$this->translateStrings();
		}
		*/
	}

 	public function render($bEcho = true) {
 		$this->compile();
 		if ($bEcho) {
 			echo $this->getContents();
 		} else {
 			return $this->getContents();
 		}
 	}	
 	
 	public function useLayout($layout) {
 		$this->layout = $layout;	
 	}
 	
 	public function getLayout() {
 		return $this->layout;	
 	}
 	
 	public function getTitle() {
 		return $this->title;	
 	}
 	
 	protected function setLanguage($code = "en-us") {
 		$this->langcode = $code;
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
				header("Location: {$failPage}");
				exit;
			}
		} else {
			return $user;
		}
	}
	
	private function loadStrings($ns = '',$dir='') {
		if ('' == $ns) {
			$this->string_data = 
				FYamlParser::parse(
					@file_get_contents(
						$GLOBALS['fconfig_root_directory']."/model/i18n/strings.{$this->langcode}.yml"
					)
				);
		} else {
			$this->string_data[$ns] = 
				FYamlParser::parse(
					@file_get_contents($dir."/strings.{$this->langcode}.yml")
				);
		}
	}
	
	protected function registerStringNamespace($ns,$i18n_dir) {
		$this->loadStrings($ns,$i18n_dir);
	}
	
 	protected function translateStrings() {
 		if ($this->string_data == array()) {
 			$this->loadStrings();
 		}
		$matches = preg_match_all("/\[string:([^\]]+)\]/",
			$this->contents,$results);
		if (!is_array($results) || 0 == @count($results[0]) ) {
			return;	// No matches found
		}
		$keys = $results[0];	// matching placeholders
		$vals = $results[1];	// placeholder name
		for ($i=0;$i<count($keys);$i++) { 
			$parts = explode(".",$vals[$i]);
			if (count($parts) > 1) {
				$this->contents = str_replace(
					$keys[$i],
					$this->getRecursively($parts,$this->string_data),
					$this->contents
				);
			} else  {
				$this->contents = str_replace(
					$keys[$i],
					$this->string_data[$vals[$i]],
					$this->contents
				);				
			}
		}	
	}
 }
?>