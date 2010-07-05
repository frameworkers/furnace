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
 * Converts {@link FResult} object data into an associative array
 * 
 * @extends FResultFormatter
 */
class FArrayResultFormatter extends FResultFormatter {
    
    public function format($result) {
        return $result->data;
    }
    
}
?>