<?php

class FController extends FPage {
	
	protected $form;
	
	public function __construct () {
		parent::__construct();
		
		if (isset($_POST) && count($_POST) > 0) {
			$this->processPostedData();	
		}
	}
	
	private function processPostedData() {
		$this->form =& $_POST;
	}
	
	public function doAction($action) {
		if (is_callable(array($this,$action))) {
			call_user_func(array($this,$action));
		}
	}
	
}
?>