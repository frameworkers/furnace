<?php
class _defaultController extends Controller {
	
	
	public function index() {
		$this->setTitle('Welcome to Fuel!  -- Data Modeling for Furnace Apps');
	}
	
	public function login() {
		if ($this->form) {
			if ('' == $GLOBALS['furnace']->config['root_password']) {
				// ALWAYS FAIL IF THE ROOT PASSWORD HAS NOT BEEN SET 
				// IN THE PROJECT CONFIGURATION FILE!
				$this->flash("Invalid login data provided. Please try again...","error");
				$this->redirect("/fuel/login");
			}
			$pw =& $this->form['rootpass'];
			if (md5($pw) == md5($GLOBALS['furnace']->config['root_password'])) {
				$_SESSION['fuel']['loggedin']  = true;
				$_SESSION['fuel']['timestamp'] = mktime();
				$this->redirect("/fuel/");
			} else {
				$this->flash("Invalid login data provided. Please try again...","error");
				$this->redirect("/fuel/login");
			}
		} 
		
		$this->setTitle("Fuel -- Login to Continue...");
		
	}
	
	public function logout() {
		if (isset($_SESSION['fuel'])) {
			$_SESSION['fuel'] = array();
		}
		$this->redirect("/");
	}
}
?>