<?php
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package   Furnace
 * @copyright Copyright (c) 2008-2010, Frameworkers.org
 * @license   http://furnace.frameworkers.org/license
 *
 */

/**
 * FApplicationRequest
 * 
 * This class represents an end-user HTTP request to the framework.
 *
 */
class FApplicationRequest {
    
    public $raw;
    public $env;
    public $get;
    public $post;
    public $form;
    public $route;
    public $stats;
    
    public function __construct($rawRequest) {
        
        $this->stats['req_start'] = microtime(true);
        $this->raw  = $rawRequest;
        $this->get  =& $_GET;
        $this->post =& $_POST;
        $this->processPostedData();
        
    }
    
    /**
     * processPostedData
     * 
     * Captures data sent via HTTP 'Post' for use by controllers
     * 
     * @return nothing
     */
    private function processPostedData() {
		// Store a pointer to the recently submitted data 
		$this->form =& $_POST;
		// Clear old validation errors from the session
		$_SESSION['_validationErrors'] = array();
	} 

	public function compileStats($outputFormat = 'html') {
		
		/*
		 * Available Statistics:
		 * 
		 * 	req_start
		 *  proc_setup_start
		 *  
		 *  proc_setup_end
		 *  proc_start
		 *  handler_start
		 *  
		 *  handler_end
		 *  proc_end
		 *  
		 *  render_start
		 *  render_end
		 *  req_end
		 */
		$setupTime   = $this->stats['proc_setup_end'] - $this->stats['req_start'];
		$handlerTime = $this->stats['handler_end']    - $this->stats['handler_start']; 
		$renderTime  = $this->stats['render_end']     - $this->stats['render_start'];
		$totalTime   = $this->stats['req_end']        - $this->stats['req_start'];
		
		
	    switch (strtolower($outputFormat)) {
	        case 'html':
                $str .= '<div id="ff-debug"><table>';
                $str .= "<tr><th>REQUEST BREAKDOWN:  </th><td><table><tr><th>SETUP TIME: </th><td>{$setupTime}</td></tr>\r\n";
                $str .= "<tr><th>HANDLER TIME: </th><td>{$handlerTime}</td></tr>\r\n";
                /*
                $str .= "<tr><th>QUERY DELAY:  </th><td>" . count($this->queries) . " queries<br/>\r\n<span style='font-size:90%;'>";
                $qd = 0;
                foreach ($this->queries as $q) {
                    $str .= "&nbsp;&nbsp;{$q['delay']}s\t{$q['sql']}<br/>\r\n";
                    $qd += $q['delay'];
                }
                
                $str .= "</span><br/>\r\n&nbsp;&nbsp;" . count($this->queries) . " queries took {$qd} seconds.</td></tr>\r\n";
                */
                $str .= "<tr><th>RENDER  TIME:  </th><td>{$renderTime}</td></tr>\r\n";
                $str .= "<tr><th>TOTAL REQUEST TIME: </th><td> {$totalTime} seconds</td></tr>\r\n";
                $str .= "</table></td></tr>";
                $str .= "</table>";
                $str .= '</div>';
                break;
	        case 'text':
	        	$str .= "T: {$totalTime} (S: {$setupTime} / H: {$handlerTime} / R: {$renderTime}) ";
	    }
	    
	    return $str;
	}
                
}
?>