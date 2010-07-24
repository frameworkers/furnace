<?php
class Controller extends FController {

    
    // Place functionality that should be common to all
    // application controllers in this file.
    
  
    // Convenience (short-cut) function for 404 errors
    protected function notfound() {
        $this->internalRedirect("/error/notfound");
    }
    
    // Convenience (short-cut) function for 403 errors
    protected function noauth() {
        $this->internalRedirect("/error/notauthorized");
    }
}
?>