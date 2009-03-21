<?php
class Furnace {
 	
 	// Variable: rootdir
 	// The path to the project root directory. This value is 
 	// automatically computed.
 	public $rootdir;
 	
 	// Variable: fueldir
	// The path to the Fuel application. This value is automatically
	// computed from the value of {rootdir}
 	public $fueldir;
 	
 	// Array: config
 	// Project configuration variables as read from {$rootdir}/app/config/app.yml
 	public $config;
 	
 	// Array: routes
 	// Route definitions for the project. The data for this
 	// array comes from the routes defined in {rootdir}/app/config/routes.yml
 	public $routes;
 	
 	// Variable: controllerBasePath
 	// The path to the controller directory. 
 	public $controllerBasePath;
 	
 	// Variable: viewBasePath
 	// The path to the view directory.
 	public $viewBasePath;
 	
 	// Variable: layoutBasePath
	// The path to the layouts directory.
	public $layoutBasePath;
 	
 	
 	// Array: benchmarks
 	// Internal benchmarking (statistics) data
 	private $benchmarks;
 	
 	// Array: queries
	// Internal storage of queries executed
	public $queries = array();
 	
 	// Benchmarking variables
 	private $bm_renderstart = 0;
 	private $bm_renderend   = 0;
 	private $bm_reqstart    = 0;
 	private $bm_reqend      = 0;
 	private $bm_envsetupstart = 0;
 	private $bm_envsetupend   = 0;
 	private $bm_processstart  = 0;
 	private $bm_processend    = 0;
 	
 	
 	public function __construct($useFuel = false) {
 		
 		// Compute the project root directory
 		$this->rootdir = dirname(dirname(dirname(__FILE__)));
 		
 		if ($useFuel) {
 			// Compute the fuel root directory
			$this->fueldir = $this->rootdir . '/lib/fuel';

			// Store the path to the controller and view directories
			$this->controllerBasePath = $this->fueldir . '/app/controllers';
			$this->viewBasePath       = $this->fueldir . '/app/views';
			$this->layoutBasePath     = $this->fueldir . '/app/layouts';
			
 		} else {
 			// Store the path to the controller and view directories
 			$this->controllerBasePath = $this->rootdir . '/app/controllers';
 			$this->viewBasePath       = $this->rootdir . '/app/views';
 			$this->layoutBasePath     = $this->rootdir . '/app/layouts';
 		}
 		
 		//Include the yml parser
		include($this->rootdir . '/lib/yaml/spyc-0.2.5.php5');
		
		// Load the YML route definitions
		$this->routes = self::yaml($this->rootdir . '/app/config/routes.yml');
		
		// Load the YML application configuration file
		$this->config = self::yaml($this->rootdir . '/app/config/app.yml');
		
		// Compute the Subversion revision number for fuel
		if (file_exists($this->fueldir . '/.svn/entries')) {
			$data = explode("\n",file_get_contents(
				$this->rootdir . '/.svn/entries',0,null,0,256));
			$this->config['svn_revision'] = trim($data[3]);
		}
		
 		// Initialize the session
 		session_start();
 	}
 	
