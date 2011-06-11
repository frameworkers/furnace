<?php

use furnace\utilities\Logger;
use furnace\utilities\LogLevel;

function debug($var,$bStop = true) {
    ob_end_flush();
    $trace = debug_backtrace();
    $inst  = array_shift($trace);
    echo "<pre>";
    echo "----------------------------------------\r\n";
    echo "in: " . basename($inst['file']).":{$inst['line']}\r\n";
    echo "----------------------------------------\r\n";
    var_dump($var);
    echo "</pre>";
    if ($bStop) {
        Logger::Log(LogLevel::DEBUG,"Halting app (at "
            . basename($inst['file']).":{$inst['line']}) "
            . "via call to `debug`"); 
        exit();
    }
}
