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

class Response {

    protected $request;
    protected $route;
    protected $body;
    
    public $data       = array();
    public $contents   = array();

    public function __construct(Request $request,Route $route,$options = array()) {
        $this->request      = $request;
        $this->route        = $route;
        $this->body         = '';
    }
    
    public static function Create ($request, Route $route, $options = array()) {
        switch (strtolower($route->contentType)) {
            case 'text':
            case 'text/plain':
                return new Response($request, $route, $options);
            case 'json':
            case 'application/json':
            case 'x-application/json':
            case 'text/javascript':
              return new JsonResponse($request, $route, $options);
            case 'html':
            case 'text/html': 
            default:
                $r = new HtmlResponse($request, $route, $options);
                return $r;
        }
    }

    public function add(ResponseChunk $chunk,$bFinal = false) {
        $this->body .= $chunk->contents();
        return $this; // allow chaining
    }

    public function text($contents) {
        $c = new ResponseChunk();
        return $c->load($contents); 
    }

    public function body($rawContents = null) {
       
         if (null !== $rawContents) {
            $this->body = ($rawContents instanceof ResponseChunk)
                ? $rawContents->contents()
                : $rawContents;
        }
        return $this->body;
    }

    public function hasLayout() {
        return false;
    }
    
    // Perform any initial processing. This is invoked by the Furnace
    // class as step 3.8 of the Request(...) method
    public function initialize() {}

    // Perform any final processing. This is invoked by the Furnace
    // class as step 4.1 of the Request(...) method
    public function finalize() {}
    
    // Store key/value data in this response
    public function set( $key, $value, $default ) {
      $this->data[$key] = (null == $value) ? $default : $value;
      return $this;
    }
    
    // Backwards-compatibility (<0.4.4). Since 0.4.4 this function
    // is now available in the HtmlResponse class.
    public function region( $which = 'content' ) { 
      return $this; 
    }
    
    // Backwards-compatibility (<0.4.4). Since 0.4.4 this function
    // is now available in the HtmlResponse class.
    public function prepare( $templateFilePath, $raw = false ) {
      return $this;
    }
    
    public static function redirect( $newURL ) {
        if ($newURL[0] == '/' && F_URL_BASE != '/') {
			$newURL = F_URL_BASE . $newURL;
		}
		header('Location: ' . $newURL);
		exit();
    }


    // Null handlers to permit complete reuse of controller logic
    // independent of functions present/not present in derived
    // handlers
    public function __call( $name, $arguments ) { /* empty */ } 
    public static function __callStatic( $name, $arguments ) { /* empty */ }

}

