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
 * A data structure describing a RDBMS table
 *
 */
class TableDescriptor {
	
	public $name;
	
	public $tableExists = false;
	
	protected $columns;
	
	public function addColumn($name,$type,$nullable = true, $bPrimaryKey = false, $defaultValue = '', $options = array()) {
		$this->columns[$name] = 
			new ColumnDescriptor($name,$type,$nullable,$bPrimaryKey,$defaultValue,$options);
		
	}
	
	public function getColumns() {
		return $this->columns;
	}
}