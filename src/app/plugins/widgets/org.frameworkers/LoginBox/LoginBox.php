<?php
class LoginBox extends FPageFragment {
    
    private $errors     = array();
    private $successURI = '/';
    private $banned     = false;
    
    public function __construct($controller,$successURI) {
        parent::__construct($controller,dirname(__FILE__));
        $this->successURI = $successURI;
        
        // Check if this IP address can attempt to log in
        $this->banned = $this->checkBan();
        
        // Process the login request
        if (!$this->banned && $this->controller->form  
            && isset($this->controller->form['username']) 
            && isset($this->controller->form['password'])) {
            $this->processLogin();
        }
    }
    
    private function checkBan() {
        // Flush old failed logins
        //TODO: parameterize this with something like: login_failure_timeout
        $q = "DELETE FROM `app_logs` WHERE `created` < '" 
            . date('Y-m-d g:i:s',strtotime('1 hour ago')) . "'";
            
        try {
        	_db()->rawExec($q); 
        
        	// Check if this IP can attempt to log in
        	//TODO: parameterize this with something like max_login_failures
			$q = "SELECT COUNT(*) FROM `app_logs` WHERE `ip`='{$_SERVER['REMOTE_ADDR']}' ";
			$attemptsUsed = _db()->rawQuery($q,array('type'=>'one'));
	
			if ($attemptsUsed->data >= 5) {
			    _log()->log("Attempt to log in from banned IP address "
			        . "[{$_SERVER['REMOTE_ADDR']}], username: "
			        . "{$this->controller->form['username']}",FF_NOTICE);
			}
			return ($attemptsUsed->data >= 5);
        } catch (FException $e) {
        	$this->controller->set('connectError',true);
        	_log()->log("LoginBox: login impossible: " . $e->getMessage(),FF_CRIT);
        	return false;
        }
    }

    private function processLogin() {
        $data =& $this->controller->form;
		// Make sure required data is present
		if ($data['username'] == '' || $data['password'] == '') {
			$this->controller->set('lb_errorNoInfo',true);
		}
		
		// Attempt to log in
		if(FSessionManager::doLogin()) {
			$this->controller->redirect($this->successURI);
		} else {
		    try {
		        // Store the failed attempt in the logs
    		    $q = "INSERT INTO `app_logs` (`created`,`ip`,`code`,`extra`) VALUES('".
    		            date('Y-m-d g:i:s')."',".
    		            "'{$_SERVER['REMOTE_ADDR']}',".
    		            "101,".            // Furnace "Login Failed Event" code
    		            "'{$data['username']}') ";
    		    _db()->rawExec($q);
		    } catch (FDatabaseException $e) {
		        // Silently ignore if app_logs does not exist
		    }
		    
			$this->controller->set('lb_loginError',true);
		}
	}

    public function render() {
		if ($this->banned) {
		    $this->controller->set('ip',$_SERVER['REMOTE_ADDR']);
		    return $this->getView('Banned');
		} else {
            return $this->getView('LoginBox');
		}
    }
}
?>