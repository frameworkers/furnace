<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\sql;
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
 * Interface used which maps to SELECT SQL statements
 * 
 * This interface has been strongly influenced by the Recess! Framework by 
 * Kris Jordan. http://www.recessframework.org/
 */
interface ISqlSelectOptions {

	public function limit ($size);
	public function offset ($offset);
	public function range ($start, $finish);
	public function orderBy ($clause);
	public function leftOuterJoin ($table, $tablePrimaryKey, $fromTableForeignKey);
	public function innerJoin ($table, $tablePrimaryKey, $fromTableForeignKey);
	public function distinct ();
		
}
?>