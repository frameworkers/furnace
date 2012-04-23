<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage core
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\core;

use furnace\exceptions\FurnaceException;
use furnace\routing\Router;
use furnace\routing\Route;
use furnace\request\Request;
use furnace\response\Response;
use furnace\response\ResponseChunk;
use furnace\response\HtmlResponse;
use furnace\utilities\Logger;
use furnace\utilities\LogLevel;
use furnace\utilities\Http;
use furnace\utilities\Benchmark;
use furnace\utilities\Inflector;

class Furnace {

  protected static $request;
  protected static $responses = array();

  public static function CreateRequest($uri) {
      return new Request($uri);
  }

  public static function Request($uri) {

    // Start buffering output
    ob_start();
    
    try {

      // 3.1 Create a request object for the given url
      $request = self::$request = Furnace::CreateRequest($uri);

      // 3.2 Attempt to route the request to a controller/handler
      $route  = Router::route($request->getCleanUri());
      $request->route($route);

      // 3.3 Ensure the requested controller file exists
      $controllerClassName = ucfirst($route->controller) . "Controller";
      $controllerFilePath  = F_MODULES_PATH . "/{$route->module}/controllers/{$controllerClassName}.class.php";

      if (!file_exists($controllerFilePath)) {

        // Try again, if there is a handler in the DefaultController matching the 
        // current $route->controller, then use it:
        $controllerClassName = "DefaultController";
        $controllerFilePath  = F_MODULES_PATH . "/{$route->module}/controllers/DefaultController.class.php";
        
        // Fix the route:
        if ($route->handler != 'index') {    
          array_unshift($route->parameters,$route->handler);
        }
        $route->handler      = $route->controller;
        $route->controller   = "default"; 
            
        if (!file_exists($controllerFilePath)) {
          $e = new FurnaceException("Unable to handle request");
          $e->setHttpStatusCode(HTTP::NOT_FOUND);
          $e->setDeveloperMessage("Furnace was unable to find a "
            . "controller file at:  "
            . "<br/><code>{$controllerFilePath}</code> ");
          throw $e;
        } 
      }

      // 3.4 Apply the staged config for the module, and, if none exists, 
      //     attempt to locate the config file and parse it now
      if (! Config::ApplyStagedModule($route->module)) {
        $moduleConfigFilePath = F_MODULES_PATH . "/{$route->module}/config.php";
        if (file_exists($moduleConfigFilePath)) {
            include($moduleConfigFilePath);
        }
      }
      
      // 3.5 Create a Response object to hold the response
      self::$responses[] = Response::Create($request, $route);
      $response = end(self::$responses);
      
      // 3.6 Include the required controller file & create an instance of the class
      require_once($controllerFilePath);
      $controllerInstance = new $controllerClassName($request,$response);

      // 3.7 Ensure the requested handler has been defined and is callable
      $handlerFunction = Inflector::toFunction($route->handler);
      
      if (!is_callable(array($controllerInstance,$handlerFunction))) {
        $e = new FurnaceException("Unable to handle request");
        $e->setHttpStatusCode(HTTP::NOT_FOUND);
        $e->setDeveloperMessage("Furnace was unable to find a "
          . "function definition for:  "
          . "<br/><code>{$controllerClassName}::{$handlerFunction}(...)</code> &nbsp;in the file:"
          . "<br/><code>{$controllerFilePath}</code>");
        throw $e;
      }
      
      // 3.8 Initialize the response
      $response->initialize();

      // 3.9 Invoke the controller function
      $a = array_values($route->parameters); // Named keys are ignored, only order matters
      switch (count($route->parameters)) {
        case 0: $controllerInstance->$handlerFunction(); break;
        case 1: $controllerInstance->$handlerFunction($a[0]); break;
        case 2: $controllerInstance->$handlerFunction($a[0],$a[1]); break;
        case 3: $controllerInstance->$handlerFunction($a[0],$a[1],$a[2]); break;
        case 4: $controllerInstance->$handlerFunction($a[0],$a[1],$a[2],$a[3]); break;
        case 5: $controllerInstance->$handlerFunction($a[0],$a[1],$a[2],$a[3],$a[4]); break;
        default:
          call_user_func_array(array($controllerClassName,$handlerFunction),$a);
      }
      
      // 3.10 Capture the final result from the controller
      $response = $controllerInstance->finalize();


      /* ============================================================================
       * 4. Send a Response
       * ============================================================================
       ***/

      // 4.1 Allow the response object to perform any final processing steps on itself
      $response->finalize();

      // 4.2 Ignore any buffered output and return the response
      ob_end_clean();
      array_pop(self::$responses);
      return $response;
      
      
    } catch (FurnaceException $e) {
      
      // Determine the client message to be shown, if any
      if ($e->clientMessage == '') {
        $e->setClientMessage(Config::Get('message.'.$e->httpStatusCode,'Unknown Error'));
      }
      
      // Stop processing this request
      Furnace::Halt($e);    
    
    } catch (Exception $e ) {
    
      // Create a FurnaceException to represent this exception
      $ex = new FurnaceException(Config::Get('message.500','Internal Server Error'));
      $ex->setDeveloperMessage($e->getMessage());
      $ex->setHttpStatusCode(HTTP::INTERNAL_SERVER_ERROR);
      
      // Stop processing this request
      Furnace::Halt($ex);
    }
  } 

