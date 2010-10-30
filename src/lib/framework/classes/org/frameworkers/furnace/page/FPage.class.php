<?php
namespace org\frameworkers\furnace\page;
use       org\frameworkers\furnace as Furnace;
use       org\frameworkers\furnace\page as Page;
use       org\frameworkers\furnace\exceptions as Exceptions;
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
  */

class FPage {
	
	protected $document;
	protected $head;
	protected $body;
	public $zones;
	
	
	
	protected $layoutContents;
	
	protected $jsraw;
	protected $cssraw;
	
	protected $stylesheets;
	protected $javascripts;
	
	protected $renderEngine;
	
	
	
	public function __construct() {
		
		$this->stylesheets = array();
		$this->javascripts = array();
		$this->layoutContent = '';
		$this->renderEngine = new \org\frameworkers\furnace\page\FPageTemplate();
		
		// Create the document's doctype
		$this->head  = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD XHTML 1.1//EN\""
					.  " \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\r\n";
		$this->head .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\r\n";
			
		// Create mandatory document elements
		$this->head .= "<head>\r\n"
					.  "  <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"/>\r\n";
		
		$this->head .= "  <title>Sucks</title>\r\n";
		
		$this->body  = "<body>\r\n";

		// Initialize the content zone
		$this->zones = array();
		$this->zones['content'] = array('structure' => '','data'=>array());
	}
	
	public function set($key,$value,$zone = 'content') {
		if (!isset($this->zones[$zone])) {
			$this->zones[$zone] = array('structure' => '','data' => array());
		}
			
		$this->zones[$zone]['data'][$key] = $value;
	}
	
	public function render($bEcho = true) {
		
		// Ensure that some sort of layout data has been requested. The only
		// requirement is that the layout contain the magic [_content_] tag
		// which will be replaced with the computed page content, which is
		// added during the call to {@see satisfyRequest}
		if (empty($this->layoutContents)) {
			$this->setLayout('default.html');
		}
		
		// Add the layout data
		$this->body .= $this->layoutContents;
		
		// Process any css includes
		foreach ($this->stylesheets as $cssFile) {
			$path = (is_array($cssFile))
				? "[%a]/assets/module/{$cssFile[1]}/{$cssFile[0]}"	// css from a module block
				: "[%theme]/css/{$cssFile}";					// css from the current theme
			
			$this->head .= "  <link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}\"/>\r\n";
		}
		
		// Process any javascript includes
		foreach ($this->javascripts as $jsFile) {
			$this->head .= "  <script type=\"text/javascript\" src=\"[%theme]/js/{$jsFile}\"></script>\r\n";
		}
		 
		// Process any raw javascript additions
		$this->head .= "  <script type=\"text/javascript\">\r\n{$this->jsraw}\r\n  </script>\r\n";

		// Close the HEAD section
		$this->head .= "</head>\r\n";
		
		// Close the BODY and HTML sections
		$this->body .= "\r\n</body>\r\n</html>\r\n";

		// Get the whole document as a string
		$this->document = $this->head . $this->body;
		
		// Consume any unseen flashes
		$flashes = _furnace()->read_flashes();
		$flashesContent = '';
		if (!empty($flashes)) {
			foreach ($flashes as $flash) {
				$flashesContent .= "<div class=\"{$flash['cssClass']}\">{$flash['message']}</div>";
			}
		}
		$this->set('_flashes_',$flashesContent);
		
		/*
		 * BEGIN FINAL DOCUMENT COMPILATION
		 */
		
		// Load Furnace application variables
		$app_vars = _furnace()->getApplicationVariables();
		
		// Compile all zones
		foreach ($this->zones as $zoneName => $zoneInfo) {
			// Merge application variables with zone data (union)
			$zone_data = $app_vars + $zoneInfo['data'];
			// Compile the zone structure
			$this->renderEngine->reset();
			$this->renderEngine->loadPageData($zone_data);
			$compiled = $this->renderEngine->compile($zoneInfo['structure']);
			// Replace the zone key in the document with the compiled result
			$this->document  = str_replace("[_{$zoneName}_]",$compiled,$this->document);
		}
		
		// Final pass for any application vars outside of defined zones (in <head/>,etc)
		
		$this->document = $this->renderEngine->compile($this->document,$app_vars);
		
		/*
		 * END FINAL DOCUMENT COMPILATION
		 */		

		
		// Return or output the final content
 		if ($bEcho) {
 			echo $this->document;
 		} else {
 			return $this->document;
 		}
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
	
	public function includeCSS($filename,$blockPath = null) {
		if ($blockPath) {
			$blockPath = str_replace("\\",'/',$blockPath);
			if (file_exists(FURNACE_APP_PATH . "/modules/{$blockPath}/{$filename}")) {
				$this->stylesheets[] = array($filename,ltrim($blockPath,'/'));
			} else {
				throw new Exceptions\FurnaceException("Requested CSS file '{$filename}' does not exist at {$blockPath}");
			}	
		} else {
			if (file_exists(FF_THEME_DIR . '/' . _furnace()->theme . "/css/{$filename}")) {
				$this->stylesheets[] = $filename;
			} else {
				throw new Exceptions\FurnaceException(
					"Requested CSS file '{$filename}' does not exist in the current theme");
			}
		}
	}
	
	public function includeJS($filename) {
		if (file_exists(FF_THEME_DIR . '/' . _furnace()->theme . "/js/{$filename}")) {
			$this->javascripts[] = $filename;
		} else {
			throw new Exceptions\FurnaceException(
				"Requested JavaScript file '{$filename}' does not exist in the current theme");
		}
	}
	
	public function setLayout($layoutFile) {
		// Open the requested layout file
		if (file_exists(FF_THEME_DIR  . '/' . _furnace()->theme .  "/layouts/{$layoutFile}")) {
			
			// Load the layout file
			$this->layoutContents = file_get_contents(FF_THEME_DIR . '/' . _furnace()->theme . "/layouts/{$layoutFile}");

		} else {
			throw new Exceptions\FurnaceException("Requested layout file '{$layoutFile}' does not exist");
		}
	}	
}
?>