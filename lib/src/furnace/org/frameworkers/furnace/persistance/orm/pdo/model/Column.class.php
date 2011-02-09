<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\model;
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
/**
 * Represents an Object-Relational model table column
 * @author andrew
 *
 */
use org\frameworkers\furnace\persistance\FurnaceType;

class Column {
	
	public $name;
	public $type;
	public $null;
	public $isPrimary;
	public $key;
	public $extra;
	
	public function __construct($name,$type,$null,$isPrimary,$extra = array()) {
		$this->name  = Lang::ToColumnName($name);
		$this->type  = $type;
		$this->null  = $null;
		$this->isPrimary = $isPrimary;
		$this->key   = ($isPrimary) ? "PRIMARY" : "";
		$this->extra = $extra;
	}
}