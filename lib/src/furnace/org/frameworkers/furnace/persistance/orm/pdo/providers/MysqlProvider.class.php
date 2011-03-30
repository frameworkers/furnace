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
 * MySql Data Source Provider
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
use org\frameworkers\furnace\Furnace;

use org\frameworkers\furnace\persistance\orm\pdo\DataSource;
use org\frameworkers\furnace\persistance\orm\pdo\sql\SqlBuilder;
use org\frameworkers\furnace\persistance\FurnaceType;
use org\frameworkers\furnace\persistance\orm\pdo\model\Table;
use org\frameworkers\furnace\persistance\orm\pdo\model\Column;
use org\frameworkers\furnace\persistance\orm\pdo\model\Lang;
use org\frameworkers\furnace\persistance\orm\pdo\providers\IPdoProvider;



class MysqlProvider implements IProvider {
	protected static $mysqlToFurnaceMappings;
	protected static $furnaceToMysqlMappings;
	protected $pdo = null;
	
	/**
	 * Initialize with a reference back to the PDO object.
	 *
	 * @param PDO $pdo
	 */
	function init(\PDO $pdo) {
		$this->pdo = $pdo;
	}
	
	/**
	 * List the tables in a data source.
	 * @return array(string) The tables in the data source ordered alphabetically.
	 */
	function getTables() {
		$results = $this->pdo->query('SHOW TABLES');
		
		$tables = array();
		
		foreach($results as $result) {
			$tables[] = $result[0];
		}
		
		sort($tables);
		
		return $tables;
	}
	
	/**
	 * List the column names of a table alphabetically.
	 * @param string $table Table whose columns to list.
	 * @return array(string) Column names sorted alphabetically.
	 */
	function getColumns($table) {
		try {
			$results = $this->pdo->query('SHOW COLUMNS FROM ' . $table . ';');
		} catch(Exception $e) {
			return array();
		}
		
		$columns = array();
		
		foreach($results as $result) {
			$columns[] = $result['Field'];
		}
		
		sort($columns);
		
		return $columns;
	}
	
	/**
	 * Retrieve the a table's description.
	 *
	 * @param string $table Name of table.
	 * @return Table
	 */
	function describeTable($table) {
		$data = new Table($table);
		
		try {
			$results = $this->pdo->query('SHOW COLUMNS FROM ' . $table . ';');
			$data->tableExists = true;
		} catch (\PDOException $e) {
			$data->tableExists = false;
			return $data;
		}
		
		foreach($results as $result) {
			$data->addColumn(
				$result['Field'],
				$this->getFurnaceType($result['Type']),
				$result['Null'] == 'NO' ? false : true,
				$result['Key'] == 'PRI' ? true : false,
				$result['Default'] == null ? '' : $result['Default'],
				$result['Extra'] == 'auto_increment' ? array('autoincrement' => true) : array());
		}
		
		return $data;
	}
	
	function getFurnaceType($mysqlType) {
		if(strtolower($mysqlType) == 'tinyint(1)')
			return FurnaceType::BOOLEAN;
		
		if( ($parenPos = strpos($mysqlType,'(')) !== false ) {
			$mysqlType = substr($mysqlType,0,$parenPos);
		}
		if( ($spacePos = strpos($mysqlType,' '))) {
			$mysqlType = substr($mysqlType(0,$spacePos));
		}
		$mysqlType = strtolower(rtrim($mysqlType));
		
		$mysqlToFurnaceMappings = self::getMysqlToFurnaceMappings();
		if(isset($mysqlToFurnaceMappings[$mysqlType])) {
			return $mysqlToFurnaceMappings[$mysqlType];
		} else {
			return FurnaceType::STRING;
		}
	}
	
