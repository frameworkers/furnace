<?php


namespace org\frameworkers\furnace\response;

use org\frameworkers\furnace\core\Object;


abstract class RenderEngine extends Object {
	
	protected $response;
	
	public abstract function reset();
	
	public abstract function compile( $content, $context, $locals );
}