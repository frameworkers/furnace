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
 	
 	public function __construct($template='',$state=array()) {
 		$contents = file_get_contents($template);
 		parent::__construct($contents); 
 		$this->state = $state;	
 		$this->register("session",$_SESSION);
 		$this->langcode = Config::PROJECT_DEFAULT_LANG;
 	}
 	
 	protected function dispatch(&$args) {
 		// Clean arguments
 		
 		// Search for 'do' and call matching user function
		$count = 0;
		foreach ($args as $arg=>$value) {
			// Handles case where 'do' is sent via POST
			if ("do" === $arg) {					
				if (method_exists($this,$value)) {
					$this->$value($args);
				} 
				break;
			}
			// Handles all other cases
			if ("do" == $value) {
				if (method_exists($this,$args[$count+1])) {
					$this->$args[$count+1]($args);
				} 
				break;
			}
			$count++;
		}
 	}

	protected function display() {
		echo $this->getContents();	
	}
	
	public function compile() {
		parent::compile();
		$this->translateStrings();
	}

 	public function render() {
 		$this->compile();
 		echo $this->buildHead();
 		echo $this->getContents();
 		echo $this->buildFoot();
 	}	
 	
 	public function setLanguage($code = "en-us") {
 		$this->langcode = $code;
 	}
 	
	public function setTitle($value) {
		$this->title = $value;
	}
	
	public function addStylesheet($path) {
		$this->stylesheets[] = 
			"<link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}\"/>";
	}
	
	public function addJavascript($path) {
		$this->javascripts[] = 
			"<script type=\"text/javascript\" src=\"{$path}\"></script>";
	}
	
	private function getStylesheets() {
		return ((count($this->stylesheets)) > 0) 
			? implode("\r\n\t",$this->stylesheets)
			: '';
	}
	
	private function getJavascripts() {
		return ((count($this->javascripts)) > 0)
			? implode("\r\n\t",$this->javascripts)
			: '';
	}
	
	private function buildHead() {
		echo <<<END
<html>
<head>
	<title>{$this->title}</title>
	<!-- CSS Stylesheets -->
	{$this->getStylesheets()}
	<!-- Javascript Includes -->
	{$this->getJavascripts()}
	
</head>
<body>

END;
	}
	
	private function buildFoot() {
		echo <<<END
		
</body>
</html>	
END;
	}

 	protected function requireLogin($failPage) {
		if (false == ($user = FSessionManager::checkLogin())) {
			if ("" == $failPage) {
				return false;
			} else {
				header("Location: {$failPage}");
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
						Config::PROJECT_ROOT_DIR."/model/i18n/strings.{$this->langcode}.yml"
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