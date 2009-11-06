<?php
/**
 * This is the main Furnace class
 * 
 * Provides the high-level functionality needed to direct the
 * handling of a Furnace application request. It handles the set-up and
 * tear-down of the Furnace environment, including benchmarking if necessary,
 * and manages the transfer of control between lower elements of the Furnace
 * library in the process of satisfying the user request.
 * 
 * @package Furnace
 * @author andrew <andrew@frameworkers.org>
 *
 */
class Furnace {

    /**
     * The path to the project root directory. This value is
     * automatically computed.
     * 
     * @var string
     */
    public $rootdir;

    /**
     * The path to the Fuel application. This value is automatically
     * computed from the value of {@link rootdir}.
     * 
     * @var string
     */
    public $fueldir;

    /**
     * Whether or not the current request should be treated as a FUEL request.
     * 
     * @var string
     */
    public $useFuel    = false;

    /**
     * The path to the Foundry application. This value is automatically
     * computed from the value of {@link rootdir}.
     * 
     * @var string
     */
    public $foundrydir;

    /**
     * Whether or not the current request should be treated as a Foundry request.
     * 
     * @var string
     */
    public $useFoundry = false;

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

    // Variable: route
    // The route information (controller + view) for this request
    public $route   = array();

    // Benchmarking variables
    public $bm_renderstart = 0;
    public $bm_renderend   = 0;
    public $bm_reqstart    = 0;
    public $bm_reqend      = 0;
    public $bm_envsetupstart = 0;
    public $bm_envsetupend   = 0;
    public $bm_processstart  = 0;
    public $bm_processend    = 0;

    public function __construct($type = 'app') {
        	
        // Compute the project root directory
        $startTime = microtime(true);
        $this->rootdir = dirname(dirname(dirname(__FILE__)));
        	
        switch ($type) {
            case 'fuel':
                // FUEL Request
                // Store the path to the controller and view directories
                $this->useFuel            = true;
                $this->fueldir            = $this->rootdir . '/lib/fuel';
                $this->controllerBasePath = $this->fueldir . '/app/controllers';
                $this->viewBasePath       = $this->fueldir . '/app/views';
                $this->layoutBasePath     = $this->fueldir . '/app/layouts';
                break;
            case 'foundry':
                // Foundry Request
                // Store the path to the controller and view directories
                $this->useFoundry         = true;
                $this->foundrydir         = $this->rootdir . '/lib/foundry';
                $this->controllerBasePath = $this->foundrydir . '/app/controllers';
                $this->viewBasePath       = $this->foundrydir . '/app/views';
                $this->layoutBasePath     = $this->foundrydir . '/app/layouts';
                break;
            default:
                // Default Application Request
                // Store the path to the controller and view directories
                $this->controllerBasePath = $this->rootdir . '/app/controllers';
                $this->viewBasePath       = $this->rootdir . '/app/views';
                $this->layoutBasePath     = $this->rootdir . '/app/layouts';
                break;
        }

        //Include the yml parser
        include($this->rootdir . '/lib/yaml/spyc-0.4.1.php');

        // Load the YML route definitions
        $this->routes = self::yaml($this->rootdir . '/app/config/routes.yml');

        // Load the YML application configuration file
        $this->config = self::yaml($this->rootdir . '/app/config/app.yml');

        // Initialize the session
        session_start();
        	
        // If benchmarking, set the start time
        if ($this->config['debug_level'] > 0) {
            $this->bm_reqstart = $startTime;
        }
    }

