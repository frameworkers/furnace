<?php
use furnace\core\Config;
use furnace\core\Furnace;
use furnace\controller\Controller;

class DefaultController extends Controller {
    
    public function index() {
        $this->title('Welcome to Furnace!');
    }
    
    /* Add additional handlers here */
    
}