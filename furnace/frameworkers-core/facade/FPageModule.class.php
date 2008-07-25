<?php
/*
 * frameworkers
 * 
 * FPageModule.class.php
 * Created on June 27, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FPageModule
  * An extension of <FPage> that permits independent code modules
  * to be registered with Tadpole (and thus inserted into a view)
  * just like an ordinary variable.
  * 
  * Extends:
  * 
  *  <FPage>
  */
class FPageModule extends FPage {
	
	public function __construct($template,&$container,$state=array()) {
		parent::__construct($template,$state);
		
		$this->page_data =&$container->page_data;
		$this->langcode  = $container->langcode;
	}

 	public function render($bEcho = false) {
 		$this->compile();		// call FPage::compile
 		if ($bEcho) {
 			echo $this->getContents();
 		} else {
 			return $this->getContents();	
 		}
 	}
}
?>