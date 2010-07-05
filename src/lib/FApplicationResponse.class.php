<?php
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
        $this->controller->ref('_furnace',$app);
        $this->controller->ref('_app',    $app->config->data);
        $this->controller->ref('_model',  $GLOBALS['fApplicationModel']);
        $this->controller->ref('_const',  get_defined_constants());
        $this->controller->ref('_server', $_SERVER);
        $this->controller->ref('_session',$_SESSION);
        $this->controller->ref('_user',   _user());
        $this->controller->set('_now',    date('Y-m-d G:i:s'));
        
        
        // Determine the prefix for relative links (especially important for extensions)
        $this->controller->set('_prefix_',$app->request->route['prefix']);
        
        // Determine theme and local urls for assets
        // Extension not present
        if (false === $extension) {
            $this->controller->set('_theme_',"{$this->url_base}assets/themes/{$this->currentTheme}");
            $this->controller->set('_local_',"{$this->url_base}pages/"
 		        .$app->request->route['controller'].'/'
 		        .$app->request->route['action']);
        } 
        // Extension present
        else {
        	$ext    = _furnace()->extensions[$extension];
        	$global = ($ext['global']) ? 'global/' : '';  
        	// Extension with inherited theme
        	if ($ext['theme'] == 'inherit') {
        		$this->controller->set('_theme_',"{$this->url_base}assets/themes/{$this->currentTheme}");
        		$this->controller->set('_local_',"{$this->url_base}extensions/{$global}{$extension}/pages/"
        			. $app->request->route['controller'] . '/'
        			. $app->request->route['action']);
        	} 
        	// Extension with its own theme
        	else {
        		$this->controller->set('_theme_',"{$this->url_base}extensions/{$global}{$extension}/themes/{$ext['theme']}");
        		$this->controller->set('_local_',"{$this->url_base}extensions/{$global}{$extension}/pages/"
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
    
    public function setPage($group,$page) {
        
        // Determine if a theme-specific view override for the requested page exists
        $page_root = (false == $this->extension && file_exists(
            FURNACE_APP_PATH . "/themes/{$this->currentTheme}/pages/{$group}/{$page}/{$page}.html"))
            ? "{$this->projectRootDir}/themes/{$this->currentTheme}"
            : "{$this->projectRootDir}/";
        
        // Determine the correct path to the page
        $path = (false !== $this->extension)
            ? _furnace()->extDirToUse . "/{$this->extension}/pages/{$group}/{$page}/{$page}.html"
            : "{$page_root}/pages/{$group}/{$page}/{$page}.html";
            
        // Attempt to load the page content
        try {
            $this->controller->setTemplate($path);
            $this->pageOverrideDetected = true;
            return true;
        } catch (Exception $e) {
            return $path;    // Page at $path did not exist
        }
    }
    
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