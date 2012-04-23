<?php
use \furnace\core\Furnace;
use \furnace\core\Config;
use \furnace\utilities\LogLevel;
use \furnace\routing\Router;
use \furnace\connections\Connections;

/******************************************************************************
 ABOUT THIS FILE
 ******************************************************************************
 
 This is your application's main configuration file. It consists of a number of 
 sections, each delineated by a header like the one above. Furnace has been 
 designed so that, as much as possible, there is only a single location where
 configuration information is specified. The only exception to this is in cases
 where third-party modules are in use. In this case, you may also need to modify
 configuration settings in the module's config.php file.
 */


/******************************************************************************
 APPLICATION ENVIRONMENT SETTINGS
 ******************************************************************************
 High-level information about the application environment in general.
 */
Config::Set('environment'      , F_ENV_DEVELOPMENT);  // or F_ENV_PRODUCTION
Config::Set('env.logging.file' , F_APP_PATH . '/data/logs/app.log');
Config::Set('env.logging.level', LogLevel::DEBUG); 

/******************************************************************************
 APPLICATION SETTINGS
 ******************************************************************************
 Application Meta-data
 */
Config::Set('app.author'      ,'Frameworkers.org');
Config::Set('app.title'       ,'My Application');
Config::Set('app.version'     ,'1.0.0');
Config::Set('app.releaseDate' ,date('d.M.Y '));
Config::Set('app.copyright'   ,'Copyright (c) ' . date('Y ') . Config::Get('app.author'));

/******************************************************************************
 APPLICATION URLS
 ******************************************************************************
 Common Application URLs
 */
Config::Set('app.url.license' ,'/license');
Config::Set('app.url.terms'   ,'/terms');
Config::Set('app.url.privacy' ,'/privacy');
Config::Set('app.url.login'   ,'/auth/login');
Config::Set('app.url.logout'  ,'/auth/logout');

/******************************************************************************
 APPLICATION ERROR MESSAGES
 ******************************************************************************
 Messages to display when a response results in the corresponding HTTP status
 code being returned. 
 */
Config::Set('message.400'     ,"Required parameters were either invalid or missing from your request");
Config::Set('message.403'     ,"You are not authorized to access the requested resource.");
Config::Set('message.404'     ,"The requested resource could not be found.");
Config::Set('message.405'     ,"The HTTP method used is not supported.");
Config::Set('message.500'     ,"An error occurred on the server while processing your request.");

/******************************************************************************
 APPLICATION THEME SETTINGS
 ******************************************************************************
 Configure global application theme information.
 */
Config::Set('app.themes.dir'  , F_APP_PATH . '/themes/');
Config::Set('app.themes.url'  , F_URL_BASE . '/themes/');
Config::Set('app.theme'       ,'default');
Config::Set('default.layout'  ,'default.php');
Config::Set('template.engine' ,false);
Config::Set('view.extension'  ,'.php');

/******************************************************************************
 APPLICATION SESSION SETTINGS
 ******************************************************************************
 Specify PHP session information.
 */
Config::Set('use.sessions'    , true);
Config::Set('sess.auth.key'   ,'_auth');
Config::Set('sess.flashes.key','_flashes');

/******************************************************************************
 APPLICATION CONNECTION SETTINGS
 ******************************************************************************
 Connections to services (e.g.: database, LDAP, MongoDB). Each connection has a
 unique label, which can be used to reference the connection in your controller
 logic. 
 */

/* Example Database Connection:
Connections::Add(
	"default", new \PDO('mysql:host=localhost;dbname=mydb','username','password',
		array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION))
);
*/

/******************************************************************************
 APPLICATION ROUTE SETTINGS
 ******************************************************************************
 Define custom routing behavior.
 */

// Note: Custom routes should be added here, and will be processed in the order
// specified. To maintain the Furnace default behavior for all routes not 
// explicitly overridden, ensure the "Furnace Default Routing Behavior" routes
// are specified last.

// Furnace Default Routing Behavior
Router::Connect("/:controller/:handler");
Router::Connect("/:handler", array("controller" => "default"));


/******************************************************************************
 APPLICATION MODULE SETTINGS
 ******************************************************************************
 Make installed third-party modules globally available
 */

// Note: Third party modules should be loaded here.
// Example: Config::LoadModule('Foo');


/******************************************************************************
 CUSTOM APPLICATION CONFIGURATION SETTINGS
 ******************************************************************************
 Any additional configuration settings needed by your controller logic
 */
 
// Note: All custom configuration settings should be specified here.
// Example: Config::Set('foo','bar');

