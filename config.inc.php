<?php
/*
 * frameworkers
 * 
 * config.inc.php
 * Created on June 05, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 // Project Settings
 class Config {
 	const PROJECT_ROOT_DIR     = "/path/to/project/root";
 	const PROJECT_DB_ENGINE    = "pear_mdb2";
	const PROJECT_DB_DSN	   = "driver://user:pass@server/dbname";
 	const PROJECT_ENV_DEBUG    = false;
 	const PROJECT_DB_ENGINE    = "pear_mdb2";
 	const PROJECT_DEFAULT_LANG = "en-us";
 }
 
 // Custom PEAR Library Location
 // If your PEAR repository is not on your path by default (as is sometimes
 // the case with applications hosted by a shared-hosting service), you can
 // specify the path to your local PEAR repository here
 $PROJECT_PEAR_PATH    = '';
 

 
 /**
  * -- NO CONFIGURABLE OPTIONS BELOW THIS LINE --
  * The code below this line is required by the framework to properly
  * initialize its environment. There should be no need to manually edit
  * anything below this line. 
  */
  require_once("frameworkers-core/core-config.inc.php");
?>