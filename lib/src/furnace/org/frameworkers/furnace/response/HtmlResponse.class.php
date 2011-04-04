<?php
namespace org\frameworkers\furnace\response;

use org\frameworkers\furnace\response\html\HtmlLayout;
use org\frameworkers\furnace\response\html\HtmlZone;

use org\frameworkers\furnace\core\StaticObject;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\request\Request;
use org\frameworkers\furnace\response\Response;
use org\frameworkers\furnace\response\renderers\TadpoleRenderer;


class HtmlResponse extends Response {
	
	public $themePath;
	
	public $layout;
	
	public $fileExtension;
	
	public $notifications;
	
	public $rawJS;
	
	
	public function __construct( $context ) {
		
		// Store the context
		$this->context = $context;
		
		// Store the extension
		$this->fileExtension = Config::Get('htmlViewFileExtension');
		
		// Default theme path
		$this->themePath = 
			 Config::Get('applicationThemesDirectory') . '/'
			.Config::Get('theme');
		
		// Set a default layout
		$this->setLayout(Config::Get('defaultLayoutFile'));
			
		// Set a default view file for the `content` zone based on the current handler
		if ($this->layout->content) {		
			$path = (($this->context->controllerBaseName == 'default')
					? ''
					: "{$this->context->controllerBaseName}/");
					
			if (is_dir(Config::Get('applicationViewsDirectory') . '/' . $path . '/' . $context->handlerName)) {
				$path .= "{$context->handlerName}/";
			}
		
			$finalViewFilePath = $path . $context->handlerName . $this->fileExtension;
			$fullPath = Config::Get('applicationViewsDirectory') . '/' . $finalViewFilePath;
			if (file_exists($fullPath)) {
				$this->layout->content->prepare($finalViewFilePath);
			}
		}
		
		// Initialize the local_data structure
		$this->local_data = array("content" => array());
		
		// Initialize any notifications
		$this->notifications = isset($_SESSION['_notifications'])
			? $_SESSION['_notifications']
			: array();
	}
	
	public function render( ) {
		
		//Get the latest notifications (in case the handler set any)
		$this->notifications = isset($_SESSION['_notifications'])
			? $_SESSION['_notifications']
			: array();
		
		$document = $this->layout->render($this);
		
		// Final pass to catch any tags outside of defined zones
		$renderer = new TadpoleRenderer($this);
		$document = $renderer->compile( $document, $this->context, array());
		
		// Reset the notifications array
		$_SESSION['_notifications'] = array();
		return $document;	
	}
		
	/**
	 * Initialize an HtmlLayout object
	 * 
	 * @param string $layoutFilename  If bRawString is false, this value will
	 *                                be interpreted to be the theme-relative
	 *                                path to the layout file to use. If 
	 *                                bRawString is true, this value itself
	 *                                will be used as the layout contents.
	 * @param string $bRawString      Whether or not to treat $layoutFilename
	 *                                as a file path (false) or as the actual
	 *                                layout contents as a string (true)
	 */
	public function setLayout($layoutFilename,$bRawString = false) {
		$bkup = $this->layout;
		if ($bRawString) {
			$this->layout = new HtmlLayout($layoutFilename);
		} else {
			$this->layout = new HtmlLayout(
				file_get_contents("{$this->themePath}/layouts/{$layoutFilename}"));
		}
		// Restore any common zone information that might apply
		if ($bkup) {
			foreach ($bkup->_zones as $z) {
				if (in_array($z,$this->layout->_zones)) {
					$this->layout->$z = $bkup->$z;
				}
			}
		}
	}
	
	public function setTheme($theme) {
		$this->themePath = Config::Get('applicationThemesDirectory')."/{$theme}";
		$this->context->urls['theme_base'] = 
			Config::Get('applicationUrlBase')."/themes/{$theme}";
	}
	
	/**
	 * If the file to include is part of a theme, then `$bLocal` is false
	 * (the default) and `$path` represents the portion of the path to the
	 * file starting from the theme's `js` directory. Examples:
	 *    $this->includeJavascript('five.js');      //-> [THEME_ROOT]/js/five.js
	 *    $this->includeJavascript('foo/seven.js'); //-> [THEME_ROOT]/js/foo/seven.js
	 * 
	 * If, on the other hand, the file to include is an asset local to a 
	 * particular view (somewhere in the application views directory heirarchy), then 
	 * `$bLocal` should be set to true and `$path` represents the portion of the path
	 * to the file starting from the application views directory. Examples:
	 *   // assume current view file is     /views/foo/index/index.html
	 *   // and there is a local js file at /views/foo/index/index.js
	 *   $this->includeJavascript('/foo/index/index.js',true);
	 * 
	 * @param string  $path   - The path to the file to include, relative either to
	 *                          the current theme (if $bLocal is false) or to the
	 *                          application views directory (if $bLocal is true)
	 * @param boolean $bLocal - Whether or not to look in the theme or the application
	 *                          views directory tree for this file. Default value is
	 *                          false, meaning look for the file in the current theme.
	 */
	public function includeJavascript($path,$bLocal = false) {
		if ($bLocal) { // File is somewhere in the application views hierarchy
			$url = "{$this->context->urls['view_base']}/" . ltrim($path,'/');
		} else {       // File is part of the current theme
			$url = "{$this->context->urls['theme_base']}/js/" . ltrim($path,'/');
		}
		// Add the file to the array of javascripts to include in the context. 
		// Indexing on url prevents duplicates
		$this->javascripts[$url] = 
				'<script type="text/javascript" src="'.$url.'"></script>';
	}
	
