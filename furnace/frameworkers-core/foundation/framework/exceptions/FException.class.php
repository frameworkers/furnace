<?php
/*
 * frameworkers-foundation
 * 
 * FException.class.php
 * Created on June 15, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
class FException extends Exception {

    public function __construct($message,$code = 0) {
    	//
    	
    	// make sure everything is assigned properly
    	parent::__construct($message,$code);	
    }
    
    public function __toString() {
    	return "<b>". __CLASS__ . "</b>" 
    		. " [{$this->code}] : {$this->message}\n"
    		. ((Config::PROJECT_ENV_DEBUG)
    			? "<pre>{$this->getTraceAsString()}</pre>\n"
    			: "<pre>Please contact the site administrator regarding this error.</pre>"
    		);	
    }
}

/**
 * FEXception Codes
 * 
 * 1xx = Database (query) related exceptions 
 */

class FDatabaseException extends FException {

	public function __construct($message='',$query='') {
		parent::__construct(
			(($message == '')
				? "Unknown database exception"
				: $message)
			. ((Config::PROJECT_ENV_DEBUG)
				? "\r\n<br/>Last query was: {$query}\r\n<br/>"
				: ""),
			100);	
	}	
}

class FUniqueValueException extends FDatabaseException {

	public function __construct($query='') {
		parent::__construct(
			"An attempt was made to insert a duplicate into a column marked UNIUQE ",
			$query);	
	}	
}

class FDatabaseErrorTranslator {

	public static function translate($code,$query='') {
		switch ($code) {
			
			case MDB2_ERROR_CONSTRAINT:
				// Portability alias of ERROR_ALREADY_EXISTS
				throw new FUniqueValueException($query);
				break;
			default:
				// If unknown code
				throw new FDatabaseException($query);
				break;
		}
	}
}
?>