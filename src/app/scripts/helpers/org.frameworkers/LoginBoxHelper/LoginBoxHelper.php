<?php
class LoginBoxHelper extends FPageFragment {
    
    private $errors     = array();
    private $successURI = '/';
    
    public function __construct($controller,$successURI) {
        parent::__construct($controller,dirname(__FILE__));
        $this->successURI = $successURI;
        
        if ($this->controller->form  
            && isset($this->controller->form['username']) 
            && isset($this->controller->form['password'])) {
            $this->processLogin();
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
			$this->controller->set('lb_loginError',true);
		}
	}

    public function render() {
        return $this->getView('LoginBox');
    }
}
?>