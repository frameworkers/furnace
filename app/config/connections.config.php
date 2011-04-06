<?php

use org\frameworkers\furnace\connections\Connections;

/*
 * Example connection to a MySQL database via Flame ORM Driver
 */
Connections::Add("default",'\org\frameworkers\flame\core\DataSource',array(
	"type"     => "mysql",
	"host"     => "localhost",
	"dbname"   => "db",
	"username" => "username", 
	"password" => "password"
));
