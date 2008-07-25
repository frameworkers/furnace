<?php
/*
 * frameworkers
 * 
 * core-config.inc.php
 * Created on Jun 22, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 // Include the project root directory in the path
 set_include_path(get_include_path() . PATH_SEPARATOR . Config::PROJECT_ROOT_DIR );
 
 // Include the custom PEAR library location, if set
 if ($PROJECT_PEAR_PATH != '') {
 	set_include_path(get_include_path() . PATH_SEPARATOR . $PROJECT_PEAR_PATH);	
 }
 
 // Include PEAR MDB2 and produce error message for now as frameworkers
 // is currently not stable enough to be multi-engine complient.
 if ("pear_mdb2" == Config::PROJECT_DB_ENGINE) {
 	require_once("MDB2.php");
 } else {
 	die("<b>Unsupported Database Engine:</b> " . Config::PROJECT_DB_ENGINE
 		."<br/>Please use PEAR MDB2 + MySQL<br/>");	
 }
 
 // Include the frameworkers-core files
 require_once("frameworkers-core/foundation/framework/core/FBaseObject.class.php");
 require_once("frameworkers-core/foundation/framework/core/FObjectCollection.class.php");
 require_once("frameworkers-core/foundation/framework/core/FAccount.class.php");
 require_once("frameworkers-core/foundation/framework/core/FAccountManager.class.php");
 require_once("frameworkers-core/foundation/framework/core/FSessionManager.class.php");
 require_once("frameworkers-core/foundation/framework/database/".Config::PROJECT_DB_ENGINE."/FDatabase.class.php");
 require_once("frameworkers-core/foundation/framework/exceptions/FException.class.php");
 require_once("frameworkers-core/facade/tadpole/Tadpole.class.php");
 require_once("frameworkers-core/facade/FPage.class.php");
 require_once("frameworkers-core/facade/FPageModule.class.php");
 require_once("frameworkers-core/foundation/framework/generation/parsing/YAML/FYamlParser.class.php");
 require_once("frameworkers-core/facade/Entrance.class.php");

 // Include the model objects
 @include_once("model/objects/compiled.php");

?>