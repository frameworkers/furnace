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

    
    // Error page to display on 404 errors
    // A 404 error can be "forced" from any controller
    // by using the convenience function $this->noexist(). See
    // /app/pages/Controller.class.php for the function definition
    public function http404() { }
    
    // Error page to display on 403 errors
    // A 403 error can be "forced" from any controller
    // by using the convenience function $this->noauth(). See
    // /app/pages/Controller.class.php for the function definition
    public function http403() { }
}

?>