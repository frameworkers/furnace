<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\core;

class Object extends \org\frameworkers\furnace\core\Object {
	
	protected $_primaryKeys = array();
	protected $_dirtyTable  = array();
	
	public function __construct() {
		
	}
	
	public function _id() {
		
	}
	
	public function toArray() {
        return $this->_processArray(get_object_vars($this));
    }
    
	public function __toString() {
        return json_encode($this->toArray(),JSON_FORCE_OBJECT);
    }
   
    private function _processArray($array) {
        foreach($array as $key => $value) {
            if (is_object($value)) {
                $array[$key] = $value->toArray();
            }
            if (is_array($value)) {
                $array[$key] = $this->_processArray($value);
            }
        }
        // If the property isn't an object or array, leave it untouched
        return $array;
    }
}