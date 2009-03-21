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
		$this->form =& $_POST;
	}
	
	
	public function redirect($url,$external=false) {
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