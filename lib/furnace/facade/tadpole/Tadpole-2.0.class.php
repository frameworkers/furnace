<?php
class Tadpole {
	
	// The data to use to determine actual tag values
	public $page_data = array();
	
	public function __construct() {
		// Make global and session variables available
		$this->page_data['_session'] =& $_SESSION;
		$this->page_data['_globals'] =& $GLOBALS;
	}
	
	public function compile($contents,$iter_data = array(),$bOnlyNonRelative = false) {
		// How far before a 'block' tag to look for the html block start tag
		$maxReverseSearch = 200; 	// look in the previous 200 characters
		
		// Where are we in the contents?
		$offset   		  = 0;		// location of the beginning of the current tag
		$outer_offset 	  = 0;		// location of the beginning of the 'outermost' tag (for nesting)
		
		while (1) {
			
			// Reset temporary variables
			$tagStart     = 0;		// The beginning of the tag
			$valueStart   = 0;		// The beginnning of the value portion of the tag
			$valueEnd     = 0;		// The end of the value portion of the tag
			$commandStart = 0;		// The beginning of a k/v command string
			$commandEnd   = 0;		// The end of a k/v command string
			$tagEnd       = 0;		// The end of the tag

			$blockStart   = 0;		// The beginning of the computed block (for block tags)
			$blockEnd     = 0;		// The end of the computed block (for block tags)
			
			// Reset the flags
			$rejected = false;			// Deprecated
			$value    		= null;		// The value portion of the tag
			$commandString  = null;		// The command string portion of the tag
			$lhs      = null;			// The left hand side of a condition pair
			$rhs      = null;			// The right hand side of a condition pair
			$conditional    = false;	// Whether or not a tag is conditional ([if:..,[assert:...)
			$conditions     = array();	// An array of the applicable conditions for a conditional tag
			$conditionHeld  = false;	// Whether or not the condition(s) ultimately were satisfied
			$commands = array();		// An array of commands attached to the tag

			// Outer offset
			$outer_offset = false;			// Outer offset marks the location of the outermost tag (for nesting);
			
			
			
			// Look for a '[' and store the offset
			$tagStart = (($tagStart = strcspn($contents,"[",$offset)) != (strlen($contents) - $offset))
				? $tagStart + $offset
				: false;
			$valueStart = $tagStart + 1; // skip the [

			
			// Make sure that a tagStart was found at an offset < the length of 'contents', quit otherwise
			if (false === $tagStart || !isset($contents[$offset])) { 
				return $contents; /* Nothing further to do. Consider FREEing memory here */
			}
			
			
			// Look for a ']' or a ';' or a '[' signal and store the offset
			$signalOffset   = (($signalOffset = strcspn($contents,";][",$valueStart)) != (strlen($contents)-($valueStart)))
				? $signalOffset + $valueStart
				: false;
				
			// Make sure that a signal was found, quit otherwise (malformed)
			if (false === $signalOffset) { die("no signal found after {$tagStart} (".$contents[$tagStart].") "); return $contents; /* Nothing further to do. Malformed tag */}
				
			// NESTED TAG DETECTION
			// If a '[' is detected between start and signal, we have a nested tag situation. In this case,
			// We store the outer_offset (the start of the outermost tag) so that processing can resume from that
			// spot after the nested tag is processed. 
			while ('[' == $contents[$signalOffset]) {
				$outer_offset = max(0,$tagStart-1);		// store the 1 minus the offset of the outermost tag
				$tagStart     = $signalOffset;			// move tagStart to the start of the nested tag
				$valueStart   = $tagStart + 1;			// move valueStart to the start of the nested tag's value

				// Look again for a ']' or a ';' or a '[' signal and store the offset
				$signalOffset   = (($signalOffset = strcspn($contents,";][",$valueStart)) != (strlen($contents)-($valueStart)))
					? $signalOffset + $valueStart
					: false;
				
				// Make sure that a signal was found, quit otherwise (malformed)
				if (false === $signalOffset) { die("TADPOLE: Confused by a nested tag. Bailing out..."); return $contents; /* Nothing further to do. Malformed tag */}
			}
			
			// COMMAND STRING DETECTION
			// If the signal was a ';', process the command string which follows it, consisting of 
			// a series of ';' delimited commands, consisting either of 'keyword=value', or simply 'keyword' strings.
			if (';' == $contents[$signalOffset]) {
				
				// Mark the end of the value
				$valueEnd = $signalOffset;
				
				//echo "VALUE{".substr($contents,$valueStart,$valueEnd-$valueStart)."} ";
				
				// PROCESS COMMANDS
				while (';' == $contents[$signalOffset]) {
					
					// Mark the beginning of the command string
					$commandStart = $signalOffset + 1;
					
					// Look for a ']' or a ';' signal and store the offset
					$signalOffset   = (($signalOffset = strcspn($contents, ";]",$commandStart)) != strlen($contents)-($commandStart))	
						? $signalOffset + $commandStart 
						: false;
						
					// NESTED TAG DETECTION WITHIN A COMMAND STRING
					// Nested command strings are not processed at the outset, but they must be completely captured. The reason they
					// are not immediately processed is that, in the event that they are relative, the iter_data has not yet been
					// determined. While a '[' exists in a single command string, keep looking for ']' signals
					$temp = $commandStart;			// initialize temp
					$secondOffset = $signalOffset;	// initialize secondOffset
					$nestedTagsInCommandString = false;
					while (false !== ($nestedStart = strpos(substr($contents,$temp,($secondOffset - $temp)),"["))) {	// BUGGER RIGHT HERE, ($signalOffset - $temp is not right)
						$nestedTagsInCommandString = true;
						// Look for a ']' signal and store the offset
						$bracketOffset   = (($bracketOffset   = strcspn($contents, "]",$temp + $nestedStart + 1)) != strlen($contents)-($temp + $nestedStart + 1))
							? $bracketOffset + $temp + $nestedStart + 1   // swallow the ]
							: false;
							
						// Look for a ';' or a ] signal after that and store the offset
						$secondOffset   = (($secondOffset   = strcspn($contents, ";]",$bracketOffset + 1)) != strlen($contents)-($bracketOffset + 1))
							? $secondOffset + $bracketOffset + 1   // swallow the ]
							: false;
						if (false === $secondOffset) {
							//var_dump(substr($contents,$temp,($signalOffset - $temp)));
							die("TADPOLE: confused, bailing out.");
						}
						// Look through the substring to determine if there is yet another nested tag
						$temp = $bracketOffset + 1;
					}
					if ($nestedTagsInCommandString) {
						$signalOffset = $secondOffset;
					}
					
					// Extract the current command from the tag
					$commandEnd = $signalOffset;
					$command    = substr($contents,$commandStart, ($commandEnd - $commandStart));
					
					
					// Split the command into its key/value components
					list ($key,$value) = explode("=",$command,2);
					$value = (null == $value) ? true : $value;	// If only 'keyword' provided, 'true' is implicit
					$value = (strspn($value,'\'"`/',0,1) == 1)  // If a valid delimiter was detected...
						? trim($value,$value[0])	// ...trim the delimiter from both sides of the string
						: $value;

					// Add the command to the array of commands parsed for this tag
					$commands[$key] = $value;
					//echo "COMMAND{{$command}}";
				}
			} 
			
			// The tag ends at the ']' signal, store the offset and compute the 'value'
			$tagEnd     = $signalOffset;	// ']' marks the end of the tag
			$valueStart = $tagStart + 1;	// skip over the '['
			$valueEnd   = ($valueEnd > $valueStart )
				? $valueEnd					// from command string processing
				: $signalOffset;			// if no commands, skip the


			
			// Store the tag
			$tag = substr($contents,$tagStart,($tagEnd - $tagStart)+1);
			//echo "TAG::{$tag}   ";
			//var_dump($contents);
			
			// Short-exit if the tag is determined to be relative or contain relative components
			// and $bOnlyNonRelative evalutates to 'true'
			if ($bOnlyNonRelative && false !== strpos($tag,"@")) {
				//echo "SKIPPING RELATIVE TAG  (bOnlyNonRelative = TRUE) \r\n";
				
				// If this was a nested tag (ie $outer_offset != false) then the containing tag
				// should also be skipped. In this case, the offset should NOT be set back to outer_offset
				// but should instead be set to the end of the current (nested relative) tag so that searching
				// can continue.
				$offset = ($tagEnd + 1);	// Increment the offset
				continue;					// Move on to the next tag
			}
			

			// Extract the value that should replace the tag
			$value = substr($contents,$valueStart, ($valueEnd - $valueStart) );
			
			
			// Determine whether the value implies a conditional statement
			$conditional = ("if:" == substr($value,0,3))
				? true
				: false;
				
			// Determine whether the value implies an assert statement
			if ("assert:" == substr($value,0,7)) {
				$conditional = true;				// mark the tag conditional
				$value = "if:" . substr($value,7);	// Internally Rewrite as an "if" tag
				$commands['else'] = '';				// Hide the content on failure	
			}
							
			// Determine whether the tag represents a block, or is simple
			if (isset($commands['block'])) {
				// Find the start of the block
				$recentContent = substr($contents,$tagStart - min($tagStart,$maxReverseSearch),min($tagStart,$maxReverseSearch));
				$blockStart = ("self" == $commands['block']) 
					? $tagStart - (strlen($recentContent) - strrpos($recentContent,"<"))
					: $tagStart - (strlen($recentContent) - strrpos($recentContent,"<{$commands['block']}"));
					
				// Find a matching closing tag
				$blockEnd = ("self" == $commands['block']) 
					? strpos($contents,"/>",$blockStart+1)
					: strpos($contents,"</{$commands['block']}",$blockStart+1);
					
				// Ensure a closing tag was found
				if (false === $blockEnd) {
					die("No block end found for {$tag}");
				}
				
				// Take possible nesting into account by trying to find a nested tag within this block
				$nestedStartTag = strpos($contents,"<{$commands['block']}",$blockStart+1);
				while ((false !== $nestedStartTag) && $nestedStartTag < $blockEnd) {
					
					// Find another closing tag
					$blockEnd = strpos($contents,"</{$commands['block']}",$blockEnd+1);
					
					// Ensure a closing tag was found
					if (false === $blockEnd) {
						die("No block end found for {$tag}");
					}
					
					// Try to find another nested start tag
					$nestedStartTag = strpos($contents,"<{$commands['block']}",$nestedStartTag+1);
				}
				
				// Extract the block
				$blockContents = substr($contents,$blockStart,
					($blockEnd + (("self" == $commands['block']) 
						? 2 
						: strlen($commands['block']) + 3)) 
					- $blockStart); // +3 => <,/>
				
				// Remove the tag from the block contents (to prevent inf. looping)
				$blockContents = str_replace($tag,"",$blockContents);
				
				if ($conditional) {
					// PROCESSING A CONDITIONAL BLOCK
					
					// Evaluate the condition
					$conditionHeld = $this->get_recursively($value,$commands,$rejected,$iter_data);
					
					// Skip this tag if it was rejected
					if ($rejected) {
						$offset = (false !== $outer_offset) ? $outer_offset : ($tagEnd + 1);	// Increment the offset
						continue;				// Move on to the next tag
					}
					if ($conditionHeld) {
						// Replace the tag with "" 
						$before   = substr($contents,0,$tagStart);
						$after    = substr($contents,$tagEnd + 1);
						$contents = $before . $after; 
						$offset   = (false !== $outer_offset) ? $outer_offset : $offset;
					} else {
						if (isset($commands['innerelse']) && $commands['block'] != "self") {
							// Replace the block interior with $commands['innerelse']
							$before   = substr($contents, 0, $blockStart +  (strpos($contents, ">",$blockStart) - $blockStart));
							$after    = substr($contents,$blockEnd);
							$contents = $before . ((false !== strpos($commands['innerelse'],'[')) ? $this->compile($commands['innerelse'],$iter_data) : $commands['innerelse']) . $after;
							$offset   = (false !== $outer_offset) ? $outer_offset : $offset;
						} else {
							// Replace the block with "" || $commands['else'] 
							$before   = substr($contents,0,$blockStart);
							$after    = substr($contents,(("self" == $commands['block']) 
								? $blockEnd + 2
								: $blockEnd + strlen($commands['block']) + 3));
							$contents = $before . (isset($commands['else'])
								? ((false !== strpos($commands['else'],'[')) 
									? $this->compile($commands['else'],$iter_data) 	// Compile 'else' text if necessary
									: $commands['else'])
								: '') . $after;
							$offset   = (false !== $outer_offset) ? $outer_offset : $offset;
						}
					}
				} else {
					// PROCESSING A REGULAR BLOCK

					// Recurse with the block contents to discover any nested blocks to expand or
					// non-relative tags which can safely be replaced with their actual values. In this 
					// case, we set $bOnlyNonRelative to 'true'.
					//echo "LOOKING FOR TAGS IN BLOCK CONTENTS:\r\n";
					$compiledBlockContents = 
						$this->compile($blockContents,array(),true);

					// Get the actual data for the block
					$block_data = $this->get_recursively($value,$commands,$rejected,$iter_data);

					
					// Skip this tag if it was rejected
					if ($rejected) {
						$offset = (false !== $outer_offset) ? $outer_offset : $tagEnd + 1;	// Increment the offset
						continue;				// Move on to the next tag
					}
					
					// Generate as many copies of "compiledBlockContents" as there are elements in "block_data", one
					// for each entry, building up a complete expansion of the block.
					$completeBlockContents = "";
					foreach ($block_data as $row) {
						$completeBlockContents .= $this->compile($compiledBlockContents,$row);
					}
					
					// Replace the block with the completely expanded block contents
					$before = substr($contents,0,$blockStart);
					$after  = substr($contents, (("self" == $commands['block']) 
						? $blockEnd + 2
						: $blockEnd + strlen($commands['block']) + 3));
					$contents = $before . $completeBlockContents . $after;
					// Increment offset
					$offset = (false !== $outer_offset) ? $outer_offset : ($blockStart + strlen($completeBlockContents));
				}
			} else {
				// PROCESSING A SIMPLE TAG
				if ($conditional) {
					// Evaluate the condition
					$conditionHeld = $this->get_recursively($value,$commands,$rejected,$iter_data);

					// Skip this tag if it was rejected
					if ($rejected) {
						$offset = (false !== $outer_offset) ? $outer_offset : ($tagEnd + 1);	// Increment the offset
						continue;				// Move on to the next tag
					}
					
					// Split the content at the tagStart
					$before   = substr($contents,0,$tagStart);
					$after    = substr($contents,$tagEnd + 1);
					
					if ($conditionHeld) {
						// Replace tag with contents, compiling if necessary and increment offset
						$contents = $before . $this->process_commands($commands,
							((false !== strpos($commands['content'],'[')) 
								? $this->compile($commands['content'],$iter_data,$bOnlyNonRelative) 
								: $commands['content']),$rejected,$iter_data) . $after;
						// Increment offset
						$offset = (false !== $outer_offset) ? $outer_offset : ($tagStart + strlen($commands['content']));
					} else {
						// Replace block with else, compiling if necessary and increment offset
						$contents = $before . $this->process_commands($commands,
							((false !== strpos($commands['else'],'['))
								? $this->compile($commands['else'],$iter_data,$bOnlyNonRelative)
								: $commands['else']),$rejected,$iter_data) . $after;
						// Increment offset
						$offset = (false !== $outer_offset) ? $outer_offset : ($tagStart + strlen($commands['else']));
					}
				} else {
					// Determine the actual value which will replace the tag
					$actual_value = $this->process_commands($commands,
						$this->get_recursively($value,$commands,$rejected,$iter_data),$rejected,$iter_data);
					//echo "ACTUAL_VALUE {$actual_value} ";
						
					// Skip this tag if it was rejected
					if ($rejected) {
						$offset = (false !== $outer_offset) ? $outer_offset : ($tagEnd + 1);	// Increment the offset
						continue;				// Move on to the next tag
					}
					
					// Recursively process the actual value, to handle any nested tags
					if (false !== strpos($actual_value,'[')) {
						$actual_value = $this->compile($actual_value,$iter_data);
					}
					
					// replace the tag with the actual value
					$before   = substr($contents,0,$tagStart);
					$after    = substr($contents,$tagEnd + 1);				
					$contents = $before . $actual_value . $after;
					// Increment offset
					$offset = (false !== $outer_offset) ? $outer_offset : ($tagStart + strlen($actual_value));
				}
			}	
		}
	}
	
