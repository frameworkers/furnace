<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage response
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\response;

use furnace\request\Request;
use furnace\routing\Route;
use furnace\core\Config;
use furnace\core\Furnace;

class HtmlResponse extends Response {

    protected $layoutFileContents = null;
    protected $layoutZones        = array("_content_" => '');
    protected $flashMessages      = array();
    
    protected $activeZone         = 'content';

    protected $rawJavascriptVars  = array();


    public function __construct(Request $request,Route $route,$options = array()) {
        parent::__construct($request, $route, $options);
        
        // Initialize the 'data' and 'contents' structures...
        $this->data     = array('content' => '');
        $this->contents = array('content' => '');

        // Determine whether a layout was specified in the options array...
        if (isset($options['layout'])) {
            if (false === $options['layout']) { // layout omitted explicitly
                $this->layout(false);
            } else {
                $this->layout(Config::Get('app.themes.dir')
                    . Config::Get('app.theme') . '/layouts/'
                    . $options['layout']);
            }
        // If not, default to using the layout specified by the route...
        } else {
            if (false === $route->layout) {     // layout omitted explicitly
                $this->layout(false);
            } else {
                $this->layout(Config::Get('app.themes.dir')
                        . Config::Get('app.theme') . '/layouts/' 
                        . $route->layout);
            }
        }
    }

    /**
     * Get or set the layout for the current response
     * 
     * An HtmlResponse is normally wrapped in a 'layout' which provides
     * aspects of the user interface that are common across all pages sharing
     * the same layout, and acts as a container for the defined content zones. 
     * There are three ways to invoke this function. Invoking 
     * this function with no parameters will cause it to return the contents 
     * of the layout that is currently being used. Invoking this function with
     * a single string argument representing an absolute path to a layout file
     * will cause the function to replace the current layout with the contents
     * of the provided file. Finally, passing boolean 'false' to this function
     * will cause the function to erase any previously stored layout information
     * for this response. 
     * 
     * NOTE: For most cases, particularly when trying to set the layout from
     *       a controller handler, the {@see setLayout} and {@see useLayout} 
     *       methods are preferable because they support relative pathing and
     *       method chaining.
     * 
     * @param mixed $path   Either a string representing the absolute path to 
     *                      the layout file to use for this response, or boolean
     *                     'false' to indicate that no layout should be used.
     */
    public function layout($path = null) { 
        // If path === null, return the current layout        
        if (null === $path) {
            return $this->layoutFileContents;
        }

        // If path === false, no layout is requested
        $this->layoutFileContents = (false === $path)
            ? false
            : file_get_contents($path);
    }
    
    /**
     * Alias for {@see setLayout}
     * 
     * @param string $relativePath The path to the layout file, relative to the
     *                             theme's 'layouts' directory. Usually, this is
     *                             a simple file name (e.g.: 'default.php').
     */
    public function useLayout($relativePath,$pathIsAbsolute = false) {
        return $this->setLayout($relativePath,$pathIsAbsolute);
    }
    
    /**
     * Set the layout to use for this response
     * 
     * @param string $relativePath The path to the layout file, relative to the
     *                             theme's 'layouts' directory. Usually this is 
     *                             a simple file name (e.g.: 'default.php').
     * @param boolean $pathIsAbsolute Whether to treat $relativePath as absolute 
     *                             (default is false)
     */
    public function setLayout($relativePath,$pathIsAbsolute = false) {
        
        if ($relativePath === false) {
            $this->layoutFileContents = false;
            return $this;
        }
        
        $fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: Config::Get('app.themes.dir')
    		    .Config::Get('app.theme')
    		    ."/layouts/{$relativePath}";
      
    	$this->layoutFileContents = file_get_contents($fullPath);
    	return $this;
    }

    public function data($zone = null) {
        $zone = trim($zone,'_');
        if (null == $zone) {
            return $this->layoutZones;
        }

        if (isset($this->layoutZones["_{$zone}_"])) {
            return $this->layoutZones["_{$zone}_"];
        } else {
            return null;
        }
    }

