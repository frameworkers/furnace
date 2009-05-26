<?php
/*
 * frameworkers-foundation
 * 
 * FValidator.class.php
 * Created on May 23, 2009
 *
 * Copyright 2008-2009 Frameworkers.org. 
 * http://www.frameworkers.org
 */
class FValidator {
	
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
	public static function Format($var,$pattern,$bNegate = false) {
		//TODO: implement this function
		return true;	
	}
	
	// Test whether var is a number, according to the criteria
	public static function Numericality($var,$is=null,$minimum=null,$maximum=null,$onlyInteger=null) {
		//TODO: implement this function
		return true;
	}
	
	// Test whether the length of var matches the criteria
	public static function Length($var,$is=null,$minimum=null,$maximum=null,$field=null) {
		if ($is != null && (strlen($var) != $is)) {
			throw new FValidationException('Length',$field,
				"<b>{$field}</b> has incorrect length. Expected {$is} character(s) but got ".strlen($var)."</b>");
		}
		
		if ($minimum != null && (!isset($var[$minimum-1]))) {
			throw new FValidationException('Length',$field,
				"<b>{$field}</b> must be at least {$minimum} character(s)");
		}
		
		if ($maximum != null && (isset($var[$maximum]))) {
			throw new FValidationException('Length',$field,
				"<b>{$field}</b> must be less than {$maximum} character(s)");
		}
	}
	
	// Test whether var IS in a set of values
	public static function Inclusion($var,$allowedValues=array(),$bCaseSensitive = true,$bPartialMatch = false) {
		//TODO: implement this function
		return true;	
	}
	
	//Test whether var is NOT in a set of values
	public static function Exclusion($var,$prohibitedValues=array(),$bCaseSensitive = true, $bPartialMatch = false) {
		//TODO: implement this function
		return true;
	}
	
	//Test whether var evaluates to true
	public static function Acceptance($var,$bStrict = false,$field=null) {
		if (($bStrict && ($var !== true)) || (!$bStrict && ($var != true))) {
			throw new FValidationException('Acceptance',$field,
				"<b>{$field}</b> did not evaluate to 'true'");
		}
	}
	
	// Test whether the values confirm one another
	public static function Confirmation($var,$match,$field=null) {
		if ($var != $match) {
			throw new FValidationException('Confirmation',$field,
				"{$var} did not match expected value {$match}");
		}
	}
	
	public static function Email($var) {
		// call the ::Format function with an email regex	
		//TODO: implement this function
		return true;
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
					$response[] = "FValidator::Format({$var},{$pattern},{$negate});";
					break;
				case "numeric":
					$is = isset($detail['is']) ? $detail['is'] : 'null';
					$min= isset($detail['min'])? $detail['min']: 'null';
					$max= isset($detail['max'])? $detail['max']: 'null';
					$int= isset($detail['integerOnly']) ? (($detail['integerOnly'])? 'true' : 'false') : 'null';
					$response[] = "FValidator::Numericality({$var},{$is},{$min},{$max},{$int});";
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
				default: break;
			}
		}
		return implode("\r\n\t\t\t",$response) . "\r\n";
	}
}
?>