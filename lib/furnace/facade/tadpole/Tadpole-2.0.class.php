<?php
class Tadpole {
	
	// The data to use to determine actual tag values
	public $page_data = array();
	
	// The cache of absolute tags that have already been processed
	// see also: the relativeCache variable in the ::compile() function holds
	// relative ('@') tags that have already been processed for that iteration.
	public $tagCache;
	public $relativeTagCache;
	public $conditionalCache;
	
	public function __construct() {
		// Make global and session variables available
		$this->page_data['_session'] =& $_SESSION;
		$this->page_data['_globals'] =& $GLOBALS;
		$this->tagCache = array();
		$this->conditionalCache = array();
	}
	
	public function compile($contents,$iter_data = array(),$bOnlyNonRelative = false) {
		// How far before a 'block' tag to look for the html block start tag
		$maxReverseSearch = 200; 	// look in the previous 200 characters
		
		// Where are we in the contents?
		$offset   		  = 0;		// location of the beginning of the current tag
		$outer_offset 	  = 0;		// location of the beginning of the 'outermost' tag (for nesting)

		// Relative tag cache
		$this->relativeTagCache= array();	// Keeps data for '@' tags which have already been processed
		$relativeConditionalCache = array();
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
			$outer_offset = false;		// Outer offset marks the location of the outermost tag (for nesting);
			
			

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
							$err = substr($contents,$temp,($signalOffset - $temp));
							
							die("TADPOLE: confused, bailing out. (clue: {$err} )");
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
			$conditional = (isset($value[2]) && "if:" == substr($value,0,3))
				? true
				: false;
				
			// Determine whether the value implies an assert statement
			if (isset($value[6]) && "assert:" == substr($value,0,7)) {
				$conditional = true;				// mark the tag conditional
				$value = "if:" . substr($value,7);	// Internally Rewrite as an "if" tag
				$commands['else']    = '';			// Hide the content on failure
				$commands['noerror'] = true;		// Hide the content on rejection	
			}
			
			// Determine whether the value implies an input/error statement
			if (isset($value[5]) && ("input:" == substr($value,0,6))) {
				// Process the input statement
				$value = substr($value,6);
				// 1. get the base object
				$dotPosition = strrpos($value,'.');
				if (false !== $dotPosition) {
					$baseObjectNeedle = substr($value,0,strrpos($value,'.'));
					$attributeNeedle  = substr($value,strrpos($value,'.') + 1);
				} else {
					$baseObjectNeedle = $value;
					$attributeNeedle  = '';
				}
				// style pre-processing (replace | with ; in style definition)
				if (isset($commands['style'])) {
					$commands['style'] = str_replace('|',';',$commands['style']);
				}

				$output = '';
				// If the baseObjectNeedle begins with '~', it is a class definition, not an object
				if ('~' == $baseObjectNeedle[0]) {
					try {
						$attributeData = call_user_func(
							array(substr($baseObjectNeedle,1),'_getAttribute'),$attributeNeedle);
						$output = $this->input_helper(substr($baseObjectNeedle,1),$attributeNeedle,$attributeData,$commands);
					} catch (Exception $e) {
						$rejected = true;
					}
				} else {
					$baseObject = $this->get_recursively($baseObjectNeedle,$commands,$rejected,$iter_data);
					if (!$rejected && is_object($baseObject)) {
						
						// Special case for the 'objId' attribute:
						if ('objId' == $attributeNeedle) {
							// check for name override
							$name   = (isset($commands['name'])) ? $commands['name'] : "{$baseObject->_getObjectClassName()}_id";
							$output = "<input type=\"hidden\" name=\"{$name}\" value=\"{$baseObject->getObjId()}\"/>";
						// All other attributes:
						} else {
							if (false !== ($attributeData = $baseObject->_getAttribute($attributeNeedle))) {
								// Generate the output for the attribute input item
								$output = $this->input_helper($baseObject,$attributeNeedle,$attributeData,$commands);
							} else {
								$rejected = true;
							}
						}
					}
				}
				
				if (!$rejected ) {
					// apply the output and update the offset
					$before   = substr($contents,0,$tagStart);
					$after    = substr($contents,$tagEnd + 1);				
					$contents = $before . $output . $after;
					// Increment offset
					$offset = (false !== $outer_offset) ? $outer_offset : ($tagStart + strlen($output));
					// Move on to the next tag
					continue;
				}  
				
				// Skip this tag if it was rejected
				if ($rejected) {
					$offset = (false !== $outer_offset) ? $outer_offset : ($tagEnd + 1);	// Increment the offset
					continue;	// Move on to the next tag
				}

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
					// Determine whether to check the global or relative conditional cache
					if ('@' == $value[3] || ('!' == $value[3] && '@' == $value[4])) { // if:@ || if:!@
						$cacheToCheck =& $relativeConditionalCache;
					} else {
						$cacheToCheck =& $this->conditionalCache;
					}
					if (! isset( $cacheToCheck[$value])) {
						// Cache Miss ... compute, and store value in the cache if it has not been rejected
						//if ($cacheToCheck == $relativeConditionalCache) {echo ' rcc chosen';} else {echo ' conditional cache chosen';}
						$v = $this->get_recursively($value,$commands,$rejected,$iter_data);
						if (!$rejected) {
							$cacheToCheck[$value] = $v;
						}
					} 
					$conditionHeld = $cacheToCheck[$value];
					
					// Skip this tag if it was rejected and 'noerror' was not requested
					if ($rejected && !isset($commands['noerror'])) {
						$offset = (false !== $outer_offset) ? $outer_offset : ($tagEnd + 1);	// Increment the offset
						continue;				// Move on to the next tag
					}
					
					// If the tag was rejected and 'noerror' was requested, treat the block as 'false' 
					if ($rejected && isset($commands['noerror'])) {
						//$conditionHeld = false;
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
							$before   = substr($contents, 0, $blockStart +  (strpos($contents, ">",$blockStart) - $blockStart + 1));
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
					// Clear the relative conditional cache since the block is finished
					$relativeConditionalCache = array();
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

					
					// Skip this tag if it was rejected and 'noerror' was not requested
					if ($rejected && !isset($this->commands['noerror'])) {
						$offset = (false !== $outer_offset) ? $outer_offset : $tagEnd + 1;	// Increment the offset
						continue;				// Move on to the next tag
					}
					
					// If the tag was rejected, and 'noerror' has been requested, simply remove the tag
					if ($rejected && isset($commands['noerror'])) {
						//TODO: handle 'noerror' for unconditional block
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
					// Clear the relative cache since the block is finished
					$this->relativeTagCache = array();
					$relativeConditionalCache = array();
					// Increment offset
					$offset = (false !== $outer_offset) ? $outer_offset : ($blockStart + strlen($completeBlockContents));
				}
			} else {
				// PROCESSING A SIMPLE TAG
				if ($conditional) {
					// Evaluate the condition
					// Determine whether to check the global or relative conditional cache
					if ('@' == $value[3] || ('!' == $value[3] && '@' == $value[4])) { // if:@ || if:!@
						$cacheToCheck =& $relativeConditionalCache;
					} else {
						$cacheToCheck =& $this->conditionalCache;
					}
					if (! isset( $cacheToCheck[$value])) {
						// Cache Miss ... compute, and store value in the cache if it has not been rejected
						$v = $this->get_recursively($value,$commands,$rejected,$iter_data);
						if (!$rejected) {
							$cacheToCheck[$value] = $v;
						}
					}
					$conditionHeld = $cacheToCheck[$value];
					
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
					// Determine the actual value which will replace the tag. Look in cache first.
					// Determine whether to check the global tag cache or the relative cache:
					if ("@" == $value[0]) {
						$cacheToCheck =& $this->relativeTagCache; 	// relative cache
					} else {
						$cacheToCheck =& $this->tagCache;	// global cache
					}
					if (! isset( $cacheToCheck["{$value}"])) {
						// Cache Miss ... compute, and store value in the cache if it is not rejected
						$v = $this->get_recursively($value,$commands,$rejected,$iter_data);
						if (!$rejected) {
							$cacheToCheck[$value] = $v;
						}
					}
					
					$actual_value = $this->process_commands($commands,$cacheToCheck[$value],$rejected,$iter_data);
					//echo "ACTUAL_VALUE {$actual_value} ";
						
					// Skip this tag if it was rejected and 'noerror' was not requested
					if ($rejected && !isset($commands['noerror'])) {
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
				case "ifnever":
					if (! strtotime($value)) {
						$value = $commandValue;
					}
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
							$date = strtotime($value);
							if (! $date && isset($commands['ifnever'])) {
								$value = $commands['ifnever'];
								break;	
							}
							$value = date((isset($commands['format'])
								? $commands['format']
								: 'Y-m-d G:i:s'),$date);
							break;
						case 'nicedate':
							$value = $this->nicedate($value);
							break;
						case 'slashdate':
							$date = strtotime($value);
							if (! $date && isset($commands['ifnever'])) {
								$value = $commands['ifnever'];
								break;	
							}
							$value = date('m/d/Y',$date);
							break;
						case 'hour':
							$value = strtotime($value);
							$value = date('h',$value);
							break;
						case 'minute':
							$value = strtotime($value);
							$value = date('i',$value);
							break;
						case 'meridiem':
							$value = strtotime($value);
							$value = date('a',$value);
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
				case 'prefix':
					// Process the prefix, in case it contains tags
					if (false !== strpos($commands['prefix'],'[')) {
						$commands['prefix'] = $this->compile($commands['prefix'],$iter_data);
					}
					if (isset($value[0])) {
						$value = $commands['prefix'] . $value;
					}
					break;
				case "trailer":
					// Process the trailer, in case it contains tags
					if (false !== strpos($commands['trailer'],'[')) {
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
				case "htmlentities":
					$value = htmlentities($value);
					break;
				case "urlencode":
					$value = urlencode($value);
					break;
				case "nl2br":
					$value = nl2br($value);
					break;
				default:
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
							// return the number of objects
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
		
		// First check the appropriate cache to see if the value exists, if not, update the cache
		if ("@" == $lhs[0]) {
			$cacheToCheck =& $this->relativeTagCache; 	// global relative tag cache
		} else {
			$cacheToCheck =& $this->tagCache;			// global absolute tag cache
		}
		if (! isset( $cacheToCheck["{$lhs}"])) {
			// Cache Miss ... compute, and store value in the cache if it is not rejected
			$v = $this->get_recursively($lhs,$commands,$rejected,$iter_data);
			if (!$rejected) {
				$cacheToCheck[$lhs] = $v;
			}
		}
		$lhs = $cacheToCheck[$lhs];
		if ($rejected) {
			if (isset($commands['noerror']) ) {
				$rejected = false;
			} else {
				return;
			}
		}
		
		// If needed (ie, if not already pegged to true/false above), obtain the actual value for rhs
		if ($rhs !== true && $rhs !== false ) {
			
			// First check the appropriate cache to see if the value exists, if not, update the cache
			if ("@" == $rhs[0]) {
				$cacheToCheck =& $this->relativeTagCache; 	// global relative tag cache
			} else {
				$cacheToCheck =& $this->tagCache;			// global absolute tag cache
			}
			if (! isset( $cacheToCheck["{$rhs}"])) {
				// Cache Miss ... compute, and store value in the cache if it is not rejected
				$v = $this->get_recursively($rhs,$commands,$rejected,$iter_data);
				if (!$rejected) {
					$cacheToCheck[$rhs] = $v;
				}
			}
			$rhstest = $cacheToCheck[$rhs];

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
	
	public function nicedate($date){
   
	    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	    $lengths         = array("60","60","24","7","4.35","12","10");
	   
	    $now             = time();
	    $unix_date       = strtotime($date);
	   
	    // check validity of date
	    if(empty($unix_date)) {   
	        return 'unknown';
	    }

	    // is it future date or past date
	    if($now > $unix_date) {   
	        $difference     = $now - $unix_date;
	        $tense         = "ago";
	       
	    } else {
	        $difference     = $unix_date - $now;
	        $tense         = "from now";
	    }
   
	    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
	        $difference /= $lengths[$j];
	    }
   
    	$difference = round($difference);
   
	    if($difference != 1) {
	        $periods[$j].= "s";
	    }
	    
	    // Fix '0 seconds from now' bug --> 'just now'
	    if ($difference == 0 && $periods[$j] == 'seconds') {
	    	return 'just now';
	    }
   
	    return "$difference $periods[$j] {$tense}";
	}
	
	public function input_helper($object,$attributeName,$attributeData,&$commands) {
		
		// prefer POSTed/submitted values over the currently stored object attribute value
		if (isset($_POST) && isset($_POST[$attributeName])) {
			// reads the value from the POST array
			$value = $_POST[$attributeName];
		} else if (null !== ($value = _readUserInput($attributeName))) {
			// reads the value from the previously saved user input
		} else {
			// reads the value from the stored object attribute value
			if (is_object($object)) {
				$fn = "get{$attributeName}";
				$value = $object->$fn();
			} else {
				if (isset($commands['value'])) {
					// use the (compiled) value stored in the command string
					if (false !== strpos($commands['value'],'[')) {
						$commands['value'] = $this->compile($commands['value'],array());
					}
					$value = $commands['value'];
				} else {
					// No value provided
					$value = '';
				}
			}
		}
		// determine whether or not user has overridden element name
		$nameToUse = isset($commands['name']) ? $commands['name'] : $attributeName;
		
		// determine whether any validation errors exist for the attribute
		if (isset($_SESSION['_validationErrors'][$attributeName])) { 
			unset($_SESSION['_validationErrors'][$attributeName]);
			$bHasErrors = true;
		} else {
			$bHasErrors = false;
		}
		$errorClass = ($bHasErrors) ? "inputError" : '';
		$disabled   = (isset($commands['disabled']) && $commands['disabled']) ? ' disabled="disabled" ' : '';
		
		
		// determine the type of input to create
		switch ($attributeData['type']) {
			case 'text':
				return "<textarea id=\"{$commands['id']}\" class=\"{$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}\" {$disabled} >".stripslashes($value)."</textarea>";
			case 'password':
				return "<input type=\"password\" id=\"{$commands['id']}\" class=\"{$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}\" {$disabled} value=\"".stripslashes($value)."\" />";
			case 'date':
			case 'datetime':
				if ('' != $value) {	// assume standard MySQL date format (yyyy-mm-dd gg:ii:ss)
					$value_year  = substr($value,0,4);
					$value_month = substr($value,5,2);
					$value_day   = substr($value,8,2);
					$value_hour  = substr($value,11,2);
					$value_min   = substr($value,14,2);
					$value_sec   = substr($value,17,2);
				} else {
					$value_year = $value_month = $value_day = $value_hour = $value_min = $value_sec = $value;
				}
				$s = '';
				if (!isset($commands['timeonly'])) {
					$s = "<input type=\"text\" maxlen=\"2\" id=\"{$commands['id']}_day\"   class=\"FDateComponent FDateDay   {$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}_month\" {$disabled}  value=\"".stripslashes($value_month)."\"/> / "
						."<input type=\"text\" maxlen=\"2\" id=\"{$commands['id']}_month\" class=\"FDateComponent FDateMonth {$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}_day\"   {$disabled}  value=\"".stripslashes($value_day)."\"/> / "
						."<input type=\"text\" maxlen=\"4\" id=\"{$commands['id']}_year\"  class=\"FDateComponent FDateYear  {$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}_year\"  {$disabled}  value=\"".stripslashes($value_year)."\"/> (mm/dd/yyyy) ";
				}
				if (!isset($commands['dateonly'])) {
					// Display the time component as well
					$s .= "&nbsp;<input type=\"text\" maxlen=\"2\" id=\"{$commands['id']}_hour\"  class=\"FDateComponent FDateHour    {$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}_hour\" {$disabled}   value=\"".stripslashes($value_hour)."\"/> : "
						."<input type=\"text\" maxlen=\"2\" id=\"{$commands['id']}_min\"   class=\"FDateComponent FDateMinute  {$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}_min\"  {$disabled}   value=\"".stripslashes($value_min)."\"/> : "
						."<input type=\"text\" maxlen=\"2\" id=\"{$commands['id']}_sec\"   class=\"FDateComponent FDateSecond  {$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}_sec\"  {$disabled}   value=\"".stripslashes($value_sec)."\"/> (hh:mm:ss) ";
				}
				return $s;
				break;
			case 'string':
				if (isset($attributeData['allowedValues'])) {
					// create a select box
					$option = '';
					foreach ($attributeData['allowedValues'] as $opt) {
						$option .= "<option value=\"{$opt['value']}\" ".(($value == $opt['value'])? ' selected="selected" ' : '').">{$opt['label']}</option>";
					}
					return "<select id=\"{$commands['id']}\" class=\"{$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}\" {$disabled} >{$option}</select>";
					break;	
				} // otherwise, just draw a normal text box:
			case 'integer':
				if (isset($attributeData['allowedValues'])) {
					// create a select box
					$option = '';
					foreach ($attributeData['allowedValues'] as $opt) {
						$option .= "<option value=\"{$opt['value']}\">{$opt['label']}</option>";
					}
					return "<select id=\"{$commands['id']}\" class=\"{$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}\" {$disabled}>{$option}</select>";
					break;	
				} // otherwise, just draw a normal text box:
			case 'float':
			default:
				return "<input type=\"text\" id=\"{$commands['id']}\" class=\"{$commands['class']} {$errorClass}\" style=\"{$commands['style']}\" name=\"{$nameToUse}\" value=\"".stripslashes(htmlentities($value))."\" {$disabled} maxlen=\"{$attributeData['size']}\"/>";
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