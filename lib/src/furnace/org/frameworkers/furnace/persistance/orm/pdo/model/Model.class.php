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

	public $metadata = array();
	public $objects  = array();
	
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
	
		
	public function load($rawContents) {
		
		// Pass 1: Cleaning Phase
		$contents = $this->cleanAndDetectObjects($rawContents);
		
		// Pass 2: Metadata Detection
		$this->detectMetadata($contents);
		
		// Pass 3: Attribute Detection
		$this->detectAttributes($contents);
		
		// Pass 4: Relationship Detection
		$this->detectRelations($contents);
		
		// Pass 5: Relationship Mapping
		$this->mapRelations($contents);
		
		// Sanity Check
		$this->sanityCheck();
	}
	
	public function cleanAndDetectObjects($contents) {
		$cleaned = array();
		foreach ($contents as $label => $objectdata) {
			// Each "top level" element is an object definition
			$properObjectLabel = Lang::ToClassName($label);
			$cleaned[$properObjectLabel] = array();
			foreach ($objectdata as $alabel => $content) {
				if ($alabel[0] != '_') {
					$properAttrLabel = Lang::ToAttributeName($alabel);
				} else {
					$properAttrLabel = $alabel;
				}
				// Collapse second level arrays
				if (is_array($content) ) { 
					$content = "[{$content[0]}]";
				}
				$cleaned[$properObjectLabel][$properAttrLabel] = $content;
			}
			
			// Add the detected object to the model array
			$this->objects[$properObjectLabel] = 
				new Object(Lang::ToClassName($label,true));
		}
		return $cleaned;
	}	
	
	public function detectMetadata(&$contents) {
		foreach ($contents as $olabel => &$objectdata) {
			// Each "second level" element is either an attribute,
			// relation, or metadata definition.
			foreach ($objectdata as $alabel => $content) {
				// Explode the string into tokens based on `;`
				$parts = explode(';',$content);

				if ('_' == $alabel[0]) {
					switch (strtoupper($alabel)) {
						case '_TABLE':
							$this->objects[$olabel]->table->name = Lang::ToTableName($parts[0]);
							break;
						case '_DESC':
						case '_DESCRIPTION':
							$this->objects[$olabel]->description = Lang::ToValue($parts[0]);
							break;
					}
					unset($objectdata[$alabel]);
				}
			}
		}	
	}
	
	public function detectAttributes($contents) {
		foreach ($contents as $olabel => $objectdata) {
			// Each "second level" element is either an attribute,
			// relation, or metadata definition.
			foreach ($objectdata as $alabel => $content) {
				$interpreted = array();
				if ($content[0] != '[') {
					// Explode the string into tokens based on `;`
					$parts = explode(';',$content);
					
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
					// Create an Attribute object from the tokenized information
					$this->objects[$olabel]->addAttribute(
						new Attribute($alabel,$interpreted['type'],$interpreted));
				}
			}
		}
	}
	
	public function detectRelations($contents) {
		
		// Parse the objectdata again, this time building a 
		// complete list of the relations linking different
		// objects to one another
		foreach ($contents as $olabel => $objectdata) {
			foreach ($objectdata as $alabel => $contentString) {
				
				if ($contentString[0] == '[') {
					
					$obj = $this->objects[$olabel];
					
					//          [ClassName(ORDINAL)(FOREIGNKEY)]
					$regex = "/\[([A-Za-z]+)\(([1|\*])\)\(([A-Za-z]+)\)\]/";
					preg_match($regex,$contentString,$matches);
					list ($matchedContent,$foreignClass,$ordinal,$foreignKey) = $matches;
					if (!$matches) { die("Malformed relation detected: {$alabel}:{$contentString}");}
					
					// Get any metadata associated with the content string
					$metadata = explode(';',$contentString);
					
					$relation = new Relation(Lang::ToAttributeName($alabel));
					$relation->localType       = ('*' == $ordinal) 
						? Relation::ORM_RELATION_MANY 
						: Relation::ORM_RELATION_ONE;
					$relation->locallyRequired = ('*' == $ordinal) ? false : true;
					$relation->locallyPrimary  = in_array('primary',$metadata);
					
					$relation->localObjectClassName  = $obj->className;
					$relation->remoteObjectClassName = Lang::ToClassName($foreignClass);
						
					$relation->localObjectTable     = $obj->table->name;
					$relation->remoteObjectLabel    = $foreignKey;
					$relation->remoteObjectTable    = $this->objects[$relation->remoteObjectClassName]
														->table->name;
					
					$this->objects[$olabel]->addRelation($relation);	
				}
			}
		}
	}
	
	public function mapRelations($contents) {
		// Connect VIA's and REVERSE_OF's to their counterparts
		foreach ($contents as $olabel => $objectdata) {
			foreach ($objectdata as $alabel => $contentString) {
				if ($contentString[0] == '[') {
					
					// Get the referenced relation from the data structure
					$relation = $this->objects[$olabel]->relations[$alabel];	

					// Set remote object column information
					// Consistency Check: Ensure remote object label maps to either 
					// an attribute or a (1) relation
					if (!isset($this->objects[$relation->remoteObjectClassName]->attributes[$relation->remoteObjectLabel])) {
						if (!isset($this->objects[$relation->remoteObjectClassName]->relations[$relation->remoteObject])) {
							die("Malformed relation detected: {$alabel}:{$contentString} " 
								. " - cause: remote object label `{$relation->remoteObjectLabel}` "
								. " does not map to an attribute or relation in `{$relation->remoteObjectClassName}` ");
						} else {
							if ($this->objects[$relation->remoteObjectClassName]->relations[$relation->remoteObjectLabel]->localType != Relation::ORM_RELATION_ONE) {
								die("Malformed relation detected: {$alabel}:{$contentString} "
									. " - cause: remote object label `{$relation->remoteObjectLabel}` "
									. " maps to a (*) relation in `{$relation->remoteObjectClassName}` ");
							} else {
								// remote object column information is the column info for the remote (1) relation
								// TODO
								die("Using a (1) Relation as the remote object label for another relation is not supported yet");
							}
						}
					} else {
						// remote object column information is the column info for the remote attribute
						$relation->remoteObjectColumn = 
							$this->objects[$relation->remoteObjectClassName]
								->attributes[$relation->remoteObjectLabel]
									->column;
						
					}

					/***
					 * PARSE OPTIONAL LOOKUP TABLE TOKENS
					 ***/
					//             VIA ClassName(LOCALKEY|FOREIGNKEY)
					$regexVia = "/ VIA ([A-Za-z]+)\s*\(([A-Za-z]+)\|([A-Za-z]+)\)/";
					preg_match($regexVia,$contentString,$matchesVia);
					if ($matchesVia) {
						$relation->remoteType = Relation::ORM_RELATION_MANY;
						$relation->remotelyRequired = false;
						$relation->remotelyPrimary  = false;
						list ($matchedContent,$lookupClass,$localKey,$remoteKey) = $matchesVia;
						$relation->lookupObjectClassName  = Lang::ToClassName($lookupClass); 
						
						// Consistency check: Ensure the referenced local lookup relation exists in the lookup object
						if (false != ($rel = $this->objects[$lookupClass]->relations[$localKey])) { 
							$rel->remoteName = $alabel;
							$rel->remoteType = Relation::ORM_RELATION_MANY;
							$relation->lookupObjectLocalRel   = $rel;
						} else {
							die("Malformed relation detected: {$alabel}:{$contentString} "
								. " - cause: VIA references a non-existant relation");
						}
						
						// Consistency check: Ensure the referenced remote lookup relation exists in the lookup object
						if (false != ($rel = $this->objects[$lookupClass]->relations[$remoteKey])) {
							if (false != ($recipRel = $this->getViaReciprocal($relation))) {
								$rel->remoteName = $recipRel->localName;
							} else {
								die("Malformed relation detected: {$alabel}:{$contentString} "
									. " - cause: No matching VIA found elsewhere in model.");
							}
							$rel->remoteType = Relation::ORM_RELATION_MANY;
							$relation->lookupObjectRemoteRel = $rel;
						} else {
							die("Malformed relation detected: {$alabel}:{$contentString} "
								. " - cause: VIA references a non-existant relation");
						}
						
						$relation->lookupObjectTable      = $this->objects[$relation->lookupObjectClassName]
															->table->name;

					} 
					
					//                 REVERSE_OF ClassName.foreignKey
					$regexReverse = "/ REVERSE_OF ([A-Za-z]+)\.([A-Za-z]+)/";
					preg_match($regexReverse,$contentString,$matchesReverse);
					if ($matchesReverse) {
						list($matchedContent,$foreignClass,$foreignKey) = $matchesReverse;
						
						// Consistency Check: Ensure the REVERSE_OF class matches the remote object class
						if (($relation->remoteObjectClassName != $foreignClass )) {
							die("Malformed relation detected:  {$alabel}:{$contentString} "
								. " - cause: class for REVERSE_OF does not match remote class for relation");
						}
						
						// Consistency Check: Ensure the referenced REVERSE_OF relation exists
						if (($reciprocalRelation = $this->getRelation($foreignClass,$foreignKey)) != false) {
							
							// Consistency Check: if the REVERSE_OF relation is also locally (*),
							// Then we need to use a VIA instead, since this is a M-M relationship
							if ($reciprocalRelation->localType == Relation::ORM_RELATION_MANY) {
								die("Malformed relation detected: {$alabel}:{$contentString} "
									. " - cause: REVERSE_OF references a (*) relation, implies many-to-many which requires a VIA");
							}
							
							$relation->remoteName = $reciprocalRelation->localName;
							$relation->remoteType = $reciprocalRelation->localType;
							$relation->remotelyRequired = $reciprocalRelation->locallyRequired;
							$relation->remotelyPrimary  = $reciprocalRelation->locallyPrimary;
							// And now in reverse, to complete the mapping:
							$reciprocalRelation->remoteName = $relation->localName;
							$reciprocalRelation->remoteType = $relation->localType;
							$reciprocalRelation->remotelyRequired = $relation->locallyRequired;
							$reciprocalRelation->remotelyPrimary  = $relation->locallyPrimary;
							
							
						} else {
							die("Malformed relation detected: {$alabel}:{$contentString} " 
								." - cause: REVERSE_OF references non-existant relation");
						}
					} 
					
					// Consistency Check: Ensure all (*) relations expressly specify either
					// a VIA or a REVERSE_OF clause
					if ($relation->localType == Relation::ORM_RELATION_MANY && 
						(!$matchesVia && !$matchesReverse)) {
						die("Malformed relation detected: {$alabel}:{$contentString} "
							." - cause: (*) without VIA or REVERSE_OF");		
					}
				}
			}
		}
		
		// Make sure all (1) -> (*) relations have a localObjectColumn defined
		foreach ($this->objects as $o) {
			foreach ($o->relations as $r) {
				if ($r->localType == Relation::ORM_RELATION_ONE) {
					$name = $r->localName;                     // The name of the relation
					$type = $r->remoteObjectColumn->type; // The type of the column this maps to
					$primary = ($r->remoteType == Relation::ORM_RELATION_MANY 
						&& $r->locallyPrimary);
					
					$c = new Column($name, $type, false, $primary, array("type"=> $type));
					
					$this->objects[$o->className]->relations[$r->localName]->localObjectColumn = $c;
					$this->objects[$o->className]->table->addColumnObject($c);
				} 
				
			}
		}
	}
		
	public function sanityCheck() {	
		// Object sanity check. 
		foreach ($this->objects as $obj) {
			
			// Implement assumptions regarding primary key
			// attributes. Specifically: if no attribute yet parsed has been declared
			// a primary key, add a new primary key attribute named <objname>Id of
			// type integer and set it to be primary and autoincrementing.
			if (empty($obj->primaryKeyAttributes) && empty($obj->primaryKeyRelations)) {
				$name = $obj->className . "Id";
				$type = "INTEGER";
				$data = array("primary"=>true,"autoincrement"=>true);
				$attr = new Attribute($name,$type,$data);
				$obj->addAttribute($attr);
			}
			
			// Ensure that all (*) relations have either a `VIA` or a `REVERSE_OF` clause
			// Ensure that no  (*) relation is marked as locally primary
			
			// Ensure that all Many-To-Many relations are correctly mapped in both directions
			// M-M relations can be identified by having a non-null `lookupObjectClassName`
			foreach ($obj->relations as $rname => $rdata) {
				if ($rdata->lookupObjectClassName) {
					// Ensure the remote object's relation points back to this relation
					
				}
			}
		}
	}
	
	public function TableFor($className) {
		
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
	
	public function getRelation($className,$name) {
		if (($o = $this->objects[Lang::ToClassName($className)]) != false) {
			return (isset($o->relations[Lang::ToAttributeName($name)]))
				? $o->relations[Lang::ToAttributeName($name)]
				: false;
		}
	}
	
	// All VIA relations must have a reciprocal defined, which is differentiated
	// only by the order in which the two relations appear: e.g:
	// for Budget <--Acl--> User (budgets have many users using Acl as lookup)
	//   Budget:
	//     users:  [User(*)(id)] VIA Acl(budget|user)
	//   User:
	//     budgets:[Budget(*)(id)] VIA Acl(user|budget)
	public function getViaReciprocal($rel) {
		// We're looking for a relation between the same to objects, using the same
		// intermediate lookup table, with only the `lookupObjectLocalRel` and 
		// `lookupObjectRemoteRel` swapped
		foreach ($this->objects as $o) {
			foreach ($o->relations as $r) {
				if ($r->localType == $rel->localType &&                         // both M-M
					$r->localObjectClassName == $rel->remoteObjectClassName &&  // with opposite local & remote objects
					$r->remoteObjectClassName == $rel->localObjectClassName     // this may need to be made more specific
					 ) {
					return $r;
				}
			}
		}
		return false;
	}
	
	/*
	public function isKeyForVia($rel) {
		$lo = $this->objects[$rel->localObjectClassName];
		$fo = $this->objects[$rel->remoteObjectClassName];
		// Does the fo have a VIA on the lo using rel->localName as a key?
		var_dump($rel);
		foreach ($fo->relations as $r) {
			if ($r->localType == Relation::ORM_RELATION_MANY
				&& $r->remoteType == Relation::ORM_RELATION_MANY
				&& $r->remoteObjectClassName = $lo->className) {
				return true;		
			}
		}
		return false;
	}
	*/
}
