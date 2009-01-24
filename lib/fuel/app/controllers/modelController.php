<?php

class ModelController extends Controller {
	
	private $dsn;
	
	
	public function __construct() {
		parent::__construct();
		
		if ($GLOBALS['fconfig_debug_level'] > 0 && 
			$GLOBALS['fconfig_debug_dsn'] == 'mysql://user:password@server/dbname') {
			die("No debug database specified. Please edit the 'fconfig_debug_dsn' variable in your application config file");
		} else if ($GLOBALS['fconfig_debug_level'] == 0 &&
				   $GLOBALS['fconfig_production_dsn'] == 'mysql://user:password@server/dbname') {
			die("No production database specified. Please edit the 'fconfig_production_dsn' variable in your application config file");	   	
		}
		
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$this->dsn = $GLOBALS['fconfig_debug_dsn'];
		} else {
			$this->dsn = $GLOBALS['fconfig_production_dsn'];
		}
	}
	
	public function index() {
		$this->init();							// Load required files
		$d = new FDatabaseSchema();				// Create an FDatabaseSchema object
		$d->discover($this->dsn);				// Load the currently active database
		$this->set('model',$this->getModel());	// Register the model with Tadpole
		
	}
	public function editor() {
		if (!file_exists($GLOBALS['fconfig_root_directory'] . 
			"/app/model/model.yml")) {
			file_put_contents($GLOBALS['fconfig_root_directory'] . 
			"/app/model/model.yml",file_get_contents(
				$GLOBALS['fconfig_root_directory'] .
					"/app/model/model.yml.example"));		
		}
		$this->set('modelcontents',
			file_get_contents(
				$GLOBALS['fconfig_root_directory'].
				"/app/model/model.yml"));
	}
	
	public function generate() {
		if (!$this->form) {
			$this->set('rootdir',$GLOBALS['fconfig_root_directory']);
			$bRootDirectorySet = 
				($GLOBALS['fconfig_root_directory'] != '' &&
				 $GLOBALS['fconfig_root_directory'] != '/path/to/project/root');
			$bModelExists = file_exists($GLOBALS['fconfig_root_directory'] . '/app/model/model.yml');
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
			$GLOBALS['fconfig_root_directory'].
			"/app/model/model.yml",$this->form['contents']);
			
		$this->flash("model changes saved. Don't forget to "
			."<a class=\"ff\" href=\"/fuel/model/generate/\">regenerate your model objects</a>!");
		$this->redirect("/fuel/model/");
	}
	
	public function generateObjects() {
		$output = array();
		// Import required files
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObj.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/building/FModel.class.php");
		 
		// Parse the YAML Model File
		 $model_data = FYamlParser::Parse($GLOBALS['fconfig_root_directory'] . "/app/model/model.yml");
		 
		 // Build a representation of the data
		 $model = new FModel($model_data);
		 
		 // Write the object code (individual and compiled)
		 $output[] =  "<h4>Generating PHP Object Code</h4><ul>";
		 $outputfile = fopen($GLOBALS['fconfig_root_directory'] . "/app/model/objects/compiled.php","w");
		 fwrite($outputfile,"<?php\r\n");
		 foreach ($model->objects as $obj) {
		 	$output[] = "<li>Writing class file: {$obj->getName()}</li>";
		 	$phpString = $obj->toPhpString();
			fwrite($outputfile,$phpString."\r\n\r\n");
			file_put_contents($GLOBALS['fconfig_root_directory'] . "/app/model/objects/{$obj->getName()}.class.php",
				"<?php\r\n{$phpString}");
		 }
		 fclose($outputfile); 
		 $output[] =  "</ul>";
		 $output[] =  "<h4>Generating SQL Schema File</h4><ul>";
		 
		 // Write the SQL Schema file
		 $sqlOutputFile = fopen($GLOBALS['fconfig_root_directory'] . "/app/model/model.sql","w");
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
			_db()->exec($m->tables[$lc_objectType]->toSqlString());
			
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
			
			$columnExtraData = array(
				'size' => $attr->getSize(),
				'min'  => $attr->getMin(),
				'max'  => $attr->getMax());
				
			$column = new FSqlColumn(
				$attr->getName(),									/* name */
				FSqlColumn::convertToSqlType($attr->getType(),$columnExtraData), /* type */
				false,												/* null */
				false,												/* autoinc */
				$attr->getDescription());							/* description */

			if ($attr->isUnique()) {
				$column->setKey("UNIQUE");
			}
			
			
			$m = $this->getModel();
			if (!isset($m->objects[strtolower($objectClass)])) {
				die("Object '{$objectClass}' is not defined in the model.");
			}
			
			$object = $m->objects[strtolower($objectClass)];
			$object->addAttribute($attr);
			$m->tables[strtolower($objectClass)]->addColumn($column);
			
			// Write the changes to the model file
			$this->writeModelFile($m->export());
			
			// Execute SQL commands
			_db()->exec("ALTER TABLE `{$objectClass}` ADD COLUMN {$column->toSqlString()}");
			if ($attr->isUnique()) {
				_db()->exec("ALTER TABLE `{$objectClass}` ADD UNIQUE (`{$attr->getName()}`) ");
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
			_db()->exec("ALTER TABLE `{$objectClass}` DROP COLUMN `{$attributeName}`");
			try {
				_db()->exec("ALTER TABLE `{$objectClass}` DROP INDEX `{$attributeName}` ");
			} catch (Exception $e) {
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
			$q = "ALTER TABLE `" . FModel::standardizeName($this->form['objectClass'])
				."` ADD COLUMN {$column->toSqlString()} AFTER `objId`";
			_db()->exec($q);
			
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
		$q = "ALTER TABLE `{$localObject->getName()}` DROP COLUMN `"
			. FModel::standardizeAttributeName($actualRemoteVariableName)
			. "_id` ";
		_db()->exec($q);
		
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
			_db()->exec($lt->toSqlString()); 								

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
					$q = "DROP TABLE `{$localSockets[$i]->getLookupTable()}`";
					_db()->exec($q);
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
		file_put_contents("{$GLOBALS['fconfig_root_directory']}/app/model/model.yml",$contents);
	}
	
	private function init() {
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/furnace/foundation/database/MDB2/FDatabase.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObj.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/building/FModel.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/dbmgmt/FDatabaseSchema.class.php");
	}
	private function getModel() {
		return new FModel(
			FYamlParser::parse(
				file_get_contents($GLOBALS['fconfig_root_directory'] . "/app/model/model.yml")
			)
		);
	}
	private function getSchema() {
		$d = new FDatabaseSchema();
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$d->discover($GLOBALS['fconfig_debug_dsn']);
		} else {
			$d->discover($GLOBALS['fconfig_production_dsn']);
		}
		return $d;
	}
}
?>