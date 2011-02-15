<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage action
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
namespace org\frameworkers\furnace\action;

use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\core\StaticObject;
use org\frameworkers\furnace\response\ResponseTypes;

/**
 * Router provides a way to connect requests (URLs) to 
 * application-defined controllers and handlers.
 * 
 * @author     Andrew Hart <andrew.hart@frameworkers.org>
 * @version    SVN: $Id$ *
 */
class Router extends StaticObject{
	/**
	 * The complete list of route definitions as parsed from the connection
	 * configuration file 
	 * 
	 * @var array
	 */
	public static $routes = array();
	
	/**
	 * Define a route your application will respond to
	 * 
	 * The purpose of {@link Connect} is to provide a mapping
	 * between a URL (or a URL pattern) and a particular
	 * controller and handler defined within your application.
	 * 
	 * @example 'routing.php'
	 * 
	 * @param string   $route     The route definition to record
	 * @param array    $options   Options to apply to this route
	 */
	public static function Connect($route,$options = array()) {

		// Set up default values
		$defaults = array(
			"controller" => "default",
			"handler"    => "index",
			"type"       => "html"
			
		);
		
		// Merge default values into provided options
		$options = array_merge($defaults,$options);
		
		// Merge the route url into the options
		$options['url'] = $route;
		
		// Add the route to the list of known routes
		if (self::$routes == null) {
			self::$routes = array();
		}
		array_push(self::$routes,$options);	
	}
	
	
	/**
	 * Attempt to find a match for the provided URL among the known
	 * application routes
	 * 
	 * @param string   $url     The candidate URL
	 */
	public static function Route($url) {

        // Strip the prefix (url_base) from the incoming url
        $prefix = Config::Get('applicationUrlBase');
        if ($prefix != '/') {
        	$url    = substr($url,strlen($prefix) + 1);
        }
        
        // Break the incoming url into its segmented parts
        $parts  = explode('/',ltrim($url,'/'));
        
        // Check the first url part for the foundry magic url and, if found,
        // reconfigure the environment for a foundry request
        if (($foundryMagic = Config::Get('foundryMagicUrlBase')) !== null
        	&& $parts[0] == $foundryMagic) {
        		
        	// Mark this request as being a foundry request
        	define("FOUNDRY_REQUEST",true);
        	Config::Set('applicationUrlBase',Config::Get('applicationUrlBase')
        		. '/' . Config::Get('foundryMagicUrlBase'));
        	Config::Set('applicationControllersDirectory',FURNACE_APP_PATH 
        		. "/foundry/controllers");
        	Config::Set('applicationViewsDirectory',FURNACE_APP_PATH 
        		. "/foundry/views");
        	Config::Set('applicationThemesDirectory',FURNACE_APP_PATH
        		. "/foundry/assets/themes");
        	Config::Set('theme','foundry');
        	Config::Set('applicationName','Furnace - Foundry');
        	
        	// Clear routes and load Foundry route definitions
        	Router::Clear();
        	require_once(FURNACE_APP_PATH . '/foundry/config/routes.config.php');
        		
        	// Pop the `$foundryMagic` segment off the url
        	array_shift($parts);
        } else {
        	define("FOUNDRY_REQUEST",false);
        }
        
        
        // Look for a response type which can be specified by the presence of
        // an extension on the request (e.g.: /blog/post/34.json => 'json').
        // Any usable type must have a corresponding entry in types.config.php
        // or it will be ignored.
        $type     = null;
        $lastPart =& $parts[count($parts)-1];
        $foundDot = strrpos($lastPart,'.') !== false;
        $ext      = ($foundDot) 
        	? substr($lastPart,strrpos($lastPart,'.') + 1) 
        	: false;
        if ( $ext && ResponseTypes::TypeExists( $ext ) ) {
        	$type = $ext;
        }
        if ( $ext ) { // Strip the extension from the route
        	$lastPart = str_replace(".{$ext}",'',$lastPart);
        }

        $the_route = array();
        
        foreach ( self::$routes as $route ) {
        	            
            $rp = explode('/',ltrim($route['url'],'/'));
            $wildcards  = array();
            $parameters = array();
            
            // If the number of defined segments is greater than
            // the provided segments, ignore this route
            if ( count($rp) > count($parts) ) {
              continue;
            }
            
            // If route is '/', both will contain empty strings
            // Clear it out to bypass following wildcard test
            if (empty($rp[0]) && empty($parts[0])) {
            	$rp = $parts = array();
            }
            
            // Test each non-wildcard part for a match
            // Wildcards are * and :text
            $matched = true;
            for ($j = 0, $c = count($rp); $j < $c; $j++ ) {
            	
              // Just ignore asterisk wildcards (don't care)
              if ($rp[$j] == '*') {
                continue;
              }
              // Capture named wildcards in the 'wildcards' array
              if ($rp[$j] && $rp[$j][0] == ':') {
                $wildcards[substr($rp[$j],1)] = $parts[$j];
                if ( ':handler'    != $rp[$j] && 
            	     ':controller' != $rp[$j] ) {
                  $parameters[substr($rp[$j],1)] = $parts[$j];
                }
                continue;
              }
              // Test for equality between non-wildcard parts
              if ($rp[$j] != $parts[$j]) {
                $matched = false;
                break;
              }
            }
            
            if ( $matched ) {
              // Capture additional view arguments (in the case that the 
              // url contained more parts than the matching route rule
              for ( $k = count($rp), $l = count($parts); $k < $l; $k++ ) {
                if ('' != $parts[$k] ) { $parameters[] = $parts[$k]; }
              }
                          
              // Build the resulting route data array
              $the_route = array(
                'url'        => $url,
                'route'      => $route['url'],
                'prefix'     => $prefix,
                'controller' => ((isset($wildcards['controller']) && 
            		             !empty($wildcards['controller']))
                   ? $wildcards['controller']
                   : (isset($route['controller'])
                      ? $route['controller']
                      : 'default')),
                'handler' => ((isset($wildcards['handler']) &&
                              !empty($wildcards['handler']))
                   ? $wildcards['handler']
                   : (isset($route['handler'])
                     ? $route['handler']
                     : 'index')),
                'parameters' => $parameters,
                'type'       => ((isset($type)    // has an extension been detected?
                     ? $type                      // use it, otherwise:
                     : (isset($route['type'])     // is there type information in the route?
                     	? $route['type']          // use it, otherwise:
                     	: app()->config->responseType))), // use the default configured type
              );
              
              
              // Validation: If the matched controller file does not actually exist, then
              // the route should be discarded because it is likely that another route exists
              // for which the "controller" is actually a handler. Continuing here gives us 
              // a chance to parse that route instead. This is particularly true for simple
              // applications which use only a DefaultController.
              if (!is_file(Config::Get('applicationControllersDirectory') .'/'
              	. ucwords($the_route['controller']).'Controller.class.php')) {
                continue;
              }
              
              // Handler fix-ups
              $the_route['handler'] = str_replace('-','_',$the_route['handler']);

              return $the_route;
            }
        }
        
        return false;
	}
	
	/**
	 * Remove all previously stored route definitions
	 * 
	 */
	public static function Clear() {
		self::$routes = array();
	}
}