    public function process($request) {
        	
        if ($this->config['debug_level'] > 0) {
            $this->bm_envsetupstart = microtime(true);
        }
        // Controller
        $controller = null;
        // View Template
        $templateFilePath = '';
        	
        // Determine the route to take
        $this->route = self::route($request,$this->routes);
        	
        // Does the requested controller file exist?
        $controllerClassName  = "{$this->route['controller']}Controller";
        $controllerFileExists = file_exists(
        $controllerFilePath = "{$this->controllerBasePath}/{$controllerClassName}.php");


        if ($controllerFileExists) {

            // Include Furnace Foundation classes
            set_include_path(get_include_path() . PATH_SEPARATOR .
            $this->rootdir . '/lib/furnace/foundation');
            include_once('foundation.bootstrap.php');

            // Include Furnace Facade classes
            set_include_path(get_include_path() . PATH_SEPARATOR .
            $this->rootdir . '/lib/furnace/facade');
            include_once('facade.bootstrap.php');

            // Include the custom controller base file
            if ($this->useFuel) {
                include_once($this->fueldir    . '/app/controllers/_base/Controller.class.php');
            } else if ($this->useFoundry) {
                include_once($this->foundrydir . '/app/controllers/_base/Controller.class.php');
            } else {
                include_once($this->rootdir    . '/app/controllers/_base/Controller.class.php');
            }

            // Include application model data
            include_once($this->rootdir . "/app/model/model.php");

            // Instantiate the global model data structure
            $GLOBALS['fApplicationModel'] = new ApplicationModel();

            // Include the controller file
            include_once($controllerFilePath);
            if ($this->config['debug_level'] > 0) {
                $this->bm_envsetupend = $this->bm_processstart = microtime(true);
            }

            // Does the requested controller class exist?
            // TODO: Check for the existence of the class within the file

            // Create an instance of the requested controller
            try {
                $controller = new $controllerClassName();
            } catch (FDatabaseException $fde) {
                $_SESSION['_exception'] = $fde;
                $this->process('/_error/exception');
                exit();
            } catch (FException $fe) {
                $_SESSION['_exception'] = $fe;
                $this->process('/_error/exception');
                exit();
            } catch (Exception $e) {
                $_SESSION['_exception'] = $e;
                $this->process('/_error/exception');
                exit();
            }

            // Does the requested controller function exist?
            $handlerExists = is_callable(array($controller,$this->route['view']));

            if ($handlerExists) {
                	
                // Set the default template file content
                $templateFilePath = $this->viewBasePath
                . "/{$this->route['controller']}/{$this->route['view']}.html";
                	
                // Call the handler
                	
                try {
                    call_user_func_array(array($controller,$this->route['view']),$this->route['parameters']);
                } catch (FDatabaseException $fde) {
                    $_SESSION['_exception'] = $fde;
                    $this->process('/_error/exception');
                    exit();
                } catch (FException $fe) {
                    $_SESSION['_exception'] = $fe;
                    $this->process('/_error/exception');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['_exception'] = $e;
                    $this->process('/_error/exception');
                    exit();
                }
                	
                if ($this->config['debug_level'] > 0) {
                    $this->bm_processend   = microtime(true);
                }
                	
                // Set the view template
                $templateFileExists = file_exists($templateFilePath);
                if ($templateFileExists) {
                    $controller->setTemplate($templateFilePath);
                } else {
                    if ($this->config['debug_level'] > 0 ) {
                        die("No template file {$templateFilePath}");
                    } else {
                        $this->process("/_error/http404");
                    }
                }
                	
                // Set the referringPage attribute in the session. This
                // allows non-view controller actions to redirect to
                // the location from which they were called.
                $_SESSION['referringPage'] = $request;
                	
                // Provide view access to Furnace, Application, and Model config vars
                $controller->ref('_furnace',$this);
                $controller->ref('_app',    $this->config);
                $controller->ref('_model',  $GLOBALS['fApplicationModel']);
                	
                // Send the rendered content out over the wire
                if ($this->config['debug_level'] > 0) {
                    $this->bm_renderstart = microtime(true);
                }
                $controller->render();
                if ($this->config['debug_level'] > 0) {
                    $this->bm_renderend  = $this->bm_reqend = microtime(true);
                }
                	
                if ($this->config['debug_level'] > 1) {
                    echo '<div id="ff-debug"><table>';
                    echo "<tr><th>TOTAL REQUEST TIME: </th><td> " . ($this->bm_reqend - $this->bm_reqstart) . " seconds</td></tr>\r\n";
                    echo "<tr><th>REQUEST BREAKDOWN:  </th><td><table><tr><th>SETUP TIME: </th><td>" . ($this->bm_envsetupend - $this->bm_envsetupstart) . "</td></tr>\r\n";
                    echo "<tr><th>PROCESS TIME: </th><td>" . ($this->bm_processend - $this->bm_processstart) . "</td></tr>\r\n";
                    echo "<tr><th>QUERY DELAY:  </th><td>" . count($this->queries) . " queries<br/>\r\n<span style='font-size:90%;'>";
                    $qd = 0;
                    foreach ($this->queries as $q) {
                        echo "&nbsp;&nbsp;{$q['delay']}s\t{$q['sql']}<br/>\r\n";
                        $qd += $q['delay'];
                    }
                    echo "</span><br/>\r\n&nbsp;&nbsp;" . count($this->queries) . " queries took {$qd} seconds.</td></tr>\r\n";
                    echo "<tr><th>RENDER  TIME:  </th><td>" . ($this->bm_renderend - $this->bm_renderstart) . "</td></tr>\r\n";
                    echo "</table></td></tr>";
                    echo "</table>";
                    echo '</div>';
                }
                // Clean up
                // TODO: free memory, etc
            } else {
                if ($this->config['debug_level'] > 0) {
                    die("No handler function '{$this->route['view']}' defined in {$controllerFilePath}");
                } else {
                    $this->process("/_error/http404");
                }
            }
        } else {
            if ($this->config['debug_level'] > 0) {
                die("No controller file {$this->route['controller']}");
            } else {
                $this->process("/_error/http404");
            }
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

// Temporary location for this utility function
function is_assoc_callback($a, $b) {
    return $a === $b ? $a + 1 : 0;
}
?>