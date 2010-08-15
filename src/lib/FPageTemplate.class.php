<?php
/*
 * frameworkers-foundation
 * 
 * FPageTemplate.class.php
 * Created on January 01, 2010
 *
 * Copyright 2008-2010 Frameworkers.org. 
 * http://www.frameworkers.org
 */

/*
 * Class: FPageTemplate
 * Provides Furnace-specific extensions to the Tadpole
 * page templating engine.
 * 
 * Extends:
 * 
 *  TadpoleEngine
 */
class FPageTemplate extends TadpoleEngine {
    
    
    public function dispatcher($tag,&$context) {
        
        // Capture [input:...] tags
        if ("input:" == substr($context['value'],0,6)) {
            return "inputTagHandler";
        }
        
        if ("eip:" == substr($context['value'],0,4)) {
        	return "eipTagHandler";
        }
        
        // Call the Tadpole Engine's native dispatcher
        return parent::dispatcher($tag,$context);
    }
    
    public function eipTagHandler($tag,&$context,$iter_data) {
    	
    	// Process the input statement
		$context['value'] = substr($context['value'],4);
		
    	// 1. Get the base object
		$dotPosition = strrpos($context['value'],'.');
		if (false !== $dotPosition) {
			$baseObjectNeedle = $this->compile(substr($context['value'],0,strrpos($context['value'],'.')),$iter_data);
			$attributeNeedle  = $this->compile(substr($context['value'],  strrpos($context['value'],'.') + 1),$iter_data);
		} else {
			$baseObjectNeedle = $context['value'];
			$attributeNeedle  = '';
		}
		
		// 2. Style pre-processing (replace | with ; in style definition)
		if (isset($context['commands']['style'])) {
			$context['commands']['style'] = str_replace('|',';',$context['commands']['style']);
		}
		
		// 3. Generate output
    	$output = '';
		$baseObject = $this->get_recursively($baseObjectNeedle,$context['commands'],$rejected,$iter_data);
		if (!$rejected && is_object($baseObject)) {
			
			// Special case for the 'id' attribute:
			if ('id' == $attributeNeedle) {
				// check for name override
				$name   = (isset($context['commands']['name'])) ? $context['commands']['name'] : "{$baseObject->_getObjectClassName()}_id";
				$this->inputApplyOutput($tag,$context,'');
				return true;
			// All other attributes:
			} else {
			    $object = $baseObject->_getObjectClassName();
			    try {
			        if (false !== ($info = _model()->$object->attributeInfo($attributeNeedle))) {
			            $this->inputApplyOutput($tag,$context,
			                $this->eip_helper($baseObject,$attributeNeedle,$info,$context['commands']));
			            return true;
			        } else {
			            $this->inputRejected($tag,$context);
			            $rejected = true;
			            return false; // attribute not found
			        }
			    } catch (FException $e) {
			         // swallow the exception
			         $this->inputRejected($tag,$context);  
			         $rejected = true;
			         return false;
			    }
			}
		} else {
                // Baseobject was rejected or is not an object as required
                $this->inputRejected($tag,$context);
                $rejected = true;
                return false;  
		}
	}
	
	public function eip_helper($object,$attributeNeedle,$attributeData,$commands) {
		$value = $object->get($attributeNeedle);
		$containerStyle = '';
		
		// If no user is logged in, no EIP is possible, therefore
		// simply echo out the value.
		if (!_user()) {
			return $value;
		}
		
		// Otherwise, calculate the type of eip element to display
		$meta  = array();
		switch ($attributeData['type']) {
			case 'text':
				$meta['class'] = 'editable area';
				$meta['style'] = 
					(isset($commands['width']) 
						? "width:{$commands['width']}; "
						: "width:150px; ") . 
					(isset($commands['height'])
						? "height:{$commands['height']}; "
						: "height:80px; ");
				break;
			default:
				$meta['class'] = 'editable' . ((isset($commands['hint']) ? "-{$commands['hint']} " : ' ')) . (isset($attributeData['allowedValues']) ? 'select' : 'text');
				$meta['style'] = (isset($commands['width']) 
					? "width:{$commands['width']}"
					: "width:150px;");
					
				// Prepare a data array if necessary
				$meta['data'] = array();
				
				if (isset($attributeData['allowedValues'])) {
					// Build an array of key/value pairs
					foreach ($attributeData['allowedValues'] as $av) {
						$meta['data']["_{$av['value']}"] = stripslashes($av['label']);
					}
					// Translate the current value, while we're at it
					$value = $meta['data']["_{$value}"];
				}
				
				// Encode the data array
				$meta['data']  = json_encode($meta['data']);	
		}
		
		// Process any commands
		if (isset($commands['inline'])) { // Displays the EIP 'inline'
			$containerStyle .= "display:inline; ";
		}
		if (isset($commands['action'])) { // Sends the EIP req. to a custom url
			$meta['action'] = $commands['action'];
		}
		if (isset($commands['callback'])) { // Calls a javascript function afterwards
			$meta['callback'] = $commands['callback'];
		}
		if (isset($commands['hint'])) {	// Formats the value based on the hint
			if ($commands['hint'] == 'dateonly') {
				$value = substr($value,0,10);
			}
		}
			
		// Generate the element itself
		return '<div class="editable-container" style="'.$containerStyle.'"><div context="'
			. str_replace(array('{','}'),array('[',']'),$commands['context']) 
			. '" _attr="'    . $attributeNeedle 
			. '" style="'    . $meta['style'] 
			. '" class="'    . $meta['class']
			. '" action="'   . $meta['action']
			. '" callback="' . $meta['callback']
			. '" data="'     . htmlentities($meta['data'])
			. '" >' . stripslashes($value) . '</div>'
			. '</div>';	
	}
    
