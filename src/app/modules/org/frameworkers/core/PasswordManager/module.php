<?php
class PasswordManager extends FPageModule {
	
	protected $selfURI  = '';		// The uri for the page where the content
									// is to be rendered
	protected $loginURI = '';		// The uri for the application log-in page
	protected $form     = array();	// The posted form data
	
	public function __construct($selfURI,$loginURI='/login',&$form=array()) {
		parent::__construct(dirname(__FILE__) . "/views/PasswordManager.html");
		
		// Initialize local variables
		$this->requireLogin($loginURI);
		$this->selfURI = $selfURI;
		$this->loginURI= $loginURI;
		$this->form    = $form;
		
		// Process any user submitted form actions
		$this->processAction();
	}
	
	public function render() {	
		// pass the user's data to the view
		$this->set('user',_user());
		// return the rendered module
		return parent::render();
	}
	
	protected function processAction() {
		// CHANGE PASSWORD ACTION
		if (isset($this->form['changepw'])) {
			$this->changePassword();
		}
	}
	
	protected function changePassword() {
		$pw1 =& $this->form['pw1'];
		$pw2 =& $this->form['pw2'];

		// Test for mismatch
		if ($pw1 !== $pw2) {
			$this->flash("The passwords did not match. Please try again...","error");
			header("Location: {$this->selfURI}");
			exit();
		}
		
		// Test  for zero-length
		if ($pw1 == '' || $pw2 == '') {
			$this->flash("Empty passwords are not allowed. Please try again...","error");
			header("Location: {$this->selfURI}");
			exit();
		}
		
		// Store the new password
		$u = _user()->setPassword(md5($pw1));
		
		// Inform the user of successful password change
		$this->flash("Password successfully changed. Please log in again.");
		FSessionManager::doLogout();
		header("Location: {$this->loginURI}");
		exit();
	}
}
?>