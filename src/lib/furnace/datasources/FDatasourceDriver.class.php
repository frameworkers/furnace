<?php
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package    Furnace
 * @subpackage Datasources
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

/**
 * FDatasourceDriver
 * 
 * Provides an abstract base class for implementing 
 * datasource specific drivers for Furnace.
 * 
 * @abstract
 */
abstract class FDatasourceDriver {
    
    /**
     * Initialize a connection to the datasource
     *  
     * The data source 
     * may require additional information. If needed, have the callee
     * make use of the $options parameter (an associative array) to 
     * provide a set of additional key-value data to this function.
     * 
     * @param  array $options Any data necessary to establish the connection 
     * @return boolean
     */
    abstract public function init($options = array());
    
    /**
     * Process an {@link FQuery} object and return an {@link FResult}
     * 
     * Implementations of this function should provide the logic necessary to convert a 
     * standard {@link FQuery} object into one (or more) native datasource
     * queries. Furthermore, it must package the response from the
     * underlying layer as an {@link FResult}, which should be returned
     * to the callee.
     * 
     * @param  FQuery $query The query to process
     * @return FResult
     */
    abstract public function query($query);
    
    /**
     * Return the unique id of the last inserted item
     * @param  array $options an optional set of options to provide
     * @return mixed
     */
    abstract public function lastInsertId($options = array());
    
    /**
     * Process an {@link FQuery} object
     * 
     * This method is almost identical to {@link query}, with the
     * sole exception being that the method does not return an 
     * {@link FResult} object.
     * 
     * @param  FQuery $query
     * @return nothing
     */
    abstract public function exec($query);
    
    /**
     * Close the connection to the datasource
     * 
     * @param array $options Any data necessary to close the connection
     * @return boolean
     */
    abstract public function close($options = array());

}
?>