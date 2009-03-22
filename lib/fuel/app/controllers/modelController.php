<?php

class ModelController extends Controller {
	
	private $dsn;

	public function __construct() {
		parent::__construct();
		
		if ($GLOBALS['furnace']->config['debug_level'] > 0 && 
			$GLOBALS['furnace']->config['debug_dsn'] == 'mysql://user:password@server/dbname') {
			die("No debug database specified. Please edit the 'debug_dsn' variable in your application config file");
		} else if ($GLOBALS['furnace']->config['debug_level'] == 0 &&
				   $GLOBALS['furnace']->config['production_dsn'] == 'mysql://user:password@server/dbname') {
			die("No production database specified. Please edit the 'production_dsn' variable in your application config file");	   	
		}
		
		if ($GLOBALS['furnace']->config['debug_level'] > 0) {
			$this->dsn = $GLOBALS['furnace']->config['debug_dsn'];
		} else {
			$this->dsn = $GLOBALS['furnace']->config['production_dsn'];
		}
	}
	
	public function index() {
		$this->init();							// Load required files
		$d = new FDatabaseSchema();				// Create an FDatabaseSchema object
		$d->discover($this->dsn);				// Load the currently active database
		$this->set('model',$this->getModel());	// Register the model with Tadpole
		
	}
	public function editor() {
		if (!file_exists(_furnace()->rootdir . 
			"/app/model/model.yml")) {
			file_put_contents(_furnace()->rootdir . 
			"/app/model/model.yml",file_get_contents(
				_furnace()->rootdir .
					"/app/model/model.yml.example"));		
		}
		$this->set('modelcontents',
			file_get_contents(
				_furnace()->rootdir .
				"/app/model/model.yml"));
	}
	
	public function generate() {
		if (!$this->form) {
			$this->set('rootdir',_furnace()->rootdir);
			$bRootDirectorySet = 
				(_furnace()->rootdir != '' &&
				 _furnace()->rootdir != '/path/to/project/root');
			$bModelExists = file_exists(_furnace()->rootdir . '/app/model/model.yml');
			$this->set('preflt',array(
				'modelFileExists' =>$bModelExists,
				'rootDirectorySet'=>$bRootDirectorySet));
			$this->set('allgood', ($bRootDirectorySet && $bModelExists));
		}	
	}
	
	public function export($format="YML") {
		$this->init();
		$m = $this->getModel();
		$this->set('contents',$m->export($format));
	}
	
	public function saveModel() {
		file_put_contents(
			_furnace()->rootdir.
			"/app/model/model.yml",$this->form['contents']);
			
		$this->flash("model changes saved. Don't forget to "
			."<a class=\"ff\" href=\"/fuel/model/generate/\">regenerate your model objects</a>!");
		$this->redirect("/fuel/model/");
	}
	
