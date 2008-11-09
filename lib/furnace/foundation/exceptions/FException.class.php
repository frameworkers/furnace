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
 
/*
 * Class: FException
 * 
 * Provides a wrapper around the built in Exception classes
 * to support object-oriented exception handling
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
    		. (($GLOBALS['fconfig_debug_level'] > 0)
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
 
/*
 * Class: FDatabaseException
 * 
 * Extends <FException> to support object-oriented exception handling
 * of database related errors.
 * 
 * Extends: <FException>
 */
class FDatabaseException extends FException {

	/*
	 * Function: __construct
	 * 
	 * Overrides the FException constructor, providing specific information
	 * about the database exception that occurred, including query details.
	 * 
	 * Parameters:
	 * 
	 *  message - The message to display
	 *  query   - The query which has caused the exception
	 * 
	 * 
	 */
	public function __construct($message='',$query='') {
		parent::__construct(
			(($message == '')
				? "Unknown database exception"
				: $message)
			. (($GLOBALS['fconfig_debug_level'] > 0)
				? "\r\n<br/>Last query was: {$query}\r\n<br/>"
				: ""),
			100);	
	}	
}

/*
 * Class: FUniqueValueException
 * 
 * Extends <FDatabaseException> to support object-oriented exception handling
 * of database related errors related to unique value violations.
 * 
 * Extends: <FDatabaseException>
 */
class FUniqueValueException extends FDatabaseException {

	public function __construct($query='') {
		parent::__construct(
			"An attempt was made to insert a duplicate into a column marked UNIUQE ",
			$query);	
	}	
}

/*
 * Class: FDatabaseErrorTranslator
 * 
 * Provides a mapping between PEAR MDB2 error codes and defined 
 * <FException> classes. This effectively provides an object-oriented
 * wrapper around the MDB2 error codes
 * 
 * Extends: <FException>
 */
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