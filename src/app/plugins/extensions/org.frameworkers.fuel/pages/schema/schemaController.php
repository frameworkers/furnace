<?php
class SchemaController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requireLogin();
        $this->setActiveMenuItem('main','schema');
        $this->setTitle('Foundry :: Schema');
        $this->set('pageTitle','Schema');
    }
	
	public function index() {
		
		$this->init();		// Load required files
		
		
		
	    try {
		    $d = new FDatabaseSchema();
    		$d->discover('default');
    		$model = $this->getModel();
    		
    		$tables = array();
    		$notices= array();
		    $this->set('dbConnectOk',true);
		} catch (FDatabaseException $fde) {
		    $this->set('dbConnectOk',false);
		    $this->set('dbConnectMessage',$fde->getMessage());
		}
		
		if ($this->page_data['dbConnectOk'] == true) {
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
	}
	public function deleteDbTable($tableName='') {
		if ($tableName == '') {
			$this->flash("Error: No Table Data Specified","error");
		} else {
			$isLookup  = (false !== strpos($tableName,"_"));
			$this->init();
			$model = $this->getModel();
			$schema= $this->getSchema();
			$query = "DROP TABLE `{$tableName}` ";
			$schema->executeStatement($query);
			$this->flash("Table Dropped Successfully");
		}
		$this->redirect("{$this->prefix}/schema/");
	}
	public function renameDbTable() {
		$data =& $this->form;
		if (!isset($data['tableName'])) {
			$this->flash("Error: No table specified.","error");
		} else {
			$this->init();
			$model = $this->getModel();
			$schema= $this->getSchema();
			$query = "ALTER TABLE `{$data['tableName']}` RENAME TO `{$data['renameTo']}` ";
			$schema->executeStatement($query);
			$this->flash("Table Successfully Renamed");
		}
		$this->redirect("{$this->prefix}/schema/");
	}
	
	public function createTable($tableName='',$bIsLookup = false) {
		if ($tableName == '') {
			$this->set('errorNoTableSpecified');
		}
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
		$this->redirect("{$this->prefix}/schema/");
	}
	
	public function compareFields($tableName) {
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		
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
							'action'=>"{$this->prefix}/schema/editColumn/",
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
					'action'=>"{$this->prefix}/schema/addColumn/",
					'tableName'=>$modelTable->getName(),
					'columnName'=>$mc->getName()),
					array(
					'type'=>'rename',
					'text'=>'Rename existing column: ',
					'action'=>"{$this->prefix}/schema/renameColumn/",
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
					'action'=> "{$this->prefix}/schema/deleteDbField/",
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
			$this->redirect("{$this->prefix}/schema/");
		}

		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` MODIFY COLUMN {$model->tables[$data['tableName'] ]->getColumn($data['columnName'])->toSqlString()}";
		$schema->executeStatement($query);
		$this->flash("Column Definition Successfully Changed");
		$this->redirect("{$this->prefix}/schema/compareFields/{$data['tableName']}");
	}
	public function renameColumn() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not rename. No Column Data Specified","error");
			$this->redirect("{$this->prefix}/schema/");
		}
		
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` CHANGE COLUMN `{$data['columnName']}` {$model->tables[$data['tableName'] ]->getColumn($data['renameTo'])->toSqlString()}";
		$schema->executeStatement($query);
		$this->flash("Column Successfully Renamed");
		$this->redirect("{$this->prefix}/schema/compareFields/{$data['tableName']}");
	}
	public function addColumn() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not add column. No Column Data Specified","error");
			$this->redirect("{$this->prefix}/schema/");
		}
		
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` ADD COLUMN {$model->tables[$data['tableName'] ]->getColumn($data['columnName'])->toSqlString()}";
		$schema->executeStatement($query);
		$this->flash("Column Added Successfully");
		$this->redirect("{$this->prefix}/schema/compareFields/{$data['tableName']}");
	}
	
	public function deleteDbField() {
		$data =& $this->form;
		if (!$data) {
			$this->flash("Error: Could not delete column. No Column Data Specified","error");
			$this->redirect("{$this->prefix}/schema/");
		}
		
		$this->init();
		$model = $this->getModel();
		$schema= $this->getSchema();
		$query = "ALTER TABLE `{$data['tableName']}` DROP COLUMN `{$data['columnName']}`";
		$schema->executeStatement($query);
		$this->flash("Column Dropped Successfully");
		$this->redirect("{$this->prefix}/schema/compareFields/{$data['tableName']}");
	}
}
?>