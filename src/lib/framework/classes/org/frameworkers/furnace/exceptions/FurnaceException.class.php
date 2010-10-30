<?php
namespace org\frameworkers\furnace\exceptions;

class FurnaceException extends \Exception {
	
	public function __construct($message,$code = 0) {
    	
    	// make sure everything is assigned properly
    	parent::__construct($message,$code);	
    }
    
    public function __toString() {
    	return "<b>". __CLASS__ . "</b>" 
    		. " [{$this->code}] : {$this->message}\n"
    		. (($GLOBALS['furnace']->config->debug_level > 0)
    			? "<pre>{$this->getTraceAsString()}</pre>\n"
    			: "<pre>Please contact the site administrator regarding this error.</pre>"
    		);	
    }
}