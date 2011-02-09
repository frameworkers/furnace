<?php
namespace org\frameworkers\furnace\persistance\orm\pdo;
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
 * A data structure describing an RDBMS column
 * 
 * 
 */
class ColumnDescriptor {
 	
	public $name;
	
	public $type;
	
	public $bPrimaryKey;
	
	public $bNullable;
	
	public $defaultValue = '';
	
	public $options = array();
	
	public function __construct($name,$type,$bNullable = true, $bPrimaryKey = false, $defaultValue = '', $options = array()) {
		$this->name         = $name;
		$this->type         = $type;
		$this->bNullable    = $bNullable;
		$this->bPrimaryKey  = $bPrimaryKey;
		$this->defaultValue = $defaultValue;
		$this->options      = $options;
	} 	
}