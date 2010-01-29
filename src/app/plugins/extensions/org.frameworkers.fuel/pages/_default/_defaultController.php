<?php
class _DefaultController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->setActiveMenuItem('main','home');
        
    }
    
    public function index() {
		$this->setTitle('Welcome to Fuel!  -- Data Modeling for Furnace Apps');
		$this->set('pageTitle',"Home");
    }
    
}
?>