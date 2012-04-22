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

use furnace\core\Config;
use furnace\utilities\Logger;
use furnace\utilities\LogLevel;
use furnace\utilities\HTTP;


class FurnaceException extends \Exception {

  public $httpStatusCode;
  public $clientMessage;
  public $developerMessage;

  public function __construct($message = '',$code = 0, $previous = NULL) {
    parent::__construct($message,$code,$previous);
   
    $this->httpStatusCode = $code;
    Logger::Log(LogLevel::ERROR,
      basename($this->getFile()) . ":{$this->getLine()} - {$message}");
  }
  
  public function setHttpStatusCode( $code ) {
    $this->code = $this->httpStatusCode = $code;
    return $this;
  }
  
  public function setClientMessage( $message ) {
    $this->clientMessage = $message;
    return $this;
  }
  
  public function setDeveloperMessage( $message ) { 
    $this->developerMessage = $message;
    return $this;
  }
  
  public function toHtml() {
    $exceptionInfo = "<h2><code>{$this->httpStatusCode}</code> &nbsp;"
      . "<em>" . HTTP::Translate($this->httpStatusCode) . "</em></h2>";
      
    $clientInfo = "<h4>Details:</h4>&quot;{$this->clientMessage}&quot;";
    
    $traceInfo  = "<h5>Backtrace:</h5><pre>{$this->getTraceAsString()}</pre>";
      
    $developerInfo = (Config::Get('environment') == F_ENV_DEVELOPMENT)
      ? "<h4>Developer Information:</h4>{$this->developerMessage}<br/>{$traceInfo}<br/>"
      : '';
      
    $css  = '<style type="text/css">
      div.furnace_error {
        background-color:#eee;
        padding:2px 20px;
        -webkit-border-radius: 8px;
        -moz-border-radius: 8px;
        border-radius: 8px;
        line-height:2em;
      }
      
      div.furnace_error pre {
        background-color:#fff;
        padding:20px;
        font-size:120%;
        overflow:scroll;
      }
      
      div.furnace_error code {
        font-size:120%;
        border:solid 1px #ccc;
        background-color:#aaa;
        padding:5px;
      }
    
    </style>';
      
    $html = "<!DOCTYPE html>"
      . "<html><head><title>An error has occurred...</title>"
      . $css
      . "</head><body>"
      . "<div class=\"furnace_error\">"
      . $exceptionInfo
      . $clientInfo    . "<br/>"
      . $developerInfo . "<br/>"
      . "</div>"
      . "</body></html>";
      
    return $html;
  }
}
