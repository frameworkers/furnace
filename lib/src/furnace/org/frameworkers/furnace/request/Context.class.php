<?php
namespace org\frameworkers\furnace\request;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\core\StaticObject;
use org\frameworkers\furnace\action\Router;

class Context extends StaticObject {
	
	// Route related context
	public $controllerClassName;
	public $controllerBaseName;
	public $handlerName;
	public $responseType;
	public $arguments;
	
	// Request related context
	public $post;
	public $get;
	
	// Environment related context
	public $urls  = array();
	
	public function __construct( ) {
		
	}
	
	public static function CreateFromUrl($url) {
		
		$context = new Context();
		
		// Determine the route to take for this url
		$route = Router::Route( $url );
		
		// Store route related context information
		$context->controllerClassName = ucwords($route['controller']).'Controller';
		$context->controllerBaseName  = $route['controller'];
		$context->handlerName         = $route['handler'];
		$context->responseType        = isset($route['type'])
			? $route['type']
			: Config::Get('defaultResponseType');
			
		// Store request related context information
		$context->arguments = $route['parameters'];
		$context->post      = $_POST;
		$context->get       = $_GET;
		
		// Store environment related context
		$context->urls['url_base']    = rtrim(Config::Get('applicationUrlBase'),'/');
		$context->urls['theme_base']  = $context->urls['url_base'].'/assets/themes/' . Config::Get('theme');
		$context->urls['view_base']   = $context->urls['url_base'].'/views';
		
		return $context;
	}
	
	public static function CreateFromControllerAndHandler($controllerName,$handlerName,$type='html',$args = array()) {
		
		$context = new Context();
		
		// Store route related context information
		$context->controllerClassName = $controllerName;
		$base                         = substr(
			$controllerName,0,strrpos($controllerName,'Controller'));
		$context->controllerBaseName  = strtolower($base[0]) . substr($base,1);
		$context->handlerName         = $handlerName;
		$context->responseType        = $type;
		
		// Store request related context information
		$context->arguments           = $args;
		$context->post                = $_POST;
		$context->get                 = $_GET;
		
		// Store environment related context
		$context->urls['url_base']    = rtrim(Config::Get('applicationUrlBase'),'/');
		$context->urls['theme_base']  = $context->urls['url_base'].'/assets/themes/' . Config::Get('theme');
		$context->urls['view_base']   = $context->urls['url_base'].'/views';
		
		return $context;
	}
}