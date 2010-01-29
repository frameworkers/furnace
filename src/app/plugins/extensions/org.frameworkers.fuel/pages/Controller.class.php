<?php
abstract class Controller extends FController {
    
    public function setActiveMenuItem($which,$label) {
        
        if ('main' == $which) {
            $this->set('mainMenuActiveItem',$label);
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