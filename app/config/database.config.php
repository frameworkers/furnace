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
  * Class: FDatabaseConfig
  * 
  * This class defines a data source name (DSN) for
  * connecting to a database using PEAR MDB2. the 
  * default engine supported by Furnace.
  * 
  */
 class FDatabaseConfig {
 	// You should change these values to be appropriate
	// for your project environment.
	//  user: the database username
	//  password: the password for 'user'
	//  server: the database server (usually 'localhost')
	//  dbname: the name of the database to connect to
	//  mysql:  use mdb2's mysql driver (default)
 	public static $DSN = "mysql://user:password@server/dbname";
 }
?>