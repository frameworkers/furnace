<?php
/*
 * frameworkers_furnace
 * 
 * fuel.php
 * Created on Oct 18, 2008
 * 
 * Based on and replaces:
 * AppEntrance.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

 /* LOAD GLOBAL FUNCTIONS */
 include('globals.inc.php');
 
 /* INITIALIZE FURNACE */
 include('../lib/furnace/Furnace.class.php');
 $furnace = new Furnace('fuel');
 
/* PROCESS A REQUEST  */
if (checkFuelLogin()) {
 	$furnace->process($_SERVER['REQUEST_URI']);
} else {
	$furnace->process("/fuel/login");
}
 
/* CLEAN UP */
unset($furnace);
 
/* EXIT */
exit();
 
function checkFuelLogin() {
 	if (isset($_SESSION['fuel']['loggedin']) && $_SESSION['fuel']['loggedin']) {
 		return true;
 	} else {
 		return false;
 	}
}
?>