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
	
	const ORM_RELATION_ONE  = '1';
	const ORM_RELATION_MANY = 'M';
	
	// Relationship metadata
	public $localObjectClassName;
	public $remoteObjectClassName;
	
	public $localObjectTable;
	public $localObjectColumn;
	
	public $remoteObjectLabel;
	public $remoteObjectTable;
	public $remoteObjectColumn;
	
	public $lookupObjectClassName;
	public $lookupObjectTable;
	public $lookupObjectLocalRel;
	public $lookupObjectRemoteRel;
	
	public $localName;
	public $localType;
	public $locallyRequired  = true;
	public $locallyPrimary   = false;
	public $remoteName;
	public $remoteType;
	public $remotelyRequired = false;
	public $remotelyPrimary  = false;

	
	public function __construct($name) {
		$this->localName = Lang::ToAttributeName($name);
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