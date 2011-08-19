<?php
use \furnace\core\Furnace;
use \furnace\core\Config;
use \furnace\utilities\LogLevel;
use \furnace\routing\Router;
use \furnace\connections\Connections;


// APPLICATION ENVIRONMENT
// ============================================================================
Config::Set('environment'      , F_ENV_DEVELOPMENT);  // or F_ENV_PRODUCTION
Config::Set('env.logging.file' , F_APP_PATH . '/data/logs/app.log');
Config::Set('env.logging.level', LogLevel::DEBUG); 

// APPLICATION SETTINGS
// ============================================================================
Config::Set('app.author'      ,'Frameworkers.org');
Config::Set('app.title'       ,'My Application');
Config::Set('app.version'     ,'1.0.0');
Config::Set('app.releaseDate' ,date('d.M.Y '));
Config::Set('app.copyright'   ,'Copyright (c) ' . date('Y ') . Config::Get('app.author'));

// APPLICATION URLS
// ============================================================================
Config::Set('app.url.license' ,'/license');
Config::Set('app.url.terms'   ,'/terms');
Config::Set('app.url.privacy' ,'/privacy');
Config::Set('app.url.login'   ,'/auth/login');
Config::Set('app.url.logout'  ,'/auth/logout');

// APPLICATION ERROR MESSAGES
// ============================================================================
Config::Set('message.403'     ,"Move along, this is not the page you're looking for");
Config::Set('message.404'     ,"Whoop, there it ain't.");
Config::Set('message.500'     ,"It takes real skill to mess things up this badly");

// APPLICATION THEME SETTINGS
// ============================================================================
Config::Set('app.theme'       ,'default');
Config::Set('default.layout'  ,'default.php');
Config::Set('template.engine' ,false);
Config::Set('view.extension'  ,'.php');

// APPLICATION SESSION KEYS
// =============================================================================
Config::Set('sess.auth.key'   ,'_auth');
Config::Set('sess.flashes.key','_flashes');

// APPLICATION CONNECTION SETTINGS
// =============================================================================

/*
Connections::Add(
	"default", new \PDO('mysql:host=localhost;dbname=mydb','username','password',
		array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION))
);
*/


// AUTHENTICATION SETTINGS
// =============================================================================
Config::Set('use.sessions'    , true);
Config::Set('auth.provider'   ,'\\furnace\\auth\\providers\\DefaultAuthenticationProvider');
Config::Set('auth.options'    ,array(
      "connection"       => 'default',
      "table"            => 'user',
      "identityColumn"   => 'username',
	  "credentialColumn" => 'password',
	  "identifierColumn" => 'id',
      "additionalColumns"=> array(),
	  "passwordSalt"     => '389c]8B3nfDChlsd8n3^239s8r3))9')
);


// APPLICATION ROUTING TABLE
// =============================================================================


// CUSTOM ROUTES MAY BE ADDED HERE IF NEEDED


// Furnace Default Routing Behavior
Router::Connect("/:controller/:handler");
Router::Connect("/:handler", array("controller" => "default"));