	static function getMysqlToFurnaceMappings() {
		if(!isset(self::$mysqlToFurnaceMappings)) {
			self::$mysqlToFurnaceMappings = array(
				'enum' => FurnaceType::STRING,
				'binary' => FurnaceType::STRING,
				'varbinary' => FurnaceType::STRING,
				'varchar' => FurnaceType::STRING,
				'char' => FurnaceType::STRING,
				'national' => FurnaceType::STRING,
			
				'text' => FurnaceType::TEXT,
				'tinytext' => FurnaceType::TEXT,
				'mediumtext' => FurnaceType::TEXT,
				'longtext' => FurnaceType::TEXT,
				'set' => FurnaceType::TEXT,
			
				'blob' => FurnaceType::BLOB,
				'tinyblob' => FurnaceType::BLOB,
				'mediumblob' => FurnaceType::BLOB,
				'longblob' => FurnaceType::BLOB,
			
				'int' => FurnaceType::INTEGER,
				'integer' => FurnaceType::INTEGER,
				'tinyint' => FurnaceType::INTEGER,
				'smallint' => FurnaceType::INTEGER,
				'mediumint' => FurnaceType::INTEGER,
				'bigint' => FurnaceType::INTEGER,
				'bit' => FurnaceType::INTEGER,
			
				'bool' => FurnaceType::BOOLEAN,
				'boolean' => FurnaceType::BOOLEAN,
			
				'float' => FurnaceType::FLOAT,
				'double' => FurnaceType::FLOAT,
				'decimal' => FurnaceType::STRING,
				'dec' => FurnaceType::STRING,
			
				'year' => FurnaceType::INTEGER,
				'date' => FurnaceType::DATE,
				'datetime'  => FurnaceType::DATETIME,
				'timestamp' => FurnaceType::TIMESTAMP,
				'time'      => FurnaceType::TIME,
			); 
		}
		return self::$mysqlToFurnaceMappings;
	}
	
	static function getFurnaceToMysqlMappings($extra = array()) {
		$size = isset($extra['max']) ? $extra['max'] : null;
		//if(!isset(self::$furnaceToMysqlMappings)) {
			self::$furnaceToMysqlMappings = array(
				FurnaceType::BLOB => 'BLOB',
				FurnaceType::BOOLEAN  => 'TINYINT(1)',
				FurnaceType::DATE     => 'DATE',
				FurnaceType::DATETIME => 'DATETIME',
				FurnaceType::FLOAT    => 'FLOAT',
				FurnaceType::INTEGER  => 'INTEGER',
				FurnaceType::STRING   => "VARCHAR({$size})",
				FurnaceType::TEXT => 'TEXT',
				FurnaceType::TIME => 'TIME',
				FurnaceType::TIMESTAMP => 'TIMESTAMP',
			);
		//}
		return self::$furnaceToMysqlMappings;
	}
	
	/**
	 * Drop a table from MySql database.
	 *
	 * @param string $table Name of table.
	 */
	function dropTable($table) {
		return $this->pdo->exec('DROP TABLE ' . $table);
	}
	
	/**
	 * Empty a table from MySql database.
	 *
	 * @param string $table Name of table.
	 */
	function emptyTable($table) {
		return $this->pdo->exec('DELETE FROM ' . $table);
	}
	
	/**
	 * Given a Table Definition, return the CREATE TABLE SQL statement
	 * in the MySQL's syntax.
	 *
	 * @param TableDescriptor $tableDescriptor
	 */
	function createTableSql(Table $definition) {
		$sql = 'CREATE TABLE IF NOT EXISTS ' . $definition->name;
		
		$columnSql   = null;
		$primaryKeys = array();
		foreach($definition->getColumns() as $column) {
			$mappings = MysqlProvider::getFurnaceToMysqlMappings($column->extra);

			if(isset($columnSql)) { $columnSql .= ', '; }
			$columnSql .= "\n\t" . $column->name . ' ' . $mappings[strtolower($column->type)];
			if($column->isPrimary) {
				$columnSql .= ' NOT NULL';
				if(isset($column->extra['autoincrement'])) {
					$columnSql .= ' AUTO_INCREMENT';
				}
				$primaryKeys[]  = $column->name;
			}
		}
		if ($column->extra['description']) {
			$columnSql .= 'COMMENT ' . str_replace('\'',"''",$column->extra['description']);
		}
		$columnSql .= ",\n\t";
		$columnSql .= "PRIMARY KEY(`" . implode("`,`",$primaryKeys)."`)\n";
		return $sql . ' (' . $columnSql . ')';
	}
	
