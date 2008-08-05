<?php
/*
 * frameworkers_furnace
 * 
 * AppEntrance.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /* ENVIRONMENT SETUP ****************************************************/
 require_once("../../app/config/project.config.php");
 require_once("../../app/config/database.config.php");
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. FProject::ROOT_DIRECTORY . "/app");
 	
 /* FOUNDATION SETUP *****************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. FProject::ROOT_DIRECTORY.'/furnace/foundation');
 require_once('foundation.bootstrap.php');
 
 /* FACADE SETUP *********************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. FProject::ROOT_DIRECTORY.'/furnace/facade');
 require_once('facade.bootstrap.php'); 
  
 /* CUSTOM CONTROLLER BASE CLASS SETUP ***********************************/
 require_once('../../app/controllers/_base/Controller.class.php');
 
 /* INCLUDE MODEL DATA ***************************************************/
 @include_once("../../model/objects/compiled.php");
 
 /* INIT ENTRANCE ********************************************************/
 $entrance = new Entrance(
 	FProject::ROOT_DIRECTORY."/app/controllers","_default");
 	
 /* PROCESS REQUEST AND STORE CONTENTS ***********************************/
 if ($entrance->validRequest($_SERVER['REQUEST_URI'])) {
 	$contents = $entrance->dispatchRequest();
 } else {
 	die("Invalid request made.");
 }
 
 /* CREATE LAYOUT OBJECT, RENDER AND SEND CONTENT ************************/
 $layout     = $entrance->getController()->getLayout();
 $layoutPath = FProject::ROOT_DIRECTORY."/app/layouts/{$layout}.html";
 if (file_exists($layoutPath)) {
 	$pageLayout = new FPageLayout($layoutPath);
 	$pageLayout->register('pageTitle',$entrance->getController()->getTitle());
 	$pageLayout->register('bodyContent',$contents);
 	$pageLayout->render();
 } else {
 	die("unknown layout '{$layout}' specified."); 	
 }
 exit();
 
 /* UTILITY FUNCTIONS ****************************************************/
 
 // FUNCTION: _db()
 //  Provides shorthand notation for accessing the database, and also
 //  insulates against API changes that will probably come as a result
 //  of a future need to support additional database connection mechanisms.
 //
 function _db() {
 	return FDatabase::singleton(FDatabaseConfig::$DSN);
 }
?>