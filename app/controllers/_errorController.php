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
	
	
	
	public function http404($request) {
		$this->set('request',$request);
		
	}
	
}
?>