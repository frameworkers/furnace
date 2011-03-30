<?php
use vendors\tadpole\TadpoleEngine;
use org\frameworkers\furnace\persistance\orm\pdo\gen\PhpClassGenerator;
use vendors\spyc\Spyc;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\persistance\orm\pdo\model\Model;
use org\frameworkers\furnace\persistance\orm\pdo\model\Table;
use org\frameworkers\furnace\connections\Connections;
use org\frameworkers\furnace\persistance\cache\Cache;
use org\frameworkers\furnace\persistance\orm\pdo\DataSource;
use org\frameworkers\furnace\persistance\orm\pdo\sql\SqlBuilder;
use org\frameworkers\furnace\action\Controller;
use org\frameworkers\furnace\persistance\orm\pdo\model\Lang;

class ORMModelController extends Controller {

	protected $modelDirectory;

	public function __construct($context, $response) {
		parent::__construct($context, $response);
		$this->modelDirectory = Config::Get('applicationModelsDirectory'). "/orm";

		// Example of using a pre-packaged widget in a zone:
		if ($this->request->responseType == 'html') {
			$this->response->layout->menu->assign(
			new org\frameworkers\flame\widgets\mainMenu\MainMenuWidget($context,$response));
			$this->response->layout->menu->set('activeTab','orm');
		}
	}

	public function index() {
		$this->response->includeJavascript('/ORMModel/index/index.js',true);
		$this->response->includeStylesheet('/ORMModel/index/index.css',true);
		
		// Show the tables in the default database
		$tables     = array();
		$tableNames = Connections::Get()->getTables();
		foreach ($tableNames as $tableName) {
			$tables[$tableName] = Connections::Get()->describeTable($tableName);
			$tables[$tableName]->tableExists  = true;
			$tables[$tableName]->tableInModel = false;
		}

		// Add the tables the model says should exist, but don't
		foreach (Model::Get()->objects as $object) {
			if (!isset($tables[$object->table->name])) {
				$tables[$object->table->name] = new Table($object->table->name);
				$tables[$object->table->name]->tableExists = false;
			}
			// All of these are in the model, by definition
			$tables[$object->table->name]->tableInModel = true;
		}

		// Table by table evaluation
		foreach ($tables as $t) {
			$columns = $this->analyzeTable($t);
			
			// Is any action required on this table?
			$tables[$t->name]->actionRequired = false;
			foreach ($columns as $c) {
				if ($c->columnExists == false 
					|| $c->columnInModel == false
					|| $c->diffDetected == true) {
					$tables[$t->name]->actionRequired = true;
					break;			
				}
			}
		}

		// Send the results to the view
		$this->response->layout->content->set('tables',$tables);
		
		$this->response->layout->content->set('sortedObjects',Model::Get()->objects);
	}

	public function generateClassFiles() {

		if (is_writable($this->modelDirectory)) {
			$count = 0;
			foreach (Model::Get()->objects as $o) {
				$classContents = PhpClassGenerator::GenerateManagedClassForObject($o,Model::Get());
				if (file_put_contents("{$this->modelDirectory}/managed/{$o->className}.class.php",$classContents)) {
					$count++;
				} else {
					echo "<span style='color:red'>Aborted: error writing managed class for `{$o->name}`</span>";
				}
				// Generate the user customizable class, if it does not exist
				if (!file_exists("{$this->modelDirectory}/{$o->className}.class.php")) {
					$classContents = PhpClassGenerator::GenerateCustomClassForObject($o);
					if (file_put_contents("{$this->modelDirectory}/{$o->className}.class.php",$classContents)) {
						$count++;
					} else {
						echo "<span style='color:red'>Aborted: error writing user class stub for `{$o->className}`</span>";
					}
				}
			}
			echo "<span style='color:green'>Success! (updated {$count} files)</span>";
				
		} else {
			echo "<span style='color:red'>Could not write to output directory</span>";
		}
		exit();
	}

	public function compareModelToDatabase() {

		
	}

	public function synchronize($tableName) {
		$dbTable = Connections::Get()->describeTable($tableName);

		// Find the corresponding model object
		$objs = Model::Get()->objects;
		foreach ($objs as $o) {
			if ($o->table->name == $tableName) {
				$this->response->layout->content->set('object',$o);
			}
		}
		
		$this->response->layout->content->set('tableName',$dbTable->name);
		$this->response->layout->content->set('tableExists',$dbTable->tableExists);
		$this->response->layout->content->set('columns',$this->analyzeTable($dbTable));
	}
	
	protected function getObjectForTable($tableName) {
		$m = Model::Get();
		$lcname = strtolower($tableName);
		foreach ($m->objects as $o) {
			if (strtolower($o->table->name) == $lcname) { return $o; }
		}
		return false;
	}
	
