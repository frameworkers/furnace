<?php
class _DefaultController extends Controller {
    
    
    public function index() {
        
        $this->set('host',$_SERVER['HTTP_HOST']);

    }
}
?>

