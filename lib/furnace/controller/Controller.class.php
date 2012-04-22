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
use furnace\exceptions\FurnaceException;

class Controller {

    protected $request;
    protected $response;

    protected $auth;    
    protected $input;

    protected $connection;
     

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

    public function useModule( $which ) {
      $moduleConfigFilePath = F_MODULES_PATH 
        . "/{$which}/config.php";
      if (file_exists($moduleConfigFilePath)) {
        require_once($moduleConfigFilePath);
      } else {
        throw new \Exception("Unable to locate module '{$which}'");
      }
      return $this;
    }

    public function set($key,$value,$default = null) {
        $this->response->set($key,$value,$default);
        return $this; // allow chaining
    }

    public function flash($message,$cssClass = 'success') {
        return $this->response->flash($message,$cssClass);
    }

    public function title($value) {
        Config::Set('page.title',$value);
    }

    public function finalize() {
        return $this->response;
    }

    public function db($connectionLabel = 'default') {
        return Connections::Get($connectionLabel);
    }
    
    public function assert( $condition, $httpStatusCode ) {
      if (false === $condition) {
        $this->request->abort($httpStatusCode
          , null
          , "Assertion failed! If a backtrace is available, see line "
            ." <code>#1</code> for the corresponding file and line number.");
      }
      return $this; // allow chaining
    }    

    public function __get($name) {
        return $this->$name();
    }
}
