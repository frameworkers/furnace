<?php
    class Controller extends FController {
        
        
        public function __construct() {
            parent::__construct();
        }
        
        
        public function ajaxSuccess($message,$payload = array()) {
            $response = array();
            $response['status']  = 'success';
            $response['message'] = $message;
            $response['payload'] = $payload;
            
            echo json_encode($response);
            exit();
        }
        
        public function ajaxFail($message,$payload = array()) {
            $response = array();
            $response['status']  = 'fail';
            $response['message'] = $message;
            $response['payload'] = $payload;
            
            echo json_encode($response);
            exit();
        } 
    }
?>