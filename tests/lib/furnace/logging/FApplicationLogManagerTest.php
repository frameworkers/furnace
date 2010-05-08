<?php
require_once('Log.php');
require_once 'PHPUnit/Framework.php';
require_once 'src/etc/globals.inc.php';
require_once 'src/lib/furnace/config/FApplicationConfig.class.php';
require_once 'src/lib/furnace/logging/FApplicationLogManager.class.php';

class FApplicationLogManagerTest extends PHPUnit_Framework_TestCase {
    
    private $applicationConfig;
    
    protected function setUp() {
        $logConfigData = array(
        
            "console" => array("type" => "console", "mask" => "DEBUG")
        );
        
        $this->applicationConfig = new FApplicationConfig(array('logging' => $logConfigData));
    }
    
    /**
     * Ensure that the default log is being properly set up
     */
    public function testDefaultLogSetup() {
        $lm = new FApplicationLogManager($this->applicationConfig);   
        $this->assertEquals(true, $lm->getLog() instanceof Log);
    }
    
    public function testConsoleLogSetup() {
        $lm = new FApplicationLogManager($this->applicationConfig);
        $this->assertEquals(true, $lm->getLog('console') instanceof Log);
    }
    
    /**
     * @depends testDefaultLogSetup
     */
    public function testDefaultLog() {
        $lm = new FApplicationLogManager($this->applicationConfig);
        $defaultLog = $lm->getLog();
        $defaultLog->log('*test* Logging to Furnace default log');
    }
    
    public function testConsoleLog() {
        $lm = new FApplicationLogManager($this->applicationConfig);
        $consoleLog = $lm->getLog('console');
        $consoleLog->log('*test* Logging to the console');
    }
}
    
?>