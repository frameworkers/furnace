<?php
namespace org\frameworkers\furnace\control;

class FApplicationResponse {
    
    
    private $currentTheme   = 'default';
    private $projectRootDir = '';
    private $url_base       = '';
    
    private $controller     = '';
    private $extension      = '';
    private $request        = '';
    
    public $pageOverrideDetected = false;
    
    public function __construct($app,$controller,$extension=false) {

        $this->controller     = $controller;
        $this->extension      = $extension;
 
        $this->currentTheme   = $app->theme;
        $this->projectRootDir = FURNACE_APP_PATH;
        $this->url_base       = $app->config->url_base;
        
        // Provide view access to Furnace, Application, and Model config vars and $_SERVER superglobal
        _furnace()->storeApplicationVariable('_furnace',$app);
        _furnace()->storeApplicationVariable('_app',    $app->config->data);
        _furnace()->storeApplicationVariable('_model',  $GLOBALS['fApplicationModel']);
        _furnace()->storeApplicationVariable('_const',  get_defined_constants());
        _furnace()->storeApplicationVariable('_server', $_SERVER);
        _furnace()->storeApplicationVariable('_session',$_SESSION);
        _furnace()->storeApplicationVariable('_user',   _user());
        _furnace()->storeApplicationVariable('_now',    date('Y-m-d G:i:s'));
        
        
        // Determine the prefix for relative links (especially important for extensions)
        $trimmedUrlBase = rtrim($this->url_base,'/'); 
        _furnace()->storeApplicationVariable('%prefix', $trimmedUrlBase . $app->request->route['prefix']);
        _furnace()->storeApplicationVariable('_prefix_',$trimmedUrlBase . $app->request->route['prefix']); // legacy, deprecated
        _furnace()->storeApplicationVariable('%a',$trimmedUrlBase);
        
        // Determine theme and local urls for assets
        // Extension not present
        if (false === $extension) {
            _furnace()->storeApplicationVariable('%theme',"{$this->url_base}assets/themes/{$this->currentTheme}");
            _furnace()->storeApplicationVariable('%local',"{$this->url_base}pages/"
 		        .$app->request->route['controller'].'/'
 		        .$app->request->route['action']);
        } 
        // Extension present
        else {
        	$ext    = _furnace()->extensions[$extension];
        	$global = ($ext['global']) ? 'global/' : '';  
        	// Extension with inherited theme
        	if ($ext['theme'] == 'inherit') {
        		_furnace()->storeApplicationVariable('%theme',"{$this->url_base}assets/themes/{$this->currentTheme}");
        		_furnace()->storeApplicationVariable('%local',"{$this->url_base}extensions/{$global}{$extension}/pages/"
        			. $app->request->route['controller'] . '/'
        			. $app->request->route['action']);
        	} 
        	// Extension with its own theme
        	else {
        		_furnace()->storeApplicationVariable('%theme',"{$this->url_base}extensions/{$global}{$extension}/themes/{$ext['theme']}");
        		_furnace()->storeApplicationVariable('%local',"{$this->url_base}extensions/{$global}{$extension}/pages/"
        			. $app->request->route['controller'] . '/'
        			. $app->request->route['action']);
        	}
        	
        	$this->controller->setTheme($app->request->route['theme']);
        	list($provider,$package) = explode('/',$extension);
        	$this->controller->extensionSetLayout($provider,$package,
        		($ext['theme'] == 'inherit')
        			? $this->currentTheme 
        			: $ext['theme'],false);
        }
        
        // Inject the furnace javascript variables into the page
        $this->controller->injectJS($this->prepareFurnaceJS($app));
    }
    
    public function setRequest($request) {
        $this->request = $request;
    }
    
    /*
    public function setPage($group,$page) {
    	
        
        // Determine if a theme-specific view override for the requested page exists
        $page_root = (false == $this->extension && file_exists(
            FURNACE_APP_PATH . "/themes/{$this->currentTheme}/pages/{$group}/{$page}/{$page}.html"))
            ? "{$this->projectRootDir}/themes/{$this->currentTheme}"
            : "{$this->projectRootDir}";
        
        // Determine the correct path to the page
		$pathBase = ($this->extension)
			? _furnace()->extDirToUse . "/{$this->extension}" 
			: $page_root;
	
		// The page will exist in one of two places. If it is a simple page (i.e. 
		// no locally-defined assets), it will simply exist in the same directory
		// as the controller. Since most pages are simple pages, check this first:	
        if (file_exists("{$pathBase}/pages/{$group}/{$page}.html")) {
        	$path = "{$pathBase}/pages/{$group}/{$page}.html";
        } else {
        	$path = "{$pathBase}/pages/{$group}/{$page}/{$page}.html";
        }

        // Attempt to load the page content
        try {
            $this->controller->setTemplate($path);
            $this->pageOverrideDetected = true;
            return true;
        } catch (\Exception $e) {
            return $path;    // Page at $path did not exist
        }
        
    }
    */
    
    public function setLayout($layout = 'default') {
        try {
            if (false !== $this->extension) {
                $this->controller->extensionSetLayout($this->extension,$layout);
            } else {
                $this->controller->setLayout($layout);
            }
        } catch (Exception $e) {
            return false;    // Layout did not exist
        }
    } 
    
    private function prepareFurnaceJS(&$app) {
        $fu_route_extension = (false == $this->extension) ? 'false' : $this->extension; 
        $fu_user_username   = (_user()) ? "\""._user()->getUsername()."\"" : 'false';
        $js = <<<__END
var fu_current_theme    = '{$this->currentTheme}';
var fu_route_action     = '{$app->request->route['action']}';
var fu_route_controller = '{$app->request->route['controller']}';
var fu_route_extension  = '{$fu_route_extension}';
var fu_route_prefix     = '{$app->request->route['prefix']}';
var fu_url_base         = '{$this->url_base}';
var fu_user_username    = {$fu_user_username};

        
__END;
        return $js;
    }
    
    
    public function handlerExists($fn) {
        return is_callable(array($this->controller,$fn));
    }
    
    public function run($fn,$params) {
        call_user_func_array(array($this->controller,$fn),$params);
    }
    
    public function send() {
    	// Time benchmark
    	$this->request->stats['render_start'] = microtime(true);
    	
        
    	$contents = $this->controller->render(false);
    	echo $contents;
    	
        
        $this->request->stats['render_end'] = 
    		$this->request->stats['req_end'] = microtime(true);
    		
    	if (_furnace()->config->debug_level > 1) {
    		echo "<div id=\"fu_page_stats\">{$this->request->compileStats()}</div>";
    	}
    	
    	_log()->log('Stats: ' . $this->request->compileStats('text'));
    }
}
?>
