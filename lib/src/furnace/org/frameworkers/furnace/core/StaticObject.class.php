<?php
namespace org\frameworkers\furnace\core;

use org\frameworkers\furnace\utilities\Filters;

class StaticObject extends FrameworkBase {
	
	protected static $_methodFilters = array();
	
	/**
	 * Apply a closure to a method of the current object instance
	 * 
	 * @param mixed  $method    The name of the method to apply the closure to. Can
	 *      either be a single method name as a string, or an array of method names.
	 * @param closure $filter   The closure that is used to filter the method(s)
	 */
	public static function applyFilter($method, $filter = null) {
		
		foreach ((array) $method as $m) {
			
			// If no filter has been assigned yet, this needs to
			// be initialized as an array()
			if (!isset(static::$_methodFilters[$m])) {
				static::$_methodFilters[$m] = array();
			}
			
			// Push the new filter onto the array of filters for
			// this method
			static::$_methodFilters[$m][] = $filter;
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
	public static function invokeMethod($method, $params = array()) {
		switch (count($params)) {
			case 0:
				return static::$method();
			case 1:
				return static::$method($params[0]);
			case 2:
				return static::$method($params[0], $params[1]);
			case 3:
				return static::$method($params[0], $params[1], $params[2]);
			case 4:
				return static::$method($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return static::$method($params[0], $params[1], $params[2], $params[3], $params[4]);
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
		$class        = get_called_class();
		$hasNoFilters = empty(static::$_methodFilters[$class][$method]); 
		
		// If no filters defined or applied, just call the function body
		if ($hasNoFilters && !$filters && !Filters::hasApplied($class, $method)) {
			return $callback($class, $params, $null);
		}
		if (!isset(static::$_methodFilters[$class][$method])) {
			static::$_methodFilters += array($class => array());
			static::$_methodFilters[$class][$method] = array();
		}
		
		// Get any filters that have been applied to this method
		$f = static::$_methodFilters[$class][$method];
		
		$data = array_merge($f, $filters, array($callback));
		
		return Filters::run($class, $params, compact('data', 'class', 'method'));
	}
	
	/**
	 * Convert the provided data (or $this if `data` is null) to an array.
	 * 
	 * @param mixed data  optional data to convert to an array. If the data is a
	 *                    PHP object, it will be recursively converted into an
	 *                    array.
	 * @return array
	 */
	public function toArray($data) {
        if (is_object($data)) $data = get_object_vars($data);
        return is_array($data)
        	? array_map(array(__CLASS__,__FUNCTION__), $data)
        	: $data;
    }
	
    /**
     * Returns a JSON representation of the provided data
     * 
     * @param mixed data  optional data to convert to JSON. If the data is a
	 *                    PHP object, it will be recursively converted into an
	 *                    array representation and encoded as JSON
     * @return string     The JSON formatted representation of the input
     */
	public function toJsonString($data) {
        return json_encode(self::toArray($data),JSON_FORCE_OBJECT);
    }
}