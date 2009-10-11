<?php
/*
 * frameworkers_furnace
 * 
 * foundry.php
 * Created on Jun 22, 2009
 * 
 * Based on and replaces:
 * fuel.php
 * Created on Oct 18, 2008
 * 
 * Based on and replaces:
 * AppEntrance.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008-2009 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
/* LOAD GLOBAL FUNCTIONS */
include('globals.inc.php');

/* INITIALIZE FURNACE */
include('../lib/furnace/Furnace.class.php');
$furnace = new Furnace('foundry');
 
/* PROCESS A REQUEST  */
if (checkFoundryLogin()) {
 	$furnace->process($_SERVER['REQUEST_URI']);
} else {
	$furnace->process("/_furnace/login");
}
 
/* CLEAN UP */
unset($furnace);
 
/* EXIT */
exit();

/* UTILITY FUNCTION: Check Login */
function checkFoundryLogin() {
 	if (isset($_SESSION['foundry']['loggedin']) && $_SESSION['foundry']['loggedin']) {
 		return true;
 	} else {
 		return false;
 	}
}
?>