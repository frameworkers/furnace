<?php
namespace org\frameworkers\furnace\utilities;

/**
 * Provides a framework for applying closures to methods as filters
 * 
 * This concept of filtering using closures was deeply inspired by the Lithium PHP
 * framework. You can learn more about it at <http://lithify.me>
 *  
 *
 */
use org\frameworkers\furnace\core\Object;

class Filters extends Object {
	
	protected static $_lazyFilters = array();
	
	protected $_data       = array(); 
	
	protected $_valid      = false;

	protected $_autoConfig = array('data', 'class', 'method');
	
	protected $_class      = null;
	
	protected $_method     = null;
	
	public function __construct($options) {
		$this->_class  = isset($options['class'])  ? $options['class']  : null;
		$this->_method = isset($options['method']) ? $options['method'] : null; 
		$this->_data   = isset($options['data'])   ? $options['data']   : null;
	}
	
	
	public static function apply($class, $method, $filter) {
		if (class_exists($class, false)) {
			return $class::applyFilter($method, $filter);
		}
		static::$_lazyFilters[$class][$method][] = $filter;
	}
	
	public static function hasApplied($class, $method) {
		return isset(static::$_lazyFilters[$class][$method]);
	}
	
	public static function run($class, $params, array $options = array()) {

		$defaults = array('class' => null, 'method' => null, 'data' => array());
		$options += $defaults;                                            // Merge options with defaults

		$lazyFilterCheck = (is_string($class) && $options['method']);     // Have lazy filters been defined?
		
		if (($lazyFilterCheck) && isset(static::$_lazyFilters[$class][$options['method']])) {
			$filters = static::$_lazyFilters[$class][$options['method']]; // Grab the filters
			unset(static::$_lazyFilters[$class][$options['method']]);     // Remove them from the wait queue
			$options['data'] = array_merge($filters, $options['data']);   // Merge with any externally provided filters

			foreach ($filters as $filter) {                               // For each filter,
				$class::applyFilter($options['method'], $filter);         // Apply it to the class
			} 
		}
		
		$chain = new Filters($options);                                   // Construct the filter chain
		$next  = $chain->rewind();                                        // Get to the front of the chain
		return $next($class, $params, $chain);                            // Return the result of the next filter in line
	}
	
	public function next($self, $params, $chain) {
		if (empty($self) || empty($chain)) {
			return $this->nextInLine();                                   // End of the line
		}
		$next = $this->nextInLine();
		return $next($self, $params, $chain);                             // Return the result of the next filter in line
	}
	
	public function method($full = false) {
		return $full 
			? $this->_class . '::' . $this->_method
			: $this->_method;
	}
	
	public function append($value) {
		is_object($value) 
			? $this->_data[] =& $value
			: $this->_data[] =  $value;
	}
	
	public function current() {
		return current($this->_data);
	}	
	
	public function nextInLine() {
		$this->_valid = (next($this->_data) !== false);
		return current($this->_data); 
	}
	
	public function rewind() {
		$this->_valid = (reset($this->_data) !== false);
		return current($this->_data);
	}
	
	public function valid() {
		return $this->_valid;
	}

}