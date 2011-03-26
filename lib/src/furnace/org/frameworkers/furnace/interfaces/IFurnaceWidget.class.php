<?php
namespace org\frameworkers\furnace\interfaces;

interface IFurnaceWidget {
	
	public function __construct($request,&$response);
	
	public function set($key,$value);
	
	public function render();
	
}