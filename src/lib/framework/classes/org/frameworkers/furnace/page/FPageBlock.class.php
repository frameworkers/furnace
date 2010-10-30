<?php
namespace org\frameworkers\furnace\page;

abstract class FPageBlock {
	
	public abstract static function render($controller, $zone = 'content', $data = false);
	
}