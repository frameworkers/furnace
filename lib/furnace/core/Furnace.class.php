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
                if (Config::Get('environment') == F_ENV_DEVELOPMENT) {
                    Furnace::halt("Unable to handle request","Furnace was unable to find a "
                    . "controller file at:  "
                    . "<br/><code>{$controllerFilePath}</code> ");
                } else {
                    Furnace::NotFound();
                }
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
            if (Config::Get('environment') == F_ENV_DEVELOPMENT) {
                Furnace::halt("Unable to handle request","Furnace was unable to find a "
                . "function definition for:  "
                . "<br/><code>{$controllerClassName}::{$handlerFunction}(...)</code> &nbsp;in the file:"
                . "<br/><code>{$controllerFilePath}</code>");
            } else {
                Furnace::NotFound();
            }
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

    public static function halt($message, 
        $debugDetails = 'No additional information available') {
        
        self::SendError( HTTP_500, $message, null, $debugDetails );
    }

    public static function InternalError($debugDetails = null) {
        self::SendError( HTTP_500, 
            Config::Get('message.500','An internal server error has occurred'), 
            null, $debugDetails );
    }

    public static function NotFound($debugDetails = null) {
        self::SendError( HTTP_404, 
            Config::Get('message.404','The requested resource could not be found'), 
            null, $debugDetails );
    }
    public static function NotAuthorized($debugDetails = null) {
        self::SendError( HTTP_403, 
            Config::Get('message.403','You are not authorized to view the requested resource'), 
            null, $debugDetails );
    }

    protected static function SendError($code = HTTP_500, 
        $message = "No additional information available",
        $details = "No additional information available",
        $debugDetails = "No additional information available") {

        $code_translated = "HTTP_{$code}";
        $code_translated = HTTP::$$code_translated;

        $backtrace = debug_backtrace();
        array_shift($backtrace);
        Logger::Log(LogLevel::CRIT, 
            "Halting app (at " . basename($backtrace[1]['file']) 
            . ":{$backtrace[1]['line']}) with message: {$debugDetails}");
        if (self::$request) {
            Logger::Log(LogLevel::INFO, "Request URI was: " . self::$request->getCleanUri());
        }
        ob_end_clean(); 
        self::header('Application Halted');?>
        <h1><?php echo Config::Get('app.title','Furnace');?></h1>        
        <h2><?php echo "<em style='font-weight:normal'>{$code}</em> &nbsp;{$code_translated}"?></h2>
        <p><big>&ldquo;</big><?php echo $message;?><big>&rdquo;</big></p>
        <?php if (Config::Get('environment') == F_ENV_PRODUCTION && $details): ?>
        <h4>Details:</h4>
        <p class="f_details"><?php echo $details?></p>
        <?php endif;?>
        <?php if(Config::Get('environment') == F_ENV_DEVELOPMENT):?>
        <h4>Details:</h4>
        <p class="f_details"><?php echo $details?></p>
        <p class="f_details"><?php echo $debugDetails?></p>
        <h4>Stack Trace:</h4>
        <pre>
        <?php array_walk(
                $backtrace,
                create_function('$a,$b','print "\r\n\t{$a[\'function\']}() ".basename($a[\'file\']).":{$a[\'line\']}";'));?>
        </pre>
        <?php endif;
        echo self::footer();
        self::Cleanup();
    }

    public static function header($title = 'Frameworkers - Furnace') {
        echo <<< END_HEADER
<!DOCTYPE html>
<html>
<head>
  <title>{$title}</title>
  <style type="text/css">
    body {
        background: -moz-linear-gradient(center top , #FFFFFF, #ECE9E9) no-repeat scroll 0 0 transparent;
        border-bottom:solid 1px #888;
        color: #555;
        font: 13px "Helvetica Neue","Lucida Grande","Arial";
        margin:0px;
        padding:80px 100px;
    }
    h1 {
        font-size:60px;
    }
    h1,h2,h3,h4 {
        margin:0px;
        color:#343434;
    }
    .f_footer {
        border-top:solid 1px #888;
        padding-top:5px;
        font-size:90%;
        color:#888;
    }
    .f_footer a {
        color:#78a;
        text-decoration:none;
    }
    .f_footer a:hover {
        text-decoration:underline;
        color:#568;
    }
    pre {
        border:dashed 2px #ccc;
        padding:0px 15px 15px 15px;
    }
    p {
        font-family:"Times New Roman", serif;
        font-size:24px;
        font-style:italic;
        margin-top:10px;
        line-height:28px;
    }
    p.f_details {
        font-style:normal;
        font-size:1em;
        font-family:"Helvetica Neue","Lucida Grande","Arial";
    }
    code {
        border:solid 1px #ccc;
        background-color:#eee;
        padding:2px;
    }
  </style>
</head>
<body>
END_HEADER;
    }

    public static function footer() {
        echo '<div class="f_footer">';
        echo Config::Get('app.copyright')                 . ' &nbsp;|&nbsp; ';
        echo 'version: ' . Config::Get('app.version')     . ' &nbsp;|&nbsp; ';
        echo '<a href="' . F_URL_BASE . Config::Get('app.url.terms')   . '">Terms of Use</a>' . ' &nbsp;|&nbsp; ';
        echo '<a href="' . F_URL_BASE . Config::Get('app.url.privacy') . '">Privacy</a>';
        echo "<br/><small>Built with Furnace v." . FURNACE_VERSION . ' | ';
        echo '<a href="http://furnace.frameworkers.org">http://furnace.frameworkers.org</a>';
        echo '</small></div>';
        echo '</body></html>';
    }
}
