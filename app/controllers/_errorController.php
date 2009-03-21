<?php
/*
 * frameworkers_furnace
 * 
 * _DefaultController.php
 * Created on Oct 14, 2008
 *
 * Copyright 2008 Frameworkers.org.
 * http://www.frameworkers.org
 */
class _ErrorController extends Controller {
	
	public function http403() {
		$this->addStylesheet('error');
	}
	
	public function http404($request='') {
		$this->addStylesheet('error');
		$this->set('request',$_SERVER['REQUEST_URI']);
		
	}
	
	public function unknown($request='') {
		$this->addStylesheet('error');
		$this->set('request',$request);	
	}
	
}
?>