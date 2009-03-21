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
  
  //CONSTANT DEFINITIONS
  define("FDATABASE_FETCHMODE_ASSOC",MDB2_FETCHMODE_ASSOC);
  define("FDATABASE_FETCHMODE_NUMERIC",MDB2_FETCHMODE_NUMERIC);
  
  //DATABASE WRAPPER
  class FDatabase  {
  	
  	private $mdb2;
  	
  	private function __construct() {
  		if (_furnace()->config['debug_level'] > 0) {
  			$this->mdb2 = MDB2::singleton(_furnace()->config['debug_dsn']);
  		} else {
  			$this->mdb2 = MDB2::singleton(_furnace()->config['production_dsn']);
  		}
  		// Turn off case-fixing portability switch
		$this->mdb2->setOption('portability',MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_FIX_CASE);
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
		if (_furnace()->config['debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->query($q);
		if ( MDB2::isError($r) ) {
			throw new FDatabaseException($r->message,"\"{$q}\"");	
		}
		if (_furnace()->config['debug_level'] == 2) {
			$bm_end   = microtime(true);
			_furnace()->queries[] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function queryRow($q,$fetchmode = FDATABASE_FETCHMODE_NUMERIC) {
		if (_furnace()->config['debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->queryRow($q,null,$fetchmode);
		if ( MDB2::isError($r) ) {
			throw new FDatabaseException($r->message,"\"{$q}\"");
		}
		if (_furnace()->config['debug_level'] == 2) {
			$bm_end   = microtime(true);
			_furnace()->queries[] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function queryOne($q) {
		if (_furnace()->config['debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->queryOne($q);
		if ( MDB2::isError($r) ) {
			throw new FDatabaseException($r->message,"\"{$q}\"");
		}
		if (_furnace()->config['debug_level'] == 2) {
			$bm_end   = microtime(true);
			_furnace()->queries[] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;
	}
	
	public function queryAll($q,$fetchmode = FDATABASE_FETCHMODE_NUMERIC) {
		if (_furnace()->config['debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->queryAll($q,null,$fetchmode);
		if ( MDB2::isError($r) ) {
			throw new FDatabaseException($r->message,"\"{$q}\"");
		}
		if (_furnace()->config['debug_level'] == 2) {
			$bm_end   = microtime(true);
			_furnace()->queries[] = array( 
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
		if (_furnace()->config['debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->exec($q);
		if ( MDB2::isError($r) ) {
			throw new FDatabaseException($r->message,"\"{$q}\"");
		}
		if (_furnace()->config['debug_level'] == 2) {
			$bm_end   = microtime(true);
			_furnace()->queries[] = array( 
				'sql'   => $q,
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	public function lastInsertID() {
		if (_furnace()->config['debug_level'] == 2) {
			$bm_start = microtime(true);	
		}	
		$r = $this->mdb2->lastInsertID();
		if ( MDB2::isError($r) ) {
			throw new FDatabaseException($r->message,"\"{$q}\"");
		}
		if (_furnace()->config['debug_level'] == 2) {
			$bm_end   = microtime(true);
			_furnace()->queries[] = array( 
				'sql'   => 'LAST INSERT ID',
				'delay' => $bm_end - $bm_start
			);	
		}
		return $r;		
	}
	
	public function setFetchMode($mode) {
		return $this->mdb2->setFetchMode($mode);	
	}
  
	public static function isError($result) {
		return MDB2::isError($result);
	}
  }

?>