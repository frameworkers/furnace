<?php
/**
 * Foundry Route Definitions
 * 
 */
use org\frameworkers\furnace\action\Router;

// Application Home Page
Router::Connect('/');

// ORM Model Routes
Router::Connect('/models/orm/:handler',array('controller'=>'ORMModel'));
Router::Connect('/models/orm',array('controller'=>'ORMModel','handler'=>'index'));

// Default Behaviors
Router::Connect("/:controller/:handler");
Router::Connect("/:handler", array("controller" => "default"));