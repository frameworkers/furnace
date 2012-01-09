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

    public $raw;
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
        $this->raw     = isset($data['raw'])             ? $data['raw']         : false;
        $this->url     = isset($data['url'])             ? $data['url']         : false;
        $this->pattern = isset($data['pattern'])         ? $data['pattern']     : false;
        $this->prefix  = isset($data['prefix'])          ? $data['prefix']      : false;
        $this->controller = isset($data['controller'])   ? $data['controller']  : false;
        $this->layout  = isset($data['layout'])          ? $data['layout']      : false;
        $this->module  = isset($data['module'])          ? $data['module']      : false;
        $this->options = isset($data['options'])         ? $data['options']     : false;
        $this->path    = isset($data['path'])            ? $data['path']        : false;
        $this->contentType = isset($data['contentType']) ? $data['contentType'] : false;
        $this->handler = isset($data['handler'])         ? $data['handler']     : false;
        $this->parameters = isset($data['parameters'])   ? $data['parameters']  : false;
    }
}

