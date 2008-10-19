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
  		$this->mdb2 = MDB2::singleton(FDatabaseConfig::$DSN);
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
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->query($q);
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function queryRow($q) {
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->queryRow($q);
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function queryOne($q) {
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->queryOne($q);
		if (FProject::DEBUG_LEVEL == 2) {
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
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->exec($q);
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_end   = microtime(true);
			$GLOBALS['queries'][] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	public function lastInsertID() {
		if (FProject::DEBUG_LEVEL == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->lastInsertID($q);
		if (FProject::DEBUG_LEVEL == 2) {
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