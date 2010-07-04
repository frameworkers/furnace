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
        
        foreach ($applicationConfig->data['logging'] as $logname => $logdata) {
            // Process environment variables in paths
            if (isset($logdata['path'])) {
                $realPath = str_replace('$LOGDIR',FF_LOG_DIR,$logdata['path']);
            } else {
                $realPath = '';
            }
            // Process mask criteria
            if (isset($logdata['mask'])) {
                switch ($logdata['mask']) {
                    case 'FF_DEBUG'  : $mask = Log::MAX(FF_DEBUG);  break;
                    case 'FF_INFO'   : $mask = Log::MAX(FF_INFO);   break;
                    case 'FF_NOTICE' : $mask = Log::MAX(FF_NOTICE); break;
                    case 'FF_WARN'   : $mask = Log::MAX(FF_WARN);   break;
                    case 'FF_ERROR'  : $mask = Log::MAX(FF_ERROR);  break;
                    case 'FF_CRIT'   : $mask = Log::MAX(FF_CRIT);   break;
                    case 'FF_ALERT'  : $mask = Log::MAX(FF_ALERT);  break;
                    case 'FF_EMERG'  : $mask = Log::MAX(FF_EMERG);  break;
                    default: $mask = Log::MAX(FF_INFO); break;
                }
            } else {
                $mask = Log::MAX(FF_INFO);
            }
            
            /* Ensure that the log directory is writable by the server. 
             */
            if (!is_writable($realPath)) {
            	die(
            		"<strong>Furnace:</strong> Insufficient permissions to write to destination for log '{$logname}'. \r\n<br/>"
            		."Ensure that your application log directory (/path/to/app/data/logs) is writable by the server:<br/><br/>"
            		."<code>chgrp -R apache_username /path/to/app/data/logs;</code><br/>"
            		."<code>chmod -R g+w /path/to/app/data/logs;</code>"
            	);
            } else {
            	$this->logs[$logname] = &Log::singleton($logdata['type'],$realPath,$logname);
            	$this->logs[$logname]->setMask($mask);
            	$this->logs['default']->log(
            		"Initialized logger \"{$logname}\" with mask {$logdata['mask']}",FF_DEBUG);
            }
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
        foreach ($this->logs as $log) {
            $log->log($message,$level);
        }
    }
}
?>