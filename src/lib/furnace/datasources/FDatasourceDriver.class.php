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
     * @param  array $options Any data necessary to establish the connection 
     * @return boolean
     */
    abstract public function init($options = array());
    
    /**
     * Process an @see FQuery object and return an {@link FResult}
     * 
     * @param  FQuery $query The query to process
     * @return FResult
     */
    abstract public function query($query);
    
    /**
     * Process an {@link FQuery} object
     * 
     * This method does not return an {@link FResult} object
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