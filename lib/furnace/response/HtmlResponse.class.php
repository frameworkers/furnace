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
                $this->layout(F_THEME_PATH . '/'
                    . Config::Get('app.theme') . '/layouts/'
                    . $options['layout']);
            }
        // Then default to using the layout specified by the route
        } else {
            if (false === $route->layout) {     // layout omitted explicitly
                $this->layout(false);
            } else {
                $this->layout(
                    F_THEME_PATH . '/'
                        . Config::Get('app.theme') . '/layouts/' 
                        . $route->layout);
            }
        }
    }

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
}