    public function add($content,$bFinal = false) {
        // An array of ResponseChunk objects to add
        if (is_array($content)) {
            // If there is no layout file, just add each to the response body
            if ($this->layoutFileContents == null) {
                foreach ($content as $chunk) {
                    $this->body .= $chunk->contents();
                }

            // If there IS a layout file, add each to the corresponding zone
            } else {
                foreach ($content as $zone => $chunk) {
                    if (!isset($this->layoutZones["_{$zone}_"])) {
                        $this->layoutZones["_{$zone}_"] = '';
                    }
                    if (count($chunk->data()) > 0) {
                        $templateResult = template($chunk);
                        $this->layoutZones["_{$zone}_"] .= $templateResult->contents();
                    } else {
                        $this->layoutZones["_{$zone}_"] .= $chunk->contents();
                    }
                }
            }

        // A single ResponseChunk object
        } else if ($content instanceof ResponseChunk) {
            // If there is no layout file, just add to the response body
            if ($this->layoutFileContents == null || $bFinal) {
                $this->body .= $content->contents();

            // If there IS a layout file, add to the 'content' zone
            } else {
                $this->layoutZones['_content_'] .= $content->contents();
            }
        }
    }

    public function hasLayout() {
        return (null != $this->layoutFileContents);
    }

    public function flash($message,$type = 'success') {
        Furnace::Flash($message,$type);
    }
    
    public function includeJavascript($relativePath,$pathIsAbsolute = false,$pathBase = null) {
    	$fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: (($pathBase === null) ? js_url($relativePath) : js_url($relativePath,$pathBase));
    	$chunk = new ResponseChunk('<script type="text/javascript" src="'.$fullPath.'"></script>');
    	$this->contents['javascripts'] .= $chunk->contents();
    }
    
    public function includeStylesheet($relativePath,$pathIsAbsolute = false) {
    	$fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: (($pathBase === null) ? css_url($relativePath) : css_url($relativePath,$pathBase));
    	$chunk = new ResponseChunk('<link rel="stylesheet" type="text/css" href="'.$fullPath.'"/>');
    	$this->contents['stylesheets'] .= $chunk->contents();
    }
    
    public function bundleJavascript($relativePath,$pathIsAbsolute = false) {
        $fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: Config::Get('app.themes.dir').Config::Get('app.theme') . "/js/{$relativePath}";
    		
    	$contents = file_get_contents($fullPath);
    	
    	$chunk = new ResponseChunk('<script type="text/javascript">'.$contents.'"></script>');
    	$this->contents['javascripts'] .= $chunk->contents();
    }
    
    public function bundleStylesheet($relativePath,$pathIsAbsolute = false) {
        $fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: Config::Get('app.themes.dir').Config::Get('app.theme') . "/css/{$relativePath}";
    		
    	$contents = file_get_contents($fullPath);
    	
    	$chunk = new ResponseChunk('<style type="text/css">'.$contents.'</style>');
    	$this->contents['stylesheets'] .= $chunk->contents();
    }

    public function setJs($key,$value,$default = null) {
      $this->rawJavascriptVars[$key] = (null === $value)
        ? $default
        : $value;
      return $this; 
    }
    
    public function region($name) {
        $this->activeZone = $name;
        return $this; // allow chaining
    }
    
    public function set( $key, $value, $default = null) {
      $this->data[$this->activeZone][$key] = (null === $value)
            ? $default
            : $value;
      return $this; // allow chaining
    }
    
    /**
     * Prepare a view template for use
     * 
     * Allows specifying of a path to a template file to use for rendering
     * the content for the currently active zone. The default behavior (i.e.: when
     * the second parameter, '$raw', is boolean 'false') is to treat the specified
     * path as relative to the 'views' directory of the module specified by the 
     * route. If the second parameter, '$raw', is true, then the value for 
     * $templateFilePath is treated as the absolute path to the resource. 
     *
     * This function also checks the current theme to see if an override template
     * has been defined. If one is found in the theme, it is used instead of the 
     * application template.
     * 
     * @param string $templateFilePath The path to the template file to use. See the note above
     *                                 for how this function will interpret the provided value
     * @param string $raw              Whether to treat the path as relative (default) or absolute/raw.	
     */
    public function prepare($templateFilePath,$raw = false) {
        if (!$raw) {
          
            // The full path to the template in the module
            $fullPath = F_MODULES_PATH 
                . "/{$this->route->module}"
                . "/views/{$templateFilePath}";
                
            // The full path to a theme-specific override in the
            // current theme
            $themeOverride = Config::Get('app.themes.dir')
                . '/' . Config::get('app.theme')
                . '/views/' . $this->route->module
                . "/{$templateFilePath}";

            // Ensure at least one of the files exists
            if (!file_exists($fullPath) && !file_exists($themeOverride)) {
              throw new \Exception("Unable to prepare template: "
                ."requested file does not exist and no corresponding theme override detected.");
            }

            // Check for a theme override of the template
            $this->contents[$this->activeZone] = 
                (file_exists($themeOverride))
                    ? file_get_contents($themeOverride)
                    : file_get_contents($fullPath);

        } else {
            if (!file_exists($templateFilePath)) {
              throw new \Exception("Unable to prepare template: "
                ."requested file does not exist.");
            }
            $this->contents[$this->activeZone] = $templateFilePath;
        }

        return $this; // allow chaining
    }
    
