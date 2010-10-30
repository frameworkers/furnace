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
 * SingleValueResultFormatter
 * 
 * Converts {@link Result} object data into an single scalar value
 * 
 * @extends ResultFormatter
 */
class SingleValueResultFormatter extends ResultFormatter {
    
    public function format($result) {
        return (is_array($result->data[0])) 
            ? array_pop($result->data[0])
            : null;
    } 
}
?>