	private function process_commands(&$commands,$value,&$rejected,$iter_data=array()) {
		foreach ($commands as $commandKey => $commandValue) {
			switch ($commandKey) {
				case "empty":
				case "ifempty":
					if ("" == $value) {
						// Process the command value in case it contains tags
						if (false !== strpos($commandValue,'[')) {
							$value = $this->compile($commandValue,$iter_data);
						} else {
							$value = $commandValue;
						}
					}
					break;
				case "ifzero":
					$value = (0 == $value) 
						? $commandValue
						: $value;
					break;
				default:
					break;
				case "error":
				case "unset":
				case "default":
					if ($rejected) {
						// Process the command value in case it contains tags
						if (false !== strpos($commandValue,'[')) {
							$commandValue = $this->compile($commandValue,$iter_data);
						}
						$value = $commandValue;	// use the alternate value
						$rejected = false;		// reset the rejection
					} 
					break;
				case 'map':
					// Process the map in case it contains tags
					if (false !== strpos($commands['map'],'[')) {
						$commands['map'] = $this->compile($commands['map'],$iter_data);
					}
					$entries = explode(',',$commands['map']);
					$kvpairs = array();
					foreach ($entries as $entry) {
						list ($k,$v) = explode('|',$entry);
						if (is_array($value)) {
							foreach ($value as &$val) {
								if ($val == $k) {
									$val = $v;
								}
							}
						} else if ($value == $k) {
							$value = $v;
						}
					}
					break;
				case 'type':
					switch ($commands['type']) {
						case 'date':
							$value = date((isset($commands['format'])
								? $commands['format']
								: 'Y-m-d g:i:s'),strtotime($value));
							break;
						case 'usphone':
							$value = str_pad($value,11,"1", STR_PAD_LEFT);
							$value = "{$value[0]}-".substr($value,1,3).'-'.substr($value,4,3).'-'.substr($value,7);
							break;
						default:
								
					}
					break;	
				
				case 'maxlen':
					// Process the contents of maxlen, in case it contains tags
					if (false !== strpos($commands['maxlen'],'[')) {
						$commands['maxlen'] = $this->compile($commands['maxlen'],$iter_data);
					}
					if (strlen($value) > $commands['maxlen']) {
						$value = substr($value,0,$commands['maxlen']) 
							. (isset($commands['trailer']) ? $commands['trailer'] : '');
					}
					break;
				case "trailer":
					// Process the trailer, in case it contains tags
					if (false !== strpos($commands['maxlen'],'[')) {
						$commands['trailer'] = $this->compile($commands['trailer'],$iter_data);
					}
					if (isset($commands['trailer']) && !isset($commands['maxlen']) && isset($value[0])) {
						$value .= $commands['trailer'];	
					}
					break;
				case "glue":
					if (is_array($value)) {
						$value = implode($commands['glue'],$value);
					} 
					break;
				case "padding":
					list($type,$char,$width) = explode(",",$commands['padding']);
					if (isset($value[$width-1])) { break; }	// value already too long, no need to pad
					if (" " == $char) {$char = "&nbsp;";}
					$cur_len = strlen($value);
					switch ($type) {
						case "left":
							$value = str_pad($value,$width,$char,STR_PAD_LEFT);
							break;
						case "right":
							$value = str_pad($value,$width,$char,STR_PAD_RIGHT);
							break;
						case "both":
							$value = str_pad($value,$width,$char,STR_PAD_BOTH);
							break;	
						default:
							break;
					}
					break;
			}
		}
		return $value;
	}
	
	private function get_recursively($needle,$commands,&$rejected,$iter_data=array()) {
		//NOTES:
		// If iteration data has been provided, it is to be used with all "relative" tags, ie those
		// beginning with [@...], otherwise needles are replaced with data from page_data

		// Short-exit for $needle = "@". In this case, the actual value is the simple string provided
		// in iter_data. There is no need to set everything else up.
		if ("@" == $needle) { return $iter_data;}

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
				$privateMethod = "get" . strtoupper($segment[0]) . substr($segment,1); 	
				
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
							return count($flashlight);
						}
						
						if ($bIsObject) {
							// If the object has a private method defined, call it
							if (is_callable(array($flashlight,$privateMethod))) {
								// Handle the case in which a limited subset of matches is requested
								if (isset($commands['start']) || isset($commands['limit'])) {
									return $flashlight
										->$privateMethod("*","collection")
											->getSubset(
												(isset($commands['start']) ? $commands['start'] : 0),
												(isset($commands['limit']) ? $commands['limit'] : 0),
												(isset($commands['sortkey']) ? $commands['sortkey'] : "objId"),
												(isset($commands['order'])   ? $commands['order']   : "asc"));
								
												// Handle the case in which all matches are to be returned
								} else {
									return $flashlight
										->$privateMethod("*","object",
											(isset($commands['sortkey']) ? $commands['sortkey'] : "objId"),
											(isset($commands['order'])   ? $commands['order']   : "asc"));
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
	
	private function condition_helper($needle,&$commands,&$rejected,$iter_data) {
		$lhs = $rhs = false;
		// Look for a '!' or a '=' or a '-' or a '+' operator and store the offset
		$operatorOffset = (($operatorOffset = strcspn($needle,"!=-+")) == strlen($needle)) 
			? false
			: $operatorOffset;
		if (false === $operatorOffset) {
			// If there was no operator, assume that the user wants to boolean test the provided value
			$lhs = $needle;
			$rhs = true;
			$operator = "=";
			
		} else {
			// Decompose the needle based on the position of the operator
			switch ($operatorOffset) {
				case 0:	// operator is first character, only valid for '!', implied is !=true'
					$lhs = substr($needle,1);
					$rhs = true;
					$operator = "!";
					break;
				default:
					if ("=" == $needle[$operatorOffset+1]) {	// ==,!=,-=,+=
						$lhs = substr($needle,0,$operatorOffset);
						$candidate = substr($needle,$operatorOffset+2);
						$rhs = (strspn($candidate,'\'"`/',0,1) == 1) // If a valid delimiter was detected...
							? trim($candidate,$candidate[0])	// ... trim the delimiter from both sides
							: $candidate;
						$operator = substr($needle,$operatorOffset,2);
					} else {									// !,=,(,),<,>
						$lhs = substr($needle,0,$operatorOffset);
						$candidate = substr($needle,$operatorOffset+1);
						$rhs = (strspn($candidate,'\'"`/',0,1) == 1) // If a valid delimiter was detected...
							? trim($candidate,$candidate[0])	// ... trim the delimiter from both sides
							: $candidate;
						$operator = $needle[$operatorOffset];
					}
					break;
			}
		}
		
		// Obtain actual value for lhs
		$lhs = $this->get_recursively($lhs,$commands,$rejected,$iter_data);
		if ($rejected) {
			// If the user has specified alternative content, display it instead
			if (isset($commands['else']) || isset($commands['innerelse'])) { $rejected = false; return;}
			
			// Set to false as it does not exist
			$rejected = false;	// this idea of rejected is causing major headaches. is there a better way?
			$lhs = false;
		}
		
		// If needed (ie, if not already pegged to true/false above), obtain the actual value for rhs
		if ($rhs !== true && $rhs !== false ) {
			$rhstest = $this->get_recursively($rhs,$commands,$rejected,$iter_data);
			if ($rejected) {
				// If the rhs was rejected, it means that no data is available which
				// matches what was provided. In that case, simply interpret the value
				// of the rhs as the literal string which was provided and clear the
				// rejected flag;
				$rejected = false;
			} else {
				// Otherwise, use the computed value for rhs
				$rhs = $rhstest;
			}
		}
		
		// Perform the comparison between the two sides, based on the detected operator
		switch ($operator) {
			case "=":
			case "==":
				return ($lhs == $rhs);
			case "!":
			case "!=":
				return ($lhs != $rhs);
			case "-":
				return ($lhs < $rhs);
			case "-=":
				return ($lhs <= $rhs);
			case "+":
				return ($lhs > $rhs);
			case "+=":
				return ($lhs >= $rhs);
			default:
				die("TADPOLE: UNKNOWN OPERATOR '{$operator}' IN: {$needle}\r\n");
		}
	}
	
	
	public function set($key,$value) {
		$this->page_data[$key] = $value;
	}
	
	public function ref($key,&$value) {
		$this->page_data[$key] = $value;
	}
}
?>