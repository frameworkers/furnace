<?php
/*
 * frameworkers-foundation
 * 
 * FDatabase.class.php
 * Created on Feb, 03, 2009
 *
 * Copyright 2008-2009 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FDatabase
  * 
  * Lightweight wrapper for the PEAR DB database abstraction package.
  * 
  */

  require_once("DB.php");
  
  // CONSTANT DEFINITIONS
  define("FDATABASE_FETCHMODE_ASSOC", DB_FETCHMODE_ASSOC);
  
  
  // DATABASE WRAPPER
  class FDatabase  {
  	
  	private $db;
  	
  	private function __construct() {
  		if ($GLOBALS['fconfig_debug_level'] > 0) {
  			$this->db = DB::connect($GLOBALS['fconfig_debug_dsn']);
  		} else {
  			$this->db = DB::connect($GLOBALS['fconfig_production_dsn']);
  		}
  	}
  	
  	public static function singleton() {
  		static $database;
  		if (!isset($database)) {
  			$c = __CLASS__;
  			$database = new $c();
  		}
  		
  		return $database;
  	}
		
	public function query($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->db->query($q);
		if ( DB::isError($r) ) {
			throw new FDatabaseException($r->getMessage(),"\"{$q}\"");
		}
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function queryRow($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->db->getRow($q);
		if ( DB::isError($r) ) {
			throw new FDatabaseException($r->getMessage(),"\"{$q}\"");
		}
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function queryOne($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->db->getOne($q);
		if ( DB::isError($r) ) {
			throw new FDatabaseException($r->getMessage(),"\"{$q}\"");
		}
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;
	}
	
	public function queryAll($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->db->getAll($q);
		if ( DB::isError($r) ) {
			throw new FDatabaseException($r->getMessage(),"\"{$q}\"");
		}
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;
	}
	
	public function setLimit($count,$offset) {
		$this->db->setLimit($count,$offset);
	}
	
	public function exec($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->db->query($q);
		if ( DB::isError($r) ) {
			throw new FDatabaseException($r->getMessage(),"\"{$q}\"");	
		}
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	public function lastInsertID() {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r =& $this->queryOne('select last_insert_id()');
		if ( DB::isError($r) ) {
			throw new FDatabaseException($r->getMessage(),"\"{$q}\"");	
		}
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => 'SELECT LAST_INSERT_ID',
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;	
	}
	
	public function setFetchMode($mode) {
		return $this->db->setFetchMode($mode);	
	}
	
	public static function isError($result) {
		return DB::isError($result);
	}
  }

?>