<?php
/*
 * frameworkers
 * 
 * loginbox.module.php
 * Created on June 28, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /**
  * This is an example of a module. Modules attempt to
  * capture the design and implementation of a self-contained
  * piece of a project.  Just like a page, A module consists 
  * of a controller (php class) and a view (html file). You 
  * are looking at the controller.
  * 
  * In this example, the module captures the process of logging
  * in to a web application. The view (html file) contains the
  * code to display a login box, and the controller (php class)
  * contains the code necessary to process logins.
  * 
  * A module is invoked from the containing page's controller,
  * passing '$this' as the 'container' parameter:
  * 
  * $lbm = new LoginBoxModule($args,$this,$state);
  * 
  * A module can easily be registered with Tadpole, allowing 
  * its view to be displayed anywhere on the page, just like a 
  * normal Tadpole variable:
  * 
  * $this->register("loginbox",$lbm->render());
  * 
  * The view file for the page containing the module might then 
  * look something like:
  * ...
  * <div class="login">
  *   [var.loginbox]
  * </div> 
  * ...
  * 
  * 
  */
class LoginBoxModule extends FPageModule {
	
	public function __construct($args,$container,$state=array()) {
		parent::__construct(dirname(__FILE__).'/loginbox.html',$container,$state);
		
		$this->dispatch(&$args);
	}
	
	public function processLogin($args) {
		if ($args['username'] == '' || $args['password'] == '') {
			$this->register("loginError",true);
			return;
		}
		if(FSessionManager::doLogin()) {
			header("Location: /");
		} else {
			$this->register("loginError",true);
		}
	}
}
?>