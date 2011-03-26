<?php
namespace org\frameworkers\flame\widgets\mainMenu;

use org\frameworkers\furnace\response\renderers\TadpoleRenderer;
use org\frameworkers\furnace\interfaces\IFurnaceWidget;

class MainMenuWidget implements IFurnaceWidget {
	
	protected $localData;
	protected $request;
	
	protected $template =<<<end_template
	
	<div class="mainMenu">
		<ul>
		  <li><a class="[assert:activeTab='overview';content='active']" href="[%a]/">Overview</a></li>
		  <li><a class="[assert:activeTab='configuration';content='active']" href="[%a]/configuration">Configuration</a></li>
		  <li><a class="[assert:activeTab='orm';content='active']"  href="[%a]/models/orm">ORM</a></li>
		  <li><a class="[assert:activeTab='data';content='active]"  href="[%a]/data">Data</a></li>
		</ul>
	</div>
	
end_template;
	
	public function __construct($request,&$response) {
		$this->request = $request;
		
		// Bundled static resources are included inline in the 
		// response, eliminating the need for a round trip HTTP
		// request for what are usually small snippets of either
		// javascript or css
		$response->bundleJavascript(dirname(__FILE__) . '/mainMenu.js');
		$response->bundleStylesheet(dirname(__FILE__) . '/mainMenu.css');
	}
	
	public function setActiveTab($value) {
		$this->localData['activeTab'] = $value;
	}
	
	public function set($key,$value) {
		$this->localData[$key] = $value;
	}
	
	public function render() {
		$renderer = new TadpoleRenderer($this->request);
		return $renderer->compile(
			$this->template,
			$this->request,
			$this->localData);
	}
}