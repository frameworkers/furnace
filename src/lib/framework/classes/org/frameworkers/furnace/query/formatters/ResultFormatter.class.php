<?php
namespace org\frameworkers\furnace\query\formatters;
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package    Furnace
 * @subpackage Datasources
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

/**
 * ResultFormatter
 * 
 * Provides an abstract base class for a heirarchy of
 * custom formatters capable of taking an {@link Result}
 * object and formatting it to suit a particular 
 * specification.
 * 
 * @abstract
 */
abstract class ResultFormatter {

    /**
     * Format an {@link Result} object according to some rules
     * 
     * @param  Result $result  The {@link Result} object to format
     * @return mixed            The formatted result 
     */
    abstract public function format($result);
}
?>