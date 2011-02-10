<?php
use org\frameworkers\furnace\Furnace;
use org\frameworkers\furnace\config\Config;


Config::Set('applicationName'     ,'Furnace - Rapid PHP Application Development');
Config::Set('applicationPort'     , 80);

// What is the theme to use by default?
Config::Set('theme'               ,'furnace');

// What is the layout file to use by default?
// This should be a filename (including the 
// file extension, e.g.: 'default.html')
Config::Set('layoutFile'          ,'default.html');

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

