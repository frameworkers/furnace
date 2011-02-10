<?php
use vendors\spyc\Spyc;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\persistance\orm\pdo\model\Model;
use org\frameworkers\furnace\connections\Connections;
use org\frameworkers\furnace\persistance\cache\Cache;
use org\frameworkers\furnace\persistance\orm\pdo\DataSource;
use org\frameworkers\furnace\persistance\orm\pdo\sql\SqlBuilder;
use org\frameworkers\furnace\action\Controller;

class PartialsController extends Controller {
	
	
	public function mainMenu ($activeTab) {
		$this->response->setLayout(false);
		$this->response->includeJavascript('partials/mainMenu/mainMenu.js',true);
		$this->response->includeStylesheet('partials/mainMenu/mainMenu.css',true);
		$this->set('activeTab',$activeTab);
	}
}