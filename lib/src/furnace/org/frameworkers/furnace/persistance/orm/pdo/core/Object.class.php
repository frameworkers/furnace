<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\core;

class Object extends \org\frameworkers\furnace\core\Object {
	
	protected $_primaryKeys = array();
	protected $_dirtyTable  = array();
	
	public function __construct() {
		
	}
	
	public function _id() {
		
	}
    
	public function __toString() {
        return json_encode($this->toArray(),JSON_FORCE_OBJECT);
    }
}