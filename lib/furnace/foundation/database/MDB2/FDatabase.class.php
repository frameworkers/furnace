<?php
/*
 * frameworkers-foundation
 * 
 * FDatabase.class.php
 * Created on May 30, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FDatabase
  * 
  * Lightweight wrapper for the PEAR MDB2 database abstraction package.
  * 
  */
  require_once("MDB2.php");
  class FDatabase  {
  	
  	private $mdb2;
  	
  	private function __construct() {
  		if ($GLOBALS['fconfig_debug_level'] > 0) {
  			$this->mdb2 = MDB2::singleton($GLOBALS['fconfig_debug_dsn']);
  		} else {
  			$this->mdb2 = MDB2::singleton($GLOBALS['fconfig_production_dsn']);
  		}
  	}
	
	public static function singleton() {
		static $db;
		
		if (!isset($db)) {
			$c  = __CLASS__;
			$db = new $c();	
		}	
		
		return $db;
	}
		
	public function query($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->query($q);
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
		$r = $this->mdb2->queryRow($q);
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
		$r = $this->mdb2->queryOne($q);
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
		$r = $this->mdb2->queryAll($q);
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
		$this->mdb2->setLimit($count,$offset);
	}
	
	public function exec($q) {
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->exec($q);
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
		$r = $this->mdb2->lastInsertID($q);
		if ($GLOBALS['fconfig_debug_level'] == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => 'LAST INSERT ID',
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function setFetchMode($mode) {
		return $this->mdb2->setFetchMode($mode);	
	}
  }

?>