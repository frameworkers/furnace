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

    public function prepare($templateFilePath,$raw = false) {
        if (!$raw) {
            $fullPath = F_MODULES_PATH 
                . "/{$this->request->route()->module}/views/{$templateFilePath}";
            $this->response->contents[$this->activeZone] = file_get_contents($fullPath);
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
