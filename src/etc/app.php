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
 * Modified significantly on Mar 14, 2009
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

 /* INCLUDE REQUIRED FILES */
 require('../lib/furnace/Furnace.class.php');
 require('../lib/furnace/config/FApplicationConfig.class.php');
 require('../lib/furnace/request/FApplicationRequest.class.php');
 require('../lib/yaml/spyc-0.4.1.php');
 require('Log.php');

 /* LOAD GLOBAL FUNCTIONS, STRUCTURES, AND DEFINITIONS */
 include('globals.inc.php');
 
 /* LOAD APPLICATION CONFIGURATION */
 $config  = new FApplicationConfig(Furnace::yaml(FF_CONFIG_FILE));
 
 /* START UP THE APPLICATION LOG MANAGER */
 $_logmgr = new FApplicationLogManager($config);
 _log()->log("Processing request: {$_SERVER['REQUEST_URI']} from {$_SERVER['REMOTE_ADDR']}");
 
 /* CREATE A REQUEST OBJECT */
 $request = new FApplicationRequest($_SERVER['REQUEST_URI']);

 /* INITIALIZE FURNACE */
 $furnace  = new Furnace($config);
 
 /* PROCESS THE REQUEST OBJECT */
 $response = $furnace->process($request);
 
 /* SEND THE RESPONSE */
 $furnace->send($response);
 
 /* CLEAN UP */
 unset($furnace);
 
 /* EXIT */
 exit();
?>