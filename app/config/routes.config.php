<?php
/**
 * This file contains route definitions that help determine how requests
 * are marshalled to controller handlers.
 * 
 * Notes:
 *  - Routes are traversed top to bottom until a match is found
 *  - You can specify variables by prefixing them with a colon (:)
 */
use org\frameworkers\furnace\action\Router;

// Application Home Page
Router::Connect('/');

// Furnace Default Behavior
Router::Connect("/:controller/:handler");
Router::Connect("/:handler", array("controller" => "default"));