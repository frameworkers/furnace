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

use furnace\core\Furnace;
use furnace\response\ResponseChunk;
use furnace\request\Request;

class Input {

    protected $request;

    protected static $post;
    protected static $get;
    protected static $cookie;
    protected static $server;


    public function __construct(Request $request = null) {
        if (null == self::$post)   { self::$post   = $_POST; }
        if (null == self::$get)    { self::$get    = $_GET; }
        if (null == self::$cookie) { self::$cookie = $_COOKIE; }
        if (null == self::$server) { self::$server = $_SERVER; }
        
        if ($request) { $this->request = $request; }

        // Unregister globals if register_globals is set
        if(ini_get('register_globals')) {
            unset($GLOBALS['_POST']);
            unset($GLOBALS['_GET']);
            unset($GLOBALS['_COOKIE']);
            unset($GLOBALS['_REQUEST']);
            unset($GLOBALS['_SERVER']);
            unset($GLOBALS['_ENV']);
            unset($GLOBALS['_FILES']);
            ini_set('register_globals', 0);
        }
    }

    public function any($key, $default = null, $filter = true) {
        // Try POST...
        if (isset(self::$post[$key])) { return $this->post($key,$default,$filter); }
        // Try GET...
        if (isset(self::$get[$key]))  { return $this->get($key,$default,$filter); }
        // No match...
        return $default;
    }

    public function post($key = null, $default = null, $filter = true) {
        
        if (null == $key)
            return ($filter) 
                ? $this->filter_data(self::$post)
                : self::$post;

        if (isset(self::$post[$key]))
            return ($filter)
                ? $this->filter_data(self::$post[$key])
                : self::$post[$key];

        return $default;
    }

    public function get($key = null, $default = null, $filter = true) {
        
        if (null == $key)
            return ($filter) 
                ? $this->filter_data(self::$get)
                : self::$get;

        if (isset(self::$get[$key]))
            return ($filter)
                ? $this->filter_data(self::$get[$key])
                : self::$get[$key];

        return $default;
    }

    //public function cookie($key = null, $default = null, $filter = true) {}
    //public function server($key = null, $default = null, $filter = true) {}

    protected function filter_data($data) {
        return $this->xss_filter($data);
    }
    
    protected function xss_filter($data) {
        return $data;
    }
}
