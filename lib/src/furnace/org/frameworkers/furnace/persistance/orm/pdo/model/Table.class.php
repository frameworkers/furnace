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
 * Represents an Object-Relational table
 * @author andrew
 *
 */
class Table {
	
	public $name;
	
	public $metadata    = array();
	
	public $columns     = array();
	
	public $tableExists = false;
	
	public function __construct($label) {
		$this->name = Lang::ToTableName($label);
	}
	
	public function addMetadata($key,$value) {
		$this->metadata[$key] = $value;
	}
	
	public function getMetadata($key) { 
		return isset($this->metadata[$key])
			? $this->metadata[$key]
			: null;
	}
	
	public function addColumn($name,
		 $type,
		 $isNull,
		 $isPrimary, /* should be a string key to support other keys */
		 $default,
		 $extra) {
		 	
		 $name = Lang::ToColumnName($name);
		 $this->columns[$name] = new Column($name, $type, $isNull, $isPrimary, $extra);

	}
	
	public function addColumnObject($colObj) {
		$this->columns[$colObj->name] = $colObj;
	}
	
	public function getColumns() {
		return $this->columns;
	}
}