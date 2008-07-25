<?php
/*
 * frameworkers_furnace
 * 
 * database.config.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org
 * http://www.frameworkers.org
 */
 
 /*
  * Class: MDB2_Config
  * 
  * This class defines a data source name (DSN) for
  * connecting to a database using PEAR MDB2. If you
  * wish to use another database engine, you must 
  * create a similar class named @@@@_Config, and 
  * extending FDatabaseConfig. @@@@ should be whatever
  * you set DB_ENGINE to in 'project.config.php'.
  * 
  */
 class MDB2_Config extends FDatabaseConfig {
 	
 	public function __construct() {
 		$this->db_user = "user";
 		$this->db_pass = "password";
 		$this->server  = "localhost";
 		$this->db_name = "databasename";		
 	}
 	
 	public function getDSN() {
 		return "mysql://{$this->db_user}:{$this->db_pass}"
 			."@{$this->server}/{$this->db_name}";		
 	}
 }
 
 
 /************************************************************************
  * NO USER EDITABLE CODE BELOW THIS LINE
  ***********************************************************************/
 /*
  * Class: FDatabaseConfig 
  *
  * This class provides a basis for creating flexible data source names
  * (DSN's) suitable for connecting to different databases.
  */
 abstract class FDatabaseConfig {
 	
 	private $db_user = '';
 	private $db_pass = '';
 	private $server  = '';
 	private $db_name = '';
 	
 	public function __construct($user,$pass,$server,$db_name) {
 		
 		// Assign provided values
 		$this->db_user = $user;
 		$this->db_pass = $pass;
 		$this->server  = $server;
 		$this->db_name = $db_name;
 	}	
 	 	
 	public abstract function getDSN();
 }
?>