	public function syncApplyModelToColumn($tableName,$fieldName) {
		// Find the corresponding model object column definition
		$fieldName = Lang::ToColumnName($fieldName);
		if ($obj = $this->getObjectForTable($tableName)) {
			$t = $obj->table;
			if (isset($t->columns[$fieldName])) {
				Connections::Get()->tableChangeColumn(
					$tableName,
					$fieldName,
					$t->columns[$fieldName]);
				$this->response->set('success',true);
				$this->response->set('message',"Model definition applied successfully");
			} else {
				$this->response->set('success',false);
				$this->response->set('message',"Column `{$fieldName}` not defined in model");
			}
		} else {
			$this->response->set('success',false);
			$this->response->set('message',"Unable to find model object for table `{$tableName}` ");
		}
	}
	
	public function syncRenameExistingColumn($tableName,$fromField,$toField) {
		// Find the corresponding model object column definition
		$toField = Lang::ToColumnName($toField);
		if ($obj = $this->getObjectForTable($tableName)) {
			$t = $obj->table;
			if (isset($t->columns[$toField])) {
				Connections::Get()->tableChangeColumn(
					$tableName,
					$fromField,
					$t->columns[$toField]);
				$this->response->set('success',true);
				$this->response->set('message',"Column renamed successfully");
			} else {
				$this->response->set('success',false);
				$this->response->set('message',"Column `{$toField}` not defined in model");
			}
		} else {
			$this->response->set('success',false);
			$this->response->set('message',"Unable to find model object for table `{$tableName}` ");
		}
	}
	
	public function syncCreateColumn($tableName,$fieldName) {
		// Find the corresponding model object column definition
		$fieldName = Lang::ToColumnName($fieldName);
		if ($obj = $this->getObjectForTable($tableName)) {
			$t = $obj->table;
			if (isset($t->columns[$fieldName])) {
				Connections::Get()->tableAddColumn(
					$tableName,
					$t->columns[$fieldName]);
				$this->response->set('success',true);
				$this->response->set('message',"Column created successfully");
			} else {
				$this->response->set('success',false);
				$this->response->set('message',"Column `{$fieldName}` not defined in model");
			}
		} else {
			$this->response->set('success',false);
			$this->response->set('message',"Unable to find model object for table `{$tableName}` ");
		}
	}
	
	public function syncDeleteColumn($tableName,$fieldName) {
		// Find the corresponding model object column definition
		$fieldName = Lang::ToColumnName($fieldName);
		if ($obj = $this->getObjectForTable($tableName)) {
			
			Connections::Get()->tableDropColumn(
				$tableName,
				$fieldName);
			$this->response->set('success',true);
			$this->response->set('message',"Column dropped successfully");
		} else {
			$this->response->set('success',false);
			$this->response->set('message',"Unable to find model object for table `{$tableName}` ");
		}
	}

	public function exportModelAsSql() {
		$this->response->setLayout('[_content_]',true);
		$output = '';
		$m = Model::Get();
		foreach ($m->objects as $o) {
			$t = $o->table;
			$output .= '-- Table: ' . $t->name . "\r\n";
			$output .= Connections::Get()->createTableSql($t);
			$output .= ";\r\n\r\n";
		}
		$this->response->layout->content->prepare('[sql]',true)
		->set('sql',$output);
	}

	protected function analyzeTable($databaseTable) {
		$objs = Model::Get()->objects;
		$object  = false;
		foreach ($objs as $obj) {
			if ($obj->table->name == $databaseTable->name) {
				$object = $obj;
				break;
			}
		}

		// If no matching table found, return false
		if (!$object) { return false; }

		$modelTable = $object->table;
		// Show the columns in the database table
		$columns    = array();
		foreach ($databaseTable->getColumns() as $c) {
			$columns[$c->name] = $c;
			$columns[$c->name]->columnExists = true;
			$columns[$c->name]->columnInModel= false;
			$columns[$c->name]->diffDetected = false;
		}

		// Add the columns the model says should be there
		foreach ($modelTable->getColumns() as $c) {
			if (!isset($columns[$c->name])) {
				$columns[$c->name] = $c;
				$columns[$c->name]->columnExists = false;
				$columns[$c->name]->columnInModel= true;
				$columns[$c->name]->diffDetected = false;
			} else {
				$columns[$c->name]->columnInModel = true;
				// Compare attributes to detect differences
				if (strtoupper($c->type) !== strtoupper($columns[$c->name]->type)
				|| $c->null !== $columns[$c->name]->null
				|| $c->isPrimary !== $columns[$c->name]->isPrimary
				|| $c->key  !== $columns[$c->name]->key) {
					$columns[$c->name] = $c;
					$columns[$c->name]->columnExists  = true;
					$columns[$c->name]->columnInModel = false;
					$columns[$c->name]->diffDetected  = true;
				}
			}
		}

		return $columns;
	}

	public function createTable($objectName,$bForce=false) {
		$objectName = Lang::ToClassName($objectName);
		if (false != ($obj = Model::Get()->objects[$objectName])) {
			$tableName = $obj->table->name;
			if ($bForce) {
				Connections::Get()->dropTable($tableName);
			}
			$sql = Connections::Get()->createTableSql($obj->table);
			Connections::Get()->exec($sql);
			$this->response->set('success',true);
			$this->response->set('message','table created successfully');
		} else {
			$this->response->set('success',false);
			$this->response->set('message','model object for table not found');
		}
	}
}