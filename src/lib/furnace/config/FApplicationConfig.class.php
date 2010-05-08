<?php
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package   Furnace
 * @copyright Copyright (c) 2008-2010, Frameworkers.org
 * @license   http://furnace.frameworkers.org/license
 *
 */

/**
 * FApplicationConfig
 * 
 * Provides a representation of the Application configuration as
 * specified in {FF_ROOT_DIR}/app/config/app.yml
 *
 */
class FApplicationConfig {
    
    public $data;
    
    public function __construct($data) {
        
        $this->data = $data;
        
    }
    
    public function getSection($label) {
        return $this->section($label);
    }
    
    public function section($label) {
        if (isset($this->data[$label])) {
            return $this->data[$label];
        } else {
            return false;
        }
    }
}
?>