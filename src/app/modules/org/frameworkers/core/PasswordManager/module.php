<?php
class PasswordManager extends FPageModule {

	protected $loginURI = '';		// The uri for the application log-in page
	
	public function __construct(&$controller,$loginURI="/login") {
		// Require logged in user
		if (false == FSessionManager::checkLogin()) {
		    throw new FException("User must be logged in first");
		}
	    
	    // Initialize the object
		parent::__construct($controller,dirname(__FILE__));
		$this->loginURI = $loginURI;
	}
	
	public function getContents() {
		// return the html to display
		$this->controller->set("user",_user());
		return $this->getView("PasswordManager");
	}
	
	public function changePassword() {
		// Create a flag to test for errors
		$errorsEncountered = false;
		
		if (! isset($this->controller->form['doChangePassword'])) {return false;}
		
		// Ensure that required data has been provided
		if (! isset($this->controller->form['pw1']) || ! isset($this->controller->form['pw2'])) {
			$this->controller->flash("Insufficient details provided. Please try again...","error");
			$errorsEncountered = true;
		}
		
		// Store the provided data with a more usable name :)
		$pw1 =& $this->controller->form['pw1'];
		$pw2 =& $this->controller->form['pw2'];

		// Test for mismatch between provided values
		if ($pw1 !== $pw2) {
			$this->controller->flash("The passwords did not match. Please try again...","error");
			$errorsEncountered = true;
		}
		
		// Test  for zero-length
		if ($pw1 == '' || $pw2 == '') {
			$this->controller->flash("Empty passwords are not allowed. Please try again...","error");
			$errorsEncountered = true;
		}
		
		// If no errors found, store the new password
		if (! $errorsEncountered) {
			FAccountManager::ChangePassword(_user(),$pw1);
			
			// Inform the user of successful password change
			FSessionManager::doLogout(true);	// logout, but preserve the session
			$this->controller->flash("Password successfully changed. Please log in again.");
			header("Location: {$this->loginURI}");
			exit();
		}
	}
}
?>