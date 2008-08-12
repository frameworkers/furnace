<?php
	class LoginBox extends FPageModule {
		
		// Variable: successURI
		// The URI of the location to redirect to when a user has
		// successfully logged in
		private $successURI;
		
		public function __construct($successURI='/') {
			parent::__construct(dirname(__FILE__) . "/views/LoginBox.html");
		}
		
		public function render() {
			if (isset($_POST['username']) && isset($_POST['password'])) {
				$this->processLogin($_POST);
			}
			
			// return the rendered module
			return parent::render();
		}
		
		private function processLogin(&$data) {
			// Make sure required data is present
			if ($data['username'] == '' || $data['password'] == '') {
				$this->register('errorNoInfo',true);
			}
			// Attempt to log in
			if(FSessionManager::doLogin()) {
				header("Location: {$this->successURI}");
			} else {
				$this->register("loginError",true);
			}
		}
	}
?>