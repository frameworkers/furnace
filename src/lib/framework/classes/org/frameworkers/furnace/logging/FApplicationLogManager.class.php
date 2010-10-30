<?php
namespace org\frameworkers\furnace\logging;
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
    
    private $logs   = array();
    private $noinit = false;
    
    public function __construct($applicationConfig) {
        
    	if (false) {
    	//if (isset($applicationConfig->data['logging'])) {
    		
    		$logDir = $applicationConfig->data['logging']['log_dir'];
    		$logDir = str_replace('${app}',FURNACE_APP_PATH,$logDir);
    		
    		/* Ensure that the log directory is writable by the server. 
	         */
	        if (!is_writable($logDir)) {
	        	die(
	            	"<strong>Furnace:</strong> Insufficient permissions to write to specified log directory. \r\n<br/>"
	            	."Ensure that your application log directory ({$logDir}) exists and is writable by the server:<br/><br/>"
	            	."<code>chgrp -R apache_username /path/to/app/data/logs;</code><br/>"
	            	."<code>chmod -R g+w /path/to/app/data/logs;</code>"
	            );
	        } else {
				define('FF_LOG_DIR', $logDir);
	        }
    		
    		
    	
	        foreach ($applicationConfig->data['logging'] as $logname => $logdata) {
	            // Process mask criteria
	            if (isset($logdata['mask'])) {
	                switch ($logdata['mask']) {
	                    case 'FF_DEBUG'  : $mask = \Log::MAX(FF_DEBUG);  break;
	                    case 'FF_INFO'   : $mask = \Log::MAX(FF_INFO);   break;
	                    case 'FF_NOTICE' : $mask = \Log::MAX(FF_NOTICE); break;
	                    case 'FF_WARN'   : $mask = \Log::MAX(FF_WARN);   break;
	                    case 'FF_ERROR'  : $mask = \Log::MAX(FF_ERROR);  break;
	                    case 'FF_CRIT'   : $mask = \Log::MAX(FF_CRIT);   break;
	                    case 'FF_ALERT'  : $mask = \Log::MAX(FF_ALERT);  break;
	                    case 'FF_EMERG'  : $mask = \Log::MAX(FF_EMERG);  break;
	                    default: $mask = \Log::MAX(FF_INFO); break;
	                }
	            } else {
	                $mask = \Log::MAX(FF_INFO);
	            }
	            
            	$this->logs[$logname] = &\Log::singleton($logdata['type'],$logDir,$logname);
            	$this->logs[$logname]->setMask($mask);
            	$this->logs['default']->log(
            		"Initialized logger \"{$logname}\" with mask {$logdata['mask']}",FF_DEBUG);
	        } 
    	} else {
    		$this->noinit = true;
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
        
        if (strtolower($which) == 'default') {
            return $this;
        } else {
            if (isset($this->logs[$which])) {
                return $this->logs[$which];
            } else {
                throw new Exception(
                	"Received request to log to undefined logger '{$which}' ");
            }
        }
    }
    
    /**
     * log
     * 
     * Log a message to *all* managed log interfaces
     * 
     * @param string The message to log
     * @param integer The log level
     * @return void
     */
    public function log($message,$level = FF_INFO) {
    	if (!$this->noinit) {
    		
	        foreach ($this->logs as $log) {
	            $log->log($message,$level);
	        }
    	}
    }
}
?>