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


    public function __construct(Request $request,Route $route,$options = array()) {
        parent::__construct($request, $route, $options);

        // First look at whether a layout was specified in the options array
        if (isset($options['layout'])) {
            if (false === $options['layout']) { // layout omitted explicitly
                $this->layout(false);
            } else {
                $this->layout(Config::Get('app.themes.dir')
                    . Config::Get('app.theme') . '/layouts/'
                    . $options['layout']);
            }
        // Then default to using the layout specified by the route
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
    
    public function includeJavascript($relativePath,$pathIsAbsolute = false) {
    	$fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: js_url($relativePath);
    	$chunk = new ResponseChunk('<script type="text/javascript" src="'.$fullPath.'"></script>');
    	$this->contents['javascripts'] .= $chunk->contents();
    }
    
    public function includeStylesheet($relativePath,$pathIsAbsolute = false) {
    	$fullPath = ($pathIsAbsolute)
    		? $relativePath
    		: css_url($relativePath);
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

}
