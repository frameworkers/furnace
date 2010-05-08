<?php
require_once 'PHPUnit/Framework.php';
require_once 's;rc/lib/furnace/request/FApplicationRequest.class.php';

class FApplicationRequestTest extends PHPUnit_Framework_TestCase {
    
    protected function setUp() {
        
    }
    
    /**
     * Ensure that the provided request URL is stored correctly upon
     * object construction.
     * 
     * @return unknown_type
     */
    public function testRequest() {
        
        $requestURL = 'http://test.local/foo';
        
        $req = new FApplicationRequest($requestURL);
        
        $this->assertEquals('http://test.local/foo',$req->raw);
    }
}
?>