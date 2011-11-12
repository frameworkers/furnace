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
namespace furnace\routing;

use furnace\core\Config;
use furnace\utilities\Logger;
use furnace\utilities\LogLevel;

/**
 * Router provides a way to connect requests (URLs) to 
 * application-defined controllers and handlers.
 * 
 * @author     Andrew Hart <andrew.hart@frameworkers.org>
 * @version    SVN: $Id$ *
 */
class Router {
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
			"type"       => "html",
			"extension"  => false,
            "module"     => "app"
			
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
		
		// Ignore any query string arguments when routing
		if (($qmark = strpos($url,'?') > 0)) {
			$url = substr($url,0,$qmark);
		}

        // Strip the prefix (url_base) from the incoming url
        $prefix = Config::Get('applicationUrlBase');
        if ($prefix != '/') {
        	$url    = substr($url,strlen($prefix) + 1);
        }
        
        // Break the incoming url into its segmented parts
        $parts  = explode('/',ltrim($url,'/'));

        // Look for a response type which can be specified by the presence of
        // an extension on the request (e.g.: /blog/post/34.json => 'json').
        // Any usable type must have a corresponding entry in types.config.php
        // or it will be ignored.
        $type     = null;
        $lastPart =& $parts[count($parts)-1];
        $foundDot = strrpos($lastPart,'>') !== false;
        $ext      = ($foundDot) 
        	? substr($lastPart,strrpos($lastPart,'>') + 1) 
        	: false;
        if ( $ext && ResponseTypes::TypeExists( $ext ) ) {
        	$type = $ext;
        }
        if ( $ext ) { // Strip the extension from the route
        	$lastPart = str_replace(">{$ext}",'',$lastPart);
        }

        $the_route = array();
        
        foreach ( self::$routes as $route ) {
            Logger::Log(LogLevel::DEBUG,"Trying route: {$route['url']}");
        	            
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
                'extension'  => isset($route['extension']) ? $route['extension'] : false,
                'module'     => isset($route['module']) ? $route['module'] : 'default',
                'layout'     => isset($route['layout']) ? $route['layout'] : Config::Get('default.layout'),
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
                     	: 'text/html'))), // use the default configured type
              );
              
              Logger::Log(LogLevel::DEBUG,"Matched route: {$the_route['route']}");
              return new Route($the_route);
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
