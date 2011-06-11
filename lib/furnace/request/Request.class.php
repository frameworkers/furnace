<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage request
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\request;

use furnace\routing\Route;

class Request {

    protected $raw_uri;
    protected $route;
    protected $method;
    protected $data;

    public function __construct($uri) {
        $this->raw_uri = $uri;
        $this->method  = $_SERVER['REQUEST_METHOD'];
        $this->data    = $_REQUEST;
    }

    public function getCleanUri() {
        if (F_URL_BASE == '/') {
            Furnace::Halt('Feature not implemented','<code>Request::getCleanUri()</code> '
                . " has not yet been implemented in the case where F_URL_BASE is '/'");
        } else {
            $urlBaseLen = strlen(F_URL_BASE);
            return substr($this->raw_uri,$urlBaseLen);
        }
    }

    public function method() {
        return $this->method;
    }

    public function route( Route $route = null) {
        if (null == $route) {
            return $this->route;
        }
        
        $this->route = $route;
    }

    public function data($key = null,$default = null) {

        if (null == $key) { return $this->data; }

        return isset($this->data[$key])
            ? $this->data[$key]
            : $default;
    }

    public function getRawUri() {
        return $this->raw_uri;
    }

    public function __get($name) {
        return $this->$name();
    }
}
