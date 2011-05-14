<?php
namespace org\frameworkers\furnace\response\html;

use org\frameworkers\furnace\response\ResponseTypes;

class HtmlLayout {
	
	public $_layout;
	public $_zones;
	
	/*
	 * ZONES will be dynamically added here as they are 
	 * parsed from the layout contents
	 */
	
	public function __construct($layoutContents) {
		// Extract zones
		preg_match_all("/\[_([A-Za-z0-9]+)_\]/", $layoutContents, $matches);
		if ($matches) {
			foreach ($matches[1] as $discoveredZone) {
				$this->$discoveredZone = new HtmlZone($discoveredZone);
				$this->_zones[] = $discoveredZone;
			}
		}
		$this->_layout = $layoutContents;
	}
	
	public function render(&$response) {
		$document = $this->_layout;
		foreach ($this->_zones as $zone) {
			
			// The template is being provided by a widget
			if (is_object($this->$zone->template)) {
				$contents = $this->$zone->template->render();
			// Otherwise, its the (usual) case of a non-widgetized template
			} else {
				// Incorporate any `flash` messages into the local content
				$this->$zone->localData['_notifications'] = (isset($_SESSION['_notifications'][$zone]))
					? implode("\r\n",$_SESSION['_notifications'][$zone])
					: "";
				$engine = $this->$zone->renderEngine;
				$renderer = new $engine($response);
				$contents = $renderer->compile(
					$this->$zone->template,
					$response->context,
					$this->$zone->localData);
			}
					
			// Replace the zone tag in the layout with the compiled contents
			$document = str_replace("[_{$this->$zone->label}_]",$contents,$document);
			
			// Replace any global tag data
			$engine   = ResponseTypes::EngineFor('html');
			$renderer = new $engine($response);
			$document = $renderer->compile($document,$response->context,$response->global_data);
		}
		
		// Return the final, compiled document
		return $document;
	}
}