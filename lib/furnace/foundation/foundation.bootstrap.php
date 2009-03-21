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
 require("core/FBaseObject.class.php");
 require("core/FObjectCollection.class.php");
 require("core/FAccount.class.php");
 require("core/FAccountManager.class.php");
 require("core/FSessionManager.class.php");
 require("database/".$GLOBALS['furnace']->config['db_engine']."/FDatabase.class.php");
 require("exceptions/FException.class.php");
?>
