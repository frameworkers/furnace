<?php
	
class FModel {
	
	public $objects   = array();
	
	public $tables    = array();
	
	public $modelData = array();
	
	public $use_accounts = false;

	public function __construct($modelData) {
		
		$this->modelData = $modelData;
	
		// Generate representation of each object and object table
		foreach ($this->modelData as $model_object_name => $model_object_data) {
			// Build objects
			$this->objects[self::standardizeName($model_object_name)] = 
				new FObj( $model_object_name, $this->modelData );
				
			// Build database tables
			$this->tables[self::standardizeTableName($model_object_name)] = 
				self::generateObjectTable($this->objects[self::standardizeName($model_object_name)]);
		}
		
		// Generate lookup tables required by inter-object relationships
		$this->generateLookupTables();
		
		// Determine whether or not this model requires the app_accounts and app_roles tables
		foreach ($this->objects as $object) {
			if ("FAccount" == $object->getParentClass() ) {
				$this->use_accounts = true;
				break;
			}
		}
	}
	
	public function export($format = "yml") {
		return self::exportAsYML();
	}
	
	public function exportAsYML() {
		$r = "# Auto-generated model file\r\n\r\n";
		foreach ($this->objects as $object) {	
			// begin object definition
			$r .= "{$object->getName()}:\r\n";
			// handle non-default inheritance
			if ($object->getParentClass() != "FBaseObject") {
				$r .= "  extends: {$object->getParentClass()}\r\n";
			}
			// export attributes
			$r .= "  attributes:\r\n";
			foreach ($object->getAttributes() as $attr) {
				$r .= "    {$attr->getName()}:\r\n"
					. "      desc: {$attr->getDescription()}\r\n"
					. "      type: {$attr->getType()}\r\n"
					. "      size: {$attr->getSize()}\r\n"
					. "      min:  {$attr->getMin()}\r\n"
					. "      max:  {$attr->getMax()}\r\n"
					. "      default: {$attr->getDefaultValue()}\r\n"
					. "      validation:\r\n";
					foreach ($attr->getValidation() as $validationInstruction => $instructionParameters) {
						$r .= "        {$validationInstruction}: { ";
						$parms = array();
						foreach ($instructionParameters as $k=>$v) {
							if ($v == null) { continue; }	// skip empty parameters
							if (true  === $v) {$v = 'y';}	// handle boolean true
							if (false === $v) {$v = 'n';}	// handle boolean false
							if ($k == "pattern") {			// patterns need to be in quotes
								$parms[] = "{$k}: \"{$v}\"";
							} else {
								$parms[] = "{$k}: {$v}";
							}
						}
						$r .= implode(' , ', $parms) . " }\r\n";
					}
						
					if ($attr->isUnique()) {
						$r .= "      unique: yes\r\n";
					}
			}
			
			// sort parents by object type
			$parentsByType = array();
			foreach ($object->getParents() as $parent) {
				$parentsByType[$parent->getForeign()][] = $parent;
			}
			// export parents
			$r .= "  parents:\r\n";
			foreach ($parentsByType as $objectName => $parents) {
				$r .= "    {$objectName}:\r\n";
				foreach ($parents as $parent) {
					$r .= "      {$parent->getName()}:\r\n";
					$r .= "        desc: {$parent->getDescription()}\r\n";
					if ($parent->doesReflect() ) {
						$r .= "        reflects: {$parent->getForeign()}.{$parent->getReflectVariable()}\r\n";
					}
					$r .= "        required: " . (($parent->isRequired()) ? "yes" : "no" ) . "\r\n";
				}
			}
			
			// sort peers by object type
			$peersByType = array();
			foreach ($object->getPeers() as $peer) {
				$peersByType[$peer->getForeign()][] = $peer;
			}
			// export peers
			$r .= "  peers:\r\n";
			foreach ($peersByType as $objectName => $peers) {
				$r .= "    {$objectName}:\r\n";
				foreach ($peers as $peer) {
					$r .= "      {$peer->getName()}:\r\n";
					$r .= "        desc: {$peer->getDescription()}\r\n";
					if ($peer->doesReflect()) {
						$r .= "        reflects: {$peer->getForeign()}.{$peer->getReflectVariable()}\r\n";
					}
				}
			}
			
			// sort children by object type
			$childrenByType = array();
			foreach ($object->getChildren() as $child) {
				$childrenByType[$child->getForeign()][] = $child;
			}
			// export children
			$r .= "  children:\r\n";
			foreach ($childrenByType as $objectName => $children) {
				$r .= "    {$objectName}:\r\n";
				foreach ($children as $child) {
					$r .= "      {$child->getName()}:\r\n";
					$r .= "        desc: {$child->getDescription()}\r\n";
					if ($child->doesReflect() ) {
						$r .= "        reflects: {$child->getForeign()}.{$child->getReflectVariable()}\r\n";
					}
				}
			}
			// add a space between objects
			$r .= "\r\n";	
		}
		// return the complete yml model definition
		return $r;
	}
	
