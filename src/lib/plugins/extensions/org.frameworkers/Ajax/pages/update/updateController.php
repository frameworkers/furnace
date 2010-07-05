<?php
class UpdateController extends Controller {
    
    /**
     * object
     * 
     * Update a model object based on the parameters passed via POST
     * 
     * Usage:
     *   
     *   This function expects 3 parameters:
     *   	_obj: a specially formatted string providing a security context
     *   	_att: a string representing the attribute to be updated
     *   	_val: a string representing the new value for the attribute
     *   
     *   The function addresses security concerns through the use of a 
     *   security context that always starts at _user(). This way, no 
     *   user can ever modify any model data that s/he would not otherwise
     *   have had access to. the _obj string consists of colon (:) delimited
     *   variables which represent the path to traverse from _user() to the
     *   object of interest. For each variable, selectors can be passed in
     *   square brackets ([]) which specify which out of a group of objects 
     *   is the object of interest. Multiple selectors can be applied to each
     *   variable. It is a firm requirement that each colon-delimited varible
     *   resolve to exactly one object after all selectors have been applied. 
     *   Values inside selectors are assumed to be object ids unless a pipe 
     *   symbol (|) is detected within the selector. A pipe can bisect a selector
     *   with the content on the left being treated as the selector attribute (key)
     *   and the content on the right as the selector value. Examples below:
     *   
     *   Default selector (id assumed as key):
     *   blogEntries[7]
     *   
     *   Selector with attribute key:
     *   wikiPages[title|Hello World!]
     *   
     *   Multi-level selector
     *   blogEntries[7]:comments[5]
     *   
     *   
     *   
     * @return unknown_type
     */
    public function object() {
        if ($this->form) {
            $this->requireLogin();
            
        	$object = _user();
            // Extract object context
            // Object context always starts with the currently logged in user (_user);
            $contexts = explode(':',$this->form['_obj']);
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
            
            // Determine the attribute
            $objectAttr = $this->form['_att'];
            $attrGetFn = "get{$objectAttr}";
            $attrSetFn = "set{$objectAttr}";
            if (method_exists($object,$attrGetFn)) {
                $oldAttrVal = $object->$attrGetFn();
                $newAttrVal = addslashes(ltrim($this->form['_val'],'_'));
                $object->$attrSetFn($newAttrVal);
                if ($object->save()) {
                	
                	// Translate the response value if necessary
                	$otype = get_class($object);
                	$attrInfo = _model()->$otype->attributeInfo($objectAttr);
                	if (isset($attrInfo['allowedValues'])) {
                		foreach ($attrInfo['allowedValues'] as $av) {
                			if ($av['value'] == $newAttrVal) {
                				$newAttrVal = $av['label'];
                				break;
                			}
                		}
                	}               	
                	
                	// Return a response
                    $this->ajaxSuccess('Update Succeeded',
                        array('oldValue'=>$oldAttrVal,'newValue'=>stripslashes($newAttrVal)));
                } else {
                    $this->ajaxFail('Update Failed',
                        array('validatorMessage' => $object->validator));
                }
            } else {
                $this->ajaxFail("No such attribute '{$objectAttr}' defined");
            }
        } else {
            $this->ajaxFail("This service endpoint only supports 'POST' requests");
        }     
    }
}
?>