	function getColumnSql(Column $definition) {
		$mappings = self::getFurnaceToMysqlMappings($definition->extra);
		$type    = $mappings[strtolower($definition->type)];
		$null    = $definition->isNull  ? "NULL " : "NOT NULL ";
		$comment = $definition->comment ? "COMMENT '" . str_replace("'","''",$definition->comment) . "' " : '';
		$default = $definition->default ? "DEFAULT \"$definition->default\" " : '';
		$autoinc = $definition->isAutoinc ? "AUTOINCREMENT " : '';
		
		$sql = "`{$definition->name}` {$type} {$null} {$autoinc} {$default} {$comment} ";
		return $sql;
	}
	
	function tableAddColumn($tableName, Column $definition) {
		$tableName = Lang::toTableName($tableName);
		$sql =  "ALTER TABLE `{$tableName}` ADD COLUMN " . $this->getColumnSql($definition);
		$this->pdo->exec($sql);
	}
	
	function tableDropColumn($tableName, $columnName) {
		$tableName = Lang::toTableName($tableName);
		$columnName= Lang::toColumnName($columnName);
		$sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}` ";
		$this->pdo->exec($sql);
	}
	
	function tableChangeColumn($tableName,$columnName,Column $definition) {
		$tableName = Lang::toTableName($tableName);
		$columnName= Lang::toColumnName($columnName);
		$sql = "ALTER TABLE `{$tableName}` CHANGE COLUMN `{$columnName}` " . $this->getColumnSql($definition);
		$this->pdo->exec($sql); 
	}
	
	
	/**
	 * Sanity check and semantic sugar from higher level
	 * representation of table pushed down to the RDBMS
	 * representation of the table.
	 *
	 * @param string $table
	 * @param TableDescriptor $descriptor
	 *
	function cascadeTableDescriptor($table, TableDescriptor $descriptor) {
		$sourceDescriptor = $this->getTableDescriptor($table);
		
		if(!$sourceDescriptor->tableExists) {
			$descriptor->tableExists = false;
			return $descriptor;
		}
		
		$sourceColumns = $sourceDescriptor->getColumns();
		
		$errors = array();
		
		foreach($descriptor->getColumns() as $column) {
			if(isset($sourceColumns[$column->name])) {
				if($column->isPrimaryKey && !$sourceColumns[$column->name]->isPrimaryKey) {
					$errors[] = 'Column "' . $column->name . '" is not the primary key in table ' . $table . '.';
				}
				if($sourceColumns[$column->name]->type != $column->type) {
					$errors[] = 'Column "' . $column->name . '" type "' . $column->type . '" does not match database column type "' . $sourceColumns[$column->name]->type . '".';
				}
			} else {
				$errors[] = 'Column "' . $column->name . '" does not exist in table ' . $table . '.';
			}
		}
		
		if(!empty($errors)) {
			throw new Exception(implode(' ', $errors), get_defined_vars());
		} else {
			return $sourceDescriptor;
		}
	}
	*/
	
	/**
	 * Fetch all returns columns typed as Furnace expects:
	 *  i.e. Dates become Unix Time Based and TinyInts are converted to Boolean
	 *
	 * TODO: Refactor this into the query code so that MySql does the type conversion
	 * instead of doing it slow and manually in PHP.
	 * 
	 * @param PDOStatement $statement
	 * @return array fetchAll() of statement
	 */
	function fetchAll(\PDOStatement $statement) {
		try {
			$columnCount = $statement->columnCount();
			$manualFetch = false;
			$booleanColumns = array();
			$dateColumns = array();
			$timeColumns = array();
			for($i = 0 ; $i < $columnCount; $i++) {
				$meta = $statement->getColumnMeta($i);
				if(isset($meta['native_type'])) {
					switch($meta['native_type']) {
						case 'TIMESTAMP': case 'DATETIME': case 'DATE':
							$dateColumns[] = $meta['name'];
							break;
						case 'TIME':
							$timeColumns[] = $meta['name'];
							break;
					}
				} else {
					if($meta['len'] == 1) {
						$booleanColumns[] = $meta['name'];
					}
				}
			}
			
			if(	!empty($booleanColumns) || 
				!empty($datetimeColumns) || 
				!empty($dateColumns) || 
				!empty($timeColumns)) {
				$manualFetch = true;
			}
		} catch(\PDOException $e) {
			return $statement->fetchAll();
		}
		
		if(!$manualFetch) {
			return $statement->fetchAll();
		} else {
			$results = array();
			while($result = $statement->fetch()) {
				foreach($booleanColumns as $column) {
					$result->$column = $result->$column == 1;
				}
				foreach($dateColumns as $column) {
					$result->$column = strtotime($result->$column);
				}
				foreach($timeColumns as $column) {
					$result->$column = strtotime('1970-01-01 ' . $result->$column);
				}
				$results[] = $result;
			}
			return $results;
		}
	}
	
	function getStatementForBuilder(SqlBuilder $builder, $action, DataSource $source) {
		$criteria = $builder->getCriteria();
		$builderTable = $builder->getTable();
		$tableDescriptors = array();
		
		foreach($criteria as $criterion) {
			$table = $builderTable;
			$column = $criterion->column;
			if(strpos($column,'.') !== false) {
				$parts = explode('.', $column);
				$table = $parts[0];
				$column = $parts[1];
			}
			
			if(!isset($tableDescriptors[$table])) {
				$tableDescriptors[$table] = $source->describeTable($table)->getColumns();
			}
			
			if(isset($tableDescriptors[$table][$column])) {
				switch($tableDescriptors[$table][$column]->type) {
					case FurnaceType::DATETIME: 
					case FurnaceType::TIMESTAMP:
						if(is_int($criterion->value)) {
							$criterion->value = date('Y-m-d H:i:s', $criterion->value);
						} else {
							$criterion->value = null;
						}
						break;
					case FurnaceType::DATE:
						$criterion->value = date('Y-m-d', $criterion->value);
						break;
					case FurnaceType::TIME:
						$criterion->value = date('H:i:s', $criterion->value);
						break;
					case FurnaceType::BOOLEAN:
						$criterion->value = $criterion->value == true ? 1 : 0;
						break;
					case FurnaceType::INTEGER:
						if(!is_numeric($criterion->value)) {
							$criterion->value = null;
						} else {
							$criterion->value = (int)$criterion->value;
						}
						break;
					case FurnaceType::FLOAT:
						if(!is_numeric($criterion->value)) {
							$criterion->value = null;
						}
						break;
				}
			}
		}
		
		$sql = $builder->$action();
		//var_dump($sql);
		$statement = $source->prepare($sql);
		$arguments = $builder->getPdoArguments();
		foreach($arguments as &$argument) {
			// Begin workaround for PDO's poor numeric binding
			$queryParameter = $argument->getQueryParameter();
			if(is_numeric($queryParameter)) { continue; } 
			// End Workaround
			// Ignore parameters that aren't used in this $action (i.e. assignments in select)
			$param = $argument->getQueryParameter();
			if(''===$param || strpos($sql, $param) === false) { continue; } 
			$statement->bindValue($param, $argument->value);
		}
		return $statement;
	}
	
	/**
	 * @param SqlBuilder $builder
	 * @param string $action
	 * @param PdoDataSource $source
	 * @return boolean
	 */
	function executeSqlBuilder(SqlBuilder $builder, $action, DataSource $source) {		
		return $this->getStatementForBuilder($builder, $action, $source)->execute();
	}
}