	// Bundling a javascript puts the contents of the file inline rather than as a 
	// <script src=.../>
	public function bundleJavascript($path) {
		$contents = file_get_contents($path);
		$this->javascripts[] = '<script type="text/javascript">'
			. $contents
			. '</script>';
	}
	
	public function includeStylesheet($path,$bLocal = false, $condition = null) {
		if ($bLocal) { // File is somewhere in the application views hierarchy
			$url = "{$this->context->urls['view_base']}/" . ltrim($path,'/');
		} else {       // File is part of the current theme
			$url = "{$this->context->urls['theme_base']}/css/" . ltrim($path,'/');
		}
		// Build a CSS snippet to insert
		$snippet = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\">";
		if ($condition !== null) {
			$snippet .= "<!--[if {$condition}]>{$snippet}<![endif]-->";		
		}
		
		// Add the snippet to the array of stylesheets to include in the context
		// Indexing on url prevents duplicates
		$this->stylesheets[$url] = $snippet;
	}
	
	// Bundling a stylesheet puts the contents of the file inline rather than as a 
	// <link rel=.../>
	public function bundleStylesheet($path) {
		$contents = file_get_contents($path);
		$this->stylesheets[] = '<style type="text/css">'
			. $contents
			. "</style>";
	}
	
	
	
	public function includeView(array $which, $zoneLabel, $type='html') {
		if (count($which) != 2 && count($which) != 3) { die('unsupported use of includeView'); }
		$controllerClassName = $which[0];
		$handlerName         = $which[1];
		$args                = isset($which[2]) ? $which[2] : array();
		
		// The array contains a controller and a view. Create a context 
		// object from this information
		$context = Request::CreateFromControllerAndHandler(
			$controllerClassName, $handlerName, $type, $args);

		// Execute the request and store the response
		$response = Response::Create( $context );
		
		// Store the request in the appropriate zone
		$this->includedViews[$zoneLabel] = $response;
	}
	
	public function getIncludedJavascripts() {
		// Build data structure of all local zone data
		$localDataByZone = array();
		foreach ($this->layout->_zones as $z) {
			$localDataByZone[$z] = $this->layout->$z->localData;
		}
		
		$str = implode("\r\n",$this->javascripts);
		$str .= "\r\n\t<script type='text/javascript'>\r\n";
		$str .= "\t\tvar _context = " . StaticObject::toJsonString($this->context) . "\r\n";
		$str .= "\t\tvar _local   = " . StaticObject::toJsonString($localDataByZone);
		$str .= "\r\n\t</script>\r\n";
		
		return $str;
	}
	
	public function getIncludedStylesheets() {
		return implode("\r\n",$this->stylesheets);
	}
	
	public function getNotifications() {
		$str = '';
		foreach ($this->notifications as $zoneMessages) {
			$str = implode("\r\n",$zoneMessages);
		}
		return $str;
	}
	
	public function set($key,$val,$zone = 'content') {
		$this->local_data[$zone][$key] = $val;
	}
	
	protected function flash($message,$title,$cssClass = "notify_info",$zone = 'content') {
		$title   = ($title == '') ? '' : "<h5>{$title}</h5>";
		$message = "<p>{$message}</p>";
		 
		$_SESSION['_notifications'][$zone][] = 
			"<div class='ff_notify {$cssClass}'>{$title}{$message}</div>";
	}

	public function success($message,$title = 'Success!',$zone = 'content') {
		$this->flash($message,$title,"notify_success",$zone);
	}
	
	public function warn($message,$title = 'Warning:',$zone = 'content') {
		$this->flash($message,$title,"notify_warn",$zone);
	}
	
	public function error($message,$title = 'An Error Occurred:',$zone = 'content') {
		$this->flash($message,$title,"notify_error",$zone);
	}
	
	public function info($message,$title = 'Information:',$zone = 'content') {
		$this->flash($message,$title,"notify_info",$zone);
	}
	
}