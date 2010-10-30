<?php
namespace org\frameworkers\furnace\validation;
use org\frameworkers\furnace\exceptions as Exceptions;
/*
 * frameworkers-foundation
 * 
 * FValidator.class.php
 * Created on May 23, 2009
 *
 * Copyright 2008-2009 Frameworkers.org. 
 * http://www.frameworkers.org
 */
class ObjectValidator {
	
	/**
	 * This class contains static validation methods which can be used
	 * to verify user input.
	 */
	
	// Test whether var is present (not null)
	public static function Present($var) {
		return isset($var);
	}
	
	// Test whether var conforms to the provided pattern, or, if bNegate is 'true'
	// whether var DOES NOT conform to the provided pattern
	public static function Format($var,$pattern,$bNegate = false,$field=null,$humanReadable=null) {
		$valid = true;
		if ($bNegate) {
			$valid = ! preg_match($pattern,$var);
		} else {
			$valid = preg_match($pattern,$var);
		}
		if (! $valid) {
			throw new Exceptions\ValidationException('Format',null, $field,
				"<b>{$field}</b> is not formatted correctly ".(($humanReadable) ? "({$humanReadable})" : ''));
			return false;
		}
		
		return true;
	}
	
	// Test whether var is a number, according to the criteria
	public static function Numericality($var,$is=null,$minimum=null,$maximum=null,$onlyInteger=null,$bNegate = false,$field=null) {

		if ($is !== null && (($bNegate && ($var == $is)) || (!$bNegate && ($var != $is)))) {
			throw new FValidationException('Numericality',array('is',$is), $field,
				"<b>{$field}</b> must " . (($bNegate) ? "not" : '') . " be {$is}"); 
			return false;
		}
		
		if ($minimum !== null && $var < $minimum) {
			throw new Exceptions\ValidationException('Numericality',array('min',$minimum), $field,
				"<b>{$field}</b> must be at least {$minimum}");
			return false;
		}
		
		if ($maximum !== null && $var > $maximum) {
			throw new Exceptions\ValidationException('Numericality',array('max',$maximum), $field,
				"<b>{$field}</b> must not be larger than {$maximum}");
			return false;
		}
		
		//TODO: implement 'onlyInteger' component
		
		return true;
	}
	
	// Test whether the length of var matches the criteria
	public static function Length($var,$is=null,$minimum=null,$maximum=null,$field=null) {
		if ($is !== null && (strlen($var) != $is)) {
			throw new Exceptions\ValidationException('Length',array('is',$is), $field,
				"<b>{$field}</b> has incorrect length. Expected {$is} character(s) but got ".strlen($var)."</b>");
			return false;
		}
		
		if ($minimum !== null && (!isset($var[$minimum-1]))) {
			throw new Exceptions\ValidationException('Length',array('min',$minimum), $field,
				"<b>{$field}</b> must be at least {$minimum} character(s)");
			return false;
		}
		
		if ($maximum !== null && (isset($var[$maximum]))) {
			throw new Exceptions\ValidationException('Length',array('max',$maximum), $field,
				"<b>{$field}</b> must be less than {$maximum} character(s)");
			return false;
		}
	}
	
	// Test whether var IS in a set of values
	public static function Inclusion($var,$allowedValues=array(),$bCaseSensitive = true,$bPartialMatch = false,$field=null) {
	    if ($bPartialMatch) {
	        throw new Exceptions\FurnaceException("FValidator::Inclusion: Partial matching has not yet been implemented");
	    }
	    
		if ($bCaseSensitive) {
		    foreach ($allowedValues as $av) {
		        if (0 == strcmp($av,$var)) return true;
		    }    
		} else {
		    foreach ($allowedValues as $av) {
		        if (0 == strcasecmp($av,$var)) return true;
		    }
		}
		throw new Exceptions\ValidationException('Inclusion',null, $field,
			"<b>{$field}</b> must be provided. Please choose from the provided values.</b>");	
		return false;
	}
	
	//Test whether var is NOT in a set of values
	public static function Exclusion($var,$prohibitedValues=array(),$bCaseSensitive = true, $bPartialMatch = false,$field=null) {
		return ! self::Inclusion($var,$prohibitedValues,$bCaseSensitive,$bPartialMatch,$field);
	}
	
