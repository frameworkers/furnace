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

define(FF_FRESULT_OK,    100);
define(FF_FRESULT_ERROR, 101);

/**
 * FResult
 * 
 * Provides a standard mechanism for returning information
 * obtained from one or more application data sources in
 * response to an {@link FQuery}.
 * 
 */
class FResult {
    
    /**
     * The driver instance that generated this result
     * @var FDatasourceDriver driver
     */
    protected $driver;
    
    /**
     * The data (result set) formatted as an assoc. array
     * @var array data
     */
    public $data;
    
    /**
     * The status of the result as reported by the driver
     */
    public $status;
    
    /**
     * An optional message associated with the result
     */
    public $info;
    
    /**
     * Constructor
     * 
     * 
     * @param  FDatasourceDriver $driver The driver used
     * @return FResult
     */
    public function __construct($driver,$status = FF_FRESULT_OK) {
        $this->driver = $driver;
        $this->status = $status;
    }
    
    /**
     * Load a result set into this object's data field
     * @param  array $data
     * @return void
     */
    public function load($data) {
        $this->data = $data;
    }
    
    /**
     * Return the {@link FDatasourceDriver} object used to generate 
     * this result
     * 
     * @return FDatasourceDriver
     */
    public function getDriver() {
        return $this->driver;
    }

}
?>