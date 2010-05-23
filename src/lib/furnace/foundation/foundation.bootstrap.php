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
 require("core/FAccountCollection.class.php");
 require("core/FAccountManager.class.php");
 require("core/FSessionManager.class.php");
 require("exceptions/FException.class.php");
 require("validation/FValidator.class.php");
 
 require("core/../../datasources/FQuery.class.php");
 require("core/../../datasources/FResult.class.php");
 require("core/../../datasources/FResultFormatter.class.php");
 require("core/../../datasources/FDatasourceDriver.class.php");
 require("core/../../datasources/drivers/FMdb2Driver.class.php");
 require("core/../../datasources/formatters/FObjectResultFormatter.class.php");
 require("core/../../datasources/formatters/FArrayResultFormatter.class.php");
 require("core/../../datasources/formatters/FSingleValueResultFormatter.class.php");
?>
