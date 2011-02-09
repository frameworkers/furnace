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
 * Defines an ORM Object
 * @author andrew
 *
 */
class Object {
	
	public $className;
	public $classPluralName;
	public $parentClassName;
	public $metadata = array();
	
	public $table;
	
	public $attributes;
	public $relations;
	
	public $primaryKeyAttributes = array();
	public $primaryKeyRelations  = array();
	
	public function __construct($label) {
		if (strchr($label,'/')) {
			list($singular,$plural) = explode('/',$label);
			$this->className       = Lang::ToClassName($singular,strpos($singular,'_') !== false);
			$this->classPluralName = Lang::ToClassName($plural,strpos($plural,'_') !== false);
		} else {
			$this->className       = Lang::ToClassName($label,strpos($label,'_') !== false);
			$this->classPluralName = $this->className . 's';
		}
		$this->parentClassName = '\org\frameworkers\furnace\persistance\orm\model\BaseObject';
		$this->attributes      = array();
		$this->relations       = array();
		$this->primaryKeyAttributes = array();
		$this->primaryKeyRelations  = array();
		
		// Initialize the table to a new, empty table object
		$this->table = new Table($this->className);
	}
	
	public function setMetadata($key,$value) {
		$this->metadata[Lang::ToCamelCase($key)] = $value;
	}
	
	public function getMetadata($key) { 
		return isset($this->metadata[$key])
			? $this->metadata[$key]
			: null;
	}
	
	public function addAttribute(Attribute $attr) {
		// Create a Column object for the attribute if it doesn't
		// have one yet
		if (empty($attr->column)) {
			$col = new Column(Lang::ToColumnName($attr->name),
				$attr->type,false,$attr->isPrimary,$attr->metadata);
			$attr->setColumn($col);
		}
		// Add the attribute to the list for this object
		$this->attributes[$attr->name] = $attr;
		if ($attr->isPrimary) {
			$this->primaryKeyAttributes[] = $attr;
		}
		
		// Add the attribute's column to the table for this object
		$this->table->addColumnObject($attr->column);
	}
	
	public function addRelation(Relation $rel) {
		// Create any necessary Column objects for this object
		// if the relation requires it, and add them to this
		// object's table
		
		// Add the relation to the list for this object
		$this->relations[$rel->name] = $rel;
		if ($rel->isPrimary) {
			$this->primaryKeyRelations[] = $rel;
		}
		
		// Add the relation columns to the table for this object
		if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {
			$this->table->addColumnObject($rel->localKeyColumn);
		}
	}
}