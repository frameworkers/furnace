<?php
class _DefaultController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->setActiveMenuItem('main','home');
        
    }
    
    public function index() {
        $this->requireLogin();
		$this->setTitle('Welcome to Fuel!  -- Data Modeling for Furnace Apps');
		$this->set('pageTitle',"Home");
    }
    
    public function login() {
		if ($this->form) {
		    if ($this->checkLogin()) {
		        $this->redirect("{$this->prefix}/");
		    } else {
		        $this->flash("Invalid credentials provided.","error");   
		    }
		}
		$this->set('pageTitle','Authorization Required');
		$this->setTitle("Authorization Required");
	} 
	
	public function logout() {
		if (isset($_SESSION['fuel'])) {
			$_SESSION['fuel'] = array();
		}
		$this->redirect("{$this->prefix}/");
	}
    
}
?>