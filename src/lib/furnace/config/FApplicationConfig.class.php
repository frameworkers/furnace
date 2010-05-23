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
    
    /**
     * The configuration data for the application
     * @var array
     */
    public $data;
    
    public $debug_level;
    
    public $theme;
    
    public $url_base;
    
    /**
     * Constructor
     * 
     * @param array $data  Configuration data read in by Spyc
     * @return FApplicationConfig
     */
    public function __construct($data) {
        
        $this->data = $data;
        
        $this->debug_level = isset($data['debug_level'])
            ? $data['debug_level']
            : 0;            // Default to production if not provided
            
        $this->theme = isset($data['theme']) 
            ? $data['theme']
            : 'default';    // Default to 'default' if not provided
            
        $this->url_base = isset($data['url_base']) 
            ? $data['url_base']
            : '/';          // Default to '/' if not provided
        
    }
    
    /** 
     * Get a section of the config information
     * 
     * Utility function to support requesting config information
     * via the page templates. This function simply calls 
     * 'section' to do the lifting.
     * 
     * @see section
     * @param string $label  The section identifier
     * @return array         The corresponding config data
     */
    public function getSection($label) {
        return $this->section($label);
    }
    
    /**
     * Get a section of the config information
     * 
     * Provides a quick way to access a particular section
     * of the application configuration file, identified by
     * its label.
     * @param string $label  The section identifier
     * @return array         The corresponding config data
     */
    public function section($label) {
        if (isset($this->data[$label])) {
            return $this->data[$label];
        } else {
            return false;
        }
    }
}
?>