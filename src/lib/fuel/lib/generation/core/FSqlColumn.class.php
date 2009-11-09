<?php
/*
 * frameworkers-foundation
 * 
 * FSqlColumn.class.php
 * Created on May 18, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FSqlColumn
  * 
  * Represents a basic SQL Column. 
  */
  class FSqlColumn {
  	
  	// Variable: name
  	// The name of this column.
  	private $name;
  	
  	// Variable: colType
  	// An SQL type declaration for the column. 
  	private $colType;
  	
  	// Variable: key
  	// The index key, or "UNIQUE" that this column belongs to
  	private $key;
  	
  	// Variable: bIsNull
  	// Whether or not a NULL value is acceptable in this column
  	private $bIsNull;
  	
  	// Variable: bIsAutoinc
  	// Whether or not the column auto-increments
  	private $bIsAutoinc;
  	
  	// Variable: defaultValue
  	// A default value for this column
  	private $defaultValue = null;
  	
  	// Variable: comment
  	// A comment about this column
  	private $comment;
  	
  	
	
	public function __construct($name,$colType,$isNull=false,$isAutoinc=false,$comment="") {
		$this->name = $name;
		$this->colType = $colType;
		$this->bIsNull = $isNull;
		$this->bIsAutoinc = $isAutoinc;
		$this->comment = $comment;
		$this->columns = array();
	}  	
  	
  	
  	public function getName() {
  		return $this->name;	
  	}
  	
  	public function getColType() {
  		return $this->colType;	
  	}
  	
  	public function isNull() {
  		return $this->bIsNull;	
  	}
  	
  	public function getKey() {
  		return $this->key;	
  	}
  	
  	public function getDefaultValue() {
  		return $this->defaultValue;	
  	}
  	
  	public function isUnique() {
  		return ("UNIQUE" == $this->key) 
  			? true
  			: false;	
  	}
  	
  	public function isAutoinc() {
  		return $this->bIsAutoinc;
  	}
  	public function getComment() {
  		return $this->comment;	
  	}
  	
  	public function setName($value) {
  		$this->name = $value;
  	}
  	
  	public function setColType($value) {
  		$this->colType = $value;	
  	}
  	
  	public function setIsNull($value) {
  		$this->bIsNull = $value;	
  	}
  	
  	public function setIsAutoinc($value) {
  		$this->bIsAutoinc = $value;
  	}
  	
  	public function setKey($value) {
  		$this->key = $value;	
  	}
  	
  	public function setDefaultValue($value) {
  		$this->defaultValue = $value;	
  	}
  	
  	public function setComment($value) {
  		$this->comment = $value;	
  	}
  	
  	public static function convertToSqlType($value,$extra=array()) {
  		switch (strtolower($value)) {
  			case "string":
  				if (isset($extra['size']) && !empty($extra['size'])){
  					return (($extra['size'] > 255)
  						? "TEXT"
  						: "VARCHAR(".max(1,$extra['size']).")");	
  				} else {
  					return "VARCHAR(255)";	
  				}
  			case "text":
  				return "TEXT";
  			case "integer":
  				if (isset($extra['min'])){
  					return (0 > $extra['min'])
  						? "INT(11)"
  						: "INT(11) UNSIGNED";
  				} else {
  					return "INT(11)";	
  				} 
  			case "date":
  				return "DATE";
  				break;
  			case "datetime":
  				return "DATETIME";
  				break;
  			case "float":
  				return "FLOAT";
  				break;
  			case "boolean":
  				return "INT(11) UNSIGNED";
  				break;
  			default:
  				return false;
  		}	
  	}
	
  	public function toSqlString() {
  		$null   = (($this->bIsNull)? "NULL " : "NOT NULL ");
  		$comment = (("" == $this->comment)? "" : "COMMENT '".str_replace("'","''",$this->getComment())."' ");
  		$default = (("" == $this->defaultValue)? "" : "DEFAULT \"{$this->getDefaultValue()}\" ");
  		$autoinc = (($this->bIsAutoinc)? "auto_increment " : "");
  		return "`{$this->getName()}` {$this->getColType()} {$null} {$default} {$autoinc} {$comment} ";
  	}  
}
?>