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
 * Represents an Object-Relational model
 * @author andrew
 *
 */
use org\frameworkers\furnace\connections\Connections;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\persistance\FurnaceType;
use vendors\com\thresholdstate\spyc\Spyc;


class Model {

	public $metadata;

	public $objects;
	
	protected function __construct() {
		$this->objects  = array();
		$this->metadata = array();
		
	}
	
	public static function Get() {
		static $model;
		
		if (!$model) {
			$model = new Model();
			$contents = Spyc::YAMLLoad(Config::Get('applicationModelsDirectory').'/orm/model.yml');
			$model->load(
				Spyc::YAMLLoad(Config::Get('applicationModelsDirectory').'/orm/model.yml'));
			ksort($model->objects);
		}
		
		return $model;
	}
	
	public function export($format) {
		//sql,php,yml
	}
	
	
	public static function DescribeDatasource($label = 'default') {
		$description = array();
		$tables = Connections::Get($label)->getTables();
		
		foreach ($tables as $tableName) {
			$description[Lang::ToTableName($tableName)] = 
				Connections::Get($label)->describeTable($tableName);
		}
		
		return $description;
	}
	
	public function load($contents) {
		
		// Each "top level" element is an object definition
		foreach ($contents as $label => $objectdata) {
			
			$obj = new Object($label);
			
			// Each "second level" element is either an attribute,
			// or metadata definition. Relations can not yet be
			// parsed because not all object definitions have been 
			// parsed yet.
			foreach ($objectdata as $label => $content) {
				
				// Add object attributes
				if ($label[0] != '_') {
					// Parse the content string
					$parsedContents = $this->parseContentString($label,$content);
					// Create a new Attribute object
					$attr = new Attribute(Lang::ToAttributeName($label),
						$parsedContents['type'],$parsedContents);
					// Add the new Attribute to the object
					$obj->addAttribute($attr);
				}
				
				// Add object metadata
				switch (strtoupper($label)) {
					case '_TABLE':
						$parsedContents   = $this->parseContentString($label,$content);
						$obj->setMetadata('table',$parsedContents);
						$obj->table->name = Lang::ToTableName($obj->getMetadata('table'));
						break;
					case '_DESCRIPTION':
						$obj->description = $this->parseContentString($label,$content);
						break;
					default:
						break;
				}
			}
			// Add the object to the model's array of objects
			$this->objects[$obj->className] = $obj;
		}
		
		// Object sanity check. Implement assumptions regarding primary key
		// attributes. Specifically: if no attribute yet parsed has been declared
		// a primary key, add a new primary key attribute named <objname>Id of
		// type integer and set it to be primary and autoincrementing.
		foreach ($this->objects as $obj) {
			
			// Ensure primary key attribute has been defined, and define
			// one if not.
			if (empty($obj->primaryKeyAttributes)) {
				$name = $obj->className . "Id";
				$type = "INTEGER";
				$data = array("primary"=>true,"autoincrement"=>true);
				$attr = new Attribute($name,$type,$data);
				$obj->addAttribute($attr);
			}
		} 
			
		
		// Parse the objectdata again, this time building a 
		// complete list of the relations linking different
		// objects to one another
		foreach ($contents as $label => $objectdata) {
			// Get a reference to the stored object
			$obj =& $this->objects[Lang::ToClassName($label,(strpos($label,'_') !== false))];
			
			foreach ($objectdata as $label => $content) {
				if (!is_array($content)) { $content = array($content); }
				switch (strtoupper($label)) {
					case '_HASONE':
						foreach ($content as $c) {
							
						}
						break;
					case '_HASMANY':
						foreach ($content as $c) {
							$parsedContents = $this->parseContentString($label, $c);
							$rel = new Relation($parsedContents['name'],$obj,$parsedContents);
							$obj->addRelation($rel);
						}
						break;
					case '_BELONGSTO':
						foreach ($content as $c) {
							$parsedContents = $this->parseContentString($label, $c);
							$rel = new Relation($parsedContents['name'],$obj,$parsedContents);
							$obj->addRelation($rel);
							// Also store the reciprocal relation
							$recip = $rel->getReciprocalRelation();
							if ($recip) {
								// Add the reciprocal relation to the remote object
								$this->objects[$rel->remoteObjectClassName]->addRelation($recip);
							}
						}
						break;	
				}
			}
		}
		
		// Relation sanity check
		foreach ($this->objects as &$object) {
			foreach ($object->relations as &$rel) {
				// Ensure all 1-M relations have a reciprocal (M-M relations handled elsewhere)
				if ($rel->type == Relation::ORM_RELATION_HASMANY && empty($rel->lookupObject)) {
					$foreign =& $this->objects[$rel->remoteObjectClassName];
					$matches = array();
					foreach ($foreign->relations as &$frel) {
						if ($frel->type == Relation::ORM_RELATION_BELONGSTO &&
							empty($frel->lookupObject) && 
							$frel->remoteObjectClassName == $rel->localObjectClassName) {
							$matches[] = $frel;	
						}
					}
					if (empty($matches)) {
						die("PARSE ERROR: no reciprocal `belongs to` relation defined in {$rel->remoteObjectClassName} for '{$object->className} - has many - {$rel->remoteObjectClassName} ({$rel->name})' ");
					}
					if (count($matches) == 1) {
						$rel->reciprocalRelation = $matches[0];
					}
					if (count($matches) > 1) {
						die('parser checks for ;reciprocal not implemented yet ');
					}
				}
			}
		}
	}
	