  public static function Subrequest($uri) {
    if (F_URL_BASE . $uri == $_SERVER['REQUEST_URI']) {
      if (Config::Get('environment') == F_ENV_DEVELOPMENT) {
        self::Halt('Infinite loop detected in subrequest','A controller requested '
            . "a subrequest with URI <code>{$uri}</code> which, when expanded, "
            . "exactly matches the URI of the parent request, creating an infinite "
            . "loop. Double check the URI for this subrequest.");
      } else {
        self::InternalError('Infinite loop detected in subrequest');
      }
    }
    return self::Request(F_URL_BASE . $uri);
  }

  public static function Response() {
    return end(self::$responses);
  } 

  public static function GetRequest() {
    return self::$request;
  }
  
  public static function Redirect($newUrl) {
    $newLocation = ('/' == $newUrl[0])
        ? F_URL_BASE . $newUrl
        : $newUrl;
    header('Location: ' . $newLocation);
    exit();
  }

  public static function GetUserMessages() {
    $flashes = $_SESSION[Config::Get('sess.flashes.key')];
    unset($_SESSION[Config::Get('sess.flashes.key')]);

    return $flashes;
  }

  // DEPRECATED!
  // This functionality has been pushed down into the 
  // Response object for greater flexibility in 
  // formatting and processing. 
  // See Furnace::GetUserMessages(), Response::finalize(),
  // and HtmlResponse::finalize()
  //
  public static function ProcessFlashMessages() {
    Furnace::InternalError("Furnace::ProcessFlashMessages has been deprecated. "
      . "Raw messages for the user can be obtained through Furnace::GetUserMessages(). "
      . "These can then be processed by the appropriate *Response object via its "
      . "finalize() method");
  }

  public static function Cleanup() {
    Benchmark::Mark('cleanup');
    exit();
  }   

  public static function Flash($message, $type = 'success') {
  	$_SESSION[Config::Get('sess.flashes.key')][] = array(
      "message" => $message,
      "type"    => $type
    );
  }

  public static function Halt( FurnaceException $e ) {
  
    // Obtain the error backtrace
    $backtrace = $e->getTrace();
    
    // Log the error
    Logger::Log(LogLevel::CRIT, 
      "Halting app (at " . basename($backtrace[0]['file']) 
        . ":{$backtrace[0]['line']}) with status: {$e->getCode()}");
    if (self::$request) {
        Logger::Log(LogLevel::INFO, "Request URI was: " . self::$request->getCleanUri());
    }
    
    // Finish up
    ob_end_clean();       // Expunge any previously generated output
    echo $e->toHtml();    // Report the error to the client
    self::Cleanup();      // End the request;
  }

  public static function InternalError($debugDetails = null) {
    $e = new FurnaceException('',HTTP::INTERNAL_SERVER_ERROR);
    $e->setDeveloperMessage($debugDetails);
    throw $e;
  }

  public static function NotFound($debugDetails = null) {
    $e = new FurnaceException('',HTTP::NOT_FOUND);
    $e->setDeveloperMessage($debugDetails);
    throw $e;
  }
  public static function NotAuthorized($debugDetails = null) {
    $e = new FurnaceException('',HTTP::NOT_AUTHORIZED);
    $e->setDeveloperMessage($debugDetails);
    throw $e;
  }
}
