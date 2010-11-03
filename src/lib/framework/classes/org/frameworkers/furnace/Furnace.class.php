<?php
namespace org\frameworkers\furnace;
use org\frameworkers\furnace\config\FApplicationConfig;

use org\frameworkers\furnace\control\FApplicationRequest;

use org\frameworkers\furnace\control as Control;
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
    
    public $defaultModule; 
    
    public $modulePathToUse;
    public $controllerClassName;
    public $controllerFilePath;
    public $controllerObject;
    
    

    public $applicationVariables = array();
    
    public $paths = array();
    public $db;
    public $model;
    public $user;

    public function __construct($config) {
    	$GLOBALS['_furnace_stats']['furnace_constructor'] = microtime(true);
    		
        // Compute the project root directory
        $this->rootdir = FURNACE_APP_PATH;
        $this->paths['rootdir'] = $this->rootdir;
        
        // Load the YML route definitions
        $this->routes = self::yaml($this->rootdir . '/config/routes.yml');
        $GLOBALS['_furnace_stats']['routes_loaded'] = microtime(true);

        // Load the YML application configuration file
        $this->config = $config;
        
        // Initialize the session
        session_start();
    }

    public function loadApplicationModel() {
		// Include application model data
	
		// Instantiate the global model data structure
		$GLOBALS['_model'] = new \ApplicationModel();
    }

    private function determineController() {

        $controllerFilePath = $this->controllerFilePath = $this->modulePathToUse . "/controllers/"
        	. "{$this->request->route['controller']}Controller.class.php";

        // Ensure that the requested controller actually exists
        if (file_exists($controllerFilePath)) {
        	$controllerClassName = $this->controllerClassName = "{$this->request->route['controller']}Controller";
        } else {
        	// Try to fall back to the root controller
        	$controllerFilePath  = $this->controllerFilePath  = $this->modulePathToUse . "/controllers/defaultController.class.php";
        	$controllerClassName = $this->controllerClassName = "defaultController";
        	// Update the route information to reflect the fallback operation
        	array_unshift($this->request->route['parameters'],$this->request->route['action']);
        	$this->request->route['action'] = $this->request->route['controller'];
        	$this->request->route['controller'] = 'default';
        }
        
        /* Ensure that everything will be ok with this request */
        // Ensure the base controller exists
        if (!file_exists($this->modulePathToUse . '/controllers/Controller.class.php')) {
        	$this->triggerError('noBaseController',$this->request->route['module']);
        }
        
        // Ensure the controller file exists
        if (!file_exists($controllerFilePath)) {
        	$this->triggerError('noController',array($this->request->route['module'],$this->request->route['controller']));
        }
	
	    // Include the base controller
	    include_once($this->modulePathToUse . '/controllers/Controller.class.php');

        // Include the controller file
        include_once($controllerFilePath);
        

        // Does the requested controller class exist?
        // TODO: Check for the existence of the class within the file

	    return $controllerClassName;
    }

    /**
     * Process an {@link FApplicationRequest} and generate an 
     * {@link FApplicationResponse}
     * 
     * @param  FApplicationRequest  The {@link FApplicationRequest} object to process
     * @return FApplicationResponse The resulting {@link FApplicationResponse} object
     */
    public function process($request) {
  		$GLOBALS['_furnace_stats']['furnace_process_start'] = microtime(true);
        // Time benchmark
        $request->stats['proc_setup_start'] = microtime(true);
        
            
        // Ensure default module specified
        if (isset($this->config->data['defaultModule']) && !empty($this->config->data['defaultModule'])) {
        	$this->defaultModule = $this->config->data['defaultModule'];
        } else {
        	$this->defaultModule = '\org\frameworkers\furnace\help';
        	$request = new FApplicationRequest('/_help/noDefaultModule');
        }        
        
        // Store the FApplicationRequest object 
        $this->request = $request;
        
        // Make the raw request available to controllers via _furnace()->request
        $this->rawRequest = $request->raw;
               	
        // Determine the route to take
        $GLOBALS['_furnace_stats']['furnace_route_start'] = microtime(true);
        $this->request->route = $this->route($this->rawRequest,$this->routes);
        $GLOBALS['_furnace_stats']['furnace_route_end'] = microtime(true);
        $GLOBALS['_furnace_stats']['furnace_route_time'] =  $GLOBALS['_furnace_stats']['furnace_route_end'] - $GLOBALS['_furnace_stats']['furnace_route_start'];
	
        // Set the Theme
        $this->theme  = isset ($this->request->route['theme']) && !empty($this->request->route['theme'])
        	? $this->request->route['theme']
        	: $this->config->theme;
        
        // Define the module path based on the route information
        $this->modulePathToUse = FURNACE_APP_PATH 
        	. "/modules" 
        	. str_replace("\\","/",$this->request->route['module']);
        	        	
        try {

            $request->stats['proc_setup_end'] = 
            	$request->stats['proc_start'] = microtime(true);
           
            $GLOBALS['_furnace_stats']['furnace_determineController_start'] = microtime(true);
	    	$controllerClassName = $this->determineController();
	    	$GLOBALS['_furnace_stats']['furnace_determineController_end'] = microtime(true);

            // Create an instance of the requested controller and load the template
            $pageExists = true;
            try {
                // Create a response object to hold response details
                // Provide view access to Furnace, Application, and Model config vars and $_SERVER superglobal
                $GLOBALS['_furnace_stats']['furnace_determineController_storeApplicationVariable_start'] = microtime(true);
		        $this->storeApplicationVariable('_furnace',$this);
		        $this->storeApplicationVariable('_app',    $this->config->data);
		        $this->storeApplicationVariable('_model',  $GLOBALS['fApplicationModel']);
		        $this->storeApplicationVariable('_const',  get_defined_constants());
		        $this->storeApplicationVariable('_server', $_SERVER);
		        $this->storeApplicationVariable('_session',$_SESSION);
		        $this->storeApplicationVariable('_user',   _user());
		        $this->storeApplicationVariable('_now',    date('Y-m-d G:i:s'));
            	
            	// Determine the prefix for relative links (especially important for extensions)
		        $trimmedUrlBase = rtrim($this->config->url_base,'/'); 
		        $this->storeApplicationVariable('%prefix', $trimmedUrlBase . $this->request->route['prefix']);
		        $this->storeApplicationVariable('%a',$trimmedUrlBase);
            	
		        // Determine theme url for assets
		        $this->storeApplicationVariable('%theme',"{$this->config->url_base}assets/themes/{$this->theme}");
		        
		        $GLOBALS['_furnace_stats']['furnace_determineController_storeApplicationVariable_end'] = microtime(true);

            	// Create an instance of the controller
            	$theController = $this->controllerObject = new $controllerClassName();
            	
            	// Inject the furnace javascript variables into the page
        		$theController->injectJS($this->prepareFurnaceJS($this));
            	
				// Does the requested controller function (handler) exist?
        		if( is_callable(array($theController,$this->request->route['action']) ) ) {

					$GLOBALS['_furnace_stats']['furnace_callHandler_start'] = microtime(true);
				    // Execute the handler
				    call_user_func_array(
				    	array($theController,"satisfyRequest"),
				    		array($this->request->route['action'],
				    			  $this->request->route['parameters']));
				    $GLOBALS['_furnace_stats']['furnace_callHandler_end'] = microtime(true);
				    $GLOBALS['_furnace_stats']['furnace_callHandler_time'] = $GLOBALS['_furnace_stats']['furnace_callHandler_end'] - $GLOBALS['_furnace_stats']['furnace_callHandler_start'];
			    	


					// Set the referringPage attribute in the session. This
                	// allows non-view controller actions to redirect to
                	// the location from which they were called.
                	$_SESSION['referringPage'] = $this->rawRequest;                
                
                	// Time benchmark	
                	$request->stats['handler_end'] = 
                		$request->stats['proc_end']  = microtime(true);
                	
                
                	// SEND THE RESPONSE OUT OVER THE WIRE
                	// Time benchmark
                	$GLOBALS['_furnace_stats']['furnace_render_start'] = microtime(true);
			    	$contents = $theController->render(false);
			    	$GLOBALS['_furnace_stats']['furnace_render_end']  = microtime(true);
			    	$GLOBALS['_furnace_stats']['furnace_render_time'] = $GLOBALS['_furnace_stats']['furnace_render_end'] - $GLOBALS['_furnace_stats']['furnace_render_start'];
			    	echo $contents;
			    	
			    
        		
        		} else {
        			$mod = $this->request->route['module'];
        			$con = $controllerClassName;
        			$fn  = $this->request->route['action'];
		    		$_SESSION['referringPage'] = $this->rawRequest;
		    		$this->triggerError('noControllerFunction',array($mod,$con,$fn));
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
                echo $fe;
                exit();
            }
        } catch (Exception $e) {
             die("Unexpected error {$e}");  
        } 
    }
    
    /*
     * YAML
     */
    public static function yaml($file_path) {
        if (file_exists($file_path)) {
            return \com\thresholdstate\Spyc::YAMLLoad(file_get_contents($file_path));
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
    
	private function prepareFurnaceJS() { 
        $fu_user_username   = (_user()) ? "\""._user()->getUsername()."\"" : 'false';
        $js = <<<__END
var fu_current_theme    = '{$this->currentTheme}';
var fu_route_action     = '{$this->request->route['action']}';
var fu_route_controller = '{$this->request->route['controller']}';
var fu_route_prefix     = '{$this->request->route['prefix']}';
var fu_url_base         = '{$this->url_base}';
var fu_user_username    = {$fu_user_username};

        
__END;
        return $js;
    }
    
    public function storeApplicationVariable($key,$val) {
    	$this->applicationVariables[$key] = $val;
    }
    
    public function getApplicationVariables() {
    	return $this->applicationVariables;
    }
    
    public function setTheme($label) {
    	$this->theme = $label;
    	
    	// (re-)determine theme url for assets
		$this->storeApplicationVariable('%theme',"{$this->config->url_base}assets/themes/{$this->theme}");	
    }
    
    public function getTemplateData() {
    	return self::parse_yaml(FF_THEME_DIR . "/{$this->theme}/template.yml");
    } 
    
    public function referrer() {
    	return $_SESSION['referringPage'];
    }
    
    public function triggerError($label,$extra = array()) {
    	// Gather basic environment information
    	$info = array();
    	$info['label']     = $label;
    	$info['request']   = $this->rawRequest;
    	$info['route']     = $this->request->route;
    	$info['module']    = $this->request->route['module'];
    	$info['theme']     = $this->theme;
    	$info['timestamp'] = mktime();
    	$info['id']        = $info['timestamp'].'.'.rand(10000,99999);
    	$info['extra']     = $extra;
    	
    	$_SESSION['lastError'] = $info;
    	
    	header('Location: ' . $this->config->url_base . 'error/'.$info['id']);
    	exit();
    }
    

    /*
     * ROUTE
     */
    public function route($request,&$routes,$prefix='') {
        // split the route into segments, ignoring any url_base segments
    	$req = str_replace($this->config->url_base,'/',$request);
        $parts = explode('/',ltrim($req,'/'));
        
        
        $the_route = array();
        
        _log()->log("Will process a maximum of ".count($routes)." routes",FF_DEBUG);
        foreach ( $routes as $r => $route ) {
        	            
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
              
              /* DEPRECATED *
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
              */
            
              // Build the resulting route data array
              $the_route = array(
                'prefix'     => $prefix,
                'controller' => ((isset($wildcards['controller']) && 
            		  !empty($wildcards['controller']))
                   ? $wildcards['controller']
                   : (isset($route['map']['controller'])
                      ? $route['map']['controller']
                      : 'default')),
                'action'     => ((isset($wildcards['view']) &&
                                  !empty($wildcards['view']))
                   ? $wildcards['view']
                   : (isset($route['map']['view'])
                     ? $route['map']['view']
                     : 'index')),
                'parameters' => $parameters,
                'module'     => (isset($route['module']) ? $route['module'] : $this->defaultModule),
                'theme'      => (isset($route['theme']) ? $route['theme']   : false)
              );
              
              //echo ("Routing {$req} to {$the_route['controller']}::{$the_route['action']}(".implode(',',$parameters).")");
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
    
	public static function StandardizeName($name,$bNameOnly = true) {
		$loc = strrpos($name,"\\");
		if ($loc && $bNameOnly) {
			$name = substr($name,$loc+1);
		}
		return str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ",$name))));
	}
	
	public static function StandardizeAttributeName($name) {
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
	
	public static function StandardizeTableName($tableName,$bIsLookupTable = false,$bNameOnly = true) {
  		if ($bNameOnly) {
	  		$loc = strrpos($tableName,"\\");
			if ($loc) {
				$tableName = substr($tableName,$loc+1);
			}
  		}
		if ($bIsLookupTable) {
  			// A lookup table needs to maintain its '_' characters (which are
			// otherwise illegal. Standardize the 2 components, but leave the 
			// '_' separator untouched.
			list($t,$v) = explode('_',$tableName);
			return (strtolower(self::StandardizeAttributeName($t).'_'.
							   self::StandardizeAttributeName($v)));
  		} else {
  			return (strtolower(self::standardizeAttributeName($tableName)));	  		
  		}
	}
}

// Temporary location for this utility function
function is_assoc_callback($a, $b) {
    return $a === $b ? $a + 1 : 0;
}
?>