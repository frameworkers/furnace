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
 * FResultFormatter
 * 
 * Provides an abstract base class for a heirarchy of
 * custom formatters capable of taking an {@link FResult}
 * object and formatting it to suit a particular 
 * specification.
 * 
 * @abstract
 */
abstract class FResultFormatter {

    /**
     * Format an {@link FResult} object according to some rules
     * 
     * @param  FResult $result  The {@link FResult} object to format
     * @return mixed            The formatted result 
     */
    abstract public function format($result);
}
?>