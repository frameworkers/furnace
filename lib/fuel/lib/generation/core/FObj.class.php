<?php
/*
 * frameworkers-foundation
 * 
 * FObj.class.php
 * Created on May 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FObj
  * Defines a basic Foundation Model Object. <FObj> objects
  * form the basis for a data-driven application and usually (unless
  * they are declared as abstract) correspond to a database table. 
  * 
  */
 class FObj {
 	// Variable: name
 	// The name of this object class.
 	private $name;
 	
 	// Array: attributes
 	// An array of <FObjAttr> objects for this object.
 	private $attributes;
 	
 	// Array: sockets
 	// An array of <FObjSocket> objects for this object.
 	private $sockets;
 	
 	// Boolean: isAbstract
 	// A boolean flag (true/false), with true indicating an abstract object.
 	private $isAbstract;
 	
 	// Variable: parentClass
 	// The class this class inherits from. The default is <FBaseObject>.
 	private $parentClass;
 	
 	// Array: depends
 	// The classes (if any) that this class depends upon.
 	private $depends;
 	
 	// Array: delete_info
	// Data needed to correctly (and completely) implement ::delete
 	private $delete_info;
 	
 	/*
 	 * Function: __construct
 	 * 
 	 * Constructor. Creates a new <FObj> object. <FObj> objects may be 
 	 * declared as "abstract" by passing a value of "true" as the second
 	 * parameter.
 	 * 
 	 * Parameters:
 	 * 
 	 *  name - the name of this object.
 	 *  isAbstract - Boolean flag (true = abstract) (default = false).
 	 */
 	public function __construct($name,$isAbstract = false,
 		$parentClass="FBaseObject"){
 			
 		$this->name = $name;
 		$this->attributes = array();
 		$this->sockets = array();
 		$this->isAbstract = $isAbstract;
 		$this->parentClass = $parentClass;
 		$this->depends = array();
 		$this->delete_info = array();
 	}	
 	
 	/* 
 	 * Function: getName
 	 * 
 	 * Get the name of this object.
 	 * 
 	 * Returns: 
 	 * 
 	 * (string) The name of this object.
 	 */
 	public function getName() {
 		return $this->name;	
 	}
 	 	
 	/*
 	 * Function: getParentClass
 	 * 
 	 * Get the parent object of this object
 	 * 
 	 * Returns: 
 	 * 
 	 * (string) The name of the parent class.
 	 */
 	public function getParentClass() {
 		return $this->parentClass;	
 	}
 	
 	/*
 	 * Function: setIsAbstract
 	 * 
 	 * Sets whether or not this object is abstract.
 	 * 
 	 * Parameters:
 	 * 
 	 *  bIsAbstract - (boolean) true=isAbstract.
 	 */
 	public function setIsAbstract($bIsAbstract) {
 		$this->isAbstract = $bIsAbstract;	
 	}
 	
 	/*
 	 * Function: setParentClass
 	 * 
 	 * Sets the name of the parent class.
 	 * 
 	 * Parameters: 
 	 * 
 	 *  value - (string) The name of the parent class.
 	 */
 	public function setParentClass($value) {
 		$this->parentClass = $value;	
 	}
 	
 	/*
 	 * Function: addAttribute
 	 * 
 	 * Adds an <FObjAttr> object to the object's <attributes> array.
 	 * 
 	 * Parameters: 
 	 * 
 	 *  attr - The <FObjAttr> object to add.
 	 * 
 	 * Returns: 
 	 * 
 	 *  (boolean) Whether the attribute was successfully added.
 	 */
 	public function addAttribute($attr) {
 		$this->attributes[] = $attr;
 	}	
 	
 	/*
 	 * Function: getAttributes
 	 * 
 	 * Returns the object's <attributes> array.
 	 * 
 	 * Parameters: 
 	 * 
 	 *  none
 	 * 
 	 * Returns: 
 	 * 
 	 * (array) The object's <attributes> array.
 	 */
 	public function getAttributes() {
 		return $this->attributes;	
 	}
 	
 	/*
 	 * Function: addSocket
 	 * 
 	 * Adds an <FObjSocket> object to the object's <sockets> array.
 	 * 
 	 * Parameters:
 	 * 
 	 *  socket - the <FObjSocket> object to add.
 	 * 
 	 * Returns:
 	 * 
 	 *  (boolean) - Whether the socket was successfully added. 
 	 */
 	public function addSocket($socket) {
 		$this->sockets[] = $socket;	
 	}
 	
 	/*
 	 * Function: getSockets
 	 * 
 	 * Returns the object's <sockets> array.
 	 * 
 	 * Parameters:
 	 * 
 	 *  none
 	 * 
 	 * Returns:
 	 * 
 	 * (array) The object's <sockets> array.
 	 */
 	public function getSockets() {
 		return $this->sockets;	
 	}
 	
 	/*
 	 * Function: addDeleteInfo
 	 * 
 	 * Adds delete information to the object's <delete_info> array.
 	 * 
 	 * Parameters:
 	 * 
 	 *  data - the delete information to add.
 	 * 
 	 * Returns:
 	 * 
 	 *  (boolean) - Whether the data was successfully added. 
 	 */
	public function addDeleteInfo($data) {
		$this->delete_info[] = $data;
	}
	
 	/*
 	 * Function: getDeleteInfo
 	 * 
 	 * Returns the object's <delete_info> array.
 	 * 
 	 * Parameters:
 	 * 
 	 *  none
 	 * 
 	 * Returns:
 	 * 
 	 * (array) The object's <delete_info> array.
 	 */	
	public function getDeleteInfo() {
		return $this->delete_info;
	}
 	
 	/*
 	 * Function: doesDepend
 	 * 
 	 * This function attempts to determine if the provided
 	 * class name is one of the classes upon which this class 
 	 * depends. 
 	 * 
 	 * Parameters:
 	 * 
 	 *  className - The name of the class to test for dependency.
 	 * 
 	 * Returns:
 	 * 
 	 *  (boolean) - True or False depending on the result of the 
 	 *              dependency check.
 	 */
 	public function doesDepend($className,$matchVariable='') {
 		
 		foreach ($this->depends as $d) {
 			// If the class name matches...
 			if ((strtolower($d['class']) == strtolower($className))) {
 				// If the match variable was supplied...
 				if ($matchVariable != '') {
 					// Perform a check to see if var matches as well
 					if ($d['var'] == strtolower($matchVariable)){
 						// It does, so we found a dependency
 						return true;	
 					} else {
 						// It does not, so continue checking
 						continue;	
 					}	
 				}
 				// No match variable supplied, just return true
 				return true;
 			}
 		}
 		// No match found, so no dependency exists
 		return false;
 	}
 	
 	/*
 	 * Function: addDependency
 	 * 
 	 * This function adds the specified dependency data to the 
 	 * dependency array for this class. 
 	 * 
 	 * Parameters:
 	 * 
 	 *  className - The name of the class which depends on this class
 	 *  matchVariable - The match variable
 	 *  socketName - The name of the socket representing this dependency
 	 */
 	public function addDependency($className,$matchVariable='',$socketName='') {
		$this->depends[$className] = 
			array("class"=>$className,
				"var"=>$matchVariable,
				"name"=>$socketName);
 	}
 	
 	/*
 	 * Function: removeDependency
 	 * 
 	 * This function removes previously stored dependency data 
 	 * 
 	 * Parameters:
 	 * 
 	 *  className - The name of the class for which dependency data should be removed
 	 */
 	public function removeDependency($className) {
 		unset($this->depends[$className]);	
 	}
 	
 	/*
 	 * Function: getDependencies
 	 * 
 	 * This function returns the currently stored array of dependency
 	 * data for this class.
 	 * 
 	 * Returns:
 	 *  (array) - the current dependency data for this class
 	 */
 	public function getDependencies() {
 		return $this->depends;
 	}
 	
 	/*
 	 * Function: toPhpString
 	 * 
 	 * Converts the object to a set of PHP classes suitable for 
 	 * writing to a *.class.php file. This function will return
 	 * a string containing the following three classes:
 	 *   - ObjectAttrs
 	 *   - Object
 	 *   - ObjectCollection
 	 * 
 	 * Returns: 
 	 * 
 	 *  string - The generated PHP code as a string.
 	 * 
 	 */
 	public function toPhpString() {
 		return $this->generateObjectAttrsClassString()
 				. $this->generateObjectClassString()
 				. $this->generateObjectCollectionClassString();
 		
 	}
 	
 	/*
 	 * Function: generateObjectAttrsClassString
 	 * 
 	 * This helper function generates the ObjectAttrs class string.
 	 * 
 	 * Returns:
 	 *  
 	 *  (string) - The php class
 	 */
 	private function generateObjectAttrsClassString() {
 		$r = "\tclass {$this->getName()}Attrs {\r\n";
 		foreach ($this->attributes as $a) {
 			$r .= "\t\t const " . strtoupper($a->getName()) . " = \"{$a->getName()}\";\r\n";	
 		}
 		$r .= "\t}\r\n\r\n";
 		
 		return $r;		
 	}
 	
 	/*
 	 * Function: generateObjectClassString
 	 * 
 	 * This helper function generates the Object class string.
 	 * 
 	 * Returns:
 	 *  
 	 *  (string) - The php class
 	 */
 	private function generateObjectClassString() {
 		$r = "\tclass {$this->getName()} extends FBaseObject {\r\n";
 		
 		// Add Attributes
 		foreach ($this->attributes as $a) {
 			$r .= "\t\t// Variable: {$a->getName()}\r\n"
 				. "\t\t// {$a->getDescription()}\r\n"
				. "\t\t{$a->getVisibility()} \${$a->getName()};\r\n\r\n";	
 		}
 		
 		// Add Sockets
 		foreach ($this->sockets as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// Add Constructor
 		$r .= "\t\tpublic function __construct(\$data) {\r\n";
 		$r .= "\t\t\tif (isset(\$data['objid'])) {\$data['objId'] = \$data['objid'];}\r\n";
 		$r .= "\t\t\tif (!isset(\$data['objId']) || \$data['objId'] <= 0) {\r\n"
 			. "\t\t\t\tthrow new FException(\"Invalid <code>objId</code> value '{\$data['objId']}' in object constructor.\");\r\n"
 			. "\t\t\t}\r\n"
 			. "\t\t\t\$this->objId = \$data['objId'];\r\n";
 		foreach ($this->sockets as $s) {
 			if ("1" == $s->getQuantity() ) {
 				$r .= "\t\t\t\$this->{$s->getName()} = \$data['".strtolower($s->getName())."_id'];\r\n";	
 			} else {
 				if ("1" == $s->getQuantity()) {
 					$filter = "WHERE `" . $s->getOwner() . "_id`='{\$data['objId']}'";
 				} else {
 					if (false === strstr($s->getLookupTable(),"_")) {
 						// If the lookup is 1M (does not have a '_') :
 						$filter = "WHERE `".strtolower(substr($s->getActualRemoteVariableName(),0,1)).substr($s->getActualRemoteVariableName(),1)."_id`='{\$data['objId']}' ";
 					} else {
 						// If the lookup is MM (contains an '_') :
 						$filter = "WHERE `objId` IN ( SELECT `"  
 							. strtolower(substr($s->getForeign(),0,1)).substr($s->getForeign(),1) 
 							. "_id` FROM `{$s->getLookupTable()}` WHERE `"
 							. strtolower(substr($s->getOwner(),0,1)).substr($s->getOwner(),1)
 							. "_id`='{\$data['objId']}' )";
 					}
 				}
 				$r .= "\t\t\t\$this->{$s->getName()} = new {$s->getForeign()}Collection(\"{$s->getLookupTable()}\",\"{$filter}\");\r\n";
 				$r .= "\t\t\t\$this->{$s->getName()}->setOwnerId(\$data['objId']);\r\n";
 			}
 		}
 		foreach ($this->attributes as $a) {
 			$r .= "\t\t\t\$this->{$a->getName()} = \$data['".strtolower($a->getName())."'];\r\n";
 		}
 		if ($this->doesDepend("FAccount")) {
 			$r .= "\r\n"
				. "\t\t\t// Preload FAccount details (and set if necessary)\r\n"
				. "\t\t\t\$this->fAccount = FAccount::Retrieve(\$data['faccount_id']);\r\n"
				. "\t\t\tif ('' == \$this->fAccount->getObjectClass()) {\r\n"
				. "\t\t\t\t\$this->fAccount->setObjectClass(\"{$this->getName()}\");\r\n"
				. "\t\t\t\t\$this->fAccount->setObjectId(\$data['objId']);\r\n"
				. "\t\t\t}\r\n";
 		}
 		$r	.= "\t\t}\r\n\r\n";
 		
 		// Add Getters
 		foreach ($this->attributes as $a) {
 			$r .= "\t\tpublic function get{$a->getFunctionName()}() {\r\n"
 				. "\t\t\treturn \$this->{$a->getName()};\r\n"
 				. "\t\t}\r\n\r\n";	
 		}
 		foreach ($this->sockets as $s) {
 			if ("1" == $s->getQuantity() ) {
 				$r .= "\t\tpublic function get{$s->getFunctionName()}() {\r\n"
 					. "\t\t\tif (is_object(\$this->{$s->getName()}) ) {\r\n"
 					. "\t\t\t\treturn \$this->{$s->getName()};\r\n"
 					. "\t\t\t} else {\r\n"
 					. "\t\t\t\t\$this->{$s->getName()} = {$s->getForeign()}::Retrieve(\$this->{$s->getName()});\r\n"
 					. "\t\t\t\treturn \$this->{$s->getName()};\r\n"
 					. "\t\t\t}\r\n"
 					. "\t\t}\r\n\r\n";	
 			} else {
 				$r .= "\t\tpublic function get{$s->getFunctionName()}(\$uniqueValues=\"*\",\$returnType=\"object\",\$key=\"objId\",\$sortOrder=\"default\") {\r\n"
 					. "\t\t\treturn \$this->{$s->getName()}->get(\$uniqueValues,\$returnType,\$key,\$sortOrder);\r\n"
 					. "\t\t}\r\n\r\n";
 			}	
 		}
 		
 		// Add Setters
 		foreach ($this->attributes as $a) {
 			$r .= "\t\tpublic function set{$a->getFunctionName()}(\$value,\$bSaveImmediately = true) {\r\n"
 				. "\t\t\t\$this->{$a->getName()} = \$value;\r\n"
 				. "\t\t\tif (\$bSaveImmediately) {\r\n"
 				. "\t\t\t\t\$this->save({$this->getName()}Attrs::".strtoupper($a->getName()).");\r\n"
 				. "\t\t\t}\r\n"
 				. "\t\t}\r\n\r\n";	
 		}
 		
 		// Add Save Function
 		$r .= "\t\tpublic function save(\$attribute = '') {\r\n"
 			. "\t\t\tif('' == \$attribute) {\r\n"
 			. "\t\t\t\t\$q = \"UPDATE `{$this->getName()}` SET \" \r\n";
 			$temp = array();
 			foreach ($this->attributes as $a) {
 			 	if ($a->getType() == "text" || $a->getType() == "string") {
 					$temp[] = "\t\t\t\t. \"`{$a->getName()}`='\".addslashes(\$this->{$a->getName()}).\"'";
 				} else {
 					$temp[] = "\t\t\t\t. \"`{$a->getName()}`='{\$this->{$a->getName()}}'";
 				}
 			}
 		$r .= implode(", \"\r\n",$temp)
 			. " \";\r\n"
 			. "\t\t\t\t\$q .= \"WHERE `objId`='{\$this->objId}'\";\r\n"
 			. "\t\t\t} else {\r\n"
 			. "\t\t\t\t\$q = \"UPDATE `{$this->getName()}` SET `{\$attribute}`='{\$this->\$attribute}' WHERE `objId`='{\$this->objId}' \";\r\n" 
 			. "\t\t\t}\r\n"
 			. "\t\t\t_db()->exec(\$q);\r\n"
 			. "\t\t}\r\n\r\n";

 		// Add Create Function
 		$ua = array();
 		$sqlua = array(); 
 		$sqluv = array();
 		$dataua = array();
 		foreach ($this->depends as $dep) {
 			$ua[] = "\${$dep['name']}_id";
 			$sqlua[] = "`{$dep['name']}_id`";
 			$sqluv[] = "'{\${$dep['name']}_id}'";
 			$dataua[]= "{$dep['name']}_id";
 		}
 		foreach ($this->attributes as $attr) {
 			if ($attr->isUnique()) {
 				$ua[] = "\${$attr->getName()}";
 				$sqlua[] = "`{$attr->getName()}`";
 				$sqluv[] = "'{\${$attr->getName()}}'";
 				$dataua[] = "{$attr->getName()}";
 			}
 		}
 		$r .= "\t\tpublic static function Create(".implode(",",$ua).") {\r\n";
 		$r .= "\t\t\t\$q = \"INSERT INTO `{$this->getName()}` (".implode(",",$sqlua).") VALUES (".implode(",",$sqluv).")\"; \r\n";
 		$r .= "\t\t\t\$r = _db()->exec(\$q);\r\n";
 		$r .= "\t\t\tif (MDB2::isError(\$r)) {\r\n";
 		$r .= "\t\t\t\tFDatabaseErrorTranslator::translate(\$r->getCode());\r\n";
 		$r .= "\t\t\t}\r\n";
 		$r .= "\t\t\t\$objectId = _db()->lastInsertID(\"{$this->getName()}\",\"objId\");\r\n";
 		$r .= "\t\t\t\$data = array(\"objId\"=>\$objectId";
 		foreach ($dataua as $a) {
 			$r .= ",\"".strtolower($a)."\"=>\${$a}";
 		}
 		$r .= ");\r\n";
 		$r .= "\t\t\treturn new {$this->getName()}(\$data);\r\n";
 		$r .= "\t\t}\r\n";
 		
 		// Add Retrieve Function
 		$r .= "\t\tpublic static function Retrieve(\$uniqueValues=\"*\",\$returnType=\"object\",\$key=\"objId\",\$sortOrder=\"default\") {\r\n";
		$r .= "\t\t\t_db()->setFetchMode(MDB2_FETCHMODE_ASSOC);\r\n";
		$r .= "\t\t\t\$collection = new {$this->getName()}Collection();\r\n";
 		$r .= "\t\t\treturn \$collection->get(\$uniqueValues,\$returnType,\$key,\$sortOrder);\r\n";
// 		$r .= "\t\t\t\$q = \"SELECT * FROM `{$this->getName()}` WHERE `objId`='{\$objId}' LIMIT 1 \";\r\n";
// 		$r .= "\t\t\ttry {\r\n"
// 			. "\t\t\t\treturn new {$this->getName()}(_db()->queryRow(\$q));\r\n"
// 			. "\t\t\t} catch (FException \$e) {\r\n"
// 			. "\t\t\t\treturn false;\r\n"
// 			. "\t\t\t}\r\n";
 		$r .= "\t\t}\r\n";
 		
 		// Add RetrieveByAccountId Function if object depends on FAccount
 		if ($this->doesDepend("FAccount")) {
 			$r .= "\t\tpublic static function RetrieveByAccountId(\$accountId) {\r\n"
 				. "\t\t\t_db()->setFetchMode(MDB2_FETCHMODE_ASSOC);\r\n"
 				. "\t\t\t\$q = \"SELECT * FROM `{$this->getName()}` WHERE `fAccount_id`='{\$accountId}'\";\r\n"
 				. "\t\t\t\$r = _db()->queryRow(\$q);\r\n"
 				. "\t\t\treturn new {$this->getName()}(\$r);\r\n"
 				. "\t\t}\r\n";
 				
 			$r .= "\t\tpublic static function ObjIdFromAccountId(\$accountId) {\r\n"
 				. "\t\t\t_db()->setFetchMode(MDB2_FETCHMODE_ASSOC);\r\n"
 				. "\t\t\t\$q = \"SELECT `objId` FROM `{$this->getName()}` WHERE `fAccount_id`='{\$accountId}'\";\r\n"
 				. "\t\t\t\$r = _db()->queryOne(\$q);\r\n"
 				. "\t\t\treturn \$r;\r\n"
 				. "\t\t}\r\n";
 		} 

 		// Add Delete Function
		$r .= "\t\tpublic static function Delete(\$objId) {\r\n";
		if ($this->doesDepend("FAccount")) {
			$r .= "\t\t\t// Delete the FAccount associated with this object\r\n";
			$r .= "\t\t\t\$q = \"DELETE FROM `FAccount` WHERE `objectClass`='{$this->getName()}' AND `objectId`='{\$objId}'\";\r\n";
			$r .= "\t\t\t\$r = _db()->exec(\$q);\r\n\r\n";
		}
			foreach ($this->delete_info as $info ) {
				if ("depends" == $info['type']) {
					$r .= "\t\t\t// Delete {$info['class']} objects that depend on this object\r\n";
					$r .= "\t\t\t\$q= \"SELECT `objId` FROM `{$info['sqltable']}` WHERE `{$info['sqlcol']}`='{\$objId}'\";\r\n";
					$r .= "\t\t\t\$r= _db()->query(\$q);\r\n";
					$r .= "\t\t\twhile (\$data = \$r->fetchRow(MDB2_FETCHMODE_ASSOC)) {\r\n"
						. "\t\t\t\t{$info['class']}::Delete(\$data['objid']);\r\n"
						. "\t\t\t}\r\n\r\n";
				}
			}
			foreach ($this->delete_info as $info) {
				if ("lookup" == $info['type']) {
					$r .= "\t\t\t// Delete entries in {$info['sqltable']} containing this object\r\n";
					if ($this->getName() == $info['class']) {
						$r .= "\t\t\t\$q = \"DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqlcol']}`='{\$objId}' OR `{$info['sqlcol2']}`='{\$objId}'\";\r\n";
					} else {
						$r .= "\t\t\t\$q = \"DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqlcol']}`='{\$objId}'\";\r\n";
					}
					$r .= "\t\t\t\$r = _db()->exec(\$q);\r\n\r\n";	
				}
			}
			$r .= "\t\t\t// Delete the object itself\r\n"
				. "\t\t\t\$q = \"DELETE FROM `{$this->getName()}` WHERE `objId`='{\$objId}'\";\r\n"
				. "\t\t\t\$r = _db()->exec(\$q);\r\n";
		$r .= "\t\t}\r\n";
 		
 		$r .= "\t}\r\n\r\n";
 		
 		return $r;
 	}
 	
 	/*
 	 * Function: generateObjectCollectionClassString
 	 * 
 	 * This helper function generates the ObjectCollection class string.
 	 * 
 	 * Returns:
 	 *  
 	 *  (string) - The php class
 	 */
 	private function generateObjectCollectionClassString() {
 		$r = "\tclass {$this->getName()}Collection extends FObjectCollection {\r\n";
 		
 		// Add Constructor
 		$r .= "\t\tpublic function __construct(\$lookupTable=\"{$this->getName()}\",\$filter=\"WHERE 1\") {\r\n"
 			. "\t\t\tparent::__construct(\"{$this->getName()}\",\$lookupTable,\$filter);\r\n";
 		
 		$r .= "\t\t}\r\n";
 		
 		// Add DestroyObject
 		$r .= "\t\tpublic function destroyObject(\$objectId) {\r\n"
 			. "\t\t\t//TODO: Implement this\r\n"
 			. "\t\t}\r\n\r\n";
 		
 		$r .= "\t}\r\n\r\n";
 		return $r;
 	}
 }

?>