<?php
namespace org\frameworkers\furnace\config;

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
    
    /**
     * The debug level (0=production,>0 = debugging)
     * @var integer
     */
    public $debug_level;
    /**
     * The current application theme name
     * @var string
     */
    public $theme;
    
    /**
     * The base url (default = '/') for the application on the server
     * @var string
     */
    public $url_base;
    
    /**
     * Constructor
     * 
     * @param array $data  Configuration data read in by Spyc
     * @return FApplicationConfig
     */
    public function __construct($data) {
        
        $this->data = $data['app'];
        
        $this->debug_level = isset($data['app']['security']['debug_level'])
            ? $data['app']['security']['debug_level']
            : 0;            // Default to production if not provided
            
        $this->theme = isset($data['app']['theme']) 
            ? $data['app']['theme']
            : 'default';    // Default to 'default' if not provided
            
        $this->url_base = isset($data['app']['base']) 
            ? $data['app']['base']
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
        if (isset($this->data[$label])) {
        	return $this->data[$label];
        } else return false;
    }
}
?>