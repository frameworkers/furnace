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
	
	// Variable: cssraw
	// Raw (loose) style declaragions injected into the page
	protected $cssraw;
	
	// Array: javascripts
	// An array of javascript declarations to add
	protected $javascripts;
	
	// Variable: jsraw
    // Raw (loose) javascript injected into the page
	protected $jsraw;
 	
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
 		$this->bThemeSet     = false;
 		$this->setLayout($layout);
 		$this->contents    = "";
 	}

 	public function render($bEcho = true) {
 		// Compile the page contents
 		$this->contents = parent::compile($this->contents);
 		
 		// Store the page content so that it is accessible to the layout
 		$this->ref('_content_',$this->contents);
 		$this->set('_title_',  $this->title);
 		$this->set('_js_',     $this->buildJavascript());
 		$this->set('_css_',    $this->buildCSS());
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

 	private function buildJavascript() {
 	    $s = '';
 	    foreach ($this->javascripts as $js) {
 	        $s .= "<script type=\"text/javascript\" src=\"{$js}\"></script>\r\n";
 	    }
 	    if ('' != $this->jsraw ) {
 	        $s .= "<script type=\"text/javascript\">\r\n{$this->jsraw}\r\n</script>\r\n";
 	    }
 	    return $s;
 	}
 	
 	private function buildCSS() {
 	    $s = '';
 	    foreach ($this->stylesheets as $css) {
 	        $s .= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"{$css}\"/>\r\n";
 	    }
 	    if ('' != $this->cssraw ) {
 	        $s .= "<style type=\"text/css\">\r\n{$this->cssraw}\r\n</style>\r\n";
 	    }
 	    return $s;
 	}
 	
 	public function setLayout($layout) {
 		$this->layout = file_get_contents(
 		    _furnace()->rootdir . "/themes/{$this->theme}/layouts/{$layout}.html"
 		);
 	}
 	
 	public function extensionSetLayout($provider,$package,$layout) {
 	    if ($this->bInheritTheme) {
 	        $baseTheme = _furnace()->config->data['app_theme'];
 	        $this->layout = file_get_contents(
 	            _furnace()->rootdir . "/themes/{$baseTheme}/layouts/{$layout}.html"
 	        );
 	    } else {
 	    	// Lookup the extension in the registry to determine the path to its files
 	    	$ext = _furnace()->extensions["{$provider}/{$package}"];
 	    	if ($ext) {
 	    		$base = ($ext['global']) ? FURNACE_LIB_PATH : FURNACE_APP_PATH;
 	    		$this->layout = file_get_contents(
 	    			"{$base}/plugins/extensions/{$provider}/{$package}/themes/{$ext['theme']}/layouts/{$layout}.html");
 	    	} else {
 	    		throw new FException("extensionSetLayout: Unknown provider or package: {$provider}/{$package}");
 	    	}
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
	
	public function addStylesheet($path,$bLocal = false) {
		if (!$bLocal) {
	        $path = "/assets/themes/{$this->theme}/css/{$path}";   
	    } else {
	        $localPath = $this->page_data['_local_'];
	        $path = "{$localPath}/{$path}";
	    }
		$this->stylesheets[] = $path;
	}
	
	public function extensionAddStylesheet($provider,$package,$path,$bLocal = false) {
		// Look up the extension in the registry:
		if ( ! ($ext = _furnace()->extensions["{$provider}/{$package}"])) {
			throw new FException("extensionAddStylesheet: Unknown provider or package: {$provider}/{$package}");
		}
		$global = ($ext['global']) ? "global/" : '';
	    if (!$bLocal) {
	        $path = "/extensions/{$global}{$provider}/{$package}/themes/{$ext['theme']}/css/{$path}";
	    } else {
	        $localPath = $this->page_data['_local_'];
	        $path = "{$localPath}/{$path}";
	    }
	    $this->stylesheets[] = $path;
	}
	
	public function addJavascript($path,$bLocal = false) {
		if (!$bLocal) {
	      $path = "/assets/themes/{$this->theme}/js/{$path}";   
	    } else {
	        $localPath = $this->page_data['_local_'];
	        $path = "{$localPath}/{$path}";
	    }
		$this->javascripts[] = $path;
	}
     
	public function extensionAddJavascript($provider,$package,$path,$bLocal = false) {
		// Look up the extension in the registry:
		if ( ! ($ext = _furnace()->extensions["{$provider}/{$package}"])) {
			throw new FException("extensionAddJavascript: Unknown provider or package: {$provider}/{$package}");
		}
		$global = ($ext['global']) ? "global/" : '';
	    if (!$bLocal) {
	        $path = "/extensions/{$global}{$provider}/{$package}/themes/{$ext['theme']}/js/{$path}";
	    } else {
	        $localPath = $this->page_data['_local_'];
	        $path = "{$localPath}/{$path}";
	    }
	    $this->javascripts[] = $path;
	}
	
	public function injectJS($code) {
	    $this->jsraw .= $code;
	}
	public function setJS($var,$value,$addQuotes = true) {
	    if ($addQuotes) {
	        $this->jsraw .= "var {$var} = \"{$value}\";\r\n";
	    } else {
	        $this->jsraw .= "var {$var} = {$value};\r\n";
	    }
	}
	
	public function injectCSS($style) {
	    $this->cssraw .= $style;
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

 	protected function requireLogin($failPage='/login') {
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
		$fileName    = $GLOBALS['furnace']->rootdir . "/i18n/{$localeToUse}/strings/{$namespace}.{$localeToUse}.yml";
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