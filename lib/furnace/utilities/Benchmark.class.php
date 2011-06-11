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

use furnace\utilities\Logger;
use furnace\utilities\LogLevel;

class Benchmark {

    protected static $marks = array();


    public static function mark($label) {
        $timestamp = microtime(true);
        $data = array(
            'current_memory'  => memory_get_usage(),
            'current_time'    => $timestamp,
            'request_elapsed' => $timestamp - F_REQUEST_START
        );
        self::$marks[$label] = $data;
        Logger::Log(LogLevel::DEBUG,
            "-mark- ({$label}): {mem: "
                .round($data['current_memory'] / 1048576,2)." MiB, "
                ."elapsed: "
                .round($data['request_elapsed']* 1000,2)." msec.}");
    }

    public static function marks($label = null) {

        if (null == $label ) { return self::$marks; }

        return (isset(self::$marks[$label]))
            ? self::$marks[$label]
            : false;
    }

    public static function diff($label1,$label2) {

        if (!isset(self::$marks[$label1]) || !isset(self::$marks[$label2])) {
            return false;
        }

        $data1 = self::$marks[$label1];
        $data2 = self::$marks[$label2];
        $diff  = array(
            'diff_memory' => $data1['current_memory'] - $data2['current_memory'],
            'diff_time'   => $data1['current_time']   - $data2['current_time']
        );

        return $diff;        
    }

    public static function summary($mark = null, $format = 'html-comment') {

        switch (strtolower($format)) {
            case 'log':  break;
            case 'xml':  break;
            case 'json': break;
            case 'html': break;
            case 'html-comment':
            default: break;
        }
    }
}
