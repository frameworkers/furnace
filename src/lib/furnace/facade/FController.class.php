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
	
	public function form() {
	    return $this->form;
	}
	
	
	public function redirect($url='',$external=false) {
		// If 'external' is indicated, don't preface with url_base
		if (!$external) {
			header("Location: ".$GLOBALS['furnace']->config->url_base . ltrim($url,'/'));
			exit();
		} else {
			header("Location: {$url}");
			exit();
		}
	}
	
	public function internalRedirect($url) {
	    $request = new FApplicationRequest($url);
		return _furnace()->process($request);
	}
	
	protected function loadFragment($label) {
		$path = _furnace()->rootdir . "/app/scripts/fragments/{$label}Fragment.php";
		if (file_exists($path)) {
			require_once($path);
		} else {
			die(
				"The page requested a fragment ({$label}) that does not exist or is not installed correctly."
			);
		}	
	}
	
    protected function loadHelper($provider,$label) {
		$path = _furnace()->rootdir . "/app/scripts/helpers/{$provider}/{$label}/{$label}.php";
		if (file_exists($path)) {
			require_once($path);
		} else {
			die(
				"The page requested a helper ({$label}) that does not exist or is not installed correctly."
			);
		}	
	}
	
	protected function loadLibrary($provider,$label) {
	    $path = _furnace()->rootdir . "/app/plugins/libraries/{$provider}/{$label}.lib.php";
	    if (file_exists($path)) {
	        require_once($path);
	    } else {
	        die(
	            "The page requested a library ({$provider}.{$label}) that does not exist or is not installed correctly."
	        );
	    }
	}
}
?>