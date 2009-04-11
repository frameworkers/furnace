<?php
class SchemaController extends Controller {
	
	public function index() {
		
		if ($GLOBALS['furnace']->config['debug_level'] > 0 && 
			$GLOBALS['furnace']->config['debug_dsn'] == 'mysql://user:password@server/dbname') {
			die("No debug database specified. Please edit the 'debug_dsn' variable in your application config file");
		} else if ($GLOBALS['furnace']->config['debug_level'] == 0 &&
				   $GLOBALS['furnace']->config['production_dsn'] == 'mysql://user:password@server/dbname') {
			die("No production database specified. Please edit the 'production_dsn' variable in your application config file");	   	
		}
		
		$this->init();		// Load required files
		
		$d = new FDatabaseSchema();
		if ($GLOBALS['furnace']->config['debug_level'] > 0) {
			$d->discover($GLOBALS['furnace']->config['debug_dsn']);
		} else {
			$d->discover($GLOBALS['furnace']->config['production_dsn']);
		}
		$model = $this->getModel();
		
		$tables = array();
		$notices= array();
		
		
		// Analyze differences DATABASE vs MODEL
		foreach ($model->tables as $mt) {
			$tables[strtolower($mt->getName())] = array("name"=> $mt->getName(),"found"=> false,"status"=> false);

			foreach ($d->getTables() as $dt) {
				if (strtolower($dt->getName()) == strtolower($mt->getName())) {
					$tables[strtolower($mt->getName())] = array('name'=> $mt->getName(),'found' => true,'table'=>$mt,'status'=>'ok');
					// Now that we found the matching table, check the fields for differences
					if (count($mt->getColumns()) != count($dt->getColumns())){
						$tables[strtolower($mt->getName())]['status'] = 'fieldsDiffer';
						break;
					}
					foreach ($mt->getColumns() as $mtc) {
						$found = false;
						foreach ($dt->getColumns() as $dtc) {
							if (strtolower($mtc->getName()) == strtolower($dtc->getName())){
								$found=true;	
								break;
							}
						}
						if (!$found || ( 
							($dtc->getColType()      != $mtc->getColType()) ||
						    ($dtc->isNull()          != $mtc->isNull())     ||
						    ($dtc->getDefaultValue() != $mtc->getDefaultValue())
						)) {
							$tables[strtolower($mt->getName())]['status'] = 'fieldsDiffer';
							break;
						}
					}	
					break;
				}
			}
		}
		
		// Analyze differences MODEL vs DATABASE
		foreach ($d->getTables() as $dt) {
			
			$bFound = false;
			foreach ($model->tables as $mt) {
				if (strtolower($mt->getName()) == strtolower($dt->getName())) {
					$tables[strtolower($mt->getName())]['found'] = true;
					$bFound = true;
					break;
				}
			}
			if (!$bFound) {
				$notices[] = $dt;
			}
		}
		
		$existingTableNames = array();
		foreach ($d->getTables() as $t) {
			$existingTableNames[] = $t->getName();
		}
		$this->set("existingTableNames",$existingTableNames);
		$this->set("tables",$tables);
		$this->set("notices",$notices);
	}
	public function deleteDbTable($tableName='') {
		if ($tableName == '') {
			$this->flash("Error: No Table Data Specified","error");
		} else {
			$tableName = FModel::standardizeTableName($tableName);
			$this->init();
			$model = $this->getModel();
			$schema= $this->getSchema();
			$query = "DROP TABLE `{$tableName}` ";
			$schema->executeStatement($query);
			$this->flash("Table Dropped Successfully");
		}
		$this->redirect("/fuel/schema/");
	}
	public function renameDbTable() {
		$data =& $this->form;
		if (!isset($data['tableName'])) {
			$this->flash("Error: No table specified.","error");
		} else {
			$data['tableName'] = FModel::standardizeTableName($data['tableName']);
			$data['renameTo']  = FModel::standardizeTableName($data['renameTo']);
			$this->init();
			$model = $this->getModel();
			$schema= $this->getSchema();
			$query = "ALTER TABLE `{$data['tableName']}` RENAME TO `{$data['renameTo']}` ";
			$schema->executeStatement($query);
			$this->flash("Table Successfully Renamed");
		}
		$this->redirect("/fuel/schema/");
	}
	
	public function createTable($tableName='') {
		if ($tableName == '') {
			$this->set('errorNoTableSpecified');
		}
		$tableName = FModel::standardizeTableName($tableName);
		$this->init();		// Load required files

		$model = $this->getModel();
		$schema= $this->getSchema();
		
		if ($schema->getTable($tableName)) {
			$this->set('errorTableExists');
		} else {
			$this->set("table",$model->tables[$tableName]);
			$this->set("sql",$model->tables[$tableName]->toSqlString());
		}
	}
	
	public function doCreateTable() {
		$data =& $this->form;
		if (!isset($data['tableName'])) {
			$this->flash("Error: No table specified.","error");
		} else {
			$data['tableName'] = FModel::standardizeTableName($data['tableName']);
			$this->init();
			$model = $this->getModel();
			if (isset($model->tables[$data['tableName'] ])) {
				$table  = $model->tables[$data['tableName'] ];
				$schema = $this->getSchema();
				$schema->executeStatement($table->toSqlString());
				$this->flash("Created Table '{$data['tableName']}'");
			} else {
				$this->flash("Error: Unknown table {$data['tableName']}","error");
			}
		}
		$this->redirect("/fuel/schema/");
	}
	
