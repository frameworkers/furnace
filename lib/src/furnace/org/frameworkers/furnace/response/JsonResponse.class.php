<?php
namespace org\frameworkers\furnace\response;

use org\frameworkers\furnace\response\Response;

class JsonResponse extends Response {
	
	public function __construct ( $context ) {
		$this->context = $context;
	}
	
	public function render( ) {
		
		// Determine the render engine for the response
		$renderEngine = ResponseTypes::EngineFor('json');
		
		// Create a renderer for the response
		$renderer     = new $renderEngine( $this );		
		
		// Get the compiled output
		$document = $renderer->compile( 
			null, $context, $this->local_data);
		
		// Return the compiled response
		return $document;
		
	}
	
	public function set($key,$value) {
		$this->local_data[$key] = $value;
	}
	
	public function flash($message,$cssClass = "ff_info") {
		$this->local_data['flashes'][] = compact($message,$cssClass);
	}
}