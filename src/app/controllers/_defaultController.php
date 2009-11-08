<?php
/*
 * frameworkers_furnace
 * 
 * _DefaultController.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org.
 * http://www.frameworkers.org
 */
 class _DefaultController extends Controller {
 	
 	public function index() {
 		// Remove the following line when you are ready to
 		// develop your application. The corresponding view
 		// for this controller function is the file:
 		// /app/views/_default/index.html.
 		$this->internalRedirect("/_furnace/start");
 	}
 	
 	public function login() {
 		$this->loadModule('org.frameworkers.core.LoginBox');
 		$lb = new LoginBox($this);
 		$this->set('loginbox',$lb->getContents());
 	}
 	
 	public function logout() {
 	    FSessionManager::dologout();
 	    $this->redirect("/");
 	}
 	
    public function forgotpass($key='') {
 	    if ($this->form) {
 	        if ($this->form['step'] == 'keyReady') {
 	            
 	            // Validate all provided information
 	            $valid = true;
 	            $pw1 = $this->form['pwrNewPassword'];
 	            $pw2 = $this->form['pwrNewPassword2'];
 	            $em  = $this->form['pwrEmailAddress'];
 	            $k   = $this->form['pwrRequestKey'];
 	            $user= FAccountManager::EmailAddressExists($em,true);
 	            
 	            if (false === $user) {
 	                $this->flash("No match for provided email address","error");
 	                $valid = false;
 	            }
 	            
 	            if ($user && ($user->getNewPasswordKey() != $k || $user->getNewPasswordKey() == '')) {
 	                $this->flash("Key does not match stored value","error");
 	                $valid = false;
 	            }
 	            
 	            if ($pw1 != $pw2) {
 	                $this->flash("Passwords do not match","error");
 	                $valid = false;
 	            }
 	            
 	            if (!isset($pw1[3])) {
 	                $this->flash("Password must be at least 4 characters","error");
 	                $valid = false;
 	            }
 	            
 	            if ($valid) {
 	                FAccountManager::ChangePassword($user,$pw1);
 	                $this->flash("Your password has been successfully changed");
 	                $this->redirect("/login");       
 	            }
 	        
 	        } else if ($this->form['step'] == 'sendInstructions') {
     	        if (false !== ($k = FAccountManager::GenerateForgotPasswordKey($this->form['pwrEmailAddress']))) {
     	            $appName = _furnace()->config['app_name'];
     	            $appURL  = _furnace()->config['app_url'];
     	            $to = $this->form['pwrEmailAddress'];
     	            $subject = "[{$appName}] Password Reset Instructions";
     	            $message = "Hello,\r\nYou recently requested password reset instructions for your {$appName} account.\n\n"
     	                . "Please click on the following link and provide the information requested.\n\n"
     	                . "<a href=\"{$appURL}/forgotpass/{$k}\">{$appURL}/forgotpass/{$k}</a>\n\n"
     	                . "If you can not click on the link, please copy and paste the following into your browser's address bar:\n\n"
     	                . "{$appURL}/forgotpass/{$k}\n\n"
     	                . "This email was automatically generated, please do not respond to this address.";
     	            
     	            $message = wordwrap($message,70);
     	            $headers  = 'MIME-Version: 1.0' . "\r\n";
     	            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
     	            mail($to,$subject,$message,$headers);     	            
     	            
     	            $this->set('instructionsSent',true);
     	            $this->set('email',$this->form['pwrEmailAddress']);
     	        } else {
     	            $this->flash("No match for provided email address","error");
     	        } 
 	        } 
 	    }
 	    if (!empty($key) ) {
 	        $this->set('keyReady',true);
 	            $this->set('key',$key);
 	    }
 	}
 	
 }
?>
