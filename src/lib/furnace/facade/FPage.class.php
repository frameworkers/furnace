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
  * A wrapper for FPageTemplate that adds, among other things,
  * support for internationalization and object oriented page
  * generation. 
  * 
  * Extends: 
  * 
  *  <Tadpole>
  */
 class FPage extends FPageTemplate {
 	
 	// Variable: title
 	// The page title
 	protected $title;
 	
 	// Variable: theme
 	// The name of the theme to use with this page
 	protected $theme;
 	protected $bInheritTheme;
 	
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
	
		
 	public function __construct($layout='default') {
 		parent::__construct(); 
 		$this->javascripts = array();
 		$this->stylesheets = array();
 		$this->theme         = 'default';
 		$this->bInheritTheme = false;
 		$this->setLayout($layout);
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
 		    _furnace()->rootdir . "/app/themes/{$this->theme}/layouts/{$layout}.html"
 		);
 	}
 	
 	public function extensionSetLayout($extension,$layout) {
 	    if ($this->bInheritTheme) {
 	        $baseTheme = _furnace()->config['app_theme'];
 	        $this->layout = file_get_contents(
 	            _furnace()->rootdir . "/app/themes/{$baseTheme}/layouts/{$layout}.html"
 	        );
 	    } else {
 	        $this->layout = file_get_contents(
 	            _furnace()->rootdir . "/app/plugins/extensions/{$extension}/themes/{$this->theme}/layouts/{$layout}.html"
 	        );
 	    }
 	}
 	
 	public function getLayout() {
 		return $this->layout;	
 	}
 	
 	public function getTheme() {
 	    return $this->theme;
 	}
 	
 	public function setTheme($val) {
 	    $this->theme = $val;
 	    $this->bInheritTheme = (strtolower($this->theme) == "inherit");
 	}
 	
 	public function getTitle() {
 		return $this->title;	
 	}
 	
 	protected function setTitle($value) {
		$this->title = $value;
	}
	
	protected function addStylesheet($path,$bTheme = true) {
		if ($bTheme) {
	        $path = "assets/themes/{$this->theme}/css/{$path}";   
	    } else {
	        //TODO: add ability to programmatically add local stylesheet
	    }
		$this->stylesheets[] = $path;
	}
	
	protected function extensionAddStylesheet($extension,$path,$bTheme = true) {
	    if ($bTheme) {
	        $path = "extensions/{$extension}/themes/{$this->theme}/css/{$path}";
	    } else {
	        $path = "extensions/{$extension}{$path}";
	    }
	    $this->stylesheets[] = $path;
	}
	
	protected function addJavascript($path,$bTheme = true) {
		if ($bTheme) {
	      $path = "assets/themes/{$this->theme}/js/{$path}\r\n";   
	    } else {
	        //TODO: add ability to programmatically add local stylesheet
	    }
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
		if (false !== ($this->contents = @file_get_contents($templatePath))) {
    		if ($bCompileNow) {
    			$this->contents = parent::compile($this->contents);	
    		}
		} else {
		    throw new FException("Requested template file \"{$templatePath}\" does not exist");
		}
	}
	
	public function flash($message,$cssClass='success',$title='') {
		if (is_object($message)) {$message = $message->__toString();}
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
	
	public function loadStrings($namespace,$locale = null) {
		$localeToUse = ($locale) ? $locale : $GLOBALS['furnace']->config['default_locale'];
		$fileName    = $GLOBALS['furnace']->rootdir . "/app/i18n/{$localeToUse}/strings/{$namespace}.{$localeToUse}.yml";
		if (false !== ($strings = $GLOBALS['furnace']->parse_yaml($fileName))) {
			// convert the namespace to a nested array structure
			$parts = explode('.',$namespace);
			
			$f =& $this->page_data['_strings'][$localeToUse];
			$count = 0;
			for ($count=0; $count < count($parts) - 1; $count++) {
				if (! isset($f[$parts[$count] ])) { $f[$parts[$count] ] = array(); }
				$f =& $f[$parts[$count] ]; 
			}
			$f[$parts[$count] ] = $strings;
			
			return true;
		} else {
			return false;
		}
	}
 }
?>