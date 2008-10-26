<?php
/*
 * frameworkers.org
 * 
 * index.php
 * Created on Oct 12, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
class FRouter {
    
    public static function Route($request) {
      global $rootdir;
      $parts  = explode("/",ltrim($request,"/"));
      $routes = Spyc::YAMLLoad(file_get_contents($rootdir.'/app/config/routes.yml'));
      $the_route = array();
      foreach ($routes as $r=>$route) {
          //echo "Testing route: {$r}<br/>";
          $rp = explode("/",ltrim($route['url'],"/"));
          $wildcards  = array();
          $parameters = array();
          // If the number of defined segments does not match, ignore this route
          if (count($rp) > count($parts)) {
              continue;
          }
         
          // Test each non-wildcard for a match
          // Wildcards are * and :text
          $matched = true;
          for ($j = 0; $j < count($rp);$j++) {
              // Just ignore unnamed wildcards
              if ($rp[$j] == "*") {
              	  if ('' != $parts[$j]) {$parameters[] = $parts[$j];}
                  continue;
              }
              // Capture named wildcards in the 'wildcards' array
              if ($rp[$j][0] == ':') {
                  $wildcards[trim($rp[$j],":")] = $parts[$j];
                  if (":controller" != $rp[$j] && ":view" != $rp[$j]) {
                  	$parameters[] = $parts[$j];
                  }
                  continue;
              }
              // Test for equality between non-wildcard parts
              if ($rp[$j] != $parts[$j]) {
                  $matched = false;
                  break;
              }
          }
          if ($matched) {
              // Capture additional view arguments (in the case that the
			  // url contained more parts than the matching route rule
			  for($k=count($rp);$k < count($parts); $k++) {
			  	if ('' != $parts[$k]) {$parameters[] = $parts[$k];}
			  }
			  // Build the resulting route data array
              $the_route = array(
              	  'prefix' => ((isset($route['prefix'])) ? $route['prefix'] : ''),
                  'controller' => ((isset($wildcards['controller']) && !empty($wildcards['controller']))
                      ? $wildcards['controller']
                      : (isset($route['map']['controller'])
                          ? $route['map']['controller']
                          : "_default")
                  ),
                  'view' => ((isset($wildcards['view']) && !empty($wildcards['view']))
                      ? $wildcards['view']
                      : (isset($route['map']['view'])
                          ? $route['map']['view']
                          : "index")
                  ),
                  'parameters' => $parameters
              );
              //var_dump($the_route);
              return $the_route;
              exit();
          }
      }
      // Invalid route specified
      return array();
    }   
} 
?>