 	public function process($request) {
 		
 		if ($this->config['debug_level'] > 0) {
 			$this->bm_reqstart = microtime(true);
 		}
 		// Controller
 		$controller = null;
 		// View Template
 		$templateFilePath = '';
 		
 		// Determine the route to take
 		$routeData = self::route($request,$this->routes);
 		
 		// Does the requested controller file exist?
 		$controllerClassName  = $routeData['controller'] . 'Controller';
 		$controllerFileExists = file_exists(
 			$controllerFilePath = $this->controllerBasePath .  "/{$controllerClassName}.php");

 			
 		if ($controllerFileExists) {
 			if ($this->config['debug_level'] > 0) {
 				$this->bm_envsetupstart = microtime(true);
 			}
 			// Include Furnace Foundation classes
 			set_include_path(get_include_path() . PATH_SEPARATOR . 
 				$this->rootdir . '/lib/furnace/foundation');
 			include('foundation.bootstrap.php');
 			
 			// Include Furnace Facade classes
 			 set_include_path(get_include_path() . PATH_SEPARATOR .
 				$this->rootdir . '/lib/furnace/facade');
 			include('facade.bootstrap.php'); 
 			
 			// Include the custom controller base file
 			include($this->rootdir . '/app/controllers/_base/Controller.class.php');
 			
 			// Include compiled model data, if available
 			@include($this->rootdir . "/app/model/objects/compiled.php");
 			
 			// Include the controller file
 			include($controllerFilePath);
 			if ($this->config['debug_level'] > 0) {
 				$this->bm_envsetupend = microtime(true);
 			}
 			
 			
 		
 			
 			// Does the requested controller class exist?
 			// TODO: Check for the existence of the class within the file
 			
 			// Create an instance of the requested controller
 			$controller = new $controllerClassName();
 			
 			// Does the requested controller function exist?
 			$handlerExists = is_callable(array($controller,$routeData['view']));
 			
 			if ($handlerExists) {
 				
 				// Set the default template file content
 				$templateFilePath = $this->viewBasePath 
 					. "/{$routeData['controller']}/{$routeData['view']}.html";
 				
 				// Call the handler
 				if ($this->config['debug_level'] > 0) {
 					$this->bm_processstart = microtime(true);
 				}
 				call_user_func_array(array($controller,$routeData['view']),$routeData['parameters']);
 				if ($this->config['debug_level'] > 0) {
 					$this->bm_processend   = microtime(true);
 				}
 				
 				// Set the view template
 				$templateFileExists = file_exists($templateFilePath);
 				if ($templateFileExists) {
 					$controller->setTemplate($templateFilePath);
 				} else {
 					die("No template file {$templateFilePath}");
 				}
 				
 				// Provide access to Furnace project variables
 				$controller->ref('_furnace',$this->config);
 				
 				// Send the rendered content out over the wire
 				if ($this->config['debug_level'] > 0) {
 					$this->bm_renderstart = microtime(true);
 				}
 				$controller->render();
 				if ($this->config['debug_level'] > 0) {
 					$this->bm_renderend  = $this->bm_reqend = microtime(true);
 				}
 				
 				if ($this->config['debug_level'] > 1) {
 					echo "REQUEST TIME: " . ($this->bm_reqend - $this->bm_reqstart) . "<br/>\r\n";
 					echo "SETUP   TIME: " . ($this->bm_envsetupend - $this->bm_envsetupstart) . "<br/>\r\n";
 					echo "PROCESS TIME: " . ($this->bm_processend - $this->bm_processstart) . "<br/>\r\n";
 					echo "QUERY DELAY:  " . count($this->queries) . " queries<br/>\r\n<span style='font-size:90%;'>";
 					$qd = 0;
 					foreach ($this->queries as $q) {
 						echo "&nbsp;&nbsp;{$q['delay']}s\t{$q['sql']}<br/>\r\n";
 						$qd += $q['delay'];
 					}
 					echo "</span><br/>\r\n&nbsp;&nbsp;" . count($this->queries) . " queries took {$qd} seconds.<br/>\r\n";
 					echo "<br/>\r\n";
 					echo "RENDER  TIME: " . ($this->bm_renderend - $this->bm_renderstart) . "<br/>\r\n";
 				}
 				// Clean up
 				// TODO: free memory, etc
 			} else {
 				die("No handler function '{$routeData['view']}' defined in {$controllerFilePath}");	
 			}
 		} else {
 			die("No controller file {$routeData['controller']}");	
 		}
 	}
 	
 	
	 /*
	  * YAML
	  */
	 public static function yaml($file_path) {
	 	if (file_exists($file_path)) {
	 		return SPYC::YAMLLoad(file_get_contents($file_path));	
	 	} else {
	 		return false;
	 	}
	 }
	 
	 public function parse_yaml($file_path) {
	 	return self::yaml($file_path);
	 }
	 
	 /*
	  * ROUTE
	  */
	public static function route($request,&$routes) {
		
		// Split the route into segments
		$parts  = explode("/",ltrim($request,"/"));
      
		$the_route = array();
		foreach ( $routes as $r => $route ) {
			$rp = explode("/",ltrim($route['url'],"/"));
			$wildcards  = array();
			$parameters = array();
			// If the number of defined segments does not match, 
			// ignore this route
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
              return $the_route;
              exit();
          }
      }
      // Invalid route specified
      return false;
    }
    
    public function read_flashes($bReset = true) {
	 	if (isset($_SESSION['flashes'])) {
	 		$flashes = $_SESSION['flashes'];
	 		if ($bReset) {
	 			$_SESSION['flashes'] = array();	
	 		}
	 		return $flashes;
	 	} else {
	 		return array();
	 	}	
 	}
 }
?>