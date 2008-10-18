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
 
 class FProject {
 	/*
 	 * ROOT_DIRECTORY
 	 *  This is the physical location of the root directory
 	 *  on the server's filesystem.
 	 */
 	const ROOT_DIRECTORY = '/path/to/project/root';
 	/*
 	 * DB_ENGINE
 	 *  Specifies which datasource to use when connecting. Please
 	 *  note that you *must* define a corresponding class 
 	 *  in database.config.php. See database.config.php for more 
 	 *  information.
 	 */
 	const DB_ENGINE  = "MDB2";
 	/*
 	 * DEBUG_LEVEL
 	 * 	2: Verbose output, benchmarks, debug info. 
 	 * 	1: Error messages only, may still contain sensitive information
 	 *     not suitable for a production environment.
 	 *  0: No errors or warnings, suitable for a production environment.
 	 */
 	const DEBUG_LEVEL = 2;
 	
 	/*
 	 * DEFAULT_LANGUAGE
 	 *  The language code for the default language to display pages in.
 	 *  This can be overridden in your controllers to serve specific 
 	 *  views in other languages.
 	 * I18N_ENABLED 
 	 *  If this is set to false, no translation is done. Conversly, it
 	 *  must be set to true if you wish to set language content dynamically.
 	 */
 	const I18N_ENABLED     = false;
 	const DEFAULT_LANGUAGE = 'en-us';
 	
 	/*
 	 * DEFAULT_VIEW
 	 *  The default view that will be invoked when no view is explicitly
 	 *  specified in the Request URI. For example, in the case of a 
 	 *  Request URI http://sitename.com/blog, the 'blog' controller would
 	 *  be invoked with the default view.
 	 */
 	 const DEFAULT_VIEW = 'index';
 	 
 	/*
 	 * GOOGLE_ANALYTICS_CODE
 	 *  If Google Analytics is used to gather usage data about your site,
 	 *  you can include the unique code here. Note that the analytics
 	 *  javascript will only be included if both GOOGLE_ANALYTICS_CODE and
 	 *  GOOGLE_ANALYTICS_SITE_BASE have non-empty values *and* DEBUG_VALUE
 	 *  is set to 0 (production mode). 
     */
	 const GOOGLE_ANALYTICS_CODE = '';

 	/*
 	 * GOOGLE_ANALYTICS_SITE_BASE
 	 * 
 	 */
	 const GOOGLE_ANALYTICS_SITE_BASE = '';
 }
?>