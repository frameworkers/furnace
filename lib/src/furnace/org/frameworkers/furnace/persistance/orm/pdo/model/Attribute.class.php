<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\model;
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
/**
 * Represents an Object-Relational model object attribute
 * @author andrew
 *
 */
class Attribute {
	
	public $name;
	public $type;
	public $description;
	public $metadata = array();
	public $key;
	public $isPrimary;
	public $isAutoinc;
	
	public $column;
	
	
	public function __construct($name,$type,$metadata = array()) {
		$this->name = Lang::ToAttributeName($name);
		$this->type = $type;
		$this->description = isset($metadata['description'])
			? $metadata['description']
			: '';
		$this->metadata  = $metadata;
		$this->key       = isset($metadata['key'])
			? $metadata['key']
			: null;
		$this->isPrimary = isset($metadata['primary']) && ($metadata['primary'])
			? true
			: false;
		$this->isAutoinc = isset($metadata['autoincrement']) && ($metadata['autoincrement'])
			? true
			: false;
		if ($this->isPrimary) { 
			$this->key = "PRIMARY";
			$this->description = "PRIMARY" . (($this->isAutoinc) ? ', auto-incrementing' : '');  
		}
		
		 
	}
	
	public function setColumn(Column $column) {
		$this->column  = $column;
	}	
}