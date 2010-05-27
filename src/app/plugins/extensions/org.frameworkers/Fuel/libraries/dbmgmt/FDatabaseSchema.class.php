<?php
/*
 * frameworkers-foundation
 * 
 * FDatabaseSchema.php
 * Created on July 27, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

class FDatabaseSchema {
	
	// Array: tables
	// An array of <FSqlTable> objects representing the 
	// tables in this schema
	private $tables;
	
	// Variable: datasource
	// A data source
	private $datasource;
	
	public function __construct() {
		$this->tables = array();
	}
	
	public function discover($datasource) {
		$this->datasource =& _db($datasource);
		
		$this->discoverTables();
		foreach ($this->tables as $t) {
			$this->discoverAttributes($t->getName());
		}
		
	}
	
	public function load($data) {
		$this->tables = $data;
	}
	
	public function getTables() {
		return $this->tables;
	}
	public function getTable($tableName) {
		if (isset($this->tables[$tableName])) {
			return $this->tables[$tableName];
		} else {
			return false;
		}
	}
	public function executeStatement($stmt) {
		$this->datasource->rawExec($stmt);
	}
	
	public function tableExists($tableName,$bIsLookupTable = false) {
	    return isset($this->tables[FModel::standardizeTableName($tableName,$bIsLookupTable)]);
	}
	
	private function discoverTables() {
		$results = $this->datasource->rawQuery("SHOW TABLES",array("mode"=>MDB2_FETCHMODE_NUMERIC));
		
		//while ($r = $results->fetchRow()) {
		foreach ($results->data as $r) {
			$bIsLookupTable = ((strpos($r[0],"_") > 0));
			$this->tables[FModel::standardizeTableName($r[0],$bIsLookupTable)] 
				= new FSqlTable($r[0],$bIsLookupTable);
		}
	}
	
	private function discoverAttributes($tableName) {
		$results = $this->datasource->rawQuery("DESCRIBE `{$tableName}`",array("mode"=>MDB2_FETCHMODE_NUMERIC));
		
		//while ($r = $results->fetchRow()) {
		foreach ($results->data as $r) {
			$bIsAutoinc = (($r[5] != null));
			$c = new FSqlColumn(
				$r[0],								// name 
				strtoupper($r[1]),					// type 
				(($r[2] == "NO")? false : true),	// null
				$bIsAutoinc							// autoinc
			);
			// Set key
			if ($r[3] != null ) {
				$c->setKey($r[3]);					// key
			}
			// Set default value
			if ($r[4] != null ) {
				$c->setDefaultValue($r[4]);			// default
			}
			// Add the column to the table
			$this->tables[$tableName]->addColumn($c);
		}
	}
}
?>