<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage exceptions
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\exceptions;

use furnace\utilities\Logger;
use furnace\utilities\LogLevel;

class FurnaceException extends \Exception {

    public function __construct($message = '',$code = 0, $previous = NULL) {
        parent::__construct($message,$code,$previous);
        Logger::Log(LogLevel::ERROR,basename($this->getFile()) . ":{$this->getLine()} - {$message}");
    }

}
