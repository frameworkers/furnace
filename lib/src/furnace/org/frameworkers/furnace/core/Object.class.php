<?php
namespace org\frameworkers\furnace\core;

use org\frameworkers\furnace\utilities\Filters;

class Object extends FrameworkBase {
	
	protected $_methodFilters = array();
	
	/**
	 * Apply a closure to a method of the current object instance
	 * 
	 * @param mixed  $method    The name of the method to apply the closure to. Can
	 *      either be a single method name as a string, or an array of method names.
	 * @param closure $filter   The closure that is used to filter the method(s)
	 */
	public function applyFilter($method, $filter = null) {
		
		foreach ((array) $method as $m) {
			
			// If no filter has been assigned yet, this needs to
			// be initialized as an array()
			if (!isset($this->_methodFilters[$m])) {
				$this->_methodFilters[$m] = array();
			}
			
			// Push the new filter onto the array of filters for
			// this method
			$this->_methodFilters[$m][] = $filter;
		}
	}
	
	/**
	 * Calls a method on this object with the given parameters. This essentially
	 * acts as an object oriented wrapper around call_user_func_array, with a 
	 * performance enhancement to use straight method calls in most situations.
	 * 
	 * 
	 * @param unknown_type $method
	 * @param unknown_type $params
	 */
	public function invokeMethod($method, $params = array()) {
		switch (count($params)) {
			case 0:
				return $this->{$method}();
			case 1:
				return $this->{$method}($params[0]);
			case 2:
				return $this->{$method}($params[0], $params[1]);
			case 3:
				return $this->{$method}($params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array(array(&$this, $method),$params);
		}
	}
	
	/**
	 * Execute a set of filters on a method by taking the method's main implementation
	 * as a callback, and iteratively wrapping the filters around it. 
	 * 
	 * This is an incredibly cool idea, which originated, as far as I can tell, from the 
	 * Lithium PHP framework. See the class docblock for a link to the project.
	 * 
	 * @param string $method    The name of the method being executed; usually the value of __METHOD__
	 * @param array  $params    An assoc. array containing all the parameters passed into the method
	 * @param array  $callback  The method's implementation, wrapped in a closure
	 * @param array  $filters   Additional filters to apply to the method for this call only
	 * @return mixed            Returns the return value of `$callback`, modified by any filters
	 *                          passed in `$filters` or applied with `applyFilter()`
	 */
	protected function _filter($method, $params, $callback, $filters = array()) {
		list($class, $method) = explode('::', $method);
		
		// If no filters defined or applied, just call the function body
		if (empty($this->_methodFilters[$method]) && empty($filters)) {
			return $callback($this, $params, null);
		}

		// Get any filters that have been applied to this method
		$f = isset($this->_methodFilters[$method]) ? $this->_methodFilters[$method] : array();

		$data = array_merge($f, $filters, array($callback));
		
		return Filters::run($this, $params, compact('data', 'class', 'method'));
	}	
}