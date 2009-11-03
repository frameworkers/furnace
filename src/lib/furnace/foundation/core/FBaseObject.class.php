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
 	
 	protected $objId;
 	
 	protected $fObjectType;
 	protected $fObjectTableName;
 	protected $fObjectModelData;
 	
 	// Internal Variable: validator
 	// The validator is the object's <<ObjectType>>Validator instance
 	public $validator;
 	
 	// Internal Variable: _dirtyTable
 	// The dirty table keeps track of which fields need to be saved on update
 	public $_dirtyTable;
 	
 	
 	public function getObjId() {
 		return $this->objId;
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
 		$data['objId'] = 0;
 		
 		$o = new $objectType($data);
 		if ($o->validator->isValid($data)) {
 			return $o;
 		} else {
 			return false;
 		}
 	}
 	
 	
 	public static function Delete($id,$class) {
 		
 		// Delete objects that depend on this object
 		$modelInfo =& _model()->$class;
 		foreach ($modelInfo['delete_info'] as $info) {
 			switch ($info['type']) {
 				case 'parent':
 					if ('yes' == $info['required']) {
 						// Delete objects of type $info['class'] that depend on this object
 						$q = "SELECT `{$info['sqltable']}`.`objId` FROM `{$info['sqltable']}` WHERE "
 							."`{$info['sqltable']}`.`{$info['sqlcol']}`={$id} ";
 						$r = _db()->query($q);
 						while ($data = $r->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
 							call_user_func(array($info['class'],"Delete"),$data['objId']);
 						}
 					} else {
 						// Clear references to this object for objects with a non-required parent relationship to this object
 						$q = "UPDATE `{$info['sqltable']}` SET `{$info['sqltable']}`.`{$info['sqlcol']}`='0' WHERE "
 							."`{$info['sqltable']}`.`{$info['sqlcol']}`={$id} ";
 						_db()->exec($q);
 					}
 					break;
 				case 'lookup':
 					// Clear entries in all lookup tables with references to this object
 					if ($this->fObjectType == $info['class']) {
 						$q = "DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{$id}' OR `{$info['sqlcol2']}`='{$id}'";
 					} else {
 						$q = "DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{$id}'";
 					}
 					_db()->exec($q);
 					break;
 				default:
 					die('Non-standard delete information encountered. FBaseObject');
 			}
 		}
 		
 			
 		return true;
 		// Delete objects that depend on this object
 		
 		/*
 		 * $delete_info = $this->determineDeleteInformationFor($object);
		foreach ($delete_info as $info ) {
			if ("parent" == $info['type']) {
				if ("yes" == $info['required']) {
					// Delete objects that depend on this object
					$r .= "\r\n\t\t\t// Delete {$info['class']} objects that depend on this object\r\n";
					$r .= "\t\t\t\$q= \"SELECT `{$info['sqltable']}`.`objId` FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{\$objId}'\";\r\n";
					$r .= "\t\t\t\$r= _db()->query(\$q);\r\n";
					$r .= "\t\t\twhile (\$data = \$r->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {\r\n"
						. "\t\t\t\t{$info['class']}::Delete(\$data['objId']);\r\n"
						. "\t\t\t}\r\n\r\n";
				} else {
					// Clear references to this object for objects with a non-required parent relationship to this object
					$r .= "\r\n\t\t\t// Delete {$info['class']} objects that have an optional parent relationship to this object\r\n";
					$r .= "\t\t\t\$q= \"UPDATE `{$info['sqltable']}` SET `{$info['sqltable']}`.`{$info['sqlcol']}`='0' WHERE `{$info['sqlcol']}`='{\$objId}'\";\r\n";
					$r .= "\t\t\t_db()->exec(\$q);\r\n";
				}
			} else if ("lookup" == $info['type']) {
				// Clear entries in all lookup tables with references to this object
				$r .= "\r\n\t\t\t// Delete entries in {$info['sqltable']} containing this object\r\n";
				if ($object->getName() == $info['class']) {
					$r .= "\t\t\t\$q = \"DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{\$objId}' OR `{$info['sqlcol2']}`='{\$objId}'\";\r\n";
				} else {
					$r .= "\t\t\t\$q = \"DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{\$objId}'\";\r\n";
				}
				$r .= "\t\t\t\$r = _db()->exec(\$q);\r\n\r\n";	
			}
		}
		$r .= "\r\n\t\t\t// Delete the object itself\r\n"
			. "\t\t\t\$q = \"DELETE FROM `".self::standardizeTableName($object->getName())."` WHERE `".self::standardizeTableName($object->getName())."`.`objId`='{\$objId}'\";\r\n"
			. "\t\t\t\$r = _db()->exec(\$q);\r\n";
		$r .= "\t\t}\r\n";
 		 */
 	}
 	
 	
 	public function save($data = array(), $bValidate = true) {
 		
 		if ($bValidate) {
 			// Validate all dirty attributes, and any data directly passed in
			$this->validator->isValid($this->_dirtyTable);
			$this->validator->isValid($data);
 		}
 		
 		// In any event, do nothing if this is not a valid object
		if (!$this->validator->valid) { return false; }
		
		
 		// Merge $data into the object
 		$properties = get_object_vars($this);
		foreach ($data as $k => $v) {
			if (isset($this->_dirtyTable[$k])) { continue; } // don't overwrite manual changes
			$realK = $k;
			if (false !== ($underscorePos = strpos($k,'_'))) {
				$realK = substr($k,0,$underscorePos);
			}
			try {
				if (array_key_exists($realK,$properties)) { 
					$this->$realK = $v;
					$this->_dirtyTable[$realK] = $v;
				} 
			} catch (Exception $e) { /* silently ignore */ }
		}
		
		// Determine whether to 'create' or 'update'
		if ( $this->objId == 0 ) {
			
			// Set the 'created' attribute if it has been defined
 			if (isset($this->fObjectModelData['attributes']['created'])) {
 				$this->setCreated(date('Y-m-d G:i:s')); 
 			}
 			
			// Set the 'modified' attribute if it has been defined
 			if (isset($this->fObjectModelData['attributes']['modified'])) {
 				$this->setModified(date('Y-m-d G:i:s')); 
 			}
			
			// Create a new object in the database
			$q  = "INSERT INTO `{$this->fObjectTableName}` ";
			$q .= "({$this->buildSqlUniqueAttributeList()}) ";
			$q .= "VALUES ({$this->buildSqlUniqueAttributeValueList()}) ";
			$r = _db()->exec($q);
			$this->objId   = _db()->lastInsertID($this->FObjectTableName,"objId");
			return true; 
		} else {
			return $this->update($data,false);	// Validation already handled above
		}
 	}
 	
 	
 	
 	/* 
 	 * HELPER FUNCTIONS
 	 */
 	
 	private function update($data = array(),$bValidate = false) {
 		if ($this->objId == 0) { die("{$this->fObjectType}::update(...) called on non-existent object. Use {$this->fObjectType}::save(...) first"); }
 		if ($bValidate && !$this->validator->isValid($data)) { return false; }  // Invalid data
 		if (empty($data) && empty($this->_dirtyTable)) { return true; }         // Nothing to do
 		
 		// Update the 'modified' attribute if it has been defined
 		if (isset($this->fObjectModelData['attributes']['modified'])) {
 			$this->setModified(date('Y-m-d G:i:s')); 
 		}

 		// Update the database for the dirty values
 		$fieldsToSave = array();
 		foreach ($this->_dirtyTable as $attr => $val) {
 		    $fieldsToSave[] = isset($this->fObjectModelData['parents'][$attr])
 		        ? "`{$attr}_id` = '".mysql_real_escape_string($val)."' "
 		        : "`{$attr}`    = '".mysql_real_escape_string($val)."' ";
 		}
 		$q = "UPDATE `{$this->fObjectTableName}` SET "
 			. implode(',',$fieldsToSave)
 			. " WHERE `{$this->fObjectTableName}`.`objId` = {$this->objId} LIMIT 1";
 			
 		_db()->exec($q);
 		
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
 	
 	
 	private function buildSqlUniqueAttributeList() {
 		$s = '`';
 		$arrayComponents = array();
 		foreach ($this->fObjectModelData['parents'] as $p) {
 			$arrayComponents[] = "{$p['sqlname']}";
 		}
 		foreach ($this->fObjectModelData['attributes'] as $a) {
 			$arrayComponents[] = "{$a['sqlname']}";
 		}

 		$s .= implode('`,`',$arrayComponents);
 		$s .= '`';
 		return $s;
 	}
 	
 	
     private function buildSqlUniqueAttributeValueList() {
 		
 		$s = "'";
 		$arrayComponents = array();
 		foreach ($this->fObjectModelData['parents'] as $p) {
 			$arrayComponents[] = mysql_real_escape_string($this->$p['name']); 
 		}
 		foreach ($this->fObjectModelData['attributes'] as $a) {
 			$arrayComponents[] = mysql_real_escape_string($this->$a['name']);
 		}
 		
 		$s .= implode("','",$arrayComponents);
 		$s .= "'";
 		return $s;
 	}
 	
 }
 
?>
