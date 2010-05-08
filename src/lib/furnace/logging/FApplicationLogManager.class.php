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
 * FApplicationLogManager
 * 
 * Provides a structure for organizing multiple application log
 * streams. 
 * 
 */
class FApplicationLogManager {
    
    private $logs = array();
    
    public function __construct($applicationConfig) {
        // Initialize the default log. Because this log is guaranteed to exist
        // it can be used to log everything else, including exceptions to the 
        // logging interface itself.
        $this->logs['default'] = &Log::singleton('file', FF_LOG_DIR . '/furnace.all.log');
        
        foreach ($applicationConfig->data['logging'] as $logname => $logdata) {
            $this->logs[$logname] = &Log::singleton($logdata['type'],$logdata['path'],$logname);
        }      
    }
    
    /**
     * getLog
     * 
     * Returns the singleton instance of the requested log object
     * 
     * @param string The identifier of the log to retrieve 
     * @return Log   The Log instance requested
     */
    public function getLog($which = 'default') {
        
        if (isset($this->logs[$which])) {
            return $this->logs[$which];
        } else {
            throw new Exception(
            	"Received request to log to undefined logger '{$which}' ");
        }
    }
}
?>