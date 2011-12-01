<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage controller
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\controller;

use furnace\core\Config;
use furnace\core\Furnace;
use furnace\response\ResponseChunk;
use furnace\request\Request;
use furnace\auth\providers\AbstractAuthenticationProvider;
use furnace\connections\Connections;

class Controller {

    protected $request;
    protected $response;

    protected $auth;    
    protected $input;

    protected $connection;
     
    protected $activeZone = 'content';

    public function __construct($request, $response) {
        $this->request  = $request;
        $this->response = $response;
        $this->input    = Input::init();
    }

    public function data() {
        return $this->data;
    }

    public function contents() {
        return $this->contents;
    }

    public function auth($auth = null) {
        if (null != $auth && $auth instanceof AbstractAuthenticationProvider) {
            $this->auth = $auth;
        }

        return $this->auth;
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
                . "/{$this->request->route()->module}"
                . "/views/{$templateFilePath}";
                
            // The full path to a theme-specific override in the
            // current theme
            $themeOverride = Config::Get('app.themes.dir')
                . '/' . Config::get('app.theme')
                . '/views/' . $this->request->route()->module
                . "/{$templateFilePath}";

            // Check for a theme override of the template
            $this->response->contents[$this->activeZone] = 
                (file_exists($themeOverride))
                    ? file_get_contents($themeOverride)
                    : file_get_contents($fullPath);

        } else {
            $this->response->contents[$this->activeZone] = $templateFilePath;
        }

        return $this; // allow chaining
    }

    public function load($modelClass,$alias = null) {
        $lastNsSeparator = strrpos($modelClass,'\\');
        if ($alias == null) {
            if ($lastNsSeparator !== false) {    
                $attrName = substr($modelClass,$lastNsSeparator+1);
            } else {
                $attrName = $modelClass;
            }
        } else {
            $attrName = $alias;
        }
        
        $this->$attrName = new $modelClass();
    }

    public function region($name) {
        $this->activeZone = $name;
        return $this; // allow chaining
    }

    public function set($key,$value,$default = null) {
        $this->response->data[$this->activeZone][$key] = (null == $value)
            ? $default
            : $value;
        return $this; // allow chaining
    }

    public function flash($message,$cssClass = 'success') {
        return $this->response->flash($message,$cssClass);
    }

    public function title($value) {
        Config::Set('page.title',$value);
    }

    public function finalize() {
        $result = array();
        foreach ($this->response->contents as $zone => $content) {

            if (count($this->response->data[$zone]) > 0) {
                $result[$zone] = template(new ResponseChunk($content,$this->response->data[$zone]));
            } else {
                $result[$zone] = new ResponseChunk($content,$this->response->data[$zone]);
            }
        }
        
        $this->response->add($result);
        return $this->response;
    }

    public function db($connectionLabel = 'default') {
        return Connections::Get($connectionLabel);
    }

    public function __get($name) {
        return $this->$name();
    }
}
