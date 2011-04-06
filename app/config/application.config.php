<?php
use org\frameworkers\furnace\Furnace;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\extension\Extension;

// What is the debug status of this application?
// 1 = Debug/Development mode
// 0 = Production mode
Config::Set('debugMode', 1);

Config::Set('applicationName'     ,'Furnace - Rapid PHP Application Development');
Config::Set('applicationPort'     , 80);

// What is the theme to use by default?
Config::Set('theme'               ,'default');

// What is the layout file to use by default?
// This should be a filename (including the 
// file extension, e.g.: 'default.html')
Config::Set('defaultLayoutFile'   ,'default.html');

// What is the default response type?
Config::Set('defaultResponseType' ,'html');

// What is the file extension for html view files? 
Config::Set('htmlViewFileExtension','.html');

/**
 * Foundry
 */
Config::Set('foundryMagicUrlBase','foundry');


/**
 * Environment
 */
Config::Set('applicationControllersDirectory',dirname(dirname(__FILE__)). '/controllers');
Config::Set('applicationModelsDirectory',dirname(dirname(__FILE__)). '/models');
Config::Set('applicationViewsDirectory', dirname(dirname(__FILE__)). '/views');
Config::Set('applicationThemesDirectory',dirname(dirname(__FILE__)). '/assets/themes');


Config::Set('applicationLoginUrl','/login');


/**
 * Extensions
 */
Extension::Register(FURNACE_APP_PATH .'/extensions/flame');