	public function compareFields($tableName) {
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		
		$tableName  = FModel::standardizeTableName($tableName);
		$modelTable = $model->tables[$tableName];
		$dbTable    = $schema->getTable($tableName);
		
		
		$fields = array();
		foreach ($modelTable->getColumns() as $mc) {
			$bFound = false;
			foreach ($dbTable->getColumns() as $dbc) {
				if ($dbc->getName() == $mc->getName()) {
					if (($dbc->getColType() != $mc->getColType()) 
						|| ($dbc->isNull() != $mc->isNull())
						|| ($dbc->getDefaultValue() != $mc->getDefaultValue())
					) {
						$state = "Changes Detected.";
						$choices = array(array(
							'type'=>'edit',
							'text'=>'Apply model definition to database field',
							'action'=>_furnace()->config['url_base']."fuel/schema/editColumn/",
							'tableName' =>$modelTable->getName(),
							'columnName'=>$mc->getName()
							));
					} else {
						$state = "No Changes Detected.";
						$choices = array();
					}
					$bFound = true;
					$fields[$mc->getName()] = array("column"=>$mc,"state"=>$state,"choices"=>$choices);
					break;
				}
			}
			if (!$bFound) {
				$choices = array(array(
					'type'=>'add',
					'text'=>'Add as new column',
					'action'=>_furnace()->config['url_base']."fuel/schema/addColumn/",
					'tableName'=>$modelTable->getName(),
					'columnName'=>$mc->getName()),
					array(
					'type'=>'rename',
					'text'=>'Rename existing column: ',
					'action'=>_furnace()->config['url_base']."fuel/schema/renameColumn/",
					'tableName'=>$modelTable->getName(),
					'renameTo'=>$mc->getName())
				);
				$fields[$mc->getName()] = array(
					"column"=>$mc,
					"state"=>"Not Found In Database.",
					"choices"=>$choices
				);
			}
		}
		$unknowns = array();
		foreach ($dbTable->getColumns() as $dbc) {	
			$choices = array();
			$bFound = false;
			foreach ($modelTable->getColumns() as $mc) {
				if ($dbc->getname() == $mc->getName()) {
					$bFound = true;
					break;
				}
			}
			if (!$bFound) {
				$choices[] = array(
					'type'  => 'unknown',
					'text'  => 'Database Column does not exist in the Model',
					'action'=> "fuel/schema/deleteDbField/",
					'tableName'  =>$modelTable->getName(),
					'columnName' =>$dbc->getName()
				);
				$unknowns[] = array("column"=>$dbc,"choices"=>$choices);
			}
		}
		$this->set('table',$modelTable);
		$this->set('fields',$fields);
		foreach ($dbTable->getColumns() as $c) {
			$existingColumnNames[] = $c->getName();
		}
		$this->set('unknowns',$unknowns);
		$this->set('unknownCount',count($unknowns));
		$this->set('renameList',$existingColumnNames);
	}
	
	public function editColumn() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not edit. No Column Data Specified","error");
			$this->redirect("/fuel/schema/");
		}
		$data['tableName'] = FModel::standardizeTableName($data['tableName']);
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` MODIFY COLUMN {$model->tables[$data['tableName'] ]->getColumn($data['columnName'])->toSqlString()}";
		$schema->executeStatement($query);
		$this->flash("Column Definition Successfully Changed");
		$this->redirect("/fuel/schema/compareFields/{$data['tableName']}");
	}
	public function renameColumn() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not rename. No Column Data Specified","error");
			$this->redirect("/fuel/schema/");
		}
		$data['tableName'] = FModel::standardizeTableName($data['tableName']);
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` CHANGE COLUMN `{$data['columnName']}` {$model->tables[$data['tableName'] ]->getColumn($data['renameTo'])->toSqlString()}";
		$schema->executeStatement($query);
		$this->flash("Column Successfully Renamed");
		$this->redirect("/fuel/schema/compareFields/{$data['tableName']}");
	}
	public function addColumn() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not add column. No Column Data Specified","error");
			$this->redirect("/fuel/schema/");
		}
		$data['tableName'] = FModel::standardizeTableName($data['tableName']);
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` ADD COLUMN {$model->tables[$data['tableName'] ]->getColumn($data['columnName'])->toSqlString()}";
		$schema->executeStatement($query);
		$this->flash("Column Added Successfully");
		$this->redirect("/fuel/schema/compareFields/{$data['tableName']}");
	}
	
	public function deleteDbField() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not delete column. No Column Data Specified","error");
			$this->redirect("/fuel/schema/");
		}
		$data['tableName'] = FModel::standardizeTableName($data['tableName']);
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` DROP COLUMN `{$data['columnName']}`";
		$schema->executeStatement($query);
		$this->flash("Column Dropped Successfully");
		$this->redirect("/fuel/schema/compareFields/{$data['tableName']}");
	}
}
?>