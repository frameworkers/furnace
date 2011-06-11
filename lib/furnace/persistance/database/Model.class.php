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

namespace furnace\persistance\database;

use furnace\controller\Input;
use furnace\connections\Connections;

class Model  {

    protected $_input;
    protected $db;

    public function __construct() {

        $this->input  = new Input();
        $this->db     = Connections::Get();

    }


    /**
	 * Convert the current object to an array, recursively.
	 * 
	 * @return array
	 */
    public function toArray() {

        $data = get_object_vars( $this );

        return is_array( $data ) 
            ? array_map ( array (__CLASS__,__FUNCTION__), $data) // recurse
            : $data;
    }

    /**
     * Returns a JSON representation of the current object
     *  
     * @return string
     */
    public function __toString() {

        return json_encode($this->toArray(),JSON_FORCE_OBJECT);

    }

}