	public function generateObjects() {
		$output = array();
		// Import required files
		 require_once(_furnace()->rootdir . "/lib/fuel/lib/generation/core/FObj.class.php");
		 require_once(_furnace()->rootdir . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		 require_once(_furnace()->rootdir . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		 require_once(_furnace()->rootdir . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		 require_once(_furnace()->rootdir . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		 require_once(_furnace()->rootdir . "/lib/fuel/lib/generation/building/FModel.class.php");
		 
		// Parse the YAML Model File
		 $model_data = _furnace()->parse_yaml(_furnace()->rootdir . "/app/model/model.yml");
		 
		 // Build a representation of the data
		 $model = new FModel($model_data);
		 
		 // Write the object code (individual and compiled)
		 $output[] =  "<h4>Generating PHP Object Code</h4><ul>";
		 $outputfile = fopen(_furnace()->rootdir . "/app/model/objects/compiled.php","w");
		 fwrite($outputfile,"<?php\r\n");
		 foreach ($model->objects as $obj) {
		 	$output[] = "<li>Writing class file: {$obj->getName()}</li>";
		 	$phpString = $obj->toPhpString();
			fwrite($outputfile,$phpString."\r\n\r\n");
			file_put_contents(_furnace()->rootdir . "/app/model/objects/{$obj->getName()}.class.php",
				"<?php\r\n{$phpString}");
		 }
		 fclose($outputfile); 
		 $output[] =  "</ul>";
		 $output[] =  "<h4>Generating SQL Schema File</h4><ul>";
		 
		 // Write the SQL Schema file
		 $sqlOutputFile = fopen(_furnace()->rootdir . "/app/model/model.sql","w");
		 foreach ($model->tables as $t) {
		 	$output[] =  "<li>Writing table definition for: {$t->getName()}</li>";
			fwrite($sqlOutputFile,$t->toSqlString()."\r\n\r\n");
		 }
		 
		 if ($model->use_accounts) {
		 	$fAccount = <<<END
-- 
-- Table structure for table `app_accounts`
-- 

CREATE TABLE `app_accounts` (
  `objId` int(11) unsigned NOT NULL auto_increment COMMENT 'The unique id of this object in the database',
  `username` varchar(20) NOT NULL COMMENT 'The username associated with this account',
  `password` varchar(160) NOT NULL COMMENT 'The password for the account',
  `emailAddress` varchar(80) NOT NULL COMMENT 'The email address associated with this account',
  `status` varchar(20) NOT NULL COMMENT 'The status of this account',
  `secretQuestion` varchar(160) NOT NULL COMMENT 'The secret question for access to this account',
  `secretAnswer` varchar(160) NOT NULL COMMENT 'The secret answer for the secret question',
  `objectClass` varchar(50) NOT NULL COMMENT 'The class of the primary object associated with this account',
  `objectId` int(11) unsigned NOT NULL COMMENT 'The id of the primary object associated with this account',
  PRIMARY KEY  (`objId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 COMMENT='table for application accounts' ;


-- 
-- Table structure for table `app_roles`
-- 

CREATE TABLE `app_roles` (
  `accountId` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`accountId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='permissions table for application accounts';

END;
		 } // END if(model->use_accounts)

		 fwrite($sqlOutputFile,$fAccount."\r\n\r\n");
		 fclose($sqlOutputFile);
		 
		 $output[] =  "</ul>";
		 $output[] =  "<h5>Finished.</h5>";
		 $this->set('results',implode($output));
	}

	
	public function createObject() {
		if ($this->form) {

			$this->init();
			$objectType   = FModel::standardizeName($this->form['objectName']);
			$objectParent = FModel::standardizeName($this->form['objectParent']);
			$lc_objectType= strtolower($objectType);

			// Validate the provided input
			if ("" == $objectType || 
				($objectParent != "FBaseObject" && $objectParent != "FAccount")
			) {
				$this->flash("<strong>Error!</strong> &nbsp;Please provide all required information before submitting!","error");
				$this->redirect("/fuel/model/");
			}
			// Actually create the object
			$m = $this->getModel();
			$m->objects[$lc_objectType] = new FObj($objectType,false,$objectParent);
			
			// Add the required SQL table
			$m->tables[$lc_objectType] = new FSqlTable($objectType);
			
			// If the object extends FAccount, add the 'faccount_id' column
			if ("FAccount" == $m->objects[$lc_objectType]->getParentClass()) {
				$extra = array("min"=>0);
				$col   = new FSqlColumn(
					"faccount_id",
					FSqlColumn::convertToSqlType("integer",$extra),
					false,
					false,
					"Link to account details for this {$objectType}");
				
				$m->tables[$lc_objectType]->addColumn($col,$extra);
			}
			
			// Write the changes to the model file
			$this->writeModelFile($m->export());
			
			// Execute SQL commands
			try {
				// If the object is derived from FAccount:
				if ("FAccount" == $objectParent) {
					
					// Verify that `app_accounts` and `app_roles` tables exist in the db
					$results = _db()->query("SHOW TABLES");
					$foundAppAccounts = false;
					$foundAppRoles    = false;
					while ($r = $results->fetchRow()) {
						if ("app_accounts" == $r[0]) {
							$foundAppAccounts = true;
						} else if ("app_roles" == $r[0]) {
							$foundAppRoles    = true;
						}
						if ($foundAppRoles && $foundAppAccounts) {
							break;
						}
					}
					
					// If they do not, create them:
					if (!$foundAppAccounts) {
						$appAccountsSql = <<<END
-- 
-- Table structure for table `app_accounts`
-- 

CREATE TABLE `app_accounts` (
  `objId` int(11) unsigned NOT NULL auto_increment COMMENT 'The unique id of this object in the database',
  `username` varchar(20) NOT NULL COMMENT 'The username associated with this account',
  `password` varchar(160) NOT NULL COMMENT 'The password for the account',
  `emailAddress` varchar(80) NOT NULL COMMENT 'The email address associated with this account',
  `status` varchar(20) NOT NULL COMMENT 'The status of this account',
  `secretQuestion` varchar(160) NOT NULL COMMENT 'The secret question for access to this account',
  `secretAnswer` varchar(160) NOT NULL COMMENT 'The secret answer for the secret question',
  `objectClass` varchar(50) NOT NULL COMMENT 'The class of the primary object associated with this account',
  `objectId` int(11) unsigned NOT NULL COMMENT 'The id of the primary object associated with this account',
  PRIMARY KEY  (`objId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 COMMENT='table for application accounts' ;
END;
						_db()->exec($appAccountsSql);
						$this->flash("Created required `app_accounts` table");
					}
					if (!$foundAppRoles) {
						$appRolesSql = <<<END
-- 
-- Table structure for table `app_roles`
-- 

CREATE TABLE `app_roles` (
  `accountId` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`accountId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='permissions table for application accounts';
END;
						_db()->exec($appRolesSql);
						$this->flash("Created required `app_roles` table");
					}
				}
				_db()->exec($m->tables[$lc_objectType]->toSqlString());
			} catch (FDatabaseException $e) {
				die($e->__toString());
			}
			
			// Regenerate PHP code
			$this->generateObjects();
			
			
			// Redirect to the new object's edit page
			$this->redirect("/fuel/model/editObject/{$objectType}");
		}
	}
	
	public function editObject($objectType='') {
		if ($this->form) {
			$name = $this->form['selectedObject'];
		} else {
			$name = $objectType;
		}
		
		$this->init();
		$m = $this->getModel();
		if (!isset($m->objects[strtolower($name)])) {
			die("Object '{$name}' is not defined in the model.");
		}
		$this->set('model',$m);
		$this->set('object',$m->objects[strtolower($name)]);
		$relationships = array();
		foreach ($m->objects[strtolower($name)]->getSockets() as $s) {
			if ($s->getQuantity() == "M" ) {
				if ($s->doesReflect()) {
					$relationships[] = $s;
				} else {
					$children[] = $s;
				}
			}
		}
		$this->set('relationships',$relationships);
		$this->set('children',$children);
	}
	
	public function editField($ot='',$attr='') {
		if ($this->form) {
			
		} else {
			$objectType = $ot;
			$attrName   = $attr;
			
			if ($objectType == '' || $attr='') {
				die("Not enough information provided.");
			}
			
			$this->init();
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($objectType)])) {
				die("Object '{$objectType}' is not defined in the model.");
			}
			
			$attribute = false;
			foreach ($m->objects[strtolower($objectType)]->getAttributes() as $a) {
				if ($a->getName() == $attrName) {
					$attribute = $a;
				}
			}
			
			if (! $attribute) {
				die("Attribute {$attrName} is not defined in object {$objectType}.");
			}
			
			$this->set('model',$m);
			$this->set('object',$m->objects[strtolower($objectType)]);
			$this->set('attr',$attribute);
		}
	}

	public function addAttribute() {
		if ($this->form) {

			$objectClass = $this->form['objectClass'];
			
			$this->init();
			
			$attr = new FObjAttr(FModel::standardizeAttributeName($this->form['attrName']));
			$attr->setDescription($this->form['attrDescription']);
			$attr->setType($this->form['attrType']);
			$attr->setSize($this->form['attrSize']);
			$attr->setMin($this->form['attrMin']);
			$attr->setMax($this->form['attrMax']);
			$attr->setDefaultValue($this->form['attrDefault']);
			$attr->setIsUnique(isset($this->form['attrUnique']));
			
			// Prepare extra information about the attribute
			$columnExtraData = array(
				'size' => $attr->getSize(),
				'min'  => $attr->getMin(),
				'max'  => $attr->getMax());
				
			// Create an FSqlColumn instance for the attribute
			$column = new FSqlColumn(
				$attr->getName(),									/* name */
				FSqlColumn::convertToSqlType($attr->getType(),$columnExtraData), /* type */
				false,												/* null */
				false,												/* autoinc */
				$attr->getDescription());							/* description */

			// Handle uniqueness
			if ($attr->isUnique()) {
				$column->setKey("UNIQUE");
			}
			
			// Handle default value
			if (false === $attr->getDefaultValue()) {
				$column->setDefaultValue('0');	
			} else if (true === $attr->getDefaultValue()) {
				$column->setDefaultValue('1');
			} else {
				$column->setDefaultValue($attr->getDefaultValue());	
			}
			
			
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($objectClass)])) {
				die("Object '{$objectClass}' is not defined in the model.");
			}
			
			// Add the attribute and the column to the model
			$m->objects[strtolower($objectClass)]->addAttribute($attr);
			$m->tables[strtolower($objectClass)]->addColumn($column);
			
			// Write the changes to the model file
			$this->writeModelFile($m->export());
			
			// Execute SQL commands
			try {
				_db()->exec("ALTER TABLE `{$objectClass}` ADD COLUMN {$column->toSqlString()}");
				if ($attr->isUnique()) {
					_db()->exec("ALTER TABLE `{$objectClass}` ADD UNIQUE (`{$attr->getName()}`) ");
				}
			} catch (FDatabaseException $e) {
				die($e->__toString());	
			}
			
			// Regenerate PHP code
			$this->generateObjects();
					
			// Redirect to the edit page
			$this->flash("Added attribute '{$attr->getName()}' to object '{$objectClass}'");
			$this->redirect("/fuel/model/editObject/{$objectClass}");
		}
	}
	
	public function editAttribute() {
		if ($this->form) {
			
			// INITIAL SETUP
			$this->init();
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($this->form['objectType'])]) ) {
				die("Object '{$objectType}' is not defined in the model.");
			}
			
			$attribute = false;
			$attributes =& $m->objects[strtolower($this->form['objectType'])]->getAttributes();
			for ($i =0; $i < count($attributes); $i++) {
				if ($attributes[$i]->getName() == $this->form['attrName']) {
					$attribute =& $attributes[$i];
					break;
				}
			}
			
			if (! $attribute) {
				die("Attribute {$this->form['attrName']} is not defined in object {$this->form['objectType']}.");
			}
			
			
			$column =& $m->tables[strtolower($this->form['objectType'])]->getColumn($this->form['attrName']);
			
			
			if ($this->form['action'] == "rename") {
				// RENAME AN ATTRIBUTE
				$columnOldName = $column->getName();
				$newName = FModel::standardizeAttributeName($this->form['attrNewName']);
				$attribute->setName($newName);
				$column->setName($newName);

				try {
					$query = "ALTER TABLE `{$this->form['objectType']}` CHANGE COLUMN `{$columnOldName}` {$column->toSqlString()}";
					_db()->exec($query);
				} catch (FDatabaseException $e) {
					die($e->__toString());	
				}
		
				$this->flash("Renamed attribute");
			}
			
			// Write changes to the model file
			$this->writeModelFile($m->export());
			$this->generateObjects();
			$this->redirect("/fuel/model/editObject/{$this->form['objectType']}");
		}
	}
	
	public function deleteAttribute($objectClass,$attributeName) {
		$this->init();
		$m = $this->getModel();
		if (!isset($m->objects[strtolower($objectClass)])) {
			die("Object '{$objectClass}' is not defined in the model.");
		}
		$object = $m->objects[strtolower($objectClass)];
		if ($object->deleteAttribute($attributeName)) {
			// Write the changes to the model file
			$this->writeModelFile($m->export());
			
			// Execute SQL commands
			try {
				_db()->exec("ALTER TABLE `{$objectClass}` DROP COLUMN `{$attributeName}`");
				_db()->exec("ALTER TABLE `{$objectClass}` DROP INDEX `{$attributeName}` ");
			} catch (FDatabaseException $e) {
				// silently ignore
			}
			
			// Regenerate PHP code
			$this->generateObjects();
			
			// Redirect to the new object's edit page
			$this->flash("Deleted attribute '{$attributeName}'");
			$this->redirect("/fuel/model/editObject/{$objectClass}");
		} else {
			// Warn of the failure
			$this->flash("Delete failed: attribute '{$attributeName}' does not exist for object of type '{$objectClass}'.","error");
			$this->redirect("/fuel/model/editObject/{$objectClass}");
		}
	}
	
	public function addDependency() {
		if ($this->form) {
			$this->init();
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($this->form['objectClass'])]) ) {
				die("Object '{$objectClass}' is not defined in the model.");
			}
			if (!isset($m->objects[strtolower($this->form['dependingClass'])]) ) {
				die("Object '{$dependingClass}' is not defined in the model.");
			}
			$localObject =& $m->objects[strtolower($this->form['objectClass'])];
			
			// Update the object's dependency data
			$localObject->addDependency(strtolower($this->form['dependingClass']),
				(("" == $this->form['matchVariable'])
					? ""
					: strtolower("{$this->form['dependingClass']}.{$this->form['matchVariable']}")),
				FModel::standardizeAttributeName($this->form['socketName']));
		
			// Create a socket to service this dependency. This allows a child
			// object to call upon its parent
			$s = new FObjSocket(FModel::standardizeAttributeName($this->form['socketName']),
				$this->form['objectClass']);
			$s->setForeign($this->form['dependingClass']);
			$s->setDescription($this->form['description']);
			$s->setQuantity("1");
			$s->setReflection(false);
			$s->setLookupTable(FModel::standardizeName($this->form['objectClass']));
			$s->setVisibility($m->config['AttributeVisibility']);
			$localObject->addSocket($s);
			$columnDescription = $s->getDescription();
			
			if ("" != $this->form['matchVariable']) {
				$foreignObject = $m->objects[strtolower($this->form['dependingClass'])];

				// Create a socket for the foreign object.
				$s = new FObjSocket(FModel::standardizeAttributeName($this->form['matchVariable']),
					$this->form['dependingClass'],
					/*$localObject->getName()
						. "."
						. */FModel::standardizeAttributeName($this->form['socketName'])
					);
				$s->setForeign($this->form['objectClass']);
				$s->setDescription("Auto-generated reflection of {$this->form['objectClass']}::{$this->form['socketName']} ");
				$s->setQuantity("M");
				$s->setReflection(false);
				$s->setLookupTable(FModel::standardizeName($this->form['objectClass']));
				$s->setVisibility($m->config['AttributeVisibility']);
				$foreignObject->addSocket($s);
			}
			
			// SQL modifications here...
			$columnExtraData = array('min' => 0);
				
			$column = new FSqlColumn(
				FModel::standardizeAttributeName($this->form['socketName'])."_id",	/* name */
				FSqlColumn::convertToSqlType("integer",$columnExtraData), 			/* type */
				false,												/* null */
				false,												/* autoinc */
				$columnDescription);								/* description */

			// Write the changes to the model file
			$this->writeModelFile($m->export());
			
			// Execute SQL commands
			try {
				$q = "ALTER TABLE `" . FModel::standardizeName($this->form['objectClass'])
					."` ADD COLUMN {$column->toSqlString()} AFTER `objId`";
				_db()->exec($q);
			} catch (FDatabaseException $e) {
				die($e->__toString());	
			}
			
			// Regenerate PHP code
			$this->generateObjects();
			

			$this->flash("Added dependency on '{$this->form['dependingClass']}' to '{$this->form['objectClass']}' ");
			$this->redirect("/fuel/model/editObject/{$this->form['objectClass']}");
		}
	}
	
	public function editDependency() {
		
	}
	
	public function deleteDependency($objectClass,$foreignClass,$localAttribute,$foreignAttribute='') {
		$this->init();
		$m = $this->getModel();
		if (!isset($m->objects[strtolower($objectClass)])) {
			die("Object '{$objectClass}' is not defined in the model.");
		}
		if (!isset($m->objects[strtolower($foreignClass)])) {
			die("Object '{$foreignClass}' is not defined in the model.");
		}
		$localObject   =& $m->objects[strtolower($objectClass)];
		$foreignObject =& $m->objects[strtolower($foreignClass)];
		$localSockets  = $localObject->getSockets();
		$actualRemoteVariableName = FModel::standardizeAttributeName($localAttribute);
		for ($i = 0; $i < count($localSockets); $i++) {
			if ($localSockets[$i]->getQuantity() == "1" 
				&& strtolower($localSockets[$i]->getForeign()) == strtolower($foreignClass)
				&& strtolower($localSockets[$i]->getName()) == strtolower($localAttribute)) {
					
				// Delete this socket
				$localObject->deleteSocket($localSockets[$i]->getName());
				
				// Delete the foreign object's socket, if one exists
				if ("" != $foreignAttribute) {
					// Trim out '.' if it exists
					$fa = substr($foreignAttribute,min(strlen($foreignAttribute),strpos($foreignAttribute,".")+1));
					$foreignSockets =& $foreignObject->getSockets();
					for ($j = 0; $j < count($foreignSockets); $j++) {
						if (strtolower($foreignSockets[$j]->getName()) == strtolower($fa)) {
							$foreignObject->deleteSocket($foreignSockets[$j]->getName());
						}
					}
				}	
				break;
			}
		}
		
		// Write the changes to the model file
		$this->writeModelFile($m->export());
		
		// Execute SQL commands
		try {
			$q = "ALTER TABLE `{$localObject->getName()}` DROP COLUMN `"
				. FModel::standardizeAttributeName($actualRemoteVariableName)
				. "_id` ";
			_db()->exec($q);
		} catch (FDatabaseException $e) {
			die($e->__toString());	
		}
		
		// Regenerate PHP code
		$this->generateObjects();
			

		$this->flash("Deleted dependency on '{$foreignClass}' by '{$objectClass}' ");
		$this->redirect("/fuel/model/editObject/{$objectClass}");
	}
	
	public function addMMRelationship() {
		if ($this->form) {
			$this->init();
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($this->form['objectClass'])]) ) {
				die("Object '{$objectClass}' is not defined in the model.");
			}
			if (!isset($m->objects[strtolower($this->form['dependingClass'])]) ) {
				die("Object '{$objectClass}' is not defined in the model.");
			}
			$localObject =& $m->objects[strtolower($this->form['objectClass'])];
			
			// Create a socket to service this dependency. 
			$s = new FObjSocket(FModel::standardizeAttributeName($this->form['socketName']),
				$this->form['objectClass'],
				FModel::standardizeAttributeName($this->form['matchVariable']));
			$s->setForeign($this->form['dependingClass']);
			$s->setDescription($this->form['description']);
			$s->setQuantity("M");
			$s->setReflection(true,FModel::standardizeAttributeName($this->form['matchVariable']));
			
			
			// Determine Lookup table name for the socket
			$ordered_names = array(
				$this->form['objectClass'],
				$this->form['dependingClass']
			);
			sort($ordered_names);
			
			if ($ordered_names[0] == $this->form['objectClass']) {
				$lookupTable = FModel::standardizeName($this->form['objectClass'])
					. "_" . FModel::standardizeName($this->form['dependingClass'])
					. "_" . FModel::standardizeAttributeName($this->form['socketName']);
			} else {
				$lookupTable = FModel::standardizeName($this->form['dependingClass'])
					. "_" . FModel::standardizeName($this->form['objectClass'])
					. "_" . FModel::standardizeAttributeName($this->form['matchVariable']);
			}
			
			$s->setLookupTable($lookupTable);
			$s->setVisibility($m->config['AttributeVisibility']);
			$localObject->addSocket($s);
			
			if ("" != $this->form['matchVariable']) {
				$foreignObject = $m->objects[strtolower($this->form['dependingClass'])];

				// Create a socket for the foreign object.
				$s = new FObjSocket(FModel::standardizeAttributeName($this->form['matchVariable']),
					$this->form['dependingClass'],
					FModel::standardizeAttributeName($this->form['socketName']));
				$s->setForeign($this->form['objectClass']);
				$s->setDescription("Auto-generated reflection of {$this->form['objectClass']}::{$this->form['socketName']} ");
				$s->setQuantity("M");
				$s->setReflection(true,FModel::standardizeAttributeName($this->form['socketName']));
				$s->setLookupTable($lookupTable);
				$s->setVisibility($m->config['AttributeVisibility']);
				$foreignObject->addSocket($s);
			}
			
			// SQL modifications here...
			$lt = new FSqlTable($lookupTable,true);
			$lc_pk1name = FModel::standardizeAttributeName($localObject->getName());
			$lc_pk2name = FModel::standardizeAttributeName($this->form['dependingClass']);
			
			if ($lc_pk1name == $lc_pk2name) {
				$lc_pk1name .= "1";
				$lc_pk2name .= "2";
			}
			
			$c1 = new FSqlColumn("{$lc_pk1name}_id","INT(11) UNSIGNED");
			$c1->setKey("PRIMARY");
			$c2 = new FSqlColumn("{$lc_pk2name}_id","INT(11) UNSIGNED");
			$c2->setKey("PRIMARY");
			$lt->addColumn($c1);
			$lt->addColumn($c2);
			
			$this->tables[strtolower($lt->getName())] = $lt;
			
			// Execute SQL Commands
			try {
				_db()->exec($lt->toSqlString()); 	
			} catch (FDatabaseException $e) {
				die($e->__toString());	
			}							

			// Write the changes to the model file
			$this->writeModelFile($m->export());
			
			// Regenerate PHP code
			$this->generateObjects();

			$this->flash("Added peer relationship between '{$this->form['dependingClass']}' and '{$this->form['objectClass']}' ");
			$this->redirect("/fuel/model/editObject/{$this->form['objectClass']}");
		}
	}
	
	public function editMMRelationship() {
		
	}
	
	public function deleteMMRelationship($objectClass,$foreignClass,$localAttribute,$foreignAttribute='') {
		$this->init();
		$m = $this->getModel();
		if (!isset($m->objects[strtolower($objectClass)])) {
			die("Object '{$objectClass}' is not defined in the model.");
		}
		if (!isset($m->objects[strtolower($foreignClass)])) {
			die("Object '{$foreignClass}' is not defined in the model.");
		}
		$localObject   =& $m->objects[strtolower($objectClass)];
		$foreignObject =& $m->objects[strtolower($foreignClass)];
		$localSockets  = $localObject->getSockets();
		for ($i = 0; $i < count($localSockets); $i++) {
			if ($localSockets[$i]->getQuantity() == "M" 
				&& strtolower($localSockets[$i]->getForeign()) == strtolower($foreignClass)
				&& strtolower($localSockets[$i]->getName()) == strtolower($localAttribute)) {

				// Delete the SQL table
				// make it impossible to delete a remote dependency from this location.
				// Only delete an sql table if the socket has reflection (is MM)
				if ($localSockets[$i]->doesReflect()) {
					try {
						$q = "DROP TABLE `{$localSockets[$i]->getLookupTable()}`";
						_db()->exec($q);
					} catch (FDatabaseException $e) {
						die($e->__toString());	
					}
				}
				
				// Delete the local object's socket
				$localObject->deleteSocket($localSockets[$i]->getName());
				
				// Delete the foreign object's socket, if one exists, and only if it is 
				if ("" != $foreignAttribute) {
					$foreignSockets =& $foreignObject->getSockets();
					for ($j = 0; $j < count($foreignSockets); $j++) {
						if (strtolower($foreignSockets[$j]->getName()) == strtolower($foreignAttribute)) {
							
							if ("M" == $foreignSockets[$j]->getQuantity()) {
								// ONLY delete the foreign socket if it is a MM relationship. Anything else
								// could potentially allow a user to unintentionally destroy a dependency relationship.
								// Dependencies should only be deleted in the 'dependencies' section
								if (!$foreignObject->deleteSocket($foreignSockets[$j]->getName())) {
									die("delete foreign socket failed.");
								}
							}
						}
					}
				}	
				break;
			}
		}

		
		// Write the changes to the model file
		$this->writeModelFile($m->export());
		
		// Regenerate PHP code
		$this->generateObjects();

		$this->flash("Deleted M-M relationship '{$localAttribute}' between '{$objectClass}', '{$foreignClass}' ");
		$this->redirect("/fuel/model/editObject/{$objectClass}");
	}
	
	public function deleteObject() {
		if ($this->form) {
			$name = $this->form['selectedObject'];
			
			$this->init();
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($name)])) {
				die("Object '{$name}' is not defined in the model.");
			}
			
			
		}
	}
	
	private function writeModelFile($contents) {
		file_put_contents($GLOBALS['furnace']->rootdir . "/app/model/model.yml",$contents);
	}
	
	private function init() {
		require_once($GLOBALS['furnace']->rootdir . "/lib/furnace/foundation/database/".$GLOBALS['furnace']->config['db_engine']."/FDatabase.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FObj.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/building/FModel.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/dbmgmt/FDatabaseSchema.class.php");
	}
	private function getModel() {
		return new FModel(
			$GLOBALS['furnace']->parse_yaml($GLOBALS['furnace']->rootdir . "/app/model/model.yml")
		);
	}
	private function getSchema() {
		$d = new FDatabaseSchema();
		if (_furnace()->config['debug_level'] > 0) {
			$d->discover(_furnace()->config['debug_dsn']);
		} else {
			$d->discover(_furnace()->config['production_dsn']);
		}
		return $d;
	}
}
?>