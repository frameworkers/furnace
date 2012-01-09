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
    
    public $data       = array('content' => '');
    public $contents   = array('content' => '');

    public function __construct(Request $request,Route $route,$options = array()) {
        $this->request      = $request;
        $this->route        = $route;
        $this->body         = '';
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

    // Perform any final processing. This is invoked by the Furnace
    // class as step 4.1.1 of the Request(...) method
    public function finalize() {}

    public static function redirect( $newURL ) {
        if ($newURL[0] == '/' && F_URL_BASE != '/') {
			$newURL = F_URL_BASE . $newURL;
		}
		header('Location: ' . $newURL);
		exit();
    }

}

