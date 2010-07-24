<?php
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package   Furnace
 * @copyright Copyright (c) 2008-2010, Frameworkers.org
 * @license   http://furnace.frameworkers.org/license
 *
 */

/**
 * Furnace
 * 
 * This class provides the high-level functionality needed to direct the
 * handling of an {@link FApplicationRequest}. It handles the set-up and
 * tear-down of the Furnace environment and manages the transfer of control 
 * between lower elements of the Furnace library in the process of 
 * satisfying the user request.
 * 
 */
class Furnace {
	/**
     * The path to the project root directory. 
     * 
     * Note that this value is automatically computed.
     * 
     * @var string
     */
    public $rootdir;

    /**
     * Project configuration variables as read from the application configuration
     * file /app/config/app.yml.
     * 
     * @var array
     */
    public $config;

    /**
     * Route definitions for the project. The data for this array is parsed
     * from the routes defined in /app/config/routes.yml. 
     * 
     * @var array
     */
    public $routes;


    // Array: benchmarks
    // Internal benchmarking (statistics) data
    private $benchmarks;

    // Array: queries
    // Internal storage of queries executed
    public $queries = array();
    
    
    public $rawRrequest;
    public $request;
    public $response;
    public $theme;
    public $extensions;
    public $extDirToUse;    
    
    public $paths = array();
    public $db;
    public $model;
    public $user;

    public function __construct($config) {
    		
        // Compute the project root directory
        $this->rootdir = FURNACE_APP_PATH;
        $this->paths['rootdir'] = $this->rootdir;
        
        // Load the YML route definitions
        $this->routes = self::yaml($this->rootdir . '/config/routes.yml');

        // Load the YML application configuration file
        $this->config = $config;

        // Set the theme
        $this->theme  = $this->config->theme;
        
        // Initialize the session
        session_start();
    }