    public function initialize() {
      // 3.8 Prepare the expected (default) view file
      $viewTemplateLoadedOk = false;        
      $subDirView = $this->route->controller . '/' . $this->route->handler . Config::Get('view.extension');
      $flatView   = $this->route->handler . Config::Get('view.extension');

      try {  
        $this->region('content')->prepare($subDirView);
        $viewTemplateLoadedOk = true;
      } catch (\Exception $e) {
        try {
          $this->region('content')->prepare($flatView);
          $viewTemplateLoadedOk = true;
        } catch (\Exception $e2) {
          // Unable to load a view template, but continuing anyway
          // in case this controller handler has no intention of
          // displaying a view (e.g.: redirection, file download, etc)
        }
      }
    }

    public function finalize() {
    
      // Process each of the content zones -------------------------------
      $result = array();
      foreach ($this->contents as $zone => $content) {

          if (count($this->data[$zone]) > 0) {
              $result[$zone] = template(new ResponseChunk($content,$this->data[$zone]));
          } else {
              $result[$zone] = new ResponseChunk($content,$this->data[$zone]);
          }
      }
      $this->add($result);
    
  
      // Prepare Javascript variables ------------------------------------
      $js  = "\r\n<script type=\"text/javascript\">\r\n";
      $js .= "var Furnace = { \r\n";
      $js .= "   'theme': '" . Config::Get('app.theme') . "'\r\n";
      $js .= "  ,'URL'  : '" . F_URL_BASE . "'\r\n";
      $js .= "  ,'modules': {\r\n";
      foreach (Config::GetModules() as $moduleName => $conf) {
         $js .= "    '{$moduleName}': { 'URL': '". F_URL_BASE . $conf[$moduleName.'.module.url']."'} ,\r\n";
      }
      $js .= "   }\r\n";
      $js .= "}\r\n";


      // Prepare user Javascript variables
      foreach ($this->rawJavascriptVars as $k => $v) {
        $js .= "var {$k} = " . json_encode($v) . ";\r\n";
      }
      $js .= "</script>\r\n";

      // Add the Furnace & user Javascript variables to the data array
      $this->add(array('javascripts' => new ResponseChunk($js)));

      // Prepare Flash message content -----------------------------------
      
      // Get the messages from the session
      $flashes = Furnace::GetUserMessages();

      if ($flashes) {
          // Format each message
          $flashMessages = '<div id="f_flashMessages">';
          foreach ($flashes as $message) {
              $fn             = $message['type'];
              $flashMessages .= ($fn($message['message'])->contents());
          }
          $flashMessages .= '</div>';
      }

      // Add the user messages to the data array
      $this->add(array('flashes' => new ResponseChunk($flashMessages)));
      
      // Sanity check the content zone for content -----------------------
      $hasLayout = $this->hasLayout();
  
      if ((!$hasLayout && empty($this->body)) || ($hasLayout && empty($this->contents['content']))) {
        if (Config::Get('environment') == F_ENV_DEVELOPMENT) {
            Furnace::halt("Unable to handle request","Furnace was unable to find a "
                . "valid template file to use. The file:<br/>"
                . "<code>".F_MODULES_PATH . "/{$this->route->module}/views/{$this->route->handler}" 
                . Config::Get('view.extension')."</code><br/> does "
                . "not exist (or is empty), and no valid <code>\$this->prepare(...)</code> "
                . "statement was issued in "
                . "<code>{$controllerClassName}::{$this->route->handler}(...)</code> ");
        } else {
            Furnace::NotFound();
        }
      }
      
      
      // Process the layout file, if necessary ---------------------------
      if ($hasLayout) {
        // Process the layout file and add it to the response
        $this->add(
            template(
                new ResponseChunk($this->layout(),$this->data()))
                ,true); // true because this is the _final_ addition
      }
    }
}
