<?php
namespace org\frameworkers\furnace\util;
/*
 * frameworkers-foundation
 * 
 * FYamlParser.php
 * Created on May 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FYamlParser
  * A class to parse YAML strings (or files) using the Simple
  * PHP YAML Class (http://spyc.sourceforge.net).
  */
class FYamlParser extends \com\thresholdstate\Spyc {	
 	
 	/*
 	 * Function: parse
 	 * 
 	 * Parses a YAML string (or file) into a PHP array object.
 	 * 
 	 * Parameters: 
 	 * 	
 	 * 	yml - a string or file path containing YAML markup.
 	 * 
 	 * Returns: 
 	 * 
 	 * A PHP array object containing the parsed data.
 	 */
 	public static function parse($yml) {
 		// Convert the YAML into an array
		return Spyc::YAMLLoad($yml);
 	}
 }
?>