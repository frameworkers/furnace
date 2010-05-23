<?php
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
 * FArrayResultFormatter
 * 
 * Converts {@link FResult} object data into an single scalar value
 * 
 * @extends FResultFormatter
 */
class FSingleValueResultFormatter extends FResultFormatter {
    
    public function format($result) {
        return (is_array($result->data[0])) 
            ? array_pop($result->data[0])
            : null;
    }
    
}
?>