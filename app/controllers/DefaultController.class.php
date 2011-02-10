<?php

use org\frameworkers\furnace\connections\Connections;
use org\frameworkers\furnace\persistance\cache\Cache;
use org\frameworkers\furnace\persistance\orm\pdo\DataSource;
use org\frameworkers\furnace\persistance\orm\pdo\sql\SqlBuilder;
use org\frameworkers\furnace\action\Controller;

use app\models\orm\Forum;


class DefaultController extends Controller {
	
	
	public function index() {
		/*
		 * This is the controller for your application's home page
		 */
	}
	
}