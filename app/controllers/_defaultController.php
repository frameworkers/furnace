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
 		// Remove the following line when you are ready to
 		// develop your application. The corresponding view
 		// for this controller function is the file:
 		// /app/views/_default/index.html.
 		$this->internalRedirect("/_furnace/start");
 	}
 	
 	public function login() {
 		$this->loadModule('org.frameworkers.core.LoginBox');
 		$lb = new LoginBox($this);
 		$this->set('loginbox',$lb->getContents());
 	}
 	
 }
?>
