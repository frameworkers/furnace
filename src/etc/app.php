<?php
/*
 * frameworkers_furnace
 * 
 * app.php
 * Created on Oct 18, 2008
 * 
 * Based on and replaces:
 * AppEntrance.php
 * Created on Jul 24, 2008
 * 
 * Modified significantly on Mar 14, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

 /* LOAD GLOBAL FUNCTIONS */
 include('globals.inc.php');

 /* INITIALIZE FURNACE */
 include('../lib/furnace/Furnace.class.php');
 $furnace = new Furnace('app');
 
 /* PROCESS A REQUEST  */
 $furnace->process($_SERVER['REQUEST_URI']);
 
 /* CLEAN UP */
 unset($furnace);
 
 /* EXIT */
 exit();
?>