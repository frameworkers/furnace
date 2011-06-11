<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage utilities
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\utilities;

class LogLevel {

    
    const DEBUG = 1;
    const INFO  = 2;
    const WARN  = 4;
    const ERROR = 8;
    const CRIT  = 16;

    public static function LabelFor($level) {
        switch ($level) {

            case LogLevel::DEBUG: return "DEBUG";
            case LogLevel::INFO:  return "INFO";
            case LogLevel::WARN:  return "WARN";
            case LogLevel::ERROR: return "ERROR";
            case LogLevel::CRIT:  return "CRIT!";
        }
    }
}
