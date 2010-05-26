<?php
class ConfigController extends Controller {
    
    
    public function index() {
        $this->extensionSetLayout('org.frameworkers','Fuel','two-column');
        $this->setActiveMenuItem('main','config');
        $this->set('pageTitle','Config');
    }

    
}
?>