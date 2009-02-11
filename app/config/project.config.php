<?php
/*
 * frameworkers_furnace
 * 
 * project.config.php
 * Created on Jul 24, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */

/*
 * ROOT_DIRECTORY
 *  This is the physical location of the root directory
 *  on the server's filesystem.
 */
$fconfig_root_directory = dirname(dirname(dirname(__FILE__)));

/*
 * ROOT PASSWORD
 *   Setting this password will allow you to manage your application
 * using the FUEL web-based management interface. If this password
 * is blank, you will not be able to use FUEL.
 */
$fconfig_root_password  = '/';

/*
 * URL_BASE
 * The base url (default is /) to expect at the beginning of 
 * each request. This is particularly helpful when your 
 * webapp does not reside at the site root (ie: in a sub-
 * directory like: http://example.com/apps/myapp). Include a
 * trailing '/'.
 */
$fconfig_url_base       = '';

/*
 * DB_ENGINE
 *  Specifies which datasource to use when connecting. Please
 *  note that you *must* define a corresponding class 
 *  in database.config.php. See database.config.php for more 
 *  information.
 */
$fconfig_db_engine      = 'MDB2';

/*
 * DEBUG_LEVEL
 * 	2: Verbose output, benchmarks, debug info. 
 * 	1: Error messages only, may still contain sensitive information
 *     not suitable for a production environment.
 *  0: No errors or warnings, suitable for a production environment.
 */
$fconfig_debug_level    = 2;

/*
 * PRODUCTION_DSN
 * This dsn will be used when DEBUG_LEVEL is 0.
 */
$fconfig_production_dsn = "mysql://user:password@server/dbname";

/*
 * DEBUG_DSN
 * This dsn will be used when DEBUG_LEVEL is greater than 0.
 */
$fconfig_debug_dsn      = "mysql://user:password@server/dbname";

/*
 * currently unused:
 *
 * $fconfig_i18n_enabled
 * $fconfig_default_language
 * 
 */
?>