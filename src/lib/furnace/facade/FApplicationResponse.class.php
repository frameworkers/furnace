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
        
        // Provide view access to Furnace, Application, and Model config vars
        $this->controller->ref('_furnace',$app);
        $this->controller->ref('_app',    $app->config);
        $this->controller->ref('_model',  $GLOBALS['fApplicationModel']);
        
        // Determine local and theme urls for assets
        if (false === $extension) {
            $this->controller->set('_local_',"{$this->url_base}pages/"
 		    .$app->req->route['controller'].'/'
 		    .$app->req->route['action']);
            $this->controller->set('_theme_',"{$this->url_base}assets/themes/{$this->currentTheme}");
        } else {
            $this->controller->set('_local_',"{$this->url_base}extensions/{$extension}/pages/"
 		    .$app->req->route['controller'].'/'
 		    .$app->req->route['action']);
            $this->controller->set('_theme_',"{$this->url_base}extensions/{$extension}/themes/{$app->theme}");
            $this->controller->setTheme($app->req->route['theme']);
            $this->controller->extensionSetLayout($extension,'default');
        }
        // Determine the prefix for relative links (especially important for extensions)
        $this->controller->set('_prefix_',$app->req->route['prefix']);
    }
    
    public function setPage($group,$page) {
        try { 
            if (false !== $this->extension) {
                $this->controller->setTemplate("{$this->projectRootDir}/app/plugins/extensions/{$this->extension}/pages/{$group}/{$page}/{$page}.html");    
            } else {
                $this->controller->setTemplate("{$this->projectRootDir}/app/pages/{$group}/{$page}/{$page}.html");
            }
            return true;
        } catch (Exception $e) {
            return false;    // Page did not exist
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