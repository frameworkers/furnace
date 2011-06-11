<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage routing
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
 
namespace furnace\routing;

use \furnace\core\Furnace;
use \furnace\utilities\Logger;
use \furnace\utilities\LogLevel;

class Route {

    public $url;
    public $pattern;
    public $prefix;
    public $controller;
    public $layout;
    public $path;
    public $module;
    public $contentType;
    public $handler;
    public $parameters;
    public $options;

    public function __construct($data = array()) {
        $this->url = $data['url'];
        $this->pattern = $data['pattern'];
        $this->prefix = $data['prefix'];
        $this->controller = $data['controller'];
        $this->layout = $data['layout'];
        $this->module = $data['module'];
        $this->options = $data['options'];
        $this->path = $data['path'];
        $this->contentType = $data['contentType'];
        $this->handler = $data['handler'];
        $this->parameters = $data['parameters'];
    }
}

