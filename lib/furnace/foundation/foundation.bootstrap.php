<?php
/*
 * frameworkers_furnace
 * 
 * foundation.bootstrap.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 // Include the frameworkers-core files (foundation)
 require_once("core/FBaseObject.class.php");
 require_once("core/FObjectCollection.class.php");
 require_once("core/FAccount.class.php");
 require_once("core/FAccountManager.class.php");
 require_once("core/FSessionManager.class.php");
 require_once("database/".FProject::DB_ENGINE."/FDatabase.class.php");
 require_once("exceptions/FException.class.php");
?>
