<?php
namespace org\frameworkers\furnace\exceptions;

class FValidationException extends FurnaceException {

	//TODO: implement this class
	protected $validator    = '';
	protected $subvalidator = '';
	protected $variable     = '';
	
	public function __construct($validator,$subValidator,$variable,$defaultMessage='') {
		parent::__construct($defaultMessage,0);
		$this->validator    = $validator;
		$this->subvalidator = $subValidator;
		$this->variable     = $variable;
		
		// store error message in the session
		$sub = ($subValidator == null || empty($subValidator)) ? false : $subValidator;
		$_SESSION['_validationErrors'][$variable][] = array(
			'validator'=>$validator,
			'subValidator'=>$sub,
			'variable'=>$variable,
			'defaultMessage'=>$defaultMessage);
		
	}
	
	public function __toString() {
		return "<b>There was a problem with your input:</b><br/>"
			."{$this->message} <br/><br/> Please check your values and try again.";
	}
}