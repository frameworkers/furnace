<?php

use org\frameworkers\furnace\connections\Connections;

/*
 * Example connection to a local MySQL database
 */
Connections::Add("default","mysql",array(
	"host"     => "localhost",
	"dbname"   => "db",
	"username" => "user", 
	"password" => "pass"
));
