<?php
/*
 * frameworkers-foundation
 * 
 * FSqlTable.class.php
 * Created on May 18, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FSqlTable
  * 
  * Represents a basic SQL Table. These correspond either directly
  * to a particular <FObj> object, or, in the case of a lookup
  * table, to a M:M relationship (as modeled by a <FObjSocket>)
  * between two <FObj> objects. 
  */
  class FSqlTable {
  	
  	// Variable: name
  	// The name of this table in the database.
  	private $name;
  	
  	// Array: columns
  	// An array of <FSqlColumn> objects comprising the table's fields
  	private $columns;
  	
  	// Array: primaryKeys
  	// An array of <FSqlColumn> objects comprising the table's primary keys
  	private $primaryKeys;
  	
  	// Variable: engine
  	// The database engine to use for this table
  	private $engine;
  	
  	
	
	public function __construct($name,$bIsLookupTable = false,$engine='MyISAM',$charset='latin1') {
		$this->name = $name;
		$this->columns = array();
		$this->primaryKeys = array();
		$this->engine = $engine;
		$this->charset = $charset;
		if (false == $bIsLookupTable) {
			$oid = new FSqlColumn("objId","INT(11) UNSIGNED",false,false,
				"The unique id of this object in the database");	
			$oid->setIsAutoinc(true);
			$this->addColumn($oid);
			$this->primaryKeys[strtolower($oid->getName())] = $oid;
		}
	}  	
  	
  	
  	public function getName() {
  		return $this->name;	
  	}
  	
  	public function setName($value) {
  		$this->name = $value;
  	}
  	
  	public function getColumns() {
  		return(array_merge($this->primaryKeys,$this->columns));	
  	}

  	public function getColumn($columnName) {
  		$tmp  = array_merge($this->primaryKeys,$this->columns);
  		if (isset($tmp[strtolower($columnName)])) {
  			return $tmp[strtolower($columnName)];
  		} else {
  			return false;
  		}
  	}
  	public function addColumn($obj) {
  		$this->columns[strtolower($obj->getName())] = $obj;
  		if ("PRIMARY" == $obj->getKey() ) {
  			$this->addPrimaryKey($obj);
  		}
  	}
  	
  	public function removeColumn($name) {
  		unset($this->columns[strtolower($name)]);	
  	}
  	
  	public function getEngine() {
  		return $this->engine;
  	}
  	
  	public function setEngine($value) {
  		$this->engine = $value;
  	}
  	
  	public function getCharset() {
  		return $this->charset;
  	}
  	
  	public function setCharset($value) {
  		$this->charset = $value;
  	}
  	
  	public function addPrimaryKey($column) {
  		$this->primaryKeys[strtolower($column->getName())] = $column;
  	}
  	
  	public function toSqlString() {
  		$prim_keys = array();
  		$uniq_keys = array();
  		$col_defs = array();
  		$sqluk = array();
  		foreach ($this->columns as $col) {
  			$col_defs[] = $col->toSqlString();
  			if ($col->isUnique()) {
  				$uniq_keys[] = $col->getName();
  			}
  		}
  		foreach ($this->primaryKeys as $pk) {
  			$prim_keys[] = $pk->getName();
  		}
  		$str = "CREATE TABLE `{$this->getName()}` (\r\n\t"
  			. implode(",\r\n\t",$col_defs)
  			. ",\r\n\tPRIMARY KEY (`"
  			. implode("`,`",$prim_keys)
  			. "`)";
  		foreach ($uniq_keys as $uk) {
  			$sqluk[] = ",\r\n\tUNIQUE KEY `{$uk}` (`{$uk}`)";
  		}
  		$str .= implode("",$sqluk);
  		$str .= "\r\n) ENGINE={$this->getEngine()} DEFAULT CHARSET={$this->getCharset()} ;\r\n";
  		return $str;
  	}
  }
?>