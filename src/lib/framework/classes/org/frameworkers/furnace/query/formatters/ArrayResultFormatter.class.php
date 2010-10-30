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
 * ArrayResultFormatter
 * 
 * Converts {@link Result} object data into an associative array
 * 
 * @extends ResultFormatter
 */
class ArrayResultFormatter extends ResultFormatter {
    
    public function format($result) {
        return $result->data;
    }
    
}
?>