	public function compilePhp() {
		$compiled = "\r\n";
		foreach ($this->objects as $object) {
			$compiled .= self::generateObjectClass($object);
			$compiled .= self::generateObjectCollectionClass($object);
		}
		return $compiled;
	}
	
	public function compileSql() {
		$sql = '';
		foreach ($this->tables as $table) {
			$sql .= "--\r\n-- Table Definition for: {$table->getName()}\r\n--\r\n";
			$sql .= $table->toSqlString();
			$sql .= "\r\n\r\n";
		}
		return $sql;
	}
	
	public function generatePhpFile($objectName,$path) {
		$object =& $this->objects[self::standardizeName($objectName)];
		if (!$object) { die("Object {$objectName} not found in model."); }
		// Generate the file contents
		$contents = "<?php\r\n"  
			. self::generateObjectClass($object) 
			. self::generateObjectCollectionClass($object)
			. "\r\n?>";
		// Write the contents to the file
		file_put_contents($path,$contents);
	}
	
	/*
 	 * Function: generateObjectClass
 	 * 
 	 * This helper function generates the Php object class
 	 * for the given object.
 	 * 
 	 * Parameters:
 	 * 
 	 *  (FObj) - the <FObj> instance to use as a data source
 	 * 
 	 * Returns:
 	 *  
 	 *  (string) - The generated php class
 	 */
	private function generateObjectClass(&$object) {
		$r .= "\tclass {$object->getName()} extends {$object->getParentClass()} {\r\n";
		
		// add inherited attributes
		if ("FAccount" == $object->getParentClass()) {
			$r .= "\t\t/**\r\n"
				. "\t\t * INHERITED ATTRIBUTES (from FAccount)\r\n"
				. "\t\t * \r\n"
				. "\t\t *  - username \r\n"
				. "\t\t *  - password \r\n"
				. "\t\t *  - emailAddress \r\n"
				. "\t\t *  - created \r\n"
				. "\t\t *  - modified \r\n"
				. "\t\t *  - lastLogin \r\n"
				. "\t\t */\r\n\r\n";
		}
		// add attributes
		foreach ($object->getAttributes() as $a) {
 			$r .= "\t\t// Variable: {$a->getName()}\r\n";
 			$r .= "\t\t// {$a->getDescription()}\r\n"
				. "\t\t{$a->getVisibility()} \${$a->getName()};\r\n\r\n";	
 		}
 		
		// add parents
 		foreach ($object->getParents() as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// add peers
 		foreach ($object->getPeers() as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// add children
 		foreach ($object->getChildren() as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// constructor
		$r .= "\t\tpublic function __construct(\$data) {\r\n";
		$r .= "\t\t\tif (!isset(\$data['objId']) || \$data['objId'] <= 0) {\r\n"
 			. "\t\t\t\tthrow new FException(\"Invalid <code>objId</code> value in object constructor.\");\r\n"
 			. "\t\t\t}\r\n\r\n";
		if ("FAccount" == $object->getParentClass()) {
 			$r .= "\t\t\tif (!isset(\$data['faccount_id']) || \$data['faccount_id'] <= 0) {\r\n"
 				. "\t\t\t\tthrow new FException(\"Invalid <code>faccount_id</code> value in object constructor.\");\r\n"
 				. "\t\t\t}\r\n\r\n";
			$r .= "\t\t\t// Initialize inherited attributes:\r\n"
				. "\t\t\tif (!isset(\$data['username'])) {\r\n"
				. "\t\t\t\t// FAccount data was NOT provided or is incomplete, get it...\r\n"
				. "\t\t\t\t\$faccount = FAccount::Retrieve(\$data['faccount_id']);\r\n"
				. "\t\t\t\t\$this->username = \$faccount->getUsername();\r\n"
				. "\t\t\t\t\$this->password = \$faccount->getPassword();\r\n"
				. "\t\t\t\t\$this->emailAddress = \$faccount->getEmailAddress();\r\n"
				. "\t\t\t\t\$this->created      = \$faccount->getCreated();\r\n"
				. "\t\t\t\t\$this->modified     = \$faccount->getModified();\r\n"
				. "\t\t\t\t\$this->lastLogin    = \$faccount->getLastLogin();\r\n"
				. "\t\t\t\t\$this->roles    = \$faccount->getRoles();\r\n"
				. "\t\t\t\t\$this->objectClass = \$faccount->getObjectClass();\r\n"
				. "\t\t\t\t\$this->objectId    = \$faccount->getObjectId();\r\n\r\n"
				. "\t\t\t} else {\r\n"
				. "\t\t\t\t// FAccount data was provided with {$object->getName()} data...\r\n"
				. "\t\t\t\t\$this->username = \$data['username'];\r\n"
				. "\t\t\t\t\$this->password = \$data['password'];\r\n"
				. "\t\t\t\t\$this->emailAddress = \$data['emailAddress'];\r\n"
				. "\t\t\t\t\$this->created  = \$data['created'];\r\n"
				. "\t\t\t\t\$this->modified = \$data['modified'];\r\n"
				. "\t\t\t\t\$this->lastLogin= \$data['lastLogin'];\r\n"
				. "\t\t\t\t\$this->objectClass = \$data['objectClass'];\r\n"
				. "\t\t\t\t\$this->objectId    = \$data['objectId'];\r\n"
				. "\t\t\t\t\$this->roles       = FAccount::getRolesForId(\$data['faccount_id']);\r\n"
				. "\t\t\t}\r\n";
		}
		$r .= "\t\t\t// Initialize local attributes and sockets:\r\n"
			. "\t\t\t\$this->objId = \$data['objId'];\r\n";
		foreach ($object->getParents() as $s) {
 			$r .= "\t\t\t\$this->{$s->getName()} = \$data['{$s->getName()}_id'];\r\n";
 		}
 		foreach ($object->getPeers() as $s) {
 			$table = self::standardizeTableName($s->getForeign());
 			if ($s->getOwner() == $s->getForeign()) {
 				$type = self::standardizeAttributeName($s->getOwner());
 				$lookup = $s->getLookupTable();
 				// Set the filter for M:M between two objects of the same type
 				$filter = "WHERE `{$table}`.`objId` IN ( SELECT (`"
					. $type . "1_id` FROM `{$lookup}` WHERE `{$lookup}`.`" . $type . "2_id`='{\$data['objId']}') "
					. "OR ("
					. $type . "2_id` FROM `{$lookup}` WHERE `{$lookup}`.`" . $type . "1_id`='{\$data['objId']}') "
					. ") ";
 			} else {
 				// Set the filter for M:M between two different object types
				$foreignName = self::standardizeAttributeName($s->getForeign());
				$ownerName   = self::standardizeAttributeName($s->getOwner());
 				$filter = "WHERE `{$table}`.`objId` IN ( SELECT `{$s->getLookupTable()}`.`"
					. $foreignName
 					. "_id` FROM `{$s->getLookupTable()}` WHERE `{$s->getLookupTable()}`.`"
					. $ownerName
					. "_id` = '{\$data['objId']}' )";
 			}
			
			$r .= "\t\t\t\$this->{$s->getName()} = new {$s->getForeign()}Collection('{$s->getLookupTable()}',\"{$filter}\");\r\n";
			$r .= "\t\t\t\$this->{$s->getName()}->setOwnerId(\$data['objId']);\r\n";
 		}
 		foreach ($object->getChildren() as $s) {
 			$filter = "WHERE `".self::standardizeTableName($s->getForeign())."`.`{$s->getReflectVariable()}_id`='{\$data['objId']}' ";
 			$r .= "\t\t\t\$this->{$s->getName()} = new {$s->getForeign()}Collection('{$s->getLookupTable()}',\"{$filter}\");\r\n";
 			$r .= "\t\t\t\$this->{$s->getName()}->setOwnerId(\$data['objId']);\r\n";
 		}
 		foreach ($object->getAttributes() as $a) {
 			$r .= "\t\t\t\$this->{$a->getName()} = \$data['{$a->getName()}'];\r\n";
 		}

 		$r .= "\t\t} // end constructor\r\n\r\n";
 		
 		// Getters
		foreach ($object->getAttributes() as $a) {
	 		$r .= "\t\tpublic function get{$a->getFunctionName()}() {\r\n";
 			if ($a->getType() == "string" || $a->getType() == "text") {
 				$r .= "\t\t\treturn stripslashes(\$this->{$a->getName()});\r\n";
 			} else {
 				$r .= "\t\t\treturn \$this->{$a->getName()};\r\n";
 			}
 			$r .= "\t\t}\r\n\r\n";
 		}
		foreach ($object->getParents() as $s) {
			$r .= "\t\tpublic function get{$s->getFunctionName()}() {\r\n"
				. "\t\t\tif (is_object(\$this->{$s->getName()}) ) {\r\n"
				. "\t\t\t\treturn \$this->{$s->getName()};\r\n"
				. "\t\t\t} else {\r\n"
				. "\t\t\t\t\$this->{$s->getName()} = {$s->getForeign()}::Retrieve(\$this->{$s->getName()});\r\n"
				. "\t\t\t\treturn \$this->{$s->getName()};\r\n"
				. "\t\t\t}\r\n"
				. "\t\t}\r\n\r\n";	
 		}
 		foreach ($object->getPeers() as $s) {
			$r .= "\t\tpublic function get{$s->getFunctionName()}(\$uniqueValues=\"*\",\$returnType=\"object\",\$key=\"objId\",\$sortOrder=\"default\") {\r\n"
				. "\t\t\treturn \$this->{$s->getName()}->get(\$uniqueValues,\$returnType,\$key,\$sortOrder);\r\n"
				. "\t\t}\r\n\r\n";
 		}
 		foreach ($object->getChildren() as $s) {
 			$r .= "\t\tpublic function get{$s->getFunctionName()}(\$uniqueValues=\"*\",\$returnType=\"object\",\$key=\"objId\",\$sortOrder=\"default\") {\r\n"
				. "\t\t\treturn \$this->{$s->getName()}->get(\$uniqueValues,\$returnType,\$key,\$sortOrder);\r\n"
				. "\t\t}\r\n\r\n";
			if ("FAccount" == $this->objects[$s->getForeign()]->getParentClass() ) {
				$r .= "\t\tpublic function get{$s->getFunctionName()}ByUsername(\$username) {\r\n"
					. "\t\t\t\$q = \"SELECT `objectId` FROM `app_accounts` WHERE `username`='{\$username}'\";\r\n"
					. "\t\t\t\$id= _db()->queryOne(\$q);\r\n"
					. "\t\t\treturn {$s->getForeign()}::Retrieve(\$id);\r\n"
					. "\t\t}\r\n\r\n";
				$r .= "\t\tpublic function get{$s->getFunctionName()}ByEmailAddress(\$email) {\r\n"
					. "\t\t\t\$q = \"SELECT `objectId` FROM `app_accounts` WHERE `emailAddress`='{\$email}'\";\r\n"
					. "\t\t\t\$id= _db()->queryOne(\$q);\r\n"
					. "\t\t\treturn {$s->getForeign()}::Retrieve(\$id);\r\n"
					. "\t\t}\r\n\r\n";
			}
 		}
 		
 		// Setters
		foreach ($object->getAttributes() as $a) {
			$r .= "\t\tpublic function set{$a->getFunctionName()}(\$value,\$bSaveImmediately = true) {\r\n";
				
			if ($a->getValidation()) {
				$r .= "\t\t\t// Validate the provided value\r\n\t\t\t// FValidationException thrown on failure)\r\n";
				$r .= "\t\t\t". FValidator::BuildValidationCodeForAttribute("\$value",$a);
				$r .= "\r\n";
			}
			$r .= "\t\t\t// Save the provided value\r\n"
				. "\t\t\t\$this->{$a->getName()} = \$value;\r\n"
 				. "\t\t\tif (\$bSaveImmediately) {\r\n"
 				. "\t\t\t\t\$this->save('{$a->getName()}');\r\n"
 				. "\t\t\t}\r\n"
 				. "\t\t}\r\n\r\n";	
		}
		// setters to allow for 'parent' reassignment
		foreach ($object->getParents() as $s) {
			$r .= "\t\tpublic function set{$s->getFunctionName()}Id(\$id) {\r\n"
				. "\t\t\t\$q = \"UPDATE `".self::standardizeTableName($object->getName())."` SET `{$s->getName()}_id`='{\$id}' WHERE `".self::standardizeTableName($object->getName())."`.`objId`='{\$this->objId}'\";\r\n"
				. "\t\t\t_db()->exec(\$q);\r\n"
				. "\t\t\t\$this->{$s->getName()} = \$id;\r\n"
				. "\t\t}\r\n\r\n";
		}
		// adders and removers (for peer relationships)
		foreach ($object->getPeers() as $s) {
			$r .= "\t\tpublic function add{$s->getFunctionName()}(\$ids) {\r\n";
			$owner   = FModel::standardizeAttributeName($s->getOwner());
			$foreign = FModel::standardizeAttributeName($s->getForeign());
			if ($s->getOwner() == $s->getForeign()) {
				$r .= "\t\t\t\$q = \"INSERT INTO `{$s->getLookupTable()}` (`{$owner}1_id`,`{$owner}2_id`) VALUES \";\r\n";
			} else {
				$r .= "\t\t\t\$q = \"INSERT INTO `{$s->getLookupTable()}` (`{$owner}_id`,`{$foreign}_id`) VALUES  \";\r\n";
			}
			$r .= "\t\t\tif (is_array(\$ids)) {\r\n"
				. "\t\t\t\t\$subq = array();\r\n"
				. "\t\t\t\tforeach (\$ids as \$id) { \$subq[] = \"({\$this->getObjId()},{\$id})\";}\r\n"
				. "\t\t\t\t\$q .= implode(\",\",\$subq);\r\n"
				. "\t\t\t} else {\r\n"
				. "\t\t\t\t\$q .= \"({\$this->getObjId()},{\$ids})\";\r\n"
				. "\t\t\t}\r\n"
				. "\t\t\t_db()->exec(\$q);\r\n"
				. "\t\t}\r\n"
				. "\t\t\r\n"
				. "\t\tpublic function remove{$s->getFunctionName()}(\$ids) {\r\n";
			if ($s->getOwner() == $s->getForeign()) {
				$r .= "\t\t\t\$q = \"DELETE FROM `{$s->getLookupTable()}` WHERE ((`{$owner}1_id`={\$this->getObjId()} AND `{$owner}2_id` IN (\".implode(',',\$ids).\")) OR (`{$owner}2_id`={\$this->getObjId()} AND `{$owner}1_id` IN (\".implode(',',\$ids).\"))  \";\r\n";
			} else {
				$r .= "\t\t\t\$q = \"DELETE FROM `{$s->getLookupTable()}` WHERE `{$owner}_id`={\$this->getObjId()} AND `{$foreign}_id` IN (\".implode(',',\$ids).\") \";\r\n";
			}
			$r .= "\t\t\t_db()->exec(\$q);\r\n"
				. "\t\t}\r\n\r\n";
			
		}
		
		// Save
 		$r .= "\t\tpublic function save(\$attribute = '') {\r\n"
 			. "\t\t\tif('' == \$attribute) {\r\n";
 		if ("FAccount" == $object->getParentClass()) {
 			$r .= "\t\t\t\tparent::faccount_save();\r\n";
 		}
 		if (count($object->getAttributes()) > 0) {
 			$r .= "\t\t\t\t\$q = \"UPDATE `".self::standardizeTableName($object->getName())."` SET \" \r\n";
 				$temp = array();
 				foreach ($object->getAttributes() as $a) {
 				 	if ($a->getType() == "text" || $a->getType() == "string") {
 						$temp[] = "\t\t\t\t\t. \"`{$a->getName()}`='\".addslashes(\$this->{$a->getName()}).\"'";
 					} else {
 						$temp[] = "\t\t\t\t\t. \"`{$a->getName()}`='{\$this->{$a->getName()}}'";
 					}
 				}
 			$r .= implode(", \"\r\n",$temp)
 				. " \";\r\n"
 				. "\t\t\t\t\$q .= \"WHERE `".self::standardizeTableName($object->getName())."`.`objId`='{\$this->objId}'\";\r\n";
			}
 			$r .= "\t\t\t} else {\r\n"
 			. "\t\t\t\t\$q = \"UPDATE `".self::standardizeTableName($object->getName())."` SET `{\$attribute}`='{\$this->\$attribute}' WHERE `".self::standardizeTableName($object->getName())."`.`objId`='{\$this->objId}' \";\r\n" 
 			. "\t\t\t}\r\n"
 			. "\t\t\t_db()->exec(\$q);\r\n"
 			. "\t\t}\r\n\r\n";
 			
 		// Create
 		$ua = array();
 		$sqlua = array(); 
 		$sqluv = array();
 		$dataua = array();
 		foreach ($object->getParents() as $dep) {
 			if ($dep->isRequired()) {
 				$ua[] = "\${$dep->getName()}_id";
 				$sqlua[] = "`{$dep->getName()}_id`";
 				$sqluv[] = "'{\${$dep->getName()}_id}'";
 				$dataua[]= "{$dep->getName()}_id";
 			}
 		}
 		foreach ($object->getAttributes() as $attr) {
 			if ($attr->isUnique()) {
 				$ua[] = "\${$attr->getName()}";
 				$sqlua[] = "`{$attr->getName()}`";
 				$sqluv[] = "'{\${$attr->getName()}}'";
 				$dataua[] = "{$attr->getName()}";
 			}
 		}
 		$parentParams = '';
 		if ("FAccount" == $object->getParentClass()) {
 			// Include 'faccount_id' as a unique attribute
			//$ua[]    = "\$faccount_id";
			$sqlua[] = "`faccount_id`";
			$sqluv[] = "'{\$faccount_id}'";
			$dataua[]= "faccount_id"; 
 		 	
			// Allow for specification of inherited FAccount parameters
 			$parentParams = '$username,$password,$emailAddress';
 			if (count($ua) > 0) {
 				$parentParams .= ",";
 			}
 		}
 		
 		$r .= "\t\tpublic static function Create(".$parentParams.implode(",",$ua).") {\r\n";	
 		if ("FAccount" == $object->getParentClass()) {	
 			// Create an FAccount object
			$r .= "\r\n"
				. "\t\t\t// Create an 'FAccount' (app_accounts + app_roles) for this object\r\n"
				. "\t\t\t\$faccount_id = FAccountManager::Create(\$username,\$password,\$emailAddress);\r\n\r\n";
 		}
 		// Create the object in the database
		$r .= "\t\t\t// Create a new {$object->getName()} object in the database\r\n";
 		$r .= "\t\t\t\$q = \"INSERT INTO `".self::standardizeTableName($object->getName())."` (".implode(",",$sqlua).") VALUES (".implode(",",$sqluv).")\"; \r\n";
 		$r .= "\t\t\t\$r = _db()->exec(\$q);\r\n";
 		$r .= "\t\t\t\$objectId = _db()->lastInsertID(\"{$object->getName()}\",\"objId\");\r\n";
 	 		
 		// If the object extends FAccount, create a new account object first, and store the core
		// attributes in the list of unique attributes for the class.
 	 	if ("FAccount" == $object->getParentClass()) {
			// Set the reverse link (object,id)
			$r .= "\t\t\t\$q = \"UPDATE `app_accounts` SET `objectClass`='{$object->getName()}', "
				. "`objectId`='{\$objectId}' WHERE `app_accounts`.`objId`='{\$faccount_id}'\";\r\n"
				. "\t\t\t\$r = _db()->exec(\$q);\r\n";
 	 	}
 		$r .= "\r\n"
			. "\t\t\t// Build the data array to pass to the constructor\r\n";
 		$r .= "\t\t\t\$data = array(\"objId\"=>\$objectId";
 		foreach ($dataua as $a) {
 			$r .= ",\r\n\t\t\t\t\"{$a}\"=>\${$a}";
 		}
 		$r .= "\r\n\t\t\t);\r\n";
 		$r .= "\r\n"
			. "\t\t\t// Return the populated array\r\n"
			. "\t\t\treturn new {$object->getName()}(\$data);\r\n";
 		$r .= "\t\t}\r\n";
 		
 		// Retrieve
 		$r .= "\t\tpublic static function Retrieve(\$uniqueValues=\"*\",\$returnType=\"object\",\$key=\"objId\",\$sortOrder=\"default\") {\r\n";
		$r .= "\t\t\t\$collection = new {$object->getName()}Collection();\r\n";
 		$r .= "\t\t\treturn \$collection->get(\$uniqueValues,\$returnType,\$key,\$sortOrder);\r\n";
 		$r .= "\t\t}\r\n";
 		
 		// Add RetrieveByAccountId Function if object depends on FAccount
 		if ("FAccount" == $object->getParentClass()) {
 			$r .= "\t\tpublic static function RetrieveByAccountId(\$accountId) {\r\n"
 				. "\t\t\t_db()->setFetchMode(FDATABASE_FETCHMODE_ASSOC);\r\n"
 				. "\t\t\t\$q = \"SELECT * FROM `".self::standardizeTableName($object->getName())."` "
 					."INNER JOIN `app_accounts` ON `".self::standardizeTableName($object->getName())."`.`objId`=`app_accounts`.`objectId` "
 					."WHERE `fAccount_id`='{\$accountId}'\";\r\n"
 				. "\t\t\t\$r = _db()->queryRow(\$q);\r\n"
 				. "\t\t\treturn new {$object->getName()}(\$r);\r\n"
 				. "\t\t}\r\n";
 				
 			$r .= "\t\tpublic static function ObjIdFromAccountId(\$accountId) {\r\n"
 				. "\t\t\t_db()->setFetchMode(FDATABASE_FETCHMODE_ASSOC);\r\n"
 				. "\t\t\t\$q = \"SELECT `".self::standardizeTableName($object->getName())."`.`objId` FROM `".self::standardizeTableName($object->getName())."` WHERE `".self::standardizeTableName($object->getName())."`.`fAccount_id`='{\$accountId}'\";\r\n"
 				. "\t\t\t\$r = _db()->queryOne(\$q);\r\n"
 				. "\t\t\treturn \$r;\r\n"
 				. "\t\t}\r\n";
 		} 
 		
 		// Delete 
		$r .= "\t\tpublic static function Delete(\$objId) {\r\n";
		
		if ("FAccount" == $object->getParentClass()) {
			$r .= "\t\t\t// Delete the FAccount associated with this object\r\n";
			$r .= "\t\t\t\$q = \"SELECT `faccount_id` FROM `".self::standardizeTableName($object->getName())."` WHERE `".self::standardizeTableName($object->getName())."`.`objId` = '{\$objId}'\"; \r\n";
			$r .= "\t\t\t\$acct_id = _db()->queryOne(\$q);\r\n";
			$r .= "\t\t\tFAccountManager::DeleteByAccountId(\$acct_id);\r\n\r\n";
		}
		
		$delete_info = $this->determineDeleteInformationFor($object);
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

		
		/*
		 * Create/Delete functions for 'child' relationships
		 */
 		foreach ($object->getChildren() as $childObject) {
 			// Compute 'Create' signature for childObject
	 		$ua    = array();
	 		$nonua = array();
	 		foreach ($this->objects[$childObject->getForeign()]->getParents() as $dep) {
	 			if ($dep->isRequired()) {
	 				$ua[] = "\$objectData['{$dep->getName()}_id']";
	 			}
	 		}
	 		foreach ($this->objects[$childObject->getForeign()]->getAttributes() as $attr) {
	 			if ($attr->isUnique()) {
	 				$ua[]    = "\$objectData['{$attr->getName()}']";
	 			} else {
	 				$nonua[] = "\t\t\t\t\$o->set".self::standardizeName($attr->getName())
	 					."(\$objectData['{$attr->getName()}'],false);\r\n";
	 			}
	 		}
	 		$parentParams = '';
	 		if ("FAccount" == $this->objects[$childObject->getForeign()]->getParentClass()) {	
				// Allow for specification of inherited FAccount parameters
	 			$parentParams = "$\$objectData['username'],\$objectData['password'],\$objectData['emailAddress']";
	 			if (count($ua) > 0) {
	 				$parentParams .= ",";
	 			}
	 		}
	 		$createSignature = $parentParams.implode(",",$ua);
	 		
	 		// Output the function bodies
 			$r .= "\r\n\t\t// Create new '{$childObject->getName()}' object(s)\r\n"
 				. "\t\tpublic function create".self::standardizeName($childObject->getName())."FromArray(\$data=array()) {\r\n"
 				. "\t\t\t//TODO: implement this\r\n"
 				. "\t\t\t\$createdObjects = array();\r\n"
 				. "\t\t\tforeach (\$data as \$objectData) {\r\n"
 				. "\t\t\t\t\$o = {$childObject->getForeign()}::Create({$createSignature});\r\n"
 				. implode($nonua)
 				. "\t\t\t\t\$o->save();\r\n"
 				. "\t\t\t}\r\n"
 				. "\t\t\treturn (count(\$createdObjects) == 1) \r\n"
 				. "\t\t\t\t? \$createdObjects[0]\r\n"
 				. "\t\t\t\t: \$createdObjects;\r\n"
 				. "\t\t}\r\n"
 				. "\r\n\t\t// Delete existing '{$childObject->getName()}' object(s)\r\n"
 				. "\t\tpublic function delete".self::standardizeName($childObject->getName())."(\$ids=array()) {\r\n"
 				. "\t\t\tif (is_array(\$ids)) {\r\n"
 				. "\t\t\t\tforeach (\$ids as \$id) {\r\n"
 				. "\t\t\t\t\t{$childObject->getForeign()}::Delete(\$id);\r\n"
 				. "\t\t\t\t}\r\n"
 				. "\t\t\t} else {\r\n"
 				. "\t\t\t\t{$childObject->getForeign()}::Delete(\$ids);\r\n"
 				. "\t\t\t}\r\n"
 				. "\t\t}\r\n";
 		}
 		
 		
		$r .="\t} // end {$object->getName()}\r\n\r\n";
		// return the class string
		return $r;
	}
	
	private function generateObjectCollectionClass(&$object) {
		if ("FAccount" == $object->getParentClass()) {
			$r = "\tclass {$object->getName()}Collection extends FAccountCollection {\r\n";
		} else {
			$r = "\tclass {$object->getName()}Collection extends FObjectCollection {\r\n";
		}
 		
 		// Add Constructor
 		$r .= "\t\tpublic function __construct(\$lookupTable=\""
 				.self::standardizeTableName($object->getName())."\","
 				."\$filter='') {\r\n"
 			. "\t\t\tparent::__construct(\"{$object->getName()}\",\$lookupTable,\$filter,"
 			.(("FAccount" == $object->getParentClass()) ? 'true' : 'false').");\r\n"
			. "\t\t\t\$this->objectTypeTableName=\"".self::standardizeTableName($object->getName())."\";\r\n";
 		
 		$r .= "\t\t}\r\n";
 		
 		// Add DestroyObject
 		$r .= "\t\tpublic function destroyObject(\$objectId) {\r\n"
 			. "\t\t\t//TODO: Implement this\r\n"
 			. "\t\t}\r\n\r\n";
 		
 		$r .= "\t} // end {$object->getName()}Collection\r\n\r\n";
 		// return the class string
 		return $r;
	}
	
	private function determineDeleteInformationFor(&$object) {
		$delete_info = array();
		foreach ($object->getChildren() as $child) {
			$remoteSocket = $this->getRemoteSocketFor($child);
			$delete_info[] = array(
				'type'      => 'parent',
				'required'  => (($remoteSocket->isRequired()) ? "yes" : "no"),
				'class'     => $remoteSocket->getOwner(),
				'sqltable'  => self::standardizeTableName($remoteSocket->getOwner()),
				'sqlcol'    => strtolower(substr($remoteSocket->getName(),0,1))
					. substr($remoteSocket->getName(),1)
					. "_id"
			);
		}
		foreach ($object->getPeers() as $peer) {
			$remoteSocket = $this->getRemoteSocketFor($peer);
			$peerInfo = array(
				'type'      => 'lookup',
				'class'     => $remoteSocket->getOwner(),
				'sqltable'  => self::standardizeTableName($remoteSocket->getLookupTable(),true));
			if ($remoteSocket->getOwner() == $peer->getOwner()) {
				$peerInfo['sqlcol']  = strtolower(substr($remoteSocket->getOwner(),0,1))
					.substr($remoteSocket->getOwner(),1)
					."1_id";
				$peerInfo['sqlcol2']  = strtolower(substr($remoteSocket->getOwner(),0,1))
					.substr($remoteSocket->getOwner(),1)
					."2_id";
			} else {
				$peerInfo['sqlcol']  = strtolower(substr($peer->getOwner(),0,1))
					.substr($peer->getOwner(),1)
					."_id";
			}
			$delete_info[] = $peerInfo;
		}
		return $delete_info;
	}
	
	private function getRemoteSocketFor(&$socket) {
		$foreignObject = $this->objects[$socket->getForeign()];
		foreach ($foreignObject->getParents() as $remote) {
			if ($remote->getName() == $socket->getReflectVariable()) {
				return $remote;
			} 
		}
		foreach ($foreignObject->getPeers() as $remote) {
			if ($remote->getName() == $socket->getReflectVariable()) {
				return $remote;
			} 
		}
		foreach ($foreignObject->getChildren() as $remote) {
			if ($remote->getName() == $socket->getReflectVariable()) {
				return $remote;
			} 
		}
		return false;
	}
	
	private function generateObjectTable(&$object) {
		$table = new FSqlTable($object->getName(),false);
		// Special processing for those objects which extend the built-in FAccount class
	 	if ("FAccount" == $object->getParentClass() ) {
			// Create a column in the object table to store the relation to the `app_accounts` table
			$colname = "faccount_id";
 			$c = new FSqlColumn("faccount_id","INT(11) UNSIGNED",false,false,"link to FAccount data for this {$object->getName()}");
 			$table->addColumn($c);	
		}
		foreach ($object->getParents() as $parent) {
			$table->addColumn(
				new FSqlColumn(
					self::standardizeAttributeName($parent->getName())."_id",
					FSqlColumn::convertToSqlType(
						"integer",array("min"=>0)),
				false,
				false,
				"objId of " . (($parent->isRequired())? "" : "(optional) ") . "{$parent->getName()} ")
			); 
		}
		foreach ($object->getAttributes() as $attr) {
			$col = new FSqlColumn(
				$attr->getName(),
				FSqlColumn::convertToSqlType($attr->getType(),
					array("min" => "{$attr->getMin()}",
						  "max" => "{$attr->getMax()}",
						  "size"=> "{$attr->getSize()}")),
				false,
				false,
				$attr->getDescription()
			);
			$col->setDefaultValue($attr->getDefaultValue());
			if ($attr->isUnique()) {
				$col->setKey("UNIQUE");
			}
			$table->addColumn($col);
		}
		// return the generated table
		return $table;
	}
	
	private function generateLookupTables() {
		// Iterate through all objects
		foreach ($this->objects as $object) {
			// If the object has any peer relationships...
			if (count($object->getPeers()) > 0) {
				// Iterate through the peer relationships
				foreach ($object->getPeers() as $peer) {
					// If the lookup table does not already exist...
					if (!isset($this->tables[$peer->getLookupTable()])) {
						$table = new FSqlTable($peer->getLookupTable(),true);
						// If the two object types are the same...
						if ($peer->getOwner() == $peer->getForeign()) {
							$col1 = new FSqlColumn(
								self::standardizeAttributeName($peer->getForeign())."1_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
							$col2 = new FSqlColumn(
								self::standardizeAttributeName($peer->getForeign())."2_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
						} else {
							$ownerObjectName   = $peer->getOwner();
							$foreignObjectName = $peer->getForeign();
							$col1 = new FSqlColumn(
								self::standardizeAttributeName($peer->getOwner())."_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
							$col2 = new FSqlColumn(
								self::standardizeAttributeName($peer->getForeign())."_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
						}
						// add the columns to the table
						$col1->setKey("PRIMARY");
						$col2->setKey("PRIMARY");
						$table->addColumn($col1);
						$table->addColumn($col2);
						// add the table to the list of tables
						$this->tables[$peer->getLookupTable()] = $table;
					}
				}
			}
		}
	}
	
	
	/*
 	 * Function: standardizeName
 	 * 
 	 * This function is private. It takes a string and 
 	 * standardizes it according to framework naming 
 	 * conventions.
 	 * 
 	 * Parameters:
 	 * 
 	 *  name - The name string to standardize.
 	 * 
 	 * Returns:
 	 * 
 	 *  (string) The standardized name.
 	 */
 	public static function standardizeName($name) {
  		// 1. Replace all '_' with ' ';
  		// 2. Capitalize all words
  		// 3. Concatenate words
  		//
  		// Turns: long_object_name
  		// into:  LongObjectName
  		return 
  			str_replace(" ","",ucwords(str_replace("_"," ",$name)));
  	}
  	
  	/*
  	 * Function: standardizeAttributeName
  	 * 
  	 * This funtion is like <standardizeName> in that it takes
  	 * a string and standardizes it according to framework
  	 * naming conventions for object attributes.
  	 * 
  	 * Parameters:
  	 * 
  	 *  name - The attribute name string to standardize.
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) The standardized attribute name
  	 */
  	public static function standardizeAttributeName($name) {
  		// 1. Replace all '_' with ' ';
		// 2. Replace all '-' with ' ';
  		// 3. Capitalize all words
  		// 4. Concatenate words
  		// 5. Make the first letter lowercase
  		//
  		// Turns: long_variable_name
  		// into:  longVariableName
  		$s = str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ",$name))));
  		return strtolower($s[0]) . substr($s,1);
  	} 

  	public static function standardizeTableName($name,$bIsLookupTable = false) {
  		if ("app_accounts" == $name || "app_roles" == $name) { return $name; }
  		if ($bIsLookupTable) {
  			// A lookup table needs to maintain its '_' characters (which are
			// otherwise illegal. Standardize the 3 components, but leave the 
			// '_' separators untouched.
			list($t1,$t2,$v) = explode("_",$name);
			return self::standardizeAttributeName($t1)
				.  "_"
				.  self::standardizeAttributeName($t2)
				.  "_"
				.  self::standardizeAttributeName($v);
  		} else {
  			$stdName = self::standardizeAttributeName($name);
	  		if (isset($GLOBALS['furnace']->config['hostOS']) &&
	  			strtolower($GLOBALS['furnace']->config['hostOS']) == 'windows') {
	  				
	  			// Windows has a case-insensitive file system, and the default 
				// settings of MySQL on windows force tablenames to be strictly lowercase
	  			return strtolower($stdName);	
	  		} else {
	  			// In all other environments, just return the standardized name
				return $stdName;
	  		}
  		}
  	}
  	
	public function getModelData() {
		return $this->modelData;
	}

}
	
	
	
	
?>