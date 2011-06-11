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

class ResponseChunk {

    protected $contents;
    protected $locals;

    public function __construct($contents = null,$data = array()) {
        $this->contents = $contents;
        $this->locals   = $data;
    }

    public function load($contents) {
        $this->contents = $contents;
        return $this; // allow chaining
    }

    public function set($key,$value,$default = null) {
        $this->locals[$key] = (null == $value)
            ? $default
            : $value;
        return $this; // allow chaining
    }

    public function contents() {
        return $this->contents;
    }

    public function data() {
        return $this->locals;
    }
}
