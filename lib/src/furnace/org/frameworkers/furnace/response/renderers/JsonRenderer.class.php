<?php
namespace org\frameworkers\furnace\response\renderers;

use org\frameworkers\furnace\response\RenderEngine;

class JsonRenderer extends RenderEngine {
	
	public function __construct (&$response) {
		$this->response = $response;
	}
	
	public function compile( $content, $context, $locals ) {
		
		// Return a JSON string consisting of each
		// of the variables in $locals
		return json_encode(self::prepare_data($locals));
	}
	
	protected static function prepare_data($data) {
		foreach ($data as &$d) {
			if (is_object($d)) {
				$d = json_decode((string)$d);
			}
			if (is_array($d)) {
				$d = self::prepare_data($d);
			}
		}
		return $data;
	}
	
	public function reset() {}
	
}