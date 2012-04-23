<?php

use furnace\core\Config;
use furnace\core\Furnace;
use furnace\response\ResponseChunk;


function env($key = null,$default = null) {

    $v = Config::Get($key);
    return (null == $v) 
        ? $default
        : $v;
}

function text($contents) {
    return new ResponseChunk($contents);
}   

function html($contents) {
    return new ResponseChunk($contents);
}

function json($contents) {
    return new ResponseChunk(json_encode($contents));
}

function template(ResponseChunk $chunk) {
    // Using a template engine
    if (Config::Get('template.engine')) {
        $renderEngine = Config::Get('template.engine');
        $renderEngine::init();
        return new ResponseChunk($renderEngine::process($chunk));
    
    // Using straight PHP
    } else {
        ob_start();
        $_data = $chunk->data();
        echo eval('?>' . $chunk->contents());
        $result = ob_get_contents();
        ob_end_clean();
        return new ResponseChunk($result);
    }
}

function success($message) {
    return flash($message,"Success!",'success');
}
function warn($message) {
    return flash($message,"Warning:",'warn');
}
function error($message) {
    return flash($message,"An error has occurred:",'error');
}
function info($message) {
    return flash($message,"For your information:",'info');
}
function flash($message,$title,$cssClasses) {
    return new ResponseChunk(
        "<div class='f_flashMessage {$cssClasses}'><h5>{$title}</h5>{$message}</div>");
}

function img_url($path, $base = 'img') {
    return Config::Get('app.themes.url') 
    	   . Config::Get('app.theme') . "/{$base}/{$path}";
}

function js_url($path, $base = 'js') {
    return Config::Get('app.themes.url') 
    	   . Config::Get('app.theme') . "/{$base}/{$path}";
}

function css_url($path,$base = 'css') {
    return Config::Get('app.themes.url') 
    	   . Config::Get('app.theme') . "/{$base}/{$path}";
}

function href($url) {
    return F_URL_BASE . $url;
}

function input($type,$name,$id='',$data = null,$default = '') {
    switch (strtolower($type)) {
        case 'textarea':
            return '<textarea name="'.$name.'" id="'.$id.'">'.$data.'</textarea>';
        case 'text':
        default:
            return '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$data.'"/>';
    }
}


function load_region($name) {
    return Furnace::Response()->data($name);
}








