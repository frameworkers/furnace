<?php
class Tadpole {
	
	// Array: page_data
	// An array of the registered variables and blocks
	protected $page_data;
	
	// Variable: contents
	// The page contents
	protected $contents;
	
	protected $startMarkers = array(
		"[tp."    => "processAbsolute",
		"[@"      => "processRelative",
		"[tp-if:" => "processConditional",
		"[!"      => "processSuper"
	);
		
	protected $endMarker = "]";
	
	protected $selfTag   = "self";
	
	// Variable: maxReverseSearch
	// The number of characters to search backwards from the marker for
	// an html tag before giving up
	protected $maxReverseSearch = 200;
	
	protected $commands;
	
	// Variable: current
	// The position of the current marker within contents
	protected $current;
	
	// Variable: marker
	// The marker currently being processed
	protected $marker;
	
	

	public function __construct($contents='') {
		$this->contents  = $contents;
		$this->page_data = array();
		$this->commands  = array();
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
		
		if ("" == $contents) { return; }
		
		
		$offset  = 0;
		$current = 0;
		
		$marker  = '';
		
		while (1) {
			
			$this->commands = array();
			
			// DETERMINE POSITION OF NEXT MARKER
			$nextTagCandidatePosition = strlen($contents);
			$foundCandidate = false;
			$candidateMarker   = '';
			$candidateFunction = '';
			foreach ($this->startMarkers as $sm => $function) {
				$test = strpos($contents,$sm,$offset);
				if ($test !== false && $test < $nextTagCandidatePosition) {
					$nextTagCandidatePosition = $test;
					$foundCandidate    = true;
					$candidateMarker   = $sm;
					$candidateFunction = $function;
				}
			}
//			var_dump($candidateMarker);
//			var_dump($candidateFunction);
//			var_dump($foundCandidate);
//			var_dump($contents);
			//die();
			
			// EXTRACT WHOLE MARKER
			if ($foundCandidate ) {
				$this->current =  $nextTagCandidatePosition; 
				$nestedMarkerDetected = false;
				
				//$markerCandidate = substr($contents,$this->current,(strpos($contents,$this->endMarker,$this->current) - ($this->current-1)));
				
				
				
				// -- find a closing tag, taking possible nesting into account
				$contentEnd = strpos($contents,$this->endMarker,min(strlen($contents)-1,$this->current + 1));
		
				$tempStart = $this->current + 1;
				while (false !== ($nestedStart = strpos($contents,"[",$tempStart)) && $nestedStart < $contentEnd) {
					$nestedMarkerDetected = true;
					$contentEnd = strpos($contents,$this->endMarker,$contentEnd+1);
					$tempStart = $nestedStart + 1;
				}
				
				// -- extract the marker
				$this->marker = substr($contents,$this->current,$contentEnd-$this->current+1);
				
				// -- process nested markers if any have been detected
				if ($nestedMarkerDetected) {
					// Dynamically replace nested marker(s)
					$markerProcessString = substr($this->marker,1,strlen($this->marker)-2);	// save the marker w/ nested markers for processing
					$storeMarkerLen= strlen($this->marker);									// save the length of the original marker
					$storeCommands = $this->commands;										// save the commands of the original marker
					$storeCurrent  = $this->current;										// save the current location of the original marker
					
					$this->process($markerProcessString,$iter_data);						// recurse to process nested markers
					
					$this->commands = $storeCommands;										// restore commands
					$this->current  = $storeCurrent;										// restore current location
					
					$this->marker   = "[{$markerProcessString}]";							// restore the marker

					// Splice in replaced marker in place of old marker
					$contents = substr($contents,0,$this->current) . $this->marker . substr($contents,$this->current + $storeMarkerLen);
				}
				
			} else {
				return(true); // No tags left.
			}
			// SPLIT IDENTIFIER AND COMMANDS
			list ($identifier,$commandstr) = explode(";",trim($this->marker,"[]"),2);

			
			// PROCESS COMMANDS
			$raw = explode(";",$commandstr);
			foreach ($raw as $r) {
				if (0 != ($start = strpos($r,"=`"))) {
					$this->commands[substr($r,0,$start)] = rtrim(substr($r,$start+2),"`");
				} else {
					list($k,$v) = explode("=",$r);
					$this->commands[$k] = $v;
				}
			}
			
			// Call the appropriate function to handle the tag
			$this->$candidateFunction($contents,$identifier,$iter_data);	
		}
	}
		
