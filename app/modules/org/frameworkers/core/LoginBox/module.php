<?php
	class LoginBox extends FPageModule {
		
		// Variable: successURI
		// The URI of the location to redirect to when a user has
		// successfully logged in
		private $successURI;
		
		public function __construct(&$controller,$successURI='/') {
			// Initialize the object
			parent::__construct($controller,dirname(__FILE__));
			
			// Set the URI to navigate to on successful login
			$this->successURI = $successURI;
			
			// Start out with a clean slate
			$this->controller->set('loginError',false);
			
			// Process any POSTed data
			if ($this->controller->form) {
				$this->processLogin();
			}
		}
		
		public function getContents() {
			// return the html to display
			return $this->getView("LoginBox");
		}
		
		private function processLogin() {
			// Obtain the data
			$data =& $this->controller->form;
			
			// Make sure required data is present
			if ($data['username'] == '' || $data['password'] == '') {
				$this->controller->set('errorNoInfo',true);
			}
			// Attempt to log in
			if(FSessionManager::doLogin()) {
				if (isset($_SESSION['afterLogin'])) {
					$goTo = $_SESSION['afterLogin'];	// save the URI
					unset($_SESSION['afterLogin']);		// clear from the session
					$this->controller->redirect($goTo);	// redirect
				} else {
					$this->controller->redirect($this->successURI);
				}
			} else {
				$this->controller->set("loginError",true);
			}
		}
	}
?>