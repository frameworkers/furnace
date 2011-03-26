<?php
namespace org\frameworkers\furnace\response\html;

use org\frameworkers\furnace\response\ResponseTypes;
use org\frameworkers\furnace\config\Config;

class HtmlZone {
	
	public $label;
	
	public $localData;
	
	public $template;
	
	public $renderEngine;
	
	public function __construct($label) {
		$this->label = $label;
		
		// By default, the render engine is the default 
		// renderer for the 'html' response type
		$this->renderEngine = ResponseTypes::EngineFor('html');
		
	}
	
	public function set($key,$value) {
		(is_object($this->template)) 
			? $this->template->set($key,$value)  // pass through to the widget
			: $this->localData[$key] = $value;
		
		// Chainable
		return $this;
	}
	
	public function setRenderEngine($engine) {
		$this->renderEngine = $engine;
		
		// Chainable
		return $this;
	}
	
	public function prepare($template,$bRawString = false) {
		if ($bRawString) {
			$this->template = $template;
		} else {
			$this->template = file_get_contents(
				Config::Get('applicationViewsDirectory')
				.'/' . $template);
		}
		
		// Chainable
		return $this;
	}
	
	public function assign($classInstance) {
		$this->template = $classInstance;
		
		// Chainable, passing control to the widget class instance
		return $this->template;
	}
	
}