<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage controller
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\utilities;

use furnace\core\Config;


class Inflector {
	
	
	// from: furnace_PHP_framework
	// to:   FurnacePHPFramework
	public static function toProperCase($string) {
		return(str_replace(' ','',ucwords(str_replace('_',' ',$string))));
	}
	
	// from: furnace_PHP_framework
	// to:   furnacePHPFramework
	public static function toCamelCase($string) {
		return lcfirst(self::toProperCase($string));
	}
	
	// from: furnacePHPFramework
	// to:   Furnace PHP Framework
	public static function toHumanReadable($string) {
		$string = self::toProperCase($string);
		$output = '';
		$i      = 0;
		$lastLetterWasCapitalized = false;
		
		// Iterate over the string
		while ($string[$i]) {
			// Proceed until we find a capital letter
			if ($string[$i] >= 'A' && $string[$i] <= 'Z') {
				// If the previous letter was not capitalized, add a space (' ')
				// before printing this letter
				if (!$lastLetterWasCapitalized) {
					$output .= ' ';
				// If the previous letter WAS capitalized, this letter is capitalized (outer 'if')
				// and the next one is NOT, add a space before printing this letter
				} else if ($string[$i+1] && ($string[$i+1] >= 'a' && $string[$i+1] <= 'z')) {
					$output .= ' ';
				}
				$lastLetterWasCapitalized = true;
			} else {
				$lastLetterWasCapitalized = false;
			}
			$output .= $string[$i++];
		}

		return trim($output);
	}
}
