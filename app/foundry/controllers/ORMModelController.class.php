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

class ORMModelController extends Controller {
	
	protected $modelDirectory;
	
	public function __construct($context, $response) {
		parent::__construct($context, $response);
		$this->modelDirectory = Config::Get('applicationModelsDirectory'). "/orm";
		
		// Example of using a pre-packaged widget in a zone:
		$this->response->layout->menu->assign(
			new org\frameworkers\flame\widgets\mainMenu\MainMenuWidget($context,$response));
	}
	
	public function index() {
		$this->response->includeJavascript('/ORMModel/index/index.js',true);
		$this->response->includeStylesheet('/ORMModel/index/index.css',true);
		$this->response->layout->menu->set('activeTab','orm');

		
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
		
		$this->response->setLayout(false);
		
		// Show the tables in the default database
		$tables     = array();
		$tableNames = Connections::Get()->getTables();
		foreach ($tableNames as $tableName) {
			$tables[$tableName] = Connections::Get()->describeTable($tableName); 
			$tables[$tableName]->tableExists = true;
		}
		
		// Add the tables the model says should exist
		foreach (Model::Get()->objects as $object) {
			$tables[$object->table->name] = new Table($object->table->name);
			$tables[$object->table->name]->tableExists = false;
		}
		
		$this->set('tables',$tables);
		
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
	
	public function createTable($objectName,$bForce=false) {
		if (false != ($obj = Model::Get()->objects[$objectName])) {
			$tableName = $obj->table->name;
			if ($bForce) {
				Connections::Get()->dropTable($tableName);
			}
			$sql = Connections::Get()->createTableSql($obj->table);
			Connections::Get()->exec($sql);
			echo "<span style='color:green'>Created table `" . $tableName . "`</span>";
			exit();
		} else {
			echo "<span style='color:red'>Model object `{$objectName}` not found</span>";
		}
		exit();
	}
}