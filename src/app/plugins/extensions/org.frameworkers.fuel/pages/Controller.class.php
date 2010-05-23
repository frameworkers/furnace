<?php
class Controller extends FController {
    
    protected $prefix = '';

    public function __construct() {
        parent::__construct();
        
        $this->prefix = _furnace()->request->route['prefix'];
    }
    
    public function setActiveMenuItem($which,$label) {
        
        if ('main' == $which) {
            $this->set('mainMenuActiveItem',$label);
        }
    }
    
    public function requireLogin() {
        if (!$this->checkLogin()) {
            $prefix = _furnace()->request->route['prefix'];
            $this->redirect("{$prefix}/login");
        }
    }
    
    public function checkLogin() {
        
        if ('' == $GLOBALS['furnace']->config->data['root_username'] || 
            '' == $GLOBALS['furnace']->config->data['root_password']) {
			// ALWAYS FAIL IF THE ROOT USERNAME & PASSWORD HAVE NOT BEEN SET 
			// IN THE PROJECT CONFIGURATION FILE!
			return false;
		}
		
		if ($this->form && isset($this->form['rootuser']) && isset($this->form['rootpass'])) {
		    // Process a login attempt
    		$un =& $this->form['rootuser'];
    		$pw =& $this->form['rootpass'];
    		
    		if (     $un == $GLOBALS['furnace']->config->data['root_username'] && 
    		    md5($pw) == md5($GLOBALS['furnace']->config->data['root_password'])) {
    			$_SESSION['fuel']['loggedin']  = true;
    			$_SESSION['fuel']['timestamp'] = mktime();
    			return true;
    		} else {
    			return false;
    		}
		} else {
		    // Simply check whether appropriate session vars exist
		   if (isset($_SESSION['fuel']['loggedin']) && $_SESSION['fuel']['loggedin'] === true) {
		       $_SESSION['fuel']['timestamp'] = mktime();
		       return true;
		   } else {
		       return false;
		   }
		}
    }
    
    protected function init() {
		
		$lib_dir = dirname(dirname(__FILE__)) . '/libraries';
		require_once("{$lib_dir}/generation/core/FObj.class.php");
		require_once("{$lib_dir}/generation/core/FObjAttr.class.php");
		require_once("{$lib_dir}/generation/core/FObjAttrValidation.class.php");
		require_once("{$lib_dir}/generation/core/FObjSocket.class.php");
		require_once("{$lib_dir}/generation/core/FSqlColumn.class.php");
		require_once("{$lib_dir}/generation/core/FSqlTable.class.php");
		require_once("{$lib_dir}/generation/building/FModel.class.php");
		require_once("{$lib_dir}/dbmgmt/FDatabaseSchema.class.php");
		require_once("{$lib_dir}/dbmgmt/FDatabaseModel.class.php");
	}
	
	protected function getModel() {
	    
	    // Retrieve the primary model data
		$data = 
			_furnace()->parse_yaml($GLOBALS['furnace']->rootdir . "/app/model/model.yml");

		
		// Retrieve any extension model data
		$extBasePath = $GLOBALS['furnace']->rootdir . '/app/plugins/extensions';
		$extDir      = dir($extBasePath);
		while (false !== ($ext = $extDir->read())) {
		    // Skip unrelated files and directories
		    if ('.' == $ext[0] || !is_dir("{$extBasePath}/{$ext}")) { continue; }
		    
		    if (is_dir("{$extBasePath}/{$ext}/model") && 
		        file_exists("{$extBasePath}/{$ext}/model/model.yml")) {
		            $data = array_merge($data,_furnace()->parse_yaml("{$extBasePath}/{$ext}/model/model.yml"));   
		        }
		}
		
		return new FModel($data);
	}
	
	protected function getApplicationModel() {
	    //return new FDatabaseModel($GLOBALS['furnace']->config['datasources']['debug']['default']);
	    throw new FException("Function deprecated");
	}
	
    protected function getSchema() {
		$d = new FDatabaseSchema();
		$d->discover('default');
		return $d;
	}
	
	protected function writeModelFile($contents) {

	    // Examine contents to determine which physical file they should be written to
	    if (!is_array($contents)) {
		    file_put_contents($GLOBALS['furnace']->rootdir . "/app/model/model.yml",$contents);
	    } else {
	        foreach ($contents as $location => $data) {
    	        if ('primary' == $location) {
        	        file_put_contents($GLOBALS['furnace']->rootdir . "/app/model/model.yml",$contents['primary']);
        	    } else {
        	        file_put_contents($GLOBALS['furnace']->rootdir . "/app/plugins/extensions/{$location}/model/model.yml",$data);
        	    }
	        }
	    }
	}
}
?>