    /**
     * Process an {@link FApplicationRequest} and generate an 
     * {@link FApplicationResponse}
     * 
     * @param  FApplicationRequest  The {@link FApplicationRequest} object to process
     * @return FApplicationResponse The resulting {@link FApplicationResponse} object
     */
    public function process($request) {
    
        // Time benchmark
        $request->stats['proc_setup_start'] = microtime(true);
        
        
        // Store the FApplicationRequest object 
        $this->request = $request;
        
        // Make the raw request available to controllers via _furnace()->request
        $this->rawRequest = $request->raw;
        
        // Build extension registry
        $this->discoverExtensions($this->routes);
               	
        // Determine the route to take
        $this->request->route = $this->route($this->rawRequest,$this->routes);

        try {
            
            // Include application model data
            include_once("{$this->rootdir}/model/model.php");
        
            // Instantiate the global model data structure
            $GLOBALS['fApplicationModel'] = new ApplicationModel();
            $GLOBALS['_model'] = new ApplicationModel();
            

            $request->stats['proc_setup_end'] = 
            	$request->stats['proc_start'] = microtime(true);
            
            
            // Determine if an extension is being requested and set the file path
            // to the appropriate controller.
            // Extensions can live in one of two places:
            //	1) Inside the application's plugins/extensions directory
            //	2) Inside the Furnace plugins/extensions directory
            $appExtDir   = FURNACE_APP_PATH . '/plugins/extensions/';
            $furExtDir   = FURNACE_LIB_PATH . '/plugins/extensions/';
            $this->extDirToUse = $appExtDir;             
            
            if ('' != $this->request->route['extension']) {
            	// First try the application extension directory
                $controllerFilePath = $appExtDir
                    . "{$this->request->route['extension']}/pages/"
                    . "{$this->request->route['controller']}/"
                    . "{$this->request->route['controller']}Controller.php";
                    
                if (!file_exists($controllerFilePath)) {
                	// Try the Furnace plugins directory as a last ditch effort
                	$controllerFilePath = $furExtDir
	                    . "{$this->request->route['extension']}/pages/"
	                    . "{$this->request->route['controller']}/"
	                    . "{$this->request->route['controller']}Controller.php";

	                    if (!file_exists($controllerFilePath)) {
		                return ( $this->config->debug_level > 0 ) 
		                    ? $this->process(new FApplicationRequest("/_debug/errors/noController/"
		                        .str_replace('/','+',$controllerFilePath)))
		                    : $this->process(new FApplicationRequest("/error/notfound"));
		                exit();
	                } else {
	                	$this->extDirToUse = $furExtDir;
	                }
                }
            } else {
                $controllerFilePath = "{$this->rootdir}/pages/"
                    . "{$this->request->route['controller']}/"
                    . "{$this->request->route['controller']}Controller.php";  
                    
                // Ensure that the requested controller actually exists
	            if (!file_exists($controllerFilePath)) {
	                return ( $this->config->debug_level > 0 ) 
	                    ? $this->process(new FApplicationRequest("/_debug/errors/noController/"
	                        .str_replace('/','+',$controllerFilePath)))
	                    : $this->process(new FApplicationRequest("/error/notfound"));
	                exit();
	            }         
            }     
            
            // Include the appropriate base controller
            if ($this->request->route['extension'] 
            	&& file_exists($this->extDirToUse ."{$this->request->route['extension']}/pages/Controller.class.php")) {
            		include_once($this->extDirToUse
	                    ."{$this->request->route['extension']}/pages/Controller.class.php");
            } else {
                include_once(FURNACE_APP_PATH . '/pages/Controller.class.php');
            }
            // Include the controller file
            @include_once($controllerFilePath);

            // Does the requested controller class exist?
            // TODO: Check for the existence of the class within the file

            // Create an instance of the requested controller
            $pageExists = true;
            try {
                // Create a response object to hold response details
                $controllerClassName = "{$this->request->route['controller']}Controller";
                $this->response = new FApplicationResponse(
                    $this,new $controllerClassName(),$this->request->route['extension']);
                $this->response->setRequest($this->request);
            
                // Attempt to load the page template     
                $pageExists = $this->response->setPage(
                    $this->request->route['controller'],
                    $this->request->route['action']);
                    
            } catch (FDatabaseException $fde) {
                $_SESSION['_exception'] = $fde;
                echo $fde;
                exit();
            } catch (FException $fe) {
                $_SESSION['_exception'] = $fe;
                echo $fe;
                exit();
            } catch (Exception $e) {
                $_SESSION['_exception'] = $e;
                echo $fe;
                exit();
            }
            
            // Does the requested controller function exist?
            $handlerExists = $this->response->handlerExists($this->request->route['action']);

            if ($handlerExists) {

                // Call the handler
                $request->stats['handler_start'] = microtime(true);
                   
                try {
                    $this->response->run($this->request->route['action'],
                        $this->request->route['parameters']);
                    // Display error if expected view template does not exist..
                    if ($pageExists !== true 
                            && !$this->response->pageOverrideDetected) {
                        return $this->process(new FApplicationRequest(
                            (($this->config->debug_level > 0) 
                                ? ('/_debug/errors/noTemplate/'.str_replace('/','+',$pageExists))
                                : '/error/notfound')
                        ));
                        exit();
                    }   
                } catch (FDatabaseException $fde) {
                    $_SESSION['_exception'] = $fde;
                    echo $fde;
                    exit();
                } catch (FException $fe) {
                    $_SESSION['_exception'] = $fe;
                    echo $fe;
                    exit();
                } catch (Exception $e) {
                    $_SESSION['_exception'] = $e;
                    echo $e;
                    exit();
                }
                
                // Set the referringPage attribute in the session. This
                // allows non-view controller actions to redirect to
                // the location from which they were called.
                $_SESSION['referringPage'] = $this->rawRequest;                
                
                // Time benchmark	
                $request->stats['handler_end'] = 
                	$request->stats['proc_end']  = microtime(true);
                	
                
                // SEND THE RESPONSE OUT OVER THE WIRE
                $this->response->send();
                
                
            } else {
                if ($this->config->debug_level > 0) {
                    $this->process("/_debug/errors/noControllerFunction/"
                        . str_replace('/','+',$controllerFilePath)
                        . "/{$this->request->route['action']}");
                } else {
                    $this->process("/error/notfound");
                } 
                exit();
            }

        } catch (Exception $e) {
             dev_messages();

             die("Unexpected error {$e}");  
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
    
    private function discoverExtensions($routes) {
    	foreach ($routes as $r => $route) {
    		if ($r[0] == '_') {
	    		// Append to extension registry:
	             $this->extensions[$route['path']] = 
	             	array("global" => (isset($route['global']) ? $route['global'] : false),
	              		  "theme"  => (isset($route['theme'])  ? $route['theme']  : 'default'));
    		}
    	}
    }

    /*
     * ROUTE
     */
    public function route($request,&$routes,$prefix='/') {
        // split the route into segments, ignoring any url_base segments
    	$req = str_replace($this->config->url_base,'/',$request);
        $parts = explode('/',ltrim($req,'/'));
        
        
        $the_route = array();
        
        _log()->log("Will process a maximum of ".count($routes)." routes",FF_DEBUG);
        foreach ( $routes as $r => $route ) {
            if ($r[0] == '_') {
              _log()->log("Found extension {$route['path']} with prefix {$route['prefix']} and "
                  .count($route['routes'])." routes",FF_DEBUG);
              $extPath   = $route['path'];
              $extPrefix = $route['prefix'];
              $result    = $this->route($request,$route['routes'],$extPrefix);
              if ( $result ) {
                $result['extension'] = $extPath;
                $result['theme']     = isset($route['theme']) ? $route['theme'] : 'default';
                return $result;
              }
              // Route not found in this extension
              _log()->log("No match among routes for: {$route['path']}",FF_DEBUG);
              continue;
            }
            
            _log()->log("Trying route: {$prefix}{$route['url']}",FF_DEBUG);
            $rp = explode('/',ltrim($prefix.$route['url'],'/'));
            $wildcards  = array();
            $parameters = array();
            
            // If the number of defined segments does not match,
            // ignore this route
            if ( count($rp) > count($parts) ) {
              continue;
            }
            
            // Test each non-wildcard part for a match
            // Wildcards are * and :text
            $matched = true;
            for ($j = 0, $c = count($rp); $j < $c; $j++ ) {
              // Just ignore unnamed wildcards
              if ($rp[$j] == '*') {
                if ('' != $parts[$j]) { $parameters[] = $parts[$j]; }
                continue;
              }
              // Capture named wildcards in the 'wildcards' array
              if ($rp[$j][0] == ':') {
                $wildcards[trim($rp[$j],':')] = $parts[$j];
                if ( ':view' != $rp[$j] && 
            	':action' != $rp[$j] && 
            	':controller' != $rp[$j] ) {
                  $parameters[$rp[$j] ] = $parts[$j];
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
              
              // Build the expanded/replaced prefix if it contains :xx variables
              if(strpos($prefix,':')) {
                  $prefixParts = explode('/',$prefix);
                  foreach ($prefixParts as &$p) {
                      if ($p[0] == ':') {
                          $p = $parameters[$p];
                          break;
                      }
                  }
                  $prefix = implode('/',$prefixParts);
              }
            
              // Build the resulting route data array
              $the_route = array(
                'prefix'     => $prefix,
                'controller' => ((isset($wildcards['controller']) && 
            		  !empty($wildcards['controller']))
                   ? $wildcards['controller']
                   : (isset($route['map']['controller'])
                      ? $route['map']['controller']
                      : 'root')),
                'action'     => ((isset($wildcards['view']) &&
                                  !empty($wildcards['view']))
                   ? $wildcards['view']
                   : (isset($route['map']['view'])
                     ? $route['map']['view']
                     : 'index')),
                'parameters' => $parameters,
                'extension'  => false
              );
              _log()->log("Routing {$req} to {$the_route['controller']}::{$the_route['action']}(".implode(',',$parameters).")",FF_INFO);
              return $the_route;
            }
        }
        // Invalid route specified
        if ($prefix == '/') {
            _log()->log("Router could not understand route: {$request}",FF_NOTICE);
            return false;
        }
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
    
	/*
 	 * Function: standardizeName
 	 * 
 	 * This function is private. It takes a string and 
 	 * standardizes it according to framework naming 
 	 * conventions.
 	 * 
 	 * Parameters:
 	 * 
 	 *  name - The name string to standardize.
 	 * 
 	 * Returns:
 	 * 
 	 *  (string) The standardized name.
 	 */
 	public static function standardizeName($name) {
  		// 1. Replace all '_' with ' ';
  		// 2. Capitalize all words
  		// 3. Concatenate words
  		//
  		// Turns: long_object_name
  		// into:  LongObjectName
  		return 
  			str_replace(" ","",ucwords(str_replace("_"," ",$name)));
  	}
  	
  	/*
  	 * Function: standardizeAttributeName
  	 * 
  	 * This funtion is like <standardizeName> in that it takes
  	 * a string and standardizes it according to framework
  	 * naming conventions for object attributes.
  	 * 
  	 * Parameters:
  	 * 
  	 *  name - The attribute name string to standardize.
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) The standardized attribute name
  	 */
  	public static function standardizeAttributeName($name) {
  		// 1. Replace all '_' with ' ';
		// 2. Replace all '-' with ' ';
  		// 3. Capitalize all words
  		// 4. Concatenate words
  		// 5. Make the first letter lowercase
  		//
  		// Turns: long_variable_name
  		// into:  longVariableName
  		$s = str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ",$name))));
  		return strtolower($s[0]) . substr($s,1);
  	} 

  	public static function standardizeTableName($name,$bIsLookupTable = false) {
  		if ("app_accounts" == $name || "app_roles" == $name || "app_logs" == $name) { return $name; }
  		if ($bIsLookupTable) {
  			// A lookup table needs to maintain its '_' characters (which are
			// otherwise illegal. Standardize the 3 components, but leave the 
			// '_' separators untouched.
			list($t1,$t2,$v) = explode("_",$name);
			return self::standardizeAttributeName($t1)
				.  "_"
				.  self::standardizeAttributeName($t2)
				.  "_"
				.  self::standardizeAttributeName($v);
  		} else {
  			$stdName = self::standardizeAttributeName($name);
	  		if (isset($GLOBALS['furnace']->config->data['hostOS']) &&
	  			strtolower($GLOBALS['furnace']->config->data['hostOS']) == 'windows') {
	  				
	  			// Windows has a case-insensitive file system, and the default 
				// settings of MySQL on windows force tablenames to be strictly lowercase
	  			return strtolower($stdName);	
	  		} else {
	  			// In all other environments, just return the standardized name
				return $stdName;
	  		}
  		}
  	}
}

// Temporary location for this utility function
function is_assoc_callback($a, $b) {
    return $a === $b ? $a + 1 : 0;
}
?>