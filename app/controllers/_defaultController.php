<?php
/*
 * frameworkers_furnace
 * 
 * _DefaultController.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org.
 * http://www.frameworkers.org
 */
 class _DefaultController extends Controller {
 	
 	public function index() {
 		$this->addStylesheet('furnace');
 		$this->set('buildinfo',file_get_contents($GLOBALS['fconfig_root_directory'] . "/lib/furnace/VERSION"));
 		$this->set('rootdir',$GLOBALS['fconfig_root_directory']);
 	}
 	
 	public function login() {
 		$this->loadModule('org.frameworkers.core.LoginBox');
 		$lb = new LoginBox();
 		$this->set('loginbox',$lb->render());
 	}
 	
 }
?>
