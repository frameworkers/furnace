<?php
/*
 * frameworkers
 * 
 * FPage.class.php
 * Created on July 24, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

class FPageLayout extends FPage {

	public function __construct($templatePath) {
		$c = file_get_contents($templatePath);
		parent::__construct($c);
	}
}
?>