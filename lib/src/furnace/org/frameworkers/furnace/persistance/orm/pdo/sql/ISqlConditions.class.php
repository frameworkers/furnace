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
 * Interface used which maps to conditional SQL statements
 * 
 * This interface has been strongly influenced by the Recess! Framework by 
 * Kris Jordan. http://www.recessframework.org/
 */
interface ISqlConditions {
	
	public function equal ($column, $value);
	public function notEqual ($column, $value);
	public function between ($column, $big, $small);
	public function greaterThan ($column, $value);
	public function greaterThanOrEqualTo ($column, $value);
	public function lessThan ($column, $value);
	public function lessThanOrEqualTo ($column, $value);
	public function like ($column, $value);
	public function notLike ($column, $value);
	
}
?>