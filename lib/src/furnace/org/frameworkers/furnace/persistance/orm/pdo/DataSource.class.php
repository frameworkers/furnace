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
 * A PDO wrapper in the Recess PHP Framework that provides a single interface for commonly 
 * needed operations (i.e.: list tables, list columns in a table, etc).
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */

use org\frameworkers\furnace\persistance\orm\pdo\sql\SqlBuilder;
use org\frameworkers\furnace\persistance\orm\pdo\Dataset;
use org\frameworkers\furnace\persistance\cache\Cache;
use org\frameworkers\furnace\persistance\orm\pdo\model\Table;

class DataSource extends \PDO {
	const PROVIDER_CLASS_LOCATION = "org\\frameworkers\\furnace\\persistance\\orm\\pdo\\providers\\";
	const PROVIDER_CLASS_SUFFIX   = 'Provider';
	const CACHE_PREFIX            = 'Furnace::Pdo::';
	
	protected $provider = null;
	
	protected $cachePrefix;
	
	/**
	 * Creates a data source instance to represent a connection to the database.
	 * The first argument can either be a string DSN or an array which contains
	 * the construction arguments.
	 *
	 * @param mixed $dsn String DSN or array of arguments (dsn, username, password)
	 * @param string $username
	 * @param string $password
	 * @param array $driver_options
	 */
	function __construct($dsn, $username = '', $password = '', $driver_options = array()) {
		if(is_array($dsn)) {
			$args = $dsn;
			if(isset($args[0])) { $dsn = $args[0]; }
			if(isset($args[1])) { $username = $args[1];	}
			if(isset($args[2])) { $password = $args[2];	}
			if(isset($args[3])) { $driver_options = $args[3]; }
		}
		
		try {
			parent::__construct($dsn, $username, $password, $driver_options);
			parent::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $exception) {
			throw new DataSourceCouldNotConnectException($exception->getMessage(), get_defined_vars());
		}
		
		$this->cachePrefix = self::CACHE_PREFIX . $dsn . '::*::';
		
		$this->provider = $this->instantiateProvider();
	}
	
	/**
	 * Locate the pdo driver specific data source provider, instantiate, and return.
	 * Throws ProviderDoesNotExistException for a pdo driver without a provider.
	 *
	 * @return IPdoDataSourceProvider
	 */
	protected function instantiateProvider() {
		$driver = ucfirst(parent::getAttribute(\PDO::ATTR_DRIVER_NAME));
		$providerClass = $driver . self::PROVIDER_CLASS_SUFFIX;
		$providerFullyQualified = self::PROVIDER_CLASS_LOCATION . $providerClass;
		
		$provider = new $providerFullyQualified();
		$provider->init($this);
		return $provider;
	}
	
	/**
	 * Begin a select operation by returning a new, unrealized PdoDataSet
	 *
	 * @param string $table Optional parameter that sets the from clause of the select to a table.
	 * @return PdoDataSet
	 */
	function select($table = '') {
		if($table != '') {
			$PdoDataSet = new DataSet($this);
			return $PdoDataSet->from($table);
		} else {
			return new DataSet($this);
		}
	}
	
	/**
	 * Takes the SQL and arguments (array of Criterion) and returns an array
	 * of objects of type $className.
	 * 
	 * @todo Determine edge conditions and throws.
	 * @param string $query
	 * @param array(Criterion) $arguments 
	 * @param string $className the type to fill from query results.
	 * @return array($className)
	 */
	function queryForClass(SqlBuilder $builder, $className) {
		$statement = $this->provider->getStatementForBuilder($builder,'select',$this);
		$statement->setFetchMode(\PDO::FETCH_CLASS, $className, array());
		$statement->execute();
		return $this->provider->fetchAll($statement);
	}
	
	/**
	 * Execute the query from a SqlBuilder instance.
	 *
	 * @param SqlBuilder $builder
	 * @param string $action
	 * @return boolean
	 */
	function executeSqlBuilder(SqlBuilder $builder, $action) {
		return $this->provider->executeSqlBuilder($builder, $action, $this);
	}
	
	function executeStatement($statement, $arguments) {
		$statement = $this->prepareStatement($statement, $arguments);
		return $statement->execute();
	}
	
	function explainStatement($statement, $arguments) {
		$statement = $this->prepareStatement('EXPLAIN QUERY PLAN ' . $statement, $arguments);
		$statement->execute();
		return $statement->fetchAll();
	}
	
	function prepareStatement($statement, $arguments) {
		try {
			$statement = $this->prepare($statement);
		} catch(\PDOException $e) {
			throw new Exception($e->getMessage() . ' SQL: ' . $statement,get_defined_vars());
		}
		foreach($arguments as &$argument) {
			// Begin workaround for PDO's poor numeric binding
			$queryParameter = $argument->getQueryParameter();
			if(is_numeric($queryParameter)) { continue; } 
			// End Workaround
			$statement->bindValue($argument->getQueryParameter(), $argument->value);
		}
		return $statement;
	}
	
	/**
	 * List the tables in a data source alphabetically.
	 * @return array(string) The tables in the data source
	 */
	function getTables() {
		$cacheKey = $this->cachePrefix . 'Tables';
		$tables = Cache::get($cacheKey);
		if(!$tables) {
			$tables = $this->provider->getTables();
			Cache::set($this->cachePrefix . 'Tables', $tables);
		}
		return $tables;
	}
	
	/**
	 * List the column names of a table alphabetically.
	 * @param string $table Table whose columns to list.
	 * @return array(string) Column names sorted alphabetically.
	 */
	function getColumns($table) {
		$cacheKey = $this->cachePrefix . $table . '::Columns';
		$columns = Cache::get($cacheKey);
		if(!$columns) {
			$columns = $this->provider->getColumns($table);
			Cache::set($cacheKey, $columns);
		}
		return $columns;
	}
	
	/**
	 * Retrieve a description of a table.
	 *
	 * @param string $table
	 * @return Table
	 */
	function describeTable($table) {
		$cacheKey = $this->cachePrefix . $table . '::Descriptor';
		$descriptor = Cache::get($cacheKey);
		if(!$descriptor) {
			$descriptor = $this->provider->describeTable($table);
			Cache::set($cacheKey, $descriptor);
		}
		return $descriptor;
	}
	
	/**
	 * Take a table descriptor and apply it / verify it on top of the
	 * table descriptor returned from a database. This is used to ensure
	 * a model's marked up fields are in congruence with the table. Also
	 * checks to ensure the number of columns in the cascaded descriptor
	 * do not outnumber the actual number of columns. Finally with a database
	 * like sqlite which largely ignores column typing it enables the model
	 * to inform the actual Recess type of the column.
	 *
	 * @param string $table
	 * @param TableDescriptor $descriptor
	 */
	function cascadeTableDescriptor($table, $descriptor) { 
		$cacheKey = $this->cachePrefix . $table . '::Descriptor';
		Cache::set($cacheKey, $this->provider->cascadeTableDescriptor($table, $descriptor));
	}
	
	/**
	 * Drop a table from the database.
	 *
	 * @param string $table
	 */
	function dropTable($table) {
		return $this->provider->dropTable($table);
	}
	
	/**
	 * Empty a table in the database.
	 *
	 * @param string $table
	 */
	function emptyTable($table) {
		return $this->provider->emptyTable($table);
	}
	
	function createTableSql($tableDescriptor) {
		return $this->provider->createTableSql($tableDescriptor);
	}
}
