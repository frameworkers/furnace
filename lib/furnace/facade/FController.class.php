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
	
	public function doAction($action,$args=null) {
		if (is_callable(array($this,$action))) {
			call_user_func_array(array($this,$action),$args);
		}
	}
	
	protected function redirect($url) {
		header("Location: {$url}");
		exit();
	}
	
	protected function loadModule($uri) {
		$path = $GLOBALS['fconfig_root_directory'] . "/app/modules/" . str_replace(".","/",$uri) . '/module.php';
		if (file_exists($path)) {
			require_once($path);
		} else {
			$this->dieWithExplanation(
				"The page requested a module ({$uri}) that does not exist or is not installed correctly."
			);
		}
	} 
	
	private function dieWithExplanation($explanation) {
		echo "<b>Furnace Error:</b> {$explanation}";
		die();
	}
	
}
?>