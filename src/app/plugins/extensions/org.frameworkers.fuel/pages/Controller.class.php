<?php
abstract class Controller extends FController {
    
    public function setActiveMenuItem($which,$label) {
        
        if ('main' == $which) {
            $this->set('mainMenuActiveItem',$label);
        }
        
        $this->prefix = _furnace()->request->route['prefix'];
    }
    
    public function requireLogin() {
        if (!$this->checkLogin()) {
            $prefix = _furnace()->request->route['prefix'];
            $this->redirect("{$prefix}/login");
        }
    }
    
    public function checkLogin() {
        
        if ('' == $GLOBALS['furnace']->config['root_username'] || 
            '' == $GLOBALS['furnace']->config['root_password']) {
			// ALWAYS FAIL IF THE ROOT USERNAME & PASSWORD HAVE NOT BEEN SET 
			// IN THE PROJECT CONFIGURATION FILE!
			return false;
		}
		
		if ($this->form && isset($this->form['rootuser']) && isset($this->form['rootpass'])) {
		    // Process a login attempt
    		$un =& $this->form['rootuser'];
    		$pw =& $this->form['rootpass'];
    		
    		if (     $un == $GLOBALS['furnace']->config['root_username'] && 
    		    md5($pw) == md5($GLOBALS['furnace']->config['root_password'])) {
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
		require_once($GLOBALS['furnace']->rootdir
			. "/lib/furnace/foundation/database/"
		    . $GLOBALS['furnace']->config['db_engine']
		    . "/FDatabase.class.php");
		
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
		return new FModel(
			_furnace()->parse_yaml($GLOBALS['furnace']->rootdir . "/app/model/model.yml")
		);
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
		file_put_contents($GLOBALS['furnace']->rootdir . "/app/model/model.yml",$contents);
	}
}
?>