	//Test whether var evaluates to true
	public static function Acceptance($var,$bStrict = false,$field=null) {
		if (($bStrict && ($var !== true)) || (!$bStrict && ($var != true))) {
			throw new Exceptions\ValidationException('Acceptance',null, $field,
				"<b>{$field}</b> did not evaluate to 'true'");
			return false;
		}
		return true;
	}
	
	// Test whether the values confirm one another
	public static function Confirmation($var,$match,$field=null) {
		if ($var != $match) {
			throw new Exceptions\ValidationException('Confirmation',null, $field,
				"{$var} did not match expected value {$match}");
			return false;
		}
		return true;
	}
	
    public static function Email($var,$field) {
		//TODO: implement this function. To do so,
		// call the ::Format function with an email regex
			
		return self::Format($var,"/[a-zA-Z0-9\._-]+@[a-zA-Z0-9\._-]+\.([a-zA-Z]{2,4})/",false,$field);
	}
	
	public static function Custom($var,$function) {
		//TODO: implement this function
		return true;
	}	
	
	public static function BuildValidationCodeForAttribute($var,$attribute) {
		// Add type-specific validators
		/* NEEDS REFACTORING
		switch ($type) {
			case 'integer':
				if (!isset($criteria['numeric'])) {
					$criteria['numeric'] = array('integerOnly'=>true);
				} else {
					$criteria['numeric']['integerOnly'] = true;
				}
				break;
			case 'float':
				if (!isset($criteria['numeric'])) {
					$criteria['numeric'] = array();
				}
		}*/
		$criteria = $attribute->getValidation();
		$field    = "'{$attribute->getName()}'";
		$response = array();
		foreach ($criteria as $validationInstruction => $detail) {
			switch (strtolower($validationInstruction)) {
				case "present":
					$response[] = "FValidator::Present({$var});";
					break;
				case "format":
					$pattern = isset($detail['pattern']) ? "\"{$detail['pattern']}\"" : 'null';
					$negate  = isset($detail['negate'])  ? (($detail['negate']) ? 'true' : 'false')  : 'null';
					$response[] = "FValidator::Format({$var},{$pattern},{$negate},{$field});";
					break;
				case "numeric":
					$is = isset($detail['is']) ? $detail['is'] : 'null';
					$min= isset($detail['min'])? $detail['min']: 'null';
					$max= isset($detail['max'])? $detail['max']: 'null';
					$int= isset($detail['integerOnly']) ? (($detail['integerOnly'])? 'true' : 'false') : 'null';
					$negate  = isset($detail['negate'])  ? (($detail['negate']) ? 'true' : 'false')  : 'null';
					$response[] = "FValidator::Numericality({$var},{$is},{$min},{$max},{$int},{$negate},{$field});";
					break;
				case "length":
					$is = isset($detail['is']) ? $detail['is'] : 'null';
					$min= isset($detail['min'])? $detail['min']: 'null';
					$max= isset($detail['max'])? $detail['max']: 'null';
					$response[] = "FValidator::Length({$var},{$is},{$min},{$max},{$field});";
					break;
				case "email":
					$response[] = "FValidator::Email({$var});";
					break;
				case "allowedvalues":
				    $values = array();
				    foreach ($detail as $d) {
				        $values[] = "\"{$d['value']}\"";
				    }
				    $values = "array(".implode(',',$values).")";
				    $response[] = "FValidator::Inclusion({$var},{$values},false,false,{$field});";
				    break;
				default: break;
			}
		}
		if (count($response) == 0) {
		    return false;
		} else {
		    return implode("\r\n\t\t\t\t",$response) . "\r\n";
		}
	}
	
	
	/**
	 * This class also represents the base class for all model object
	 * validators
	 */
	
	public $valid;
	
	public $errors;
	
	public function __construct() {
		$this->valid  = true;
		$this->errors = array();
	}
	
	public function getValid() {
		return $this->valid;
	}
	public function isValid() {
	    return $this->valid;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function getErrorsAsHTMLList() {
		$li = '';
		foreach ($this->errors as $k => $v) {
			$li .= "<li>{$v}</li>";
		}
		return "<ul>{$li}</ul>";
	}

	public function __toString() {
		return "<strong>Errors encountered...</strong> " . $this->getErrorsAsHTMLList();
	}
	
	public function addError($attributeName,$errorMessage) {
		$this->errors[$attributeName] = $errorMessage;
		$this->valid = false;
	}
}
?>