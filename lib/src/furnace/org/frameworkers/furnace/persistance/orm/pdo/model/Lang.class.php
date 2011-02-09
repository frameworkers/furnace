<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\model;
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
/**
 * String formatting routines for ORM objects and relations
 * @author andrew
 *
 */
class Lang {
	
	
	public static function ToClassName($name,$bIsIntermediate = false) {
		list($name,$ignore) = explode('/',$name); // trim plural information
  		return
  			($bIsIntermediate)
  				? self::ToProperCase(trim($name,"\"';-_ "),array('-'))  
  				: self::ToProperCase(trim($name,"\"';-_ "));
	}
	
	public static function ToAttributeName($name) {
		return
			self::ToCamelCase(trim($name,"\"';-_ "));
	}
	
	public static function ToTableName($name) {
		
		return strtolower($name[0])
			. substr($name,1);
	}
	
	public static function ToColumnName($name) {
		return self::ToCamelCase($name);
	}
	
	
	public static function ToValue($string) {
		$v   = trim($string,"\"'");
		$lcv = strtolower($v);
		
		if ($lcv == 'false' || $lcv == 'no') { return false; }
		if ($lcv == 'true'  || $lcv == 'yes'){ return true; }
		return $v; 
	}
	
	/**
	 * Convert an arbitrary string into camelCaseCaps, where
	 * the first letter is lowercased and distinct words are 
	 * concatenated by capitalizing the first letter in each.
	 * 
	 * By default, this function treats the `-` and `_` characters
	 * as indicating a word boundary. This can be modified by
	 * changing the contents of $chars.
	 *  
	 * @param string $string  The string to convert
	 * @param array  $chars   Characters that should be interpreted as 
	 *                        word boundaries.
	 */
	public static function ToCamelCase($string,$chars=array('-','_')) {
		return 
		    strtolower($string[0]) .
		    	substr(
  					str_replace(" ","",ucwords(
  						str_replace($chars," ",$string)))
  					,1);
	}
	
	/**
	 * Convert an arbitrary string into ProperCaseCaps, where
	 * the first letter is capitalized and distinct words are 
	 * concatenated by capitalizing the first letter in each.
	 * 
	 * By default, this function treats the `-` and `_` characters
	 * as indicating a word boundary. This can be modified by
	 * changing the contents of $chars.
	 *  
	 * @param string $string  The string to convert
	 * @param array  $chars   Characters that should be interpreted as
	 *                        word boundaries.
	 */
	public static function ToProperCase($string,$chars=array('-','_')) {
		return 
  			str_replace(" ","",ucwords(
  				str_replace($chars," ",$string)));
	}
}