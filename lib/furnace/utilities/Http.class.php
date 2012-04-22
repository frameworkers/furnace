<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage response
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\utilities;

use furnace\request\Request;
use furnace\routing\Route;

class Http {

  // HTTP Status Codes
  const NOT_AUTHORIZED = '403';
  const NOT_FOUND = '404';
  const METHOD_NOT_ALLOWED = '405';
  const INTERNAL_SERVER_ERROR = '500'; 
  
  // HTTP Methods
  const GET  = "GET";
  const POST = "POST";
  
  public static function translate( $code ) {
    switch ($code) {
      case HTTP::NOT_AUTHORIZED: return "Not Authorized";
      case HTTP::NOT_FOUND: return "Not Found";
      case HTTP::METHOD_NOT_ALLOWED: return "Method Not Allowed";
      case HTTP::INTERNAL_SERVER_ERROR: return "Internal Server Error";
      default: return "Unknown Error";
    }
  }

}
