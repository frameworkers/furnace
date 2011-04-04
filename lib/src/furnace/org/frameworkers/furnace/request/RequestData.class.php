<?php
namespace org\frameworkers\furnace\request;

/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
/**
 * A simple wrapper for storing user submitted form data
 *
 */
class RequestData {
	
	protected $_data;
	
	protected $missing;
	
	public function __construct($data) {
		// Store the data as provided
		$this->_data = $data;
		
		// Also provide `->` accessibility
		foreach ($data as $k => $v) {
			$this->$k = $v;
		}	

		// Nothing has been found to be missing yet
		$this->missing = array();
	}
	
	public function expect($keyOrArray) {
		// Determine whether any of the expected data 
		// are missing:
		$this->missing = array();
		if (is_array($keyOrArray)) {
			foreach ($keyOrArray as $k) {
				if (!isset($this->_data[$k]) || empty($this->_data[$k])) {
					$this->missing[] = $k;
				}
			}
		} else {
			if (!isset($this->_data[$keyOrArray]) || empty($this->_data[$keyOrArray])) {
				$this->missing[] = $keyOrArray;
			}
		}
		return empty($this->missing);		
	}
	
	public function has($keyOrArray) {
		return $this->expect($keyOrArray);
	}
	
	public function complete() {
		return empty($this->missing);
	}
	
	public function getMissing() {
		return $this->missing;
	}
	
	public function errorsAsHtml() {
		$html  = "The following required information was not provided: \"";
		$html .= implode("\", \"",$this->missing);
		$html .= "\"";
		return $html;
	}
}
 