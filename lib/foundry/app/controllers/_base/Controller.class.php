<?php
/*
 * frameworkers_furnace
 * 
 * Controller.class.php
 * Created on Jul 27, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

abstract class Controller extends FController {
	
	
	/**
	 * Here you can define functions that should be accessible
	 * to ALL controllers. 
	 */
	protected function init() {
		require_once($GLOBALS['furnace']->rootdir . "/lib/furnace/foundation/database/".$GLOBALS['furnace']->config['db_engine']."/FDatabase.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FObj.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/generation/building/FModel.class.php");
		require_once($GLOBALS['furnace']->rootdir . "/lib/fuel/lib/dbmgmt/FDatabaseSchema.class.php");
	}
	protected function getModel() {
		return new FModel(
			_furnace()->parse_yaml($GLOBALS['furnace']->rootdir . "/app/model/model.yml")
		);
	}
	protected function getSchema() {
		$d = new FDatabaseSchema();
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$d->discover($GLOBALS['furnace']->config['debug_dsn']);
		} else {
			$d->discover($GLOBALS['furnace']->config['production_dsn']);
		}
		return $d;
	}
	protected function writeModelFile($contents) {
		file_put_contents($GLOBALS['furnace']->rootdir . "/app/model/model.yml",$contents);
	}
	
	
	
}
?>