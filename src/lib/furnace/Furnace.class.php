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
    
    



    // Benchmarking variables
    public $bm_renderstart = 0;
    public $bm_renderend   = 0;
    public $bm_reqstart    = 0;
    public $bm_reqend      = 0;
    public $bm_envsetupstart = 0;
    public $bm_envsetupend   = 0;
    public $bm_processstart  = 0;
    public $bm_processend    = 0;
    
    
    public $paths = array();
    public $db;
    public $model;
    public $user;

    public function __construct($config) {
        	
        // Compute the project root directory
        $this->rootdir = FF_ROOT_DIR;
        $this->paths['rootdir'] = $this->rootdir;
        
        // Load the YML route definitions
        $this->routes = self::yaml($this->rootdir . '/app/config/routes.yml');

        // Load the YML application configuration file
        $this->config = self::yaml($this->rootdir . '/app/config/app.yml');

        // Set the theme
        $this->theme  = (isset($this->config['theme'])) 
            ? $this->config['theme']
            : 'default';
        
        // Initialize the session
        session_start();
        	
        // If benchmarking, set the start time
        if ($this->config['debug_level'] > 0) {
            $this->bm_reqstart = $startTime;
        }
    }

    /**
     * Process an {@link FApplicationRequest} and generate an 
     * {@link FApplicationResponse}
     * 
     * @param  FApplicationRequest  The {@link FApplicationRequest} object to process
     * @return FApplicationResponse The resulting {@link FApplicationResponse} object
     */
    public function process($request) {
    
        if ($this->config['debug_level'] > 0) {
            $request->stats['proc_setup_start'] = microtime(true);
        }
        
        // Store the FApplicationRequest object 
        $this->request = $request;
        
        // Make the raw request available to controllers via _furnace()->request
        $this->rawRequest = $request->raw;
               	
        // Determine the route to take
        $this->request->route = Furnace::route($this->rawRequest,$this->routes);

        try {
            
            // Include Furnace Foundation classes
            set_include_path(get_include_path() . PATH_SEPARATOR .
            $this->rootdir . '/lib/furnace/foundation');
            include_once('foundation.bootstrap.php');
        
            // Include Furnace Facade classes
            set_include_path(get_include_path() . PATH_SEPARATOR .
            $this->rootdir . '/lib/furnace/facade');
            include_once('facade.bootstrap.php');
            
            // Include application model data
            include_once("{$this->rootdir}/app/model/model.php");
        
            // Instantiate the global model data structure
            $GLOBALS['fApplicationModel'] = new ApplicationModel();
            $GLOBALS['_model'] = new ApplicationModel();
            
            if ($this->config['debug_level'] > 0) {
                $request->stats['proc_setup_end'] = 
                    $request->stats['proc_proc_start'] = microtime(true);
            }
            
            // Determine if an extension is being requested and set the file path
            // to the appropriate controller
            if ('' != $this->request->route['extension']) {
                $controllerFilePath = "{$this->rootdir}/app/plugins/extensions/"
                    . "{$this->request->route['extension']}/pages/"
                    . "{$this->request->route['controller']}/"
                    . "{$this->request->route['controller']}Controller.php";
            } else {
                $controllerFilePath = "{$this->rootdir}/app/pages/"
                    . "{$this->request->route['controller']}/"
                    . "{$this->request->route['controller']}Controller.php";        
            }
            
            // Ensure that the requested controller actually exists
            if (!file_exists($controllerFilePath)) {
                ( $this->config['debug_level'] > 0 ) 
                    ? $this->process("/_debug/errors/noController/"
                        .str_replace('/','+',$controllerFilePath))
                    : $this->process("/_default/http404");
                exit();
            }        
            
            // Include the base controller
            if ($this->request->route['extension'] && file_exists(
            		 "{$this->rootdir}/app/plugins/extensions/"
                    ."{$this->request->route['extension']}/pages/Controller.class.php")) {
                include_once("{$this->rootdir}/app/plugins/extensions/"
                    ."{$this->request->route['extension']}/pages/Controller.class.php");
            } else {
                include_once("{$this->rootdir}/app/pages/Controller.class.php");
            }
            
            // Include the controller file
            @include_once($controllerFilePath);
            
            if ($this->config['debug_level'] > 0) {
                $request->stats['proc_end'] = microtime(true);
            }

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
                if ($this->config['debug_level'] > 0) {
                    $request->stats['handler_start'] = microtime(true);
                }   
                try {
                    $this->response->run($this->request->route['action'],
                        $this->request->route['parameters']);
                    // Display error if expected view template does not exist..
                    if ($pageExists !== true 
                            && !$this->response->pageOverrideDetected) {
                        $this->process(
                            (($this->config['debug_level'] > 0) 
                                ? ('/_debug/errors/noTemplate/'.str_replace('/','+',$pageExists))
                                : '/_default/http404')
                        );
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
                	
                if ($this->config['debug_level'] > 0) {
                    $request->stats['handler_end'] = 
                        $request->stats['proc_proc_end']  = microtime(true);
                }
                	
                // Set the referringPage attribute in the session. This
                // allows non-view controller actions to redirect to
                // the location from which they were called.
                $_SESSION['referringPage'] = $this->rawRequest;
                	
                // Send the rendered content out over the wire
                if ($this->config['debug_level'] > 0) {
                    $this->bm_renderstart = microtime(true);
                }
                
                if ($this->config['debug_level'] > 0) {
                    $this->bm_renderend  = $this->bm_reqend = microtime(true);
                }
                
                return $this->response;
                
            } else {
                if ($this->config['debug_level'] > 0) {
                    $this->process("/_debug/errors/noControllerFunction/"
                        . str_replace('/','+',$controllerFilePath)
                        . "/{$this->request->route['action']}");
                } else {
                    $this->process("/_default/http404");
                } 
                exit();
            }
            
            if ($this->config['debug_level'] > 0) {
                $this->bm_processend = microtime(true);
            }
        } catch (Exception $e) {
             dev_messages();

             die("Unexpected error {$e}");  
        } 
    }
    
    /**
     * Send
     * 
     * Begins the process of sending a response back to the requestor
     * 
     * @param  FApplicationResponse The response object to send
     * @return void
     */
    public function send($response) {
        $response->send();
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
    public static function route($request,&$routes,$prefix='/') {
        // split the route into segments
        $parts = explode('/',ltrim($request,'/'));
        
        $the_route = array();
        if (FF_DEBUG) { debug("Will process a maximum of ".count($routes)." routes"); }
        foreach ( $routes as $r => $route ) {
        if ($r[0] == '_') {
          if (FF_DEBUG) { debug("Found extension {$route['path']} with prefix {$route['prefix']} and ".count($route['routes'])." routes");}
          $extPath   = $route['path'];
          $extPrefix = $route['prefix'];
          $result    = self::route($request,$route['routes'],$extPrefix);
          if ( $result ) {
            $result['extension'] = $extPath;
            $result['theme']     = isset($route['theme']) ? $route['theme'] : 'default';
            return $result;
          }
          // Route not found in this extension
          if (FF_DEBUG) { debug("No match among routes for: {$route['path']}"); }
          continue;
        }
        
        if (FF_DEBUG) { debug("Trying route: {$route['url']}"); }
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
                  : '_default')),
            'action'     => ((isset($wildcards['view']) &&
                              !empty($wildcards['view']))
               ? $wildcards['view']
               : (isset($route['map']['view'])
                 ? $route['map']['view']
                 : 'index')),
            'parameters' => $parameters,
            'extension'  => false
          );
          if (FF_INFO) {info("Matched route: {$route['url']}");}
          return $the_route;
        }
        }
        // Invalid route specified
        if (FF_WARN) { if ($prefix == '/') {warn("Router could not understand provided route: {$request}");} }
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

// Temporary location for this utility function
function is_assoc_callback($a, $b) {
    return $a === $b ? $a + 1 : 0;
}
?>