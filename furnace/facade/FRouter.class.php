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
          $wildcards = array();
         
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
                  continue;
              }
              // Capture named wildcards in the 'wildcards' array
              if ($rp[$j][0] == ':') {
                  $wildcards[trim($rp[$j],":")] = $parts[$j];
                  continue;
              }
              // Test for equality between non-wildcard parts
              if ($rp[$j] != $parts[$j]) {
                  $matched = false;
                  break;
              }
          }
          if ($matched) {
              //var_dump($wildcards);
              $the_route = array(
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
                  'parameters' => $wildcards 
              );
              //var_dump($the_route);
              return $the_route;
              exit();
          }
      }
      echo "<b>Invalid Route</b><br/>";
      if (FProject::DEBUG_LEVEL > 0) {
      	echo "Route:<br/>";
      	var_dump($the_route);
      }
      die();
      //return false;
    }   
} 
?>