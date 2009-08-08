<?php
class _defaultController extends Controller {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		// 'start' aliases to here
	}
	
	public function login() {
		if ($this->form) {
			if ('' == $GLOBALS['furnace']->config['root_password']) {
				// ALWAYS FAIL IF THE ROOT PASSWORD HAS NOT BEEN SET 
				// IN THE PROJECT CONFIGURATION FILE!
				$this->flash("Invalid login data provided. Please try again...","error");
				$this->redirect("/_furnace/login");
			}
			$pw =& $this->form['rootpass'];
			if (md5($pw) == md5($GLOBALS['furnace']->config['root_password'])) {
				$_SESSION['foundry']['loggedin']  = true;
				$_SESSION['foundry']['timestamp'] = mktime();
				$this->redirect("/_furnace");
			} else {
				$this->flash("Invalid login data provided. Please try again...","error");
				$this->redirect("/_furnace/login");
			}
		} 
		
		$this->setTitle("Fuel -- Login to Continue...");
	}
	
	public function logout() {
		unset($_SESSION['foundry']);
		$this->redirect("/");
	}
}
?>