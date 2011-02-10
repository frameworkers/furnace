<?php
use org\frameworkers\furnace\connections\Connections;
use org\frameworkers\furnace\persistance\cache\Cache;
use org\frameworkers\furnace\persistance\orm\pdo\DataSource;
use org\frameworkers\furnace\persistance\orm\pdo\sql\SqlBuilder;
use org\frameworkers\furnace\action\Controller;

class DefaultController extends Controller {
	
	public function index() {
		$this->response->includeView(
			array('PartialsController','mainMenu',array('overview')),'menu');
	}
	
}