	public function parseContentString($label,$content) {
		
		$interpreted = array();
		
		$parts = explode(';',$content);
		
		// Interpret attribute definition content strings
		if ($label[0] != '_') {
			$interpreted = $this->interpretAttribute($parts);
		
		// Interpret relation and metadata definition content strings
		} else {
			switch (strtoupper($label)) {
				case "_TABLE":
					$interpreted = Lang::ToTableName($parts[0]);
					break;
				case "_DESCRIPTION":
					$interpreted = Lang::ToValue($parts[0]);
					break;
				case "_BELONGSTO":
					$interpreted = $this->interpretRelation(Relation::ORM_RELATION_BELONGSTO,$parts);
					$interpreted['type'] = Relation::ORM_RELATION_BELONGSTO;
					break;
				case '_HASMANY':
					$interpreted = $this->interpretRelation(Relation::ORM_RELATION_HASMANY,$parts);
					$interpreted['type'] = Relation::ORM_RELATION_HASMANY;	
					break;
			}
		}
		// Return the result
		return $interpreted;	
	}
	
	protected function interpretAttribute($parts) {
		$interpreted = array();
		if (($start = strpos($parts[0],'(')) !== false &&
			($end   = strpos($parts[0],')')) !== false) {
			$len    = strlen($parts[0]);
			$interpreted["type"] = strtoupper(substr($parts[0],0,$start));
			$interpreted["max"]  = substr($parts[0],$start+1,($len-1)-($start+1));
		} else {
			$interpreted["type"] = strtoupper($parts[0]);
		}
		unset($parts[0]);
		foreach ($parts as $part) {
			if ($part == '') continue;
			if (strpos($part,'=') !== false) {
				list ($key,$value)  = explode('=',$part);
				$interpreted[Lang::ToCamelCase($key)]  = Lang::ToValue($value);
			} else {
				$interpreted[Lang::ToCamelCase($part)] = true;
			}
			
		}
		return $interpreted;
	}
	
	protected function interpretRelation($type,$parts) {
		$interpreted = array();
		// If the first part has a [], then the modeler has
		// specified an attribute name for the relation, which
		// should be extracted
		if (($start = strpos($parts[0],'[')) !== false &&
			($end   = strpos($parts[0],']')) !== false) {
				$len    = strlen($parts[0]);
				$inner  = substr($parts[0],$start+1,($len-1)-($start+1)); // trim []
				list($local,$remote) = explode('|',$inner);
				$interpreted['name'] = Lang::ToAttributeName($local);
				if ($remote) { $interpreted['reciprocalName'] = Lang::ToAttributeName($remote); }
				$interpreted['remoteObjectClassName'] = Lang::ToClassName(substr($parts[0],0,$start));
		} 
		// If no [] are found, then the modeler has assumed that
		// the attribute name will be the remote object class name
		// (or an explicit name=... part will be found later and 
		// will override.
		else {
			$remoteObjectClassName = Lang::ToClassName($parts[0]);
			$remoteObjectClass     = $this->objects[$remoteObjectClassName];
			$remoteObjectClassPluralName = $remoteObjectClass->classPluralName;
			$interpreted['name'] = 
				($type == Relation::ORM_RELATION_HASMANY)
					? Lang::ToAttributeName($remoteObjectClassPluralName)
					: Lang::ToAttributeName($remoteObjectClassName);
			$interpreted['remoteObjectClassName'] = $remoteObjectClassName;
		}
		
		// Process all additional existing parts (starting with 1, not 0)
		$partlen = count($parts);
		for ($i = 1; $i < $partlen; $i++) {
			// If an equality operator exists, store the rhs
			if (strpos($parts[$i],'=') !== false) {
				list ($key,$value)  = explode('=',$parts[$i]);
				$interpreted[Lang::ToCamelCase($key)] = Lang::ToValue($value);
			// If no equality operator, assume boolean true for rhs
			} else {
				$interpreted[Lang::ToCamelCase($parts[$i])] = true;
			}
		}
		
		// Return the result
		return $interpreted;
	}
	
	public static function TableFor($className) {
		
		if (isset($this->tables[$className])) {
			return $this->tables[$className];
		} else if (false != ($table = Connections::Get()->describeTable(
			Model::Get()->objects[$className]->tableName))) {
			return $table;
		} else {
			// Build a Table object for an object whose table does not yet exist ;)
			if (false !== ($object = Model::Get()->objects[$className])) {
				$table = new Table($object->tableName);
				
				// add all attr and rel fields
				
			} 
		}
		return false;
	}
	

	public static function PrimaryKeysFor($className,$askingClassName = null) {
		// return a Column object representing the primary key for the requested class
		// asking class name is useful when the className is a class with more
		// than one primary key. The asking class name can be used for additional 
		// context in deciding which key to return
		if (false != ($obj = Model::Get()->objects[$className])) {

				return array("attrKeys"=>$obj->primaryKeyAttributes,
							 "relKeys" =>$obj->primaryKeyRelations);

		}
		return false;
	}
	
	public static function AttrForColumn($className,$columnName) {
		if (false != ($obj = Model::Get()->objects[$className])) {
			foreach ($obj->attributes as $attr) {
				if ($attr->column->name == $columnName) {
					return $attr;
				}
			}
		}
		return false;
	}
	
}