	protected function processAbsolute(&$contents,$identifier,$iter_data = array()) {
		
		if (isset($this->commands['block'])) {
			
			/** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** *
			 * ABSOLUTE (BLOCK) Tag
			 **********************************************************/

			// DETERMINE CORRESPONDING DATA
			$id_parts = explode(".",$identifier);
			$id_parts_size = count($id_parts);
			unset($id_parts[0]);	// drop the 'tp.'
			if ("#" == $id_parts[$id_parts_size-1]) {
				unset($id_parts[$id_parts_size-1]);	
			}

			// PROCESS THE BLOCK
			$this->processBlock($contents,$this->getRecursively($id_parts));
				
		} else {
			
			/** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** *
			 * ABSOLUTE (NON-BLOCK) Tag
			 **********************************************************/
			
			// DETERMINE CORRESPONDING DATA
			$id_parts = explode(".",$identifier);

			if (count($id_parts) < 2) { 
				$this->error("malformed identifier: {$identifier} ");
			}
			unset($id_parts[0]);	// drop the 'tp'

			if ("" == $id_parts[1]) {
				unset($id_parts[1]);// drop the '' and use GLOBALS
				$data = $this->getRecursively($id_parts,$GLOBALS);
			} else {
				$data = $this->getRecursively($id_parts);
			}
			// PROCESS COMMANDS
			$this->processTagCommands($data);
			// REPLACE
			$contents = substr($contents,0,$this->current)
				. $data
				. substr($contents,$this->current + strlen($this->marker));
		}
		
	}
	
	protected function processRelative(&$contents,$identifier,$iter_data = array()) {
		if (isset($this->commands['block'])) {
			
			/** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** *
			 * RELATIVE (BLOCK) Tag
			 **********************************************************/

			// DETERMINE CORRESPONDING DATA
			$id_parts = explode(".",$identifier);
			$id_parts_size = count($id_parts);

			unset($id_parts[0]);	// drop the '@'
			if ("#" == $id_parts[$id_parts_size-1]) {
				unset($id_parts[$id_parts_size-1]);	
			}
			
			// PROCESS THE BLOCK
			$this->processBlock($contents,$this->getRecursively($id_parts,$iter_data));
			
		} else {
			
			/** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** *
			 * RELATIVE (NON-BLOCK) Tag
			 **********************************************************/

			// DETERMINE CORRESPONDING DATA
			$id_parts = explode(".",$identifier);
			unset($id_parts[0]);	// drop the '@' and use iter_data
			if (count($id_parts) == 0) {
				$data = $iter_data;
			} else {
				$data = $this->getRecursively($id_parts,$iter_data);
			}
			// PROCESS COMMANDS
			$this->processTagCommands($data);
			// REPLACE
			$contents = substr($contents,0,$this->current)
				. $data
				. substr($contents,$this->current + strlen($this->marker));
		}
	}
	
