<?php
namespace org\frameworkers\furnace\exceptions;
/*
 * Class: FDatabaseException
 * 
 * Extends <FurnaceException> to support object-oriented exception handling
 * of database related errors.
 * 
 * Extends: <FurnaceException>
 */
class DatabaseException extends FurnaceException {

	private $query;
	/*
	 * Function: __construct
	 * 
	 * Overrides the FurnaceException constructor, providing specific information
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
			. (($GLOBALS['furnace']->config->debug_level > 0)
				? "\r\n<br/>Last query was: {$query}\r\n<br/>"
				: ""),
			100);	
		// Store the query
		$this->query = $query;
	}	
	
	public function getQuery() {
		return $this->query;
	}
}