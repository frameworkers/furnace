<?php

class FController extends FPage {
	
	// Variable: form
	// The contents of any POSTed form data
	public $form = array();
	
	public function __construct ($layout = 'default') {
		parent::__construct($layout);
		
		if (isset($_POST) && count($_POST) > 0) {
			$this->processPostedData();	
		}
	}
	
	private function processPostedData() {
		// Store a pointer to the recently submitted data 
		$this->form =& $_POST;
		// Clear old validation errors from the session
		$_SESSION['_validationErrors'] = array();
	}
	
	public function form() {
	    return $this->form;
	}
	
	
	public function redirect($url='',$external=false) {
		// If 'external' is indicated, don't preface with url_base
		if (!$external) {
			header("Location: ".$GLOBALS['furnace']->config->url_base . ltrim($url,'/'));
			exit();
		} else {
			header("Location: {$url}");
			exit();
		}
	}
	
	public function internalRedirect($url) {
	    $request = new FApplicationRequest($url);
		_furnace()->process($request);
		exit();
	}
	
	protected function loadFragment($label) {
		$path = _furnace()->rootdir . "/scripts/fragments/{$label}Fragment.php";
		if (file_exists($path)) {
			require_once($path);
		} else {
			die(
				"The page requested a fragment ({$label}) that does not exist or is not installed correctly."
			);
		}	
	}
	
    protected function loadWidget($provider,$label) {
		$path = _furnace()->rootdir . "/plugins/widgets/{$provider}/{$label}/{$label}.php";
		if (file_exists($path)) {
			require_once($path);
		} else {
			die(
				"The page requested a helper ({$label}) that does not exist or is not installed correctly."
			);
		}	
	}
	
	protected function loadLibrary($provider,$label) {
	    $path = _furnace()->rootdir . "/plugins/libraries/{$provider}/{$label}.lib.php";
	    if (file_exists($path)) {
	        require_once($path);
	    } else {
	        die(
	            "The page requested a library ({$provider}.{$label}) that does not exist or is not installed correctly."
	        );
	    }
	}
	
	protected function ajaxContext($c) {
    		$object = _user();
            // Extract object context
            // Object context always starts with the currently logged in user (_user);
            $c = str_replace(array('{','}'),array('[',']'),$c);
    		$contexts = explode(':',$c);
            foreach ($contexts as $context) {
                // Extract the base
                $base     = substr($context,0,strpos($context,'['));
                $baseFn   = "get{$base}";
                // Obtain the selectors ([foo])
                $selectors = array();
                if (preg_match_all('/\[[^\]]+\]/',$context,$selectors)) {
                    // Use the selectors as filters on the base
                    if (false !== ($object = $object->$baseFn())) {
                        
                        foreach ($selectors[0] as $selector) {
                            list($k,$v) = explode('|',$selector);
                            if ($v == null) { $v = $k; $k = 'id'; }
                            $v = trim($v,'[]');
                            $k = trim($k,'[]');
    
                            $object->filter($k,$v);
                        }
                    }
                }
                if ( $object instanceof FObjectCollection) {
                    $object = $object->first();
                } else {
                    $this->ajaxFail('Selectors do not reduce scope to single object');
                }
                if (false == $object) {
                    $this->ajaxFail('Object does not exist, or you have insufficient access');
                }
            }
            return $object;
    }
	protected function ajaxSuccess($message,$payload = array()) {
            $response = array();
            $response['status']  = 'success';
            $response['message'] = $message;
            $response['payload'] = $payload;
            
            echo json_encode($response);
            exit();
    }
        
    protected function ajaxFail($message,$payload = array()) {
            $response = array();
            $response['status']  = 'fail';
            $response['message'] = $message;
            $response['payload'] = $payload;
            
            echo json_encode($response);
            exit();
    }
}
?>