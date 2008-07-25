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
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. FProject::ROOT_DIRECTORY);
 	
 /* FOUNDATION SETUP *****************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. FProject::ROOT_DIRECTORY.'/furnace/foundation');
 require_once('foundation.bootstrap.php');
 
 /* FACADE SETUP *********************************************************/	
 set_include_path(get_include_path() . PATH_SEPARATOR 
 	. FProject::ROOT_DIRECTORY.'/furnace/facade');
 require_once('facade.bootstrap.php'); 
 
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
?>