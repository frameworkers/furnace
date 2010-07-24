<?php
class errorController extends Controller {
	
	
	// Error page to display on 404 errors
    // A 404 error can be "forced" from any controller
    // by using the convenience function $this->noexist(). See
    // /app/pages/Controller.class.php for the function definition
    public function notfound() { }
    
    // Error page to display on 403 errors
    // A 403 error can be "forced" from any controller
    // by using the convenience function $this->noauth(). See
    // /app/pages/Controller.class.php for the function definition
    public function notauthorized() { }
	
	
}