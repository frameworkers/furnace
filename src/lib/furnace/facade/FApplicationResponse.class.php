<?php
class FApplicationResponse {
    
    
    private $currentTheme   = 'default';
    private $projectRootDir = '';
    private $url_base       = '';
    
    private $controller     = '';
    private $extension      = '';
    
    public function __construct($app,$controller,$extension=false) {

        $this->controller     = $controller;
        $this->extension      = $extension;
 
        $this->currentTheme   = $app->theme;
        $this->projectRootDir = $app->rootdir;
        $this->url_base       = $app->config['url_base'];
        
        // Provide view access to Furnace, Application, and Model config vars and $_SERVER superglobal
        $this->controller->ref('_furnace',$app);
        $this->controller->ref('_app',    $app->config);
        $this->controller->ref('_model',  $GLOBALS['fApplicationModel']);
        $this->controller->ref('_server', $_SERVER);
        $this->controller->ref('_session',$_SESSION);
        
        // Determine theme and local urls for assets
        if (false === $extension) {
            // No extension
            $this->controller->set('_theme_',"{$this->url_base}assets/themes/{$this->currentTheme}");
            $this->controller->set('_local_',"{$this->url_base}pages/"
 		        .$app->req->route['controller'].'/'
 		        .$app->req->route['action']);
        } else if (strtolower($app->req->route['theme'] == 'inherit')) {
            // Extension with inherited theme
            $this->controller->set('_theme_',"{$this->url_base}assets/themes/{$this->currentTheme}");
            $this->controller->set('_local_',"{$this->url_base}extensions/{$extension}/pages/"
 		        .$app->req->route['controller'].'/'
 		        .$app->req->route['action']);
 		    $this->controller->setTheme($app->req->route['theme']);
            $this->controller->extensionSetLayout($extension,'default');
        } else {
            // Extension with its own theme
            $this->controller->set('_theme_',"{$this->url_base}extensions/{$extension}/themes/{$app->theme}");
            $this->controller->set('_local_',"{$this->url_base}extensions/{$extension}/pages/"
 		        .$app->req->route['controller'].'/'
 		        .$app->req->route['action']);
 		    $this->controller->setTheme($app->req->route['theme']);
            $this->controller->extensionSetLayout($extension,'default');
        }
         
        // Determine the prefix for relative links (especially important for extensions)
        $this->controller->set('_prefix_',$app->req->route['prefix']);
    }
    
    public function setPage($group,$page) {
        // Determine the correct path to the page
        $path = (false !== $this->extension)
            ? "{$this->projectRootDir}/app/plugins/extensions/{$this->extension}/pages/{$group}/{$page}/{$page}.html"
            : "{$this->projectRootDir}/app/pages/{$group}/{$page}/{$page}.html";
            
        // Attempt to load the page content
        try {
            $this->controller->setTemplate($path);
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
    
    public function handlerExists($fn) {
        return is_callable(array($this->controller,$fn));
    }
    
    public function run($fn,$params) {
        call_user_func_array(array($this->controller,$fn),$params);
    }
    
    public function send() {
        $this->controller->render($this->currentTheme);
    }
}
?>