    public function inputTagHandler($tag,&$context,$iter_data) {
        
		// Process the input statement
		$context['value'] = substr($context['value'],6);
		
		// 1. get the base object
		$dotPosition = strrpos($context['value'],'.');
		if (false !== $dotPosition) {
			$baseObjectNeedle = $this->compile(substr($context['value'],0,strrpos($context['value'],'.')),$iter_data);
			$attributeNeedle  = $this->compile(substr($context['value'],  strrpos($context['value'],'.') + 1),$iter_data);
		} else {
			$baseObjectNeedle = $context['value'];
			$attributeNeedle  = '';
		}
		// style pre-processing (replace | with ; in style definition)
		if (isset($context['commands']['style'])) {
			$context['commands']['style'] = str_replace('|',';',$context['commands']['style']);
		}

		$output = '';
		// If the baseObjectNeedle begins with '~', it is a class definition, not an object
		if ('~' == $baseObjectNeedle[0]) {
			try {
			    $object = substr($baseObjectNeedle,1);
			    if (false !== ($info = _model()->$object->attributeInfo($attributeNeedle))) {
				    $this->inputApplyOutput($tag,$context,
				        $this->input_helper($object,$attributeNeedle,$info,$context['commands']));
				    return true;
			    } else {
			        $this->inputRejected($tag,$context);    // No such attribute
			        $rejected = true;
			        return false;
			    }
			} catch (Exception $e) {
			    $this->inputRejected($tag,$context);
				$rejected = true;
				return false;
			}
		} else {
			$baseObject = $this->get_recursively($baseObjectNeedle,$context['commands'],$rejected,$iter_data);
			if (!$rejected && is_object($baseObject)) {
				
				// Special case for the 'id' attribute:
				if ('id' == $attributeNeedle) {
					// check for name override
					$name   = (isset($context['commands']['name'])) ? $context['commands']['name'] : "{$baseObject->_getObjectClassName()}_id";
					$this->inputApplyOutput($tag,$context,
						"<input type=\"hidden\" name=\"{$name}\" value=\"{$baseObject->getId()}\"/>");
					return true;
				// All other attributes:
				} else {
				    $object = $baseObject->_getObjectClassName();
				    try {
				        if (false !== ($info = _model()->$object->attributeInfo($attributeNeedle))) {
				            $this->inputApplyOutput($tag,$context,
				                $this->input_helper($baseObject,$attributeNeedle,$info,$context['commands']));
				            return true;
				        } else {
				            $this->inputRejected($tag,$context);
				            $rejected = true;
				            return false; // attribute not found
				        }
				    } catch (FException $e) {
				         // swallow the exception
				         $this->inputRejected($tag,$context);  
				         $rejected = true;
				         return false;
				    }
				}
			} else {
                // Baseobject was rejected or is not an object as required
                $this->inputRejected($tag,$context);
                $rejected = true;
                return false;  
			}
		}
		
		if (!$rejected ) {
			$this->inputApplyOutput($tag,$context,$output);
		} else {
		    $this->inputRejected($tag,$context);
		} 
    }
    
    public function input_helper($object,$attributeName,$attributeData,&$commands) {
		
		// prefer POSTed/submitted values over the currently stored object attribute value
		if (isset($_POST) && isset($_POST[$attributeName])) {
			// reads the value from the POST array
			$value = $_POST[$attributeName];
		} else if (null !== ($value = _readUserInput($attributeName))) {
			// reads the value from the previously saved user input
		} else if (is_object($object)) {
			// reads the value from the stored object attribute value
			$fn = "get{$attributeName}";
			$value = $object->$fn();
		} else if (isset($commands['value'])) {
			$value = $this->compile($commands['value']);
		} else {
			// No value provided
			$value = '';
		}
		
		// determine whether any validation errors exist for the attribute
		if (isset($_SESSION['_validationErrors'][$attributeName])) { 
			unset($_SESSION['_validationErrors'][$attributeName]);
			$bHasErrors = true;
			// Update the class list to add the inputError class
			if (!isset($commands['class'])) {
				$commands['class']  = "inputError";
			} else {
				$commands['class'] .= " inputError ";
			}
		} else {
			$bHasErrors = false;
		}

		// Input name and max length
		$name     = " name=\"{$attributeName}\" ";
		$maxlen   = " maxlen=\"{$attributeData['maxlen']}\" ";
		
		// Process commands
		$id       = (isset($commands['id']))       ? " id=\"{$commands['id']}\" " : '';
		$class    = (isset($commands['class']))    ? " class=\"{$commands['class']}\" " : '';
		$style    = (isset($commands['style']))    ? " style=\"{$commands['style']}\" " : '';
		$title    = (isset($commands['title']))    ? " title=\"{$commands['title']}\" " : '';
		$tabindex = (isset($commands['tabindex'])) ? " tabindex=\"{$commands['tabindex']}\" " : '';
		
		// determine the type of input to create
		switch ($attributeData['type']) {
			case 'boolean':
				$checked = ($value === true) ? ' checked="checked" ' : '';
				return "<input type=\"checkbox\" {$id} {$name} {$class} {$style} {$title} {$tabindex} {$checked}/>";
			case 'text':
				return "<textarea {$id} {$name} {$class} {$style} {$title} {$tabindex}>".stripslashes($value)."</textarea>";
			case 'password':
				return "<input type=\"password\" {$id} {$name} {$class} {$style} {$title} {$tabindex} value=\"".stripslashes(htmlentities($value))."\" />";
			case 'string':
				if (isset($attributeData['allowedValues'])) {
					// create a select box
					if (is_object($object)) { $commands['selected'] = $value; }
					$option = '';
					foreach ($attributeData['allowedValues'] as $opt) {
					    $selected = (isset($commands['selected']) && ($opt['value'] == ($selectedValue = $this->compile($commands['selected'],$iter_data))))  
					        ? " selected='selected' "
					        : '';
						$option .= "<option value=\"{$opt['value']}\" {$selected}>{$opt['label']}</option>";
					}
					return "<select {$id} {$name} {$class} {$style} {$title} {$tabindex}>{$option}</select>";
					break;	
				} // otherwise, just draw a normal text box:
			case 'integer':
				if (isset($attributeData['allowedValues'])) {
					// create a select box
					if (is_object($object)) { $commands['selected'] = $value; }
					$option = '';
					foreach ($attributeData['allowedValues'] as $opt) {
						$selected = (isset($commands['selected']) && ($opt['value'] == $this->compile($commands['selected'],$iter_data))) 
					        ? " selected='selected' "
					        : '';
						$option .= "<option value=\"{$opt['value']}\" {$selected}>{$opt['label']}</option>";
					}
					return "<select {$id} {$name} {$class} {$style} {$title} {$tabindex}>{$option}</select>";
					break;	
				} // otherwise, just draw a normal text box:
			case 'float':
			default:
				return "<input type=\"text\" {$id} {$name} {$class} {$style} {$title} {$tabindex} {$maxlen} value=\"".stripslashes(htmlentities($value))."\"/>";
		}
	}
    
    private function inputApplyOutput($tag,&$context,$output) {
        // apply the output and update the offset
		$before   = substr($context['contents'],0,$context['tagStart']);
		$after    = substr($context['contents'],$context['tagEnd'] + 1);				

		$context['contents'] = $before . $output . $after;
		// Increment offset
		$context['offset'] = (false !== $context['outerOffset']) 
		    ? $context['outerOffset'] 
		    : ($context['tagStart'] + strlen($output));
		// Move on to the next tag
		return true;
    }
    
    private function inputRejected($tag,&$context) {
        $context['offset'] = (false !== $context['outerOffset'])
            ? $context['outerOffset']
            : ($context['tagEnd'] + 1);
    }
    
    /**
     * OVERRIDE
     */
    protected function get_recursively($needle,&$commands,&$rejected,$iter_data=array()) { 

		//NOTES:
		// If iteration data has been provided, it is to be used with all "relative" tags, ie those
		// beginning with [@...], otherwise needles are replaced with data from page_data

		// Short-exit for $needle = "@". In this case, the actual value is the simple string provided
		// in iter_data. There is no need to set everything else up.
		if ("@" == $needle) { return $iter_data;}

        // Short-exit for iteration count. In this case, simply return [__idx]. The engine will later
		// replace instances of this string with the appropriate iteration index.
		if ('@.##' == $needle) { return "[__idx]";}
		
		// If we have not shorted out, set things up to decompose and process the needle.
		$flashlight = false;
		$conditional = (isset($needle[2]) && "if:" == substr($needle,0,3));	// test for strlen at least 3 and beginning with "if:"
		
		// If this is a conditional tag...
		if ($conditional) {
			// ...call a helper to decompose and evaluate the condition, returning either true or false
			return $this->condition_helper(substr($needle,3),$commands,$rejected,$iter_data);
		
		// Otherwise, decompose and process the needle here
		} else {
			// Determine whether this is a relative or absolute tag
			$relative = ("@" == $needle[0]);

			// Determine which data to use when interpreting the needle
			if ($relative) {
				// Use ITERATION DATA
				$flashlight = $iter_data;
				$needle = substr($needle,2);	// Trim off the "@."
			} else {
				$flashlight = $this->page_data;
			}
			
			// Split the needle into its constituent parts
			$parts = explode(".",$needle);
			$segmentCountMinusOne = count($parts) - 1;

					
			// Process the needle
			$count = 0;
			foreach ($parts as $segment) {			    
				$bIsObject     = is_object($flashlight);
				$bExists       = false;
				$privateMethod = "get{$segment}"; 	
				
				if ($bIsObject && is_subclass_of($flashlight,"FObjectCollection")) {
				    // Go directly to the 'data' attribute of this collection
				    $flashlight =& array_values($flashlight->data);    // strip the o_* keys
				    $bIsObject  =  false;
				}
				
				if ($bIsObject) {
					$bExists = (!empty($flashlight->$segment) || is_callable(array($flashlight,$privateMethod)));
				} else {
					$bExists = (isset($flashlight[$segment]) || '#' == $segment);
				}
				
				if ($bExists) {
					// If we are at the penultimate segment, or if there is only one segment, prepare the return value
					if ($count == $segmentCountMinusOne) {
						
						// Handle the case in which a 'count' of the number of objects is requested
						if ( "#" == $segment ) {
							// return the number of objects
							return count($flashlight);
						}
						
						if ($bIsObject) {
							// If the object has a private method defined, call it
							if (is_callable(array($flashlight,$privateMethod))) {
							    // Call the private method and return its result
							    if (isset($commands['argv'])) {
							        $args = explode('|',$this->compile($commands['argv']));
							        $result = call_user_func_array(array($flashlight,$privateMethod),$args);   
							    } else {
							        $result = $flashlight->$privateMethod();
							    }
							    if (is_object($result) && $result instanceof FObjectCollection) {
							        return $result->output();            // return the objects in the collection
							    } else {
							        if (isset($commands['decode'])) {
							            $decodeFn = "Decode{$segment}";  // Decode the object before returning
							            $commands['nocache'] = true;     // Do not cache the decoded result;   
							            return $flashlight->$decodeFn($result);
							        } else {
							            return $result;
							        }
							    }
							
							// If the object does not have a private method defined, try to return the public attribute
							} else {
								
								// Handle the case in which a limited subset of matches is requested
								if (isset($commands['start']) || isset($commands['limit'])) {
									
									return array_slice($flashlight->$segment,
										(isset($commands['start']) ? $commands['start'] : 0),
										(isset($commands['limit']) ? $commands['limit'] : count($flashlight->segment)));
								
								// Handle the case in which all matches are to be returned
								} else {
									
									return $flashlight->$segment;
								}
							}
						// Handle the case where the flashlight is not an object
						} else {
							
							// Handle the case in which a limited subset of matches is requested
							if (isset($commands['start']) || isset($commands['limit'])) {
								
								return array_slice($flashlight[$segment],
									(isset($commands['start']) ? $commands['start'] : 0),
									(isset($commands['limit']) ? $commands['limit'] : count($flashlight[$segment])));
									
							// Handle the case in which all matches are to be returned
							} else {
								
								return $flashlight[$segment];
							}
							
						}
					// Otherwise, advance to the next segment of the needle by whichever of the 3 methods is
					// appropriate depending on the nature of the current segment.
					} else {
						// Advance to the next segment
						if ($bIsObject) {
							if (is_callable(array($flashlight,$privateMethod))) {
								$flashlight =& $flashlight->$privateMethod();
							} else {
								$flashlight =& $flashlight->$segment;
							}
						} else {
							$flashlight =& $flashlight[$segment];
						}
						
						// Increment the segment counter
						$count++;
					}
				// If the object does not exist, set the rejected flag and return;
				} else {
					$rejected = true;	// set the rejected flag
					return false;
				}
			}
		}
	}
}
?>
