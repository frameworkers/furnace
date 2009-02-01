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
class _FurnaceController extends Controller {
	
	public function debug($message) {
		$this->set('message',$message);
	}
	
	public function unavailable() {
		
	}
	
	
}
?>