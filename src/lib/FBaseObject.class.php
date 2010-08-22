<?php
/*
 * frameworkers-foundation
 * 
 * FBaseObject.class.php
 * Created on May 20, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 * 
 */
 
  /*
  * Class: FBaseObject
  * An abstract base class for all objects generated from a Furnace
  * model.
  */
 abstract class FBaseObject {
 	
 	protected $id;
 	
 	protected $fObjectType;
 	protected $fObjectTableName;
 	protected $fObjectModelData;
 	
 	// Internal Variable: validator
 	// The validator is the object's FValidator instance
 	public $validator;
 	
 	// Internal Variable: _dirtyTable
 	// The dirty table keeps track of which fields need to be saved on update
 	public $_dirtyTable;
 	
 	
 	public function getId() {
 		return $this->id;
 	}
 	
 	public function touch() {
 	    // Set the 'modified' attribute if it has been defined
		if (property_exists($this->fObjectType,'modified')) {
			$this->setModified(date('Y-m-d G:i:s')); 
		}
 	}
 	
 	/**
 	 * 
 	 * @param $objectType
 	 * @param $requiredAttributes
 	 * @param $additionalData
 	 * @return unknown_type
 	 * 
 	 * NOTE: Create does not actually save anything to the database, it merely constructs a new instance
 	 * of the object with the given data. To persist this newly created object to the database, a ::save()
 	 * call must be issued.
 	 */
 	public static function Create($objectType, $requiredAttributes, $additionalData) {
 		// Build the data array to pass to the object constructor
 		$data = array_merge($additionalData,$requiredAttributes);	// Prefer values in requiredAttributes
 		$data['id'] = 0;
 		
 		return new $objectType($data);
 	}
 	
 	
 	public function save($data = array(), $bValidate = true) {
 		
 		// Merge $data into the object
 		$properties = get_object_vars($this);
		foreach ($data as $k => $v) {
			if (isset($this->_dirtyTable[$k])) { continue; } // don't overwrite manual changes
			$realK = $k;
			if (false !== ($underscorePos = strpos($k,'_id'))) {
				$realK = substr($k,0,$underscorePos);
			}
			try {
				if (array_key_exists($realK,$properties)) { 
					$this->$realK = $v;
					$this->_dirtyTable[$realK] = $v;
				} 
			} catch (Exception $e) { /* silently ignore */ }
		}
		
 	    if ($bValidate) {
 			// if requested, validate the object before saving
 			$objectType = $this->fObjectType;
 			_model()->$objectType->validate($this);
 		}
 		
 		// In any event, do nothing if this is not a valid object
		if (!$this->validator->valid) { return false; }
		
		// Determine whether to 'create' or 'update'
		if ( $this->id == 0 ) {
			
			// Set the 'created' attribute if it has been defined
			if (property_exists($this->fObjectType,'created')) {
 				$this->setCreated(date('Y-m-d G:i:s')); 
 			}
 			
			// Set the 'modified' attribute if it has been defined
			if (property_exists($this->fObjectType,'modified')) {
 				$this->setModified(date('Y-m-d G:i:s')); 
 			}
			
			// Create a new object in the database
			$q  = "INSERT INTO `{$this->fObjectTableName}` ";
			$q .= "({$this->buildSqlUniqueAttributeList()}) ";
			$q .= "VALUES ({$this->buildSqlUniqueAttributeValueList()}) ";
			$r = _db()->rawExec($q);
			$this->id   = _db()->lastInsertID($this->fObjectTableName,"{$this->fObjectTableName}_id");
			return true; 
		} else {
			return $this->update($data,false);	// Validation already handled above
		}
 	}
 	
 	
 	
 	/* 
 	 * HELPER FUNCTIONS
 	 */
 	
 	private function update($data = array(),$bValidate = false) {
 		if ($this->id == 0) { die("{$this->fObjectType}::update(...) called on non-existent object. Use {$this->fObjectType}::save(...) first"); }
 		if ($bValidate && !$this->validator->isValid($data)) { return false; }  // Invalid data
 		if (empty($data) && empty($this->_dirtyTable)) { return true; }         // Nothing to do
 		
 		// Update the 'modified' attribute if it has been defined
 		// NOTE: For FAccount-derived objects, 'modified' will have already been handled in 
 		// FAccountObject::save() and so does not need to be handled here.
 		if (property_exists($this->fObjectType,'modified') && !is_subclass_of($this,"FAccount")) {
 			$this->setModified(date('Y-m-d G:i:s')); 
 		}

 		// Update the database for the dirty values
 		$fieldsToSave = array();
 		$ot = $this->fObjectType;
 		$parents = _model()->$ot->parentsAsArray();
 		foreach ($this->_dirtyTable as $attr => $val) {
 		    $fieldsToSave[] = isset($parents[$attr])
 		        ? "`{$attr}_id` = '".addslashes($val)."' "
 		        : "`{$attr}`    = '".addslashes($val)."' ";
 		}
 		
 		// If nothing to save, don't bother making a round trip to the db
 		if (empty($fieldsToSave)) { return true; }
 		
 		$q = "UPDATE `{$this->fObjectTableName}` SET "
 			. implode(',',$fieldsToSave)
 			. " WHERE `{$this->fObjectTableName}`.`{$this->fObjectTableName}_id` = {$this->id} LIMIT 1";
 			
 		_db()->rawExec($q);
 		
 		return true;
 		
 		//TODO: FAccountSave
 		/** 
 		 * HANDLE FACCOUNT_SAVE by duplicating this method in FAccount.class.php
 		 */
 		
 		/*
 		// Update the object in the database
		if ("FAccount" == $object->getParentClass()) {
	 		$r .= "\t\t\tparent::faccount_save();\r\n";
	 	}
		} // end if count($object->getAttributes() > 0)
		else {
			$r .= "\t\t\t// No attributes defined\r\n";
		}
		$r .= "\t\t\treturn true;\r\n";
		$r .= "\t\t}\r\n\r\n";
		*/
 	}
 	
 	
 	protected function buildSqlUniqueAttributeList() {
 		$s = '`';
 		$arrayComponents = array();
 		$ot = $this->fObjectType;

 		foreach (_model()->$ot->parentsAsArray() as $p) {
 			$arrayComponents[] = "{$p['column']}";
 		}
 		foreach (_model()->$ot->attributeInfo() as $a) {
 			$arrayComponents[] = "{$a['column']}";
 		}

 		$s .= implode('`,`',$arrayComponents);
 		$s .= '`';
 		return $s;
 	}
 	
 	
     protected function buildSqlUniqueAttributeValueList() {
 		
 		$s = "'";
 		$arrayComponents = array();
 		$ot = $this->fObjectType;
 		
 		foreach (_model()->$ot->parentsAsArray() as $p) {
 		    $arrayComponents[] = is_object($this->$p['name']) 
 		        ? $this->$p['name']->getId()
 		        : $this->$p['name'];
 		}
 		foreach (_model()->$ot->attributeInfo() as $a) {
 			$arrayComponents[] = addslashes($this->$a['column']);
 		}
 		
 		$s .= implode("','",$arrayComponents);
 		$s .= "'";
 		return $s;
 	}
 	
 }
 
?>
