<?php

class FController extends FPage {
	
	// Variable: form
	// The contents of any POSTed form data
	public $form = array();
	
	public function __construct ($layout = 'default') {
		parent::__construct($layout);
		
		if (isset($_POST) && count($_POST) > 0) {
			$this->processPostedData();	
		}
	}
	
	private function processPostedData() {
		// Store a pointer to the recently submitted data 
		$this->form =& $_POST;
		// Clear old validation errors from the session
		$_SESSION['_validationErrors'] = array();
	}
	
	
	public function redirect($url='',$external=false) {
		// If 'external' is indicated, don't preface with url_base
		if (!$external) {
			header("Location: ".$GLOBALS['furnace']->config['url_base'] . ltrim($url,'/'));
			exit();
		} else {
			header("Location: {$url}");
			exit();
		}
	}
	
	public function internalRedirect($url) {
		_furnace()->process($url);
		exit();
	}
	
	protected function loadModule($uri) {
		$path = _furnace()->rootdir . "/app/modules/" . str_replace(".","/",$uri) . '/module.php';
		if (file_exists($path)) {
			require_once($path);
		} else {
			die(
				"The page requested a module ({$uri}) that does not exist or is not installed correctly."
			);
		}	
	}
}
?>