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
 * Describes a relation between two ORM objects
 * @author andrew
 *
 */
class Relation {
	
	const ORM_RELATION_HASMANY=   'Has Many';
	const ORM_RELATION_BELONGSTO= 'Belongs To';
	
	public $localObjectClassName;
	public $remoteObjectClassName;
	
	public $name;
	public $metadata;
	
	public $type;
	public $isRequired = false;
	public $isPrimary  = false;
	
	public $localObjectTable;
	public $remoteObjectTable;
	
	public $lookupObject;
	public $reciprocalRelation;
	
	public $localKeyColumn;
	
	public $remoteKeyAttr;
	public $remoteKeyColumn;
	
	public function __construct($name,$objOwner,$data = array()) {
		// Store the name for this relation
		$this->name     = Lang::ToAttributeName($name);
		$this->metadata = $data;
		
		// Store type information
		$this->type = $data['type'];
		
		// Store whether this relation is required or not
		$this->isRequired = (isset($data['required']) || isset($data['primary']));
		
		// Store whether this relation is a primary key for the local object
		$this->isPrimary  = (isset($data['primary']));
		
		// Store the owner object information
		$this->localObjectClassName  = $objOwner->className;
		$this->remoteObjectClassName = $data['remoteObjectClassName'];		
		
		// ORM_RELATION_HASMANY
		if ($this->type == Relation::ORM_RELATION_HASMANY) {
			// This is a M-M relation with an intermediate lookup object
			if (isset($data['via'])) {
				// Determine lookup information
				$this->lookupObject = Lang::ToClassName($data['via'],true);
				// Determine local key column    -- there is none, maybe local  lookup key column
				// Determine remote key column   -- there is none, maybe remote lookup key column
				
				
			}
			// This is a 1-M relation
			// These can ONLY be generated *internally* in response to the
			// modeler creating a "Belongs To" relation and specifying a 
			// reciprocal name.
			else {
				// Determine lookup information  -- there is none
				// Determine local key column    -- there is none
				// Determine remote key column
				$this->remoteKeyColumn =  $data['remoteKeyColumn'];
			}
			
		// ORM_RELATION_BELONGSTO	
		} else {

			if (isset($data['key'])) {
				// Explicitly overriding the foreign key. Also check if there are
				// commas in the provided value, meaning we have to generate more
				// than one column on the local object to fully map to all remote keys!
			} else {
				// Primary key detection on the remote object to determine the 
				// remote key.
				$remotePrimaryKeys = Model::Get()->PrimaryKeysFor($this->remoteObjectClassName,$this->localObjectClassName);

				// The remote object has multiple primary keys defined
				if (count($remotePrimaryKeys['attrKeys']) + count($remotePrimaryKeys['relKeys']) > 1) {
					die("not implemented yet Relation.class.php:87");
				}
				
				// The remote object has EXACTLY 1 primary key defined, then
				else {
					// If the key corresponds to an attribute
					if (count($remotePrimaryKeys['attrKeys']) == 1) {
						$this->remoteKeyColumn = $remotePrimaryKeys['attrKeys'][0]->column;
						$this->remoteKeyAttr   = $remotePrimaryKeys['attrKeys'][0];
					}
					//   Else the key corresponds to a relation
					else {
						$this->remoteKeyColumn = $remotePrimaryKeys['relKeys'][0]->localKeyColumn;
					}
					// In either case, a local key column needs to be generated:
					$this->localKeyColumn  = new Column(
						$this->remoteKeyColumn->name,
						$this->remoteKeyColumn->type,
						false,
						true,
						$this->remoteKeyColumn->extra);
				}
			}
		}
	}
	
	public function getReciprocalRelation() {
		if ($this->reciprocalRelation) {
			return $this->reciprocalRelation;
		}
		
		// Generate a reciprocal relation for this relation, if needed
		if ($this->type == Relation::ORM_RELATION_BELONGSTO) {
			// ORM_RELATION_BELONGSTO relations may have a reciprocal
			// relation of type ORM_RELATION_HASMANY. The name for the
			// reciprocal relation, if one is desired, will exist in 
			// the key `reciprocalName`. If this does note exist, no
			// reciprocal relation will be generated, so return false.
			if (!isset($this->metadata['reciprocalName'])) {
				return false;
			}
			
			// Build a data array for the reciprocal relation
			$data = array(
				"type" => Relation::ORM_RELATION_HASMANY,
				"name" => $this->metadata['reciprocalName'],
				"remoteObjectClassName"    => $this->localObjectClassName,
				"remoteKeyColumn"          => $this->localKeyColumn
			);
			
			// Construct and return the relation
			$rel = new Relation($this->metadata['reciprocalName'],
				Model::Get()->objects[$this->remoteObjectClassName],
				$data);
			return $this->reciprocalRelation = $rel;
		}
	}
}