	protected function processConditional(&$contents,$identifier,$iter_data = array()) {
		
		if (isset($this->commands['block'])) {
			
			/** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** *
			 * CONDITIONAL (BLOCK) Tag
			 **********************************************************/
			$result = $this->evaluateCondition($identifier,$iter_data);
			if ($result) {
				// CONDITION HOLDS
				$before = substr($contents,0,$this->current);
				$after  = substr($contents,$this->current+strlen($this->marker));
				// Display the block, minus the tadpole tag
				$contents = $before.$after;	
				
			} else {
				// CONDITION FAILS
				list($contentStart,$contentLength) = $this->determineConditionalBlock($contents);
				if (isset($this->commands['innerelse'])) {
					$before = substr($contents,0,$this->current);
					$after  = substr($contents,$contentStart + ($contentLength-(strlen($this->commands['block'])+3)));
					$contents = $before . $this->commands['innerelse'] . $after;
				} else {
					$before = substr($contents,0,$contentStart);
					$after  = substr($contents,$contentStart + $contentLength);
					$contents = $before . 
						(empty($this->commands['else']) 
							? '' 
							: $this->commands['else']
						).$after;
				}
			}
				
		} else {
			
			/** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** *
			 * CONDITIONAL (NON-BLOCK) Tag
			 **********************************************************/

			if ($this->evaluateCondition($identifier,$iter_data)) {
				// CONDITION HOLDS
				$before = substr($contents,0,$this->current);
				$after  = substr($contents,$this->current + strlen($this->marker));
				$contents = $before.$this->commands['content'].$after;
			} else {
				// CONDITION FAILS
				$before = substr($contents,0,$this->current);
				$after  = substr($contents,$this->current + strlen($this->marker));
				$contents = $before.
					(empty($this->commands['else'])
						? ''
						: $this->commands['else']
					).$after;
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
	
	protected function evaluateCondition(&$identifier,$iter_data = array()) {
		// DETERMINE CONDITION FORMAT AND COMPONENTS
		$bNegate = false;
		list($var,$value) = explode("=",(substr($identifier,6)));
		
		// -- check for rhs negation
		if ("~" == substr($value,0,1)) {
			$bNegate = true;
			$value   = substr($value,1);
		}
		//echo "[bnegate: " . (($bNegate)? "true":"false")."] ";
		
		// -- break out the variables, if necessary
		$lhsParts = explode(".",$var);
		$rhsParts = explode(".",$value);
		
		$lhs_value      = false;
		$rhs_value      = false;
		
		// -- Process the left hand side variable (tp,@)
		if ("tp" == $lhsParts[0]){
			if (count($lhsParts) > 1 && "" == $lhsParts[1]) {
				unset($lhsParts[0]);
				unset($lhsParts[1]);
				$lhs_value = $this->getRecursively($lhsParts,$GLOBALS);
			} else {
				unset($lhsParts[0]);
				$lhs_value = $this->getRecursively($lhsParts);
			}
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
			if ("" == $value && $bNegate) {
				$rhs_value = true;
			} else if ("" == $value || "true" == $value) {
				// This is a truth comparison, any non-false lhs_value will suffice.
				// The conditional evaluation is a '===', so, if a non-false lhs_value 
				// is detected, the lhs_value is set directly to 'true' to satisfy 
				// the '==='.
				$rhs_value = true;
				if ($lhs_value) {
					$lhs_value = true;
				}
			} else if ("false" == $value) {
				$rhs_value = false;
			} else {
				$rhs_value = $value;
			}
		}
		
		// EVALUATE CONDITION
		if ($bNegate) {
			// Force lhs to be boolean if rhs is boolean
			if ($rhs_value === true && $lhs_value == true) {return false;}
			if ($rhs_value === false && $lhs_value == false) {return false;}
			if ($lhs_value == $rhs_value) {
				return false;
			} else {
				return true;
			}
		} else {
			// Force lhs to be boolean if rhs is boolean
			if ($rhs_value === true && $lhs_value == true) {return true;}
			if ($rhs_value === false && $lhs_value == false) {return true;}
			if ($lhs_value == $rhs_value) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	protected function processBlock(&$contents,&$data) {
		$blockContents = '';
		// DETERMINE HTML TAG
		$tag = $this->commands['block'];
		
		// EXTRACT AND STORE CONTENT
		// -- determine content start
		$contentStart = $this->current 
			- ((min($this->current,$this->maxReverseSearch)) 
			- strrpos(
				substr($contents,$this->current-min($this->current,$this->maxReverseSearch),
					min($this->current,$this->maxReverseSearch)),(($tag == $this->selfTag) ? "<" : "<{$tag}")));

		// -- find a closing tag, taking possible nesting into account
		$contentEnd = strpos($contents,(($tag == $this->selfTag) ? ">" : "</{$tag}>"),
			min(strlen($contents)-1,
			$this->current + strlen($this->marker)));
		
		$tempStart = $this->current + strlen($this->marker);
		while (false !== 
			($nestedStart = strpos($contents,(($tag == $this->selfTag) ? "<" : "<{$tag}"),$tempStart)) 
				&& $nestedStart < $contentEnd) {
			$contentEnd = strpos($contents,(($tag == $this->selfTag) ? ">" : "</{$tag}>"),
				$contentEnd+1);
			$tempStart = $nestedStart + 1;
		}
		
		// -- extract the content block
		$content = substr($contents,$contentStart,
			$contentEnd-$contentStart+((($tag == $this->selfTag) ? 1 : strlen($tag)+3)));
		
			
		// -- store relevant information about the block
		$originalContentLength = strlen($content);
		$coda = substr($contents,$contentStart+$originalContentLength);
		
		// -- compute the position of the marker within the block content
		$relativeMarkerStart = strpos($content,$this->marker);
		$relativeMarkerEnd   = $relativeMarkerStart + strlen($this->marker);
		
		// -- compute the portion of the content that will be checked
		$subcontent = substr($content,$relativeMarkerEnd);
		
		if (count($data) == 0) {
			$blockContents = ((isset($commands['ifempty'])) 
				? $commands['ifempty'] 
				: '');
		} else {
			// FOREACH DATA, RECURSE & APPEND CONTENT
			foreach ($data as $iteration_data) {
				$iterContents  = $subcontent;
				$this->process($iterContents,$iteration_data);
				$blockContents .= 
					substr($content,0,$relativeMarkerStart) 
					. $iterContents;
			}
		}

		// REPLACE BLOCK DEFINITION WITH BLOCK CONTENTS
		$contents = substr($contents,0,$contentStart)
			. $blockContents
			. $coda;
	}
	
	protected function determineConditionalBlock(&$contents) {
		
		/* special processing to determine the boundaries for a conditional block */
		// DETERMINE HTML TAG
		$tag = $this->commands['block'];

		// GET THE BLOCK CONTENTS
		// -- determine content start
		$contentStart = $this->current 
			- ((min($this->current,$this->maxReverseSearch)) 
			- strrpos(
				substr($contents,$this->current-min($this->current,$this->maxReverseSearch),
					min($this->current,$this->maxReverseSearch)),(($tag == $this->selfTag) ? "<" : "<{$tag}")));

		// -- find a closing tag, taking possible nesting into account
		$contentEnd = strpos($contents,(($tag == $this->selfTag) ? ">" : "</{$tag}>"),
			min(strlen($contents)-1,
			$this->current + strlen($this->marker)));
	
		$tempStart = $this->current + strlen($this->marker);
		while (false !== 
			($nestedStart = strpos($contents,(($tag == $this->selfTag) ? "<" : "<{$tag}"),$tempStart)) 
				&& $nestedStart < $contentEnd) {
			$contentEnd = strpos($contents,(($tag == $this->selfTag) ? ">" : "</{$tag}>"),
				$contentEnd+1);
			$tempStart = $nestedStart + 1;
		}
	
		// -- extract the content block
		$content = substr($contents,$contentStart,
			$contentEnd-$contentStart+((($tag == $this->selfTag) ? 1 : strlen($tag)+3)));
			
			
		// Return the relevant details about the block:
		// - start position
		// - length
		return array($contentStart,strlen($content));			
	}
	
	protected function processTagCommands(&$data) {
		// USE DEFAULT VALUE, IF SPECIFIED
		if ("" == $data && "" != $this->commands['ifempty']) {
			$data = $this->commands['ifempty'];
		}
		
		
		// USE DEFAULT VALUE FOR DATES, IF SPECIFIED
		if ("0000-00-00" == $data && "" != $this->commands['ifnever']) {
			$data = $this->commands['ifnever'];
		}
		
		// USE DEFAULT VALUE FOR 0-valued NUMBERS, IF SPECIFIED 
		if ("0" == $data && "" != $this->commands['ifzero']) {
			$data = $this->commands['ifzero'];
		}
		
		// GLUE DATA TOGETHER, IF REQUIRED
		if (is_array($data) && isset($this->commands['glue'])) {
			$data = implode($this->commands['glue'],$data);
		}
		
		// MAP DATA TO VALUES, IF SPECIFIED
		// mapped values look like:
		// ;map=0|Zero,1|One,2|Something Else,...
		if (isset($this->commands['map'])) {
			$opts = explode(",",$this->commands['map']);
			foreach($opts as $o) {
				if ($data == substr($o,0,strpos($o,"|"))) {
					$data = trim(substr($o,strpos($o,"|")),"|");
					break;
				}
			}	
		}
	}
	
	protected function error($message) {
		echo "<b>Tadpole Error:</b> {$message}<br/>";
		die();
	}
	
}
?>