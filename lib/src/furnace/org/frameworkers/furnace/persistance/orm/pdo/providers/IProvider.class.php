<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\providers;
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
 * Interface for vendor specific operations needed by Pdo DataSource
 * instances.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
use org\frameworkers\furnace\persistance\orm\pdo\model\Table;

interface IProvider {
	
	/**
	 * Initialize with a reference back to the PDO object.
	 *
	 * @param PDO $pdo
	 */
	function init(\PDO $pdo);
	
	/**
	 * List the tables in a data source alphabetically.
	 * @return array(string) The tables in the data source
	 */
	function getTables();
	
	/**
	 * List the column names of a table alphabetically.
	 * @param string $table Table whose columns to list.
	 * @return array(string) Column names sorted alphabetically.
	 */
	function getColumns($table);

	/**
	 * Retrieve the a table's description.
	 *
	 * @param string $table Name of table.
	 * @return Table
	 */
	function describeTable($table);
	
	/**
	 * Sanity check and semantic sugar from higher level
	 * representation of table pushed down to the RDBMS
	 * representation of the table.
	 *
	 * @param string $table
	 * @param TableDescriptor $descriptor
	 */
	/*
	function cascadeTableDescriptor($table, TableDescriptor $descriptor);
	*/
	
	/**
	 * Drop a table from the data source.
	 *
	 * @param string $table Table to drop.
	 */
	function dropTable($table);
	
	/**
	 * Empty a table in the data source.
	 *
	 * @param string $table Table to drop.
	 */
	function emptyTable($table);
	
	/**
	 * Given a Table Definition, return the CREATE TABLE SQL statement
	 * in the provider's desired syntax.
	 *
	 * @param TableDescriptor $tableDescriptor
	 */
	function createTableSql(Table $definition);
}

?>