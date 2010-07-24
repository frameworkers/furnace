<?php
class _DefaultController extends Controller {
    
    // Application home (index) page. If the default routing table
    // (see: /app/config/routes.yml) is unchanged, this function will be
    // invoked on requests for '/', aka the home page.
    public function index() {
        
        //
        // TODO: prepare data to be passed to the corresponding view
        //

    }
    
    
    public function login() {
        $this->loadWidget('org.frameworkers','LoginBox');
        $lb = new LoginBox($this,'/');
        $this->set('loginBox',$lb->render());
    }
    
    public function logout() {
        FSessionManager::doLogout();
        $this->redirect("/");
    }
}
?>