<?php
class ConfigController extends Controller {
    
    
    public function index() {
        $this->extensionSetLayout('org.frameworkers.fuel','two-column');
        $this->setActiveMenuItem('main','config');
        $this->set('pageTitle','Config');
    }

    
}
?>