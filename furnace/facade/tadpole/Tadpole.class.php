<?php
/*
 * Tadpole Page Templating Engine
 * 
 * Tadpole.class.php
 * Created on June 10, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: Tadpole
  * A simple, lightweight page template engine.
  */
class Tadpole {

	// Array: page_data
	// An array of the registered variables and blocks
	protected $page_data;
	
	// Variable: contents
	// The page contents
	protected $contents;
	
	public function __construct($contents='') {
		$this->contents  = $contents;
		$this->page_data = array();
		$this->iter_data = array();
	}
	
	public function setTemplate($templatePath,$compileNow = false) {
		$this->contents = file_get_contents($templatePath);
		if ($compileNow) {
			$this->compile();
		}
	}
	
	public function register($key,$value = true) {
		$this->page_data[$key] = $value;	
	}
	public function set($key,$value = true) {
		$this->page_data[$key] = $value;	
	}
	
	public function compile() {
		$this->process($this->contents);
	}
	public function getContents() {
		return $this->contents;	
	}	
	protected function process(&$contents, $iter_data = array()) {
		
		if ("" == $contents) {return;}
		
		// FIND_NEXT_TAG
		$startMarkers = array(
			"[tp.",				/* absolute var/block definitions */
			"[@",				/* relative var/block definitions */
			"[tp-if:",			/* conditional var/block definitions */
			"[!");				/* super tags */
		$endMarker    = "]";
		
		// maxReverseSearch: The number of characters to search
		// backwards from the marker for an html tag before giving up
		$maxReverseSearch = 200;
		// selfTag: The tag to use when referring to an element that
		// consists of a single tag (<img>,<input>,<link>,etc). It can
		// be used in statements like [images.#;block=<<selfTag>>]. The
		// default value is 'self'. An example:
		// $images = array('img1.jpg','img2.jpg','img3.jpg');
		// <div class="gallery"><img src="[tp.images.#;block=self][@]"/></div>
		// Generates:
		// <div class="gallery"><img src="img1.jpg"/><img src="img2.jpg"/>...
		$selfTag = "self";
				
		$offset  = 0;
		$current = 0;
		$marker  = '';
		
		while (1) {
			$offset = 0;	//Todo: This needs rework.
			// DETERMINE POSITION OF NEXT MARKER
			$nextTagCandidatePosition = strlen($contents);
			$foundCandidate = false;
			$candidateMarker = '';
			foreach ($startMarkers as $sm) {
				$test = strpos($contents,$sm,$offset);
				if ($test !== false && $test < $nextTagCandidatePosition) {
					$nextTagCandidatePosition = $test;
					$foundCandidate = true;
					$candidateMarker = $sm;
				}
			}
			// EXTRACT WHOLE MARKER
			if ($foundCandidate ) {
				$current = $nextTagCandidatePosition; 
				$marker  = substr($contents,
					$current,
					(strpos($contents,$endMarker,$current) - ($current-1)));
			} else {
				return(true); // No tags left.
			}
			// SPLIT IDENTIFIER AND COMMANDS
			list ($identifier,$commandstr) = explode(";",trim($marker,"[]"),2);
			
			// PROCESS COMMANDS
			$commands = array();
			$raw = explode(";",$commandstr);
			foreach ($raw as $r) {
				if (0 != ($start = strpos($r,"=`"))) {
					$commands[substr($r,0,$start)] = rtrim(substr($r,$start+2),"`");
					//die("command: " . substr($r,0,$start) . " value: " . rtrim(substr($r,$start+2),"`"));
				} else {
					list($k,$v) = explode("=",$r);
					$commands[$k] = $v;
				}
			}
	
			
			// IF BLOCK DEFINITION
			if ("#" == substr($identifier,strlen($identifier)-1,1) ) {
				$blockContents = '';
				// DETERMINE HTML TAG
				$tag = $commands['block'];
				
				// EXTRACT AND STORE CONTENT
				// -- determine content start
				$contentStart = $current 
					- ((min($current,$maxReverseSearch)) 
					- strrpos(
						substr($contents,$current-min($current,$maxReverseSearch),
							min($current,$maxReverseSearch)),(($tag == $selfTag) ? "<" : "<{$tag}")));
	
				// -- find a closing tag, taking possible nesting into account
				$contentEnd = strpos($contents,(($tag == $selfTag) ? ">" : "</{$tag}>"),
					min(strlen($contents)-1,
					$current + strlen($marker)));
				
				$tempStart = $current + strlen($marker);
				while (false !== 
					($nestedStart = strpos($contents,(($tag == $selfTag) ? "<" : "<{$tag}"),$tempStart)) 
						&& $nestedStart < $contentEnd) {
					$contentEnd = strpos($contents,(($tag == $selfTag) ? ">" : "</{$tag}>"),
						$contentEnd+1);
					$tempStart = $nestedStart + 1;
				}
				
				// -- extract the content block
				$content = substr($contents,$contentStart,
					$contentEnd-$contentStart+((($tag == $selfTag) ? 1 : strlen($tag)+3)));
					
				// -- store relevant information about the block
				$originalContentLength = strlen($content);
				$coda = substr($contents,$contentStart+$originalContentLength);
				
				// -- compute the position of the marker within the block content
				$relativeMarkerStart = strpos($content,$marker);
				$relativeMarkerEnd   = $relativeMarkerStart + strlen($marker);
				
				// -- compute the portion of the content that will be checked
				$subcontent = substr($content,$relativeMarkerEnd);
	
				// DETERMINE CORRESPONDING DATA
				// if blockname begins with an @, use iter_data
				// else use page_data
				$id_parts = explode(".",$identifier);
				$id_parts_size = count($id_parts);
				if ("@" == $id_parts[0]) {
					unset($id_parts[0]);				// drop the 'tp.'
					unset($id_parts[$id_parts_size-1]);	// drop the '#'
					$data = $this->getRecursively($id_parts,$iter_data);
				} else {
					unset($id_parts[0]);				// drop the 'tp.'
					unset($id_parts[$id_parts_size-1]);	// drop the '#'
					$data = $this->getRecursively($id_parts);
				}
				
				if (count($data) == 0) {
					$blockContents = ((isset($commands['ifempty'])) ? $commands['ifempty'] : '');
				} else {
					// FOREACH DATA, RECURSE & APPEND CONTENT
					foreach ($data as $iteration) {
						$iterContents  = $subcontent;
						$this->process($iterContents,$iteration);
						$blockContents .= 
							substr($content,0,$relativeMarkerStart) 
							. $iterContents;
					}
				}

				// REPLACE BLOCK DEFINITION WITH BLOCK CONTENTS
				$contents = substr($contents,0,$contentStart)
					. $blockContents
					. $coda;
			} else if ($candidateMarker == "[tp-if:") {
				// IS_CONDITIONAL
				if (isset($commands['block'])) {
					/* special processing for conditional block */
					// DETERMINE HTML TAG
					$tag = $commands['block'];

					// GET THE BLOCK CONTENTS
					// -- determine content start
					$contentStart = $current 
						- ((min($current,$maxReverseSearch)) 
						- strrpos(
							substr($contents,$current-min($current,$maxReverseSearch),
								min($current,$maxReverseSearch)),(($tag == $selfTag) ? "<" : "<{$tag}")));
	
					// -- find a closing tag, taking possible nesting into account
					$contentEnd = strpos($contents,(($tag == $selfTag) ? ">" : "</{$tag}>"),
						min(strlen($contents)-1,
						$current + strlen($marker)));
				
					$tempStart = $current + strlen($marker);
					while (false !== 
						($nestedStart = strpos($contents,(($tag == $selfTag) ? "<" : "<{$tag}"),$tempStart)) 
							&& $nestedStart < $contentEnd) {
						$contentEnd = strpos($contents,(($tag == $selfTag) ? ">" : "</{$tag}>"),
							$contentEnd+1);
						$tempStart = $nestedStart + 1;
					}
				
					// -- extract the content block
					$content = substr($contents,$contentStart,
						$contentEnd-$contentStart+((($tag == $selfTag) ? 1 : strlen($tag)+3)));
						
					// -- store relevant information about the block
					$originalContentLength = strlen($content);
					$coda = substr($contents,$contentStart+$originalContentLength);
					
					// -- compute the position of the marker within the block content
					$relativeMarkerStart = strpos($content,$marker);
					$relativeMarkerEnd   = $relativeMarkerStart + strlen($marker);
				
					// -- compute the portion of the content that will be checked
					$subcontent = substr($content,$relativeMarkerEnd);
					
				}
				
				// DETERMINE CONDITION FORMAT AND COMPONENTS
				$bNegate = false;
				list($var,$value) = explode("=",(substr($identifier,6)));
				
				// -- check for rhs negation
				if ("~" == substr($value,0,1)) {
					$bNegate = true;
					$value   = substr($value,1);
				}
				
				// -- break out the variables, if necessary
				$lhsParts = explode(".",$var);
				$rhsParts = explode(".",$value);
				
				$lhs_value      = false;
				$rhs_value      = false;
				
				// -- Process the left hand side variable (tp,@)
				if ("tp" == $lhsParts[0]){
					unset($lhsParts[0]);
					$lhs_value = $this->getRecursively($lhsParts);
				} else if ("@" == $lhsParts[0]) {
					unset($lhsParts[0]);
					$lhs_value = $this->getRecursively($lhsParts,$iter_data);
				}
				
				// -- Process the right hand side variable (tp,@,~,true,false,_scalar_)
				if (count($rhsParts) > 1 && ("tp" == $rhsParts[0] || "@" == $rhsParts[0])) {
					if ("tp" == $rhsParts[0]){
						unset($rhsParts[0]);
						$rhs_value = $this->getRecursively($rhsParts);
					} else if ("@" == $rhsParts[0]) {
						unset($rhsParts[0]);
						$rhs_value = $this->getRecursively($rhsParts,$iter_data);
					}
				} else {
					if (empty($value) || "true" == $value) {
						$rhs_value = true;
					} else if ("false" == $value) {
						$rhs_value = false;
					} else {
						$rhs_value = $value;
					}
				}

				// EVALUATE CONDITION
				if ((!$bNegate && ($lhs_value == $rhs_value)) || ($bNegate && ($lhs_value != $rhs_value))) {
					// IF CONDITION HOLDS, REPLACE TAG with content 
					if (isset($commands['block'])) {
						/* conditional blocks */
						$before = substr($contents,0,$current);
						$after  = substr($contents,$current+strlen($marker));
						// Display the block, minus the tadpole tag
						$contents = $before.$after;	
					} else {
						/* conditional variables */
						$before = substr($contents,0,$current);
						$after  = substr($contents,$current+strlen($marker));
						$contents = $before.$commands['content'].$after;
					}
				} else {
					// IF CONDITION FAILS, REPLACE TAG with ""
					if (isset($commands['block'])) {
						/* conditional blocks */
						$before = substr($contents,0,$contentStart);
						$after  = substr($contents,$contentStart + strlen($content));
					} else {
						/* conditional variables */
						$before = substr($contents,0,$current);
						$after  = substr($contents,$current+strlen($marker));	
					}
					$contents = $before . (empty($commands['else']) ? '' : $commands['else']) . $after;
				}
				
				
				
			} else {
				// IS_VARIABLE
				// DETERMINE CORRESPONDING DATA
				// if blockname begins with an @, use iter_data
				// else use page_data
				$id_parts = explode(".",$identifier);
				if ("@" == $id_parts[0]) {
					unset($id_parts[0]);
					if (count($id_parts) == 0) {	// added to support [@] tags for block=self blocks
						$data = $iter_data;
					} else {
						$data = $this->getRecursively($id_parts,$iter_data);
					}
				} else if ("!" == $id_parts[0]) {
					if ("date" == $id_parts[1]) {
						$data = date(((isset($commands['format'])?$commands['format']:'Y-m-d')),mktime());	
					} else {
						die("Unsupported super tag");
					}	
				} else {
					unset($id_parts[0]);
					$data = $this->getRecursively($id_parts);
				}
				
				// USE DEFAULT VALUE, IF SPECIFIED
				if ("" == $data && "" != $commands['ifempty']) {
					$data = $commands['ifempty'];
				}
				
				
				// USE DEFAULT VALUE FOR DATES, IF SPECIFIED
				if ("0000-00-00" == $data && "" != $commands['ifnever']) {
					$data = $commands['ifnever'];
				}
				
				// USE DEFAULT VALUE FOR 0-valued NUMBERS, IF SPECIFIED 
				if ("0" == $data && "" != $commands['ifzero']) {
					$data = $commands['ifzero'];
				}
				
				// GLUE DATA TOGETHER, IF REQUIRED
				if (is_array($data) && isset($commands['glue'])) {
					$data = implode($commands['glue'],$data);
				}
				
				// MAP DATA TO VALUES, IF SPECIFIED
				// mapped values look like:
				// ;map=0|Zero,1|One,2|Something Else,...
				if (isset($commands['map'])) {
					$opts = explode(",",$commands['map']);
					foreach($opts as $o) {
						if ($data == substr($o,0,strpos($o,"|"))) {
							$data = trim(substr($o,strpos($o,"|")),"|");
							break;
						}
					}	
				}
				
				// REPLACE TAG WITH DATA
				$contents = substr($contents,0,$current)
					. $data
					. substr($contents,$current + strlen($marker)/*+strlen($candidateMarker)-4*/);
			}
		}
	}
	
	protected function getRecursively(&$vars,$data_source=null,$start="",$ordinal=null) {
		if ("" == $start) {
			$flashlight = (null == $data_source) ? $this->page_data : $data_source;
		} else {
			$flashlight = (null == $data_source) ? $this->page_data[$start] : $data_source[$start];
			$count = 0;
			foreach ($flashlight as $item) {
				if ($count++ == $ordinal) {
					$flashlight =& $item;
					break;	
				}
			}
		}
		
		$count = 0;
		foreach ($vars as $elmt) {
			$bIsObject = is_object($flashlight);
			$privateMethod = "get" . (strtoupper(substr($elmt,0,1)) . substr($elmt,1));
			$exists = false;
			if ($bIsObject) {
				if (!empty($flashlight->$elmt) || (is_callable(array($flashlight,$privateMethod)))) {
					$exists = true;
				}
			} else {
				$exists = ((isset($flashlight[$elmt]))
					? true
					: false
				);
			}
			if ($exists) {
				if ($count == count($vars)-1) {
					// Return the current data
					if ($bIsObject) {
						if (is_callable(array($flashlight,$privateMethod))) {
							return $flashlight->$privateMethod();
						} else {
							return $flashlight->$elmt;	
						}
					} else {
						return $flashlight[$elmt];
					}
				} else {
					// Advance through the data
					if ($bIsObject) {
						if (is_callable(array($flashlight,$privateMethod))) {
							$flashlight =& $flashlight->$privateMethod();
						} else {
							$flashlight =& $flashlight->$elmt;	
						}
					} else {
						$flashlight =& $flashlight[$elmt];
					}
					$count++;	
				}
			} else {
				return null;	
			}
		}
	}
}