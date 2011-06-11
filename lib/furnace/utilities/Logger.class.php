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

use furnace\core\Config;

class Logger {


    public static function Log($level,$message) {
        
        $minLevel = Config::Get('env.logging.level');
        if ($level >= $minLevel) {   
            $msg = "[".microtime()."]" 
                    . sprintf('[%-5s] ',LogLevel::LabelFor($level)) 
                    . "{$message}\r\n";

            $logFile  = Config::Get('env.logging.file');
            file_put_contents( $logFile, $msg, FILE_APPEND | LOCK_EX );
        }
    }

}
