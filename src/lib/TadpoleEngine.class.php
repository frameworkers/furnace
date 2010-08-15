<?php
class TadpoleEngine {
	
	const MAX_REVERSE_SEARCH  = 200;
	const TAG_START_CHAR   = '[';
	const TAG_END_CHAR     = ']';
	const PSEUDO_TAG_CHAR  = '$';
	const VALUE_DELIMITERS = '\'"`/'; 
	
	public $page_data;
	
	// The cache of absolute tags that have already been processed
	// see also: the relativeCache variable in the ::compile() function holds
	// relative ('@') tags that have already been processed for that iteration.
	public $tagCache;
	public $relativeTagCache;
	public $conditionalCache;
	public $relativeConditionalCache;
	
	
	public function __construct() {
		
	}
	
	public function compile($contents, $iter_data = array(), $bOnlyNonRelative = false) {
		
		// Where are we in the contents?
		$offset   		  = 0;		// location of the beginning of the current tag
		$outer_offset 	  = 0;		// location of the beginning of the 'outermost' tag (for nesting)
		
		// Relative tag cache
		$this->relativeTagCache   = array(); // Keeps data for '@' tags which have already been processed
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
			$lhs      		= null;		// The left hand side of a condition pair
			$rhs      		= null;		// The right hand side of a condition pair
			$conditional    = false;	// Whether or not a tag is conditional ([if:..,[assert:...)
			$conditions     = array();	// An array of the applicable conditions for a conditional tag
			$conditionHeld  = false;	// Whether or not the condition(s) ultimately were satisfied
			$commands = array();		// An array of commands attached to the tag

			// Outer offset
			$outer_offset = false;		// Outer offset marks the location of the outermost tag (for nesting);
			
			

			// Look for a TAG_START_CHAR and store the offset
			$tagStart = (($tagStart = strcspn($contents,self::TAG_START_CHAR,$offset)) != (strlen($contents) - $offset))
				? $tagStart + $offset
				: false;
			$valueStart = $tagStart + 1; // skip the TAG_START_CHAR
			
			// Make sure that a tagStart was found at an offset < the length of 'contents', quit otherwise
			if (false === $tagStart || !isset($contents[$offset])) { 
				return $contents; // Nothing further to do. Consider FREEing memory here 
			} 
			
			// Look for a TAG_END_CHAR or a ';' or a TAG_START_CHAR signal and store the offset
			$signalOffset   = (($signalOffset = strcspn($contents,
				';'.					// end of the value, beginning of the command string
				self::TAG_END_CHAR.		// end of the tag 
				self::TAG_START_CHAR,	// beginning of a nested tag 
				$valueStart)) != (strlen($contents)-($valueStart)))
				? $signalOffset + $valueStart
				: false;
				
			// Make sure that a signal was found, quit otherwise (malformed)
			if (false === $signalOffset) { 
				$this->fatal("No signal found after <code>{$tagStart}</code> (<code>".$contents[$tagStart]."</code>) "); 
				return $contents; // Nothing further to do. Malformed tag
			}
			
			// NESTED TAG DETECTION
			// If a TAG_START_CHAR is detected between start and signal, we have a nested tag situation. In this case,
			// We store the outer_offset (the start of the outermost tag) so that processing can resume from that
			// spot after the nested tag is processed. 
			while (self::TAG_START_CHAR == $contents[$signalOffset]) {
				$outer_offset = max(0,$tagStart-1);		// store the 1 minus the offset of the outermost tag
				$tagStart     = $signalOffset;			// move tagStart to the start of the nested tag
				$valueStart   = $tagStart + 1;			// move valueStart to the start of the nested tag's value

				// Look again for a TAG_END_CHAR or a ';' or a TAG_START_CHAR signal and store the offset
				$signalOffset   = (($signalOffset = strcspn($contents,
					';'.					// end of the value, beginning of the command string
					self::TAG_END_CHAR.		// end of the tag 
					self::TAG_START_CHAR,	// beginning of a nested tag
					$valueStart)) != (strlen($contents)-($valueStart)))
					? $signalOffset + $valueStart
					: false;
				
				// Make sure that a signal was found, quit otherwise (malformed)
				if (false === $signalOffset) { 
				    $this->fatal("Confused by a nested tag. Bailing out..."); 
					return $contents; // Nothing further to do. Malformed tag 
				}
			}
			
			// COMMAND STRING DETECTION
			// If the signal was a ';', process the command string which follows it, consisting of 
			// a series of ';' delimited commands, consisting either of 'keyword=value', or simply 'keyword' strings.
			if (';' == $contents[$signalOffset]) {
				
				// Mark the end of the value
				$valueEnd = $signalOffset;
				
				// PROCESS COMMANDS
				while (';' == $contents[$signalOffset]) {
					
					// Mark the beginning of the command string
					$commandStart = $signalOffset + 1;
					
					// Look for a TAG_END_CHAR or a ';' signal and store the offset
					$signalOffset   = (($signalOffset = strcspn($contents, ";".self::TAG_END_CHAR,$commandStart)) != strlen($contents)-($commandStart))	
						? $signalOffset + $commandStart 
						: false;
						
					// NESTED TAG DETECTION WITHIN A COMMAND STRING
					// Nested command strings are not processed at the outset, but they must be completely captured. The reason they
					// are not immediately processed is that, in the event that they are relative, the iter_data has not yet been
					// determined. While a TAG_START_CHAR exists in a single command string, keep looking for TAG_END_CHAR signals
					$temp = $commandStart;			// initialize temp
					$secondOffset = $signalOffset;	// initialize secondOffset
					$nestedTagsInCommandString = false;
					while (false !== ($nestedStart = strpos(substr($contents,$temp,($secondOffset - $temp)),self::TAG_START_CHAR))) {	// BUGGER RIGHT HERE, ($signalOffset - $temp is not right)
						$nestedTagsInCommandString = true;
						// Look for a TAG_END_CHAR signal and store the offset
						$bracketOffset   = (($bracketOffset   = strcspn($contents,self::TAG_END_CHAR,$temp + $nestedStart + 1)) != strlen($contents)-($temp + $nestedStart + 1))
							? $bracketOffset + $temp + $nestedStart + 1   // swallow the TAG_END_CHAR
							: false;
							
						// Look for a ';' or a TAG_END_CHAR signal after that and store the offset
						$secondOffset   = (($secondOffset   = strcspn($contents, ";".self::TAG_END_CHAR,$bracketOffset + 1)) != strlen($contents)-($bracketOffset + 1))
							? $secondOffset + $bracketOffset + 1   // swallow the TAG_END_CHAR
							: false;
						if (false === $secondOffset) {
							//var_dump(substr($contents,$temp,($signalOffset - $temp)));
							$this->fatal("Confused while processing nested tags in tag comand string, bailing out.");
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
					$value = (strspn($value,self::VALUE_DELIMITERS,0,1) == 1)  // If a valid delimiter was detected...
						? trim($value,$value[0])	// ...trim the delimiter from both sides of the string
						: $value;

					// Add the command to the array of commands parsed for this tag
					$commands[$key] = $value;
					//echo "COMMAND{{$command}}";
				}
			} 
			
			// The tag ends at the TAG_END_CHAR signal, store the offset and compute the 'value'
			$tagEnd     = $signalOffset;	// TAG_END_CHAR marks the end of the tag
			$valueStart = $tagStart + 1;	// skip over the TAG_START_CHAR to get the value start
			$valueEnd   = ($valueEnd > $valueStart)
				? $valueEnd					// from command string processing
				: $signalOffset;			// if no commands, skip the

			
			// Store the tag
			$tag = substr($contents,$tagStart,($tagEnd - $tagStart)+1);
			
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
			
			// Build a context for this tag
			$context = array(
				'tagStart' => $tagStart,
				'valueStart' => $valueStart,
				'valueEnd' => $valueEnd,
				'value'      => $value,
				'commandStart' => $commandStart,
				'commandEnd' => $commandEnd,
				'tagEnd'     => $tagEnd,
				'blockStart' => $blockStart,
				'blockEnd'   => $blockEnd,
				'commands'   => $commands,
				'offset'     => $offset,
				'outerOffset'=> $outer_offset,
				'contents'   => &$contents
			);
			
			

		    // Determine which function will process the tag
			if (null == ($fn = $this->dispatcher($tag,$context))) {
			    throw new Exception("Tadpole: dispatcher could not satisfy tag: [{$tag}]");
			}
		
			// Process the tag with the appropriate handler
			$this->$fn($tag,$context,$iter_data);
			
			// Update the global offset based on the context of the processed tag
			$offset       = $context['offset'];
			$outer_offset = $context['outerOffset'];
			
			// TODO: Flush caches as appropriate following tag completion
			
			// Free up memory
			unset($context);

		}
	}
	
	
	public function dispatcher($tag,&$context) {
		// Determine whether the value implies a conditional statement
		$context['isConditional'] = (isset($context['value'][2]) 
			&& "if:" == substr($context['value'],0,3))
			? true
			: false;
			
		// Determine whether the value implies an assert statement
		if (isset($context['value'][6]) && "assert:" == substr($context['value'],0,7)) {
			$context['isConditional'] = true;	// mark the tag conditional
			$context['value'] = "if:" . substr($context['value'],7); // Internally Rewrite as an "if" tag
			$context['commands']['else'] = '';				         // Hide the content on failure
		    $commands['noerror'] = true;                             // Hide the content on rejection
		}
		
		
		// Determine whether the tag represents a block, or is simple
		if (isset($context['commands']['block'])) {
			$context['isBlock'] = true;
		} else {
		    $context['isBlock'] = false;
		} 

		// Determine whether or not the block tag is a pseudo tag
		if($context['isBlock'] && $context['commands']['block'][0] == self::PSEUDO_TAG_CHAR) {
		    $context['isPseudo'] = true;
		} else {
		    $context['isPseudo'] = false;
		}
		
		// Depending on the results of the tests above, determine 
		// the handler that should be called to process this tag:
		
		// Simple tags [ ]
		if (!$context['isBlock'] && !$context['isConditional']) {
			return "simpleTagHandler";
		}
		
		// Simple conditional tags [if: ] and [assert:  ]
		if (!$context['isBlock'] && $context['isConditional']) {
		    return "simpleConditionalHandler";
		}
		
		// Conditional blocks [if: ;block= ] and [assert: ;block= ]
		if ($context['isBlock'] && $context['isConditional']) {
		    return "conditionalBlockHandler";
		}
		
		// Regular blocks [ ;block= ]
		if ($context['isBlock'] && !$context['isConditional']) {
		    return "regularBlockHandler";
		}
		
		$this->fatal("Dispatcher could not dispatch tag <code>{$tag}</code>");
	}
	
	
	public function extractBlock($tag,&$context) {
		
		// Find the start of the block
		$recentContent = substr($context['contents'],
			$context['tagStart'] - min($context['tagStart'],self::MAX_REVERSE_SEARCH),
			min($context['tagStart'],self::MAX_REVERSE_SEARCH));
		
			
		if ($context['isPseudo']) {
		    $context['blockStart'] = 
		        $context['tagStart']
		        - (strlen($recentContent) - (strrpos($recentContent,"<{$context['commands']['block']}") 
		        + strlen($context['commands']['block'])+2));    
		} else {
	        $context['blockStart'] = ("self" == $context['commands']['block']) 
			    ? $context['tagStart'] - (strlen($recentContent) - strrpos($recentContent,"<"))
			    : $context['tagStart'] - (strlen($recentContent) - strrpos($recentContent,"<{$context['commands']['block']}"));
		}
			
		// Find a matching closing tag
		$context['blockEnd'] = ("self" == $context['commands']['block']) 
			? strpos($context['contents'],"/>",$context['blockStart']+1)
			: strpos($context['contents'],"</{$context['commands']['block']}",$context['blockStart']+1);
			
		// Ensure a closing tag was found
		if (false === $context['blockEnd']) {
		    $this->fatal("No block end found for <code>{$tag}</code>");
		}
		
		// Take possible nesting into account by trying to find a nested tag within this block
		$nestedStartTag = strpos($context['contents'],"<{$context['commands']['block']}",$context['blockStart']+1);
		while ((false !== $nestedStartTag) && $nestedStartTag < $context['blockEnd']) {
			
			// Find another closing tag
			$context['blockEnd'] = strpos($context['contents'],"</{$context['commands']['block']}",$context['blockEnd']+1);
			
			// Ensure a closing tag was found
			if (false === $context['blockEnd']) {
			    $this->fatal("No block end found for <code>{$tag}</code>");
			}
			
			// Try to find another nested start tag
			$nestedStartTag = strpos($context['contents'],"<{$context['commands']['block']}",$nestedStartTag+1);
		}
		
		
		// Extract the block
		if ($context['isPseudo']) {
		    $context['blockContents'] = 
		        substr($context['contents'],$context['blockStart'],
		        (($context['blockEnd']) - ($context['blockStart'])));    
		} else {
		    $context['blockContents'] = substr($context['contents'],$context['blockStart'],
			    ($context['blockEnd'] + (("self" == $context['commands']['block']) 
				    ? 2 
				    : strlen($context['commands']['block']) + 3)) 
			    - $context['blockStart']); // + 3 is the 3 extra characters: <,/>
		}
		
        // Remove the tag from the block contents (to prevent inf. looping)
		$tagStart = strpos($context['blockContents'],$tag);
		$before   = substr($context['blockContents'],0,$tagStart);
		$after    = substr($context['blockContents'],$tagStart + strlen($tag));
		$context['blockContents'] = $before . $after;

	}
	
	public function regularBlockHandler($tag,&$context,$iter_data) {
	    // PROCESSING A REGULAR BLOCK
	    $this->extractBlock($tag,$context);	 // adds data about the block to the $context of the tag

		// Recurse with the block contents to discover any nested blocks to expand or
		// non-relative tags which can safely be replaced with their actual values. In this 
		// case, we set $bOnlyNonRelative to 'true'.
		//echo "LOOKING FOR TAGS IN BLOCK CONTENTS:\r\n";
	    $compiledBlockContents = 
			$this->compile($context['blockContents'],array(),true);
			
		

		// Get the actual data for the block
		$rejected = false;
		$block_data = $this->get_recursively($context['value'],$context['commands'],$rejected,$iter_data);
		
		// Skip this tag if it was rejected and 'noerror' was not requested
		if ($rejected && !isset($context['commands']['noerror'])) {
			$context['offset'] = (false !== $context['outerOffset']) 
			    ? $context['outerOffset']
			    : $context['blockEnd'];    	// Increment the offset
			return false;			        // Move on to the next tag
		}
		
		if ($rejected && isset($context['commands']['noerror'])) {
		    // provide an empty data array so that the block is replaced with nothing
		    $block_data = array();
		}
		
		if (!is_array($block_data)) {
		    $this->fatal("<code>{$tag}</code> requests 'block', but <code>{$context['value']}</code> does not resolve to an array");
		}
		
		// Generate as many copies of "compiledBlockContents" as there are elements in "block_data", one
		// for each entry, building up a complete expansion of the block.
		$completeBlockContents = "";
		$keys = array_keys($block_data);
		$cnt  = 0;
		foreach ($block_data as $row) {
		    //for ($rowIdx = 0,$rows = count($block_data);$rowIdx < $rows;$rowIdx++) {
			$completeBlockContents .= $this->compile($compiledBlockContents,$row);
			$completeBlockContents  = str_replace('[__idx]',++$cnt,$completeBlockContents);
			
			// Clear the relative conditional cache since an iter of the block is finished
		    $this->relativeConditionalCache = array();
		}
		
		// Replace the block with the completely expanded block contents
		if ($context['isPseudo']) {
		    $before = substr($context['contents'],0,$context['blockStart'] - (strlen($context['commands']['block'])+2));
		    $after  = substr($context['contents'],$context['blockEnd'] + (strlen($context['commands']['block']) + 3)); 
		} else {
		    $before = substr($context['contents'],0,$context['blockStart']);
		    $after  = substr($context['contents'], (("self" == $context['commands']['block']) 
			    ? $context['blockEnd'] + 2
			    : $context['blockEnd'] + strlen($context['commands']['block']) + 3));
		}
		$context['contents'] = $before . $completeBlockContents . $after;
		
		// Clear the relative caches since the block is finished
		$this->relativeTagCache         = array();
		$this->relativeConditionalCache = array();
		
		// Increment offset
		$context['offset'] = (false !== $context['outerOffset']) 
		    ? $context['outerOffset'] : ($context['blockStart'] + strlen($completeBlockContents));

		return true;
	}
	
	
	public function simpleTagHandler($tag,&$context,$iter_data) {
		// Determine the actual value which will replace the tag. Look in cache first.
		// Determine whether to check the global tag cache or the relative cache:
		if ("@" == $context['value'][0]) {
			$cacheToCheck =& $this->relativeTagCache; 	// relative cache
		} else {
			$cacheToCheck =& $this->tagCache;			// global cache
		}
		if (! isset( $cacheToCheck["{$context['value']}"])) {
			// Cache Miss ... compute, and store value in the cache if it is not rejected
			$rejected = false;
			$v = $this->get_recursively($context['value'],$context['commands'],$rejected,$iter_data);
			if (!$rejected) {
			    if (isset($context['commands']['nocache'])) {
			        $val = $v;
			    } else {
				    $val = $cacheToCheck[$context['value']] = $v;
			    }
			} 
		} else {
		    $val = $cacheToCheck[$context['value']];
		}
		
		$actual_value = $this->process_commands($context['commands'],$val,$rejected,$iter_data);
		//echo "ACTUAL_VALUE {$actual_value} ";
		
			
		// Skip this tag if it was rejected and 'noerror' not requested
		if ($rejected && !isset($context['commands']['noerror'])) {
			$context['offset'] = (false !== $context['outerOffset'])
				? $context['outerOffset'] 
				: ($context['tagEnd'] + 1);	// Increment the offset
			return false;
		}
		
		// Recursively process the actual value, to handle any nested tags
		if (false !== strpos($actual_value,self::TAG_START_CHAR) && !isset($context['commands']['noparse'])) {
			$actual_value = $this->compile($actual_value,$iter_data);
		}
		/*
		if (isset($context['commands']['noparse'])) {
		    $actual_value = str_replace(self::TAG_START_CHAR,'\\\!'.self::TAG_START_CHAR,$actual_value);
		    var_dump($actual_value);
		    die();
		}
		*/
		
		// replace the tag with the actual value
		$before   = substr($context['contents'],0,$context['tagStart']);
		$after    = substr($context['contents'],$context['tagEnd'] + 1);				
		$context['contents'] = $before . $actual_value . $after;

		// Increment offset
		$context['offset'] = (false !== $context['outerOffset']) 
			? $context['outerOffset'] 
			: ($context['tagStart'] + strlen($actual_value));
			
		return true;
	}
	
	public function simpleConditionalHandler($tag,&$context,$iter_data) {
	    // Evaluate the condition
	    $rejected = false;
    	// Determine whether to check the global or relative conditional cache
    	if ('@' == $context['value'][3] || ('!' == $context['value'][3] && '@' == $context['value'][4])) { // if:@ || if:!@
    		$cacheToCheck =& $this->relativeConditionalCache;
    	} else {
    		$cacheToCheck =& $this->conditionalCache;
    	}
    	if (! isset( $cacheToCheck[$context['value']])) {
    		// Cache Miss ... compute, and store value in the cache if it has not been rejected
    		$v = $this->get_recursively($context['value'],$context['commands'],$rejected,$iter_data);
    		if (!$rejected) {
    			$cacheToCheck[$context['value']] = $v;
    		}
    	}
    	$conditionHeld = $cacheToCheck[$context['value']];
    	
    	// Skip this tag if it was rejected and 'noerror' was not requested
    	if ($rejected && !isset($context['commands']['noerror'])) {
    		$context['offset'] = (false !== $context['outerOffset']) 
    		    ? $context['outerOffset'] 
    		    : $context['tagEnd'] + 1;	// Increment the offset 
    		return false;    // Move on to the next tag
    	}
    	
    	// Split the content at the tagStart
    	$before   = substr($context['contents'],0,$context['tagStart']);
    	$after    = substr($context['contents'],$context['tagEnd'] + 1);
    	
    	if ($conditionHeld) {
    		// Replace tag with contents, compiling if necessary and increment offset
    		$context['contents'] = $before . $this->process_commands($context['commands'],
    			((false !== strpos($context['commands']['content'],'[')) 
    				? $this->compile($context['commands']['content'],$iter_data,$bOnlyNonRelative) 
    				: $context['commands']['content']),$rejected,$iter_data) . $after;
    		// Increment offset
    		$context['offset'] = (false !== $context['outerOffset']) ? $context['outerOffset'] : ($context['tagStart'] + strlen($context['commands']['content']));
    	} else {
    		// Replace block with else, compiling if necessary and increment offset
    		$context['contents'] = $before . $this->process_commands($context['commands'],
    			((false !== strpos($context['commands']['else'],'['))
    				? $this->compile($context['commands']['else'],$iter_data,$bOnlyNonRelative)
    				: $context['commands']['else']),$rejected,$iter_data) . $after;
    		// Increment offset
    		$context['offset'] = (false !== $context['outerOffset']) 
    		    ? $context['outerOffset'] 
    		    : ($context['tagStart'] + strlen($context['commands']['else']));
    	}
    	return true;
	}
	
	
	public function conditionalBlockHandler($tag,&$context,$iter_data) {
	    // PROCESSING A CONDITIONAL BLOCK
	    $this->extractBlock($tag,$context);
		$rejected                 = false;
					
		// Evaluate the condition
		// Determine whether to check the global or relative conditional cache
		if ('@' == $context['value'][3] || ('!' == $context['value'][3] && '@' == $context['value'][4])) { // if:@ || if:!@
			$cacheToCheck =& $relativeConditionalCache;
		} else {
			$cacheToCheck =& $this->conditionalCache;
		}
		if (! isset( $cacheToCheck[$context['value'] ])) {
			// Cache Miss ... compute, and store value in the cache if it has not been rejected
			//if ($cacheToCheck == $relativeConditionalCache) {echo ' rcc chosen';} else {echo ' conditional cache chosen';}
			$v = $this->get_recursively($context['value'],$context['commands'],$rejected,$iter_data);
			if (!$rejected) {
				$cacheToCheck[$context['value'] ] = $v;
			}
		} 
		$conditionHeld = $cacheToCheck[$context['value'] ];
		
		// Skip this tag if it was rejected
		if ($rejected) {
			$context['offset'] = (false !== $context['outerOffset']) 
			    ? $context['outerOffset'] 
			    : ($context['tagEnd'] + 1);	// Increment the offset
			return false;      				// Move on to the next tag
		}
		if ($conditionHeld) {
			// Replace the tag with "" 
			
		    if ($context['isPseudo']) {
		        $before = substr($context['contents'],0,$context['blockStart'] - (strlen($context['commands']['block'])+2));
		        $after  = substr($context['contents'],$context['blockEnd'] + strlen($context['commands']['block']) + 3);
		        $inner  = substr($context['contents'],$context['blockStart'],$context['blockEnd']-$context['blockStart']);
		        $inner  = str_replace($tag,'',$inner);
		        $context['contents'] = $before . $inner . $after;
		    } else {
			    $before   = substr($context['contents'],0,$context['tagStart']);
			    $after    = substr($context['contents'],$context['tagEnd'] + 1);
			    $context['contents'] = $before . $after; 
		    }
			
			$context['offset']   = (false !== $context['outerOffset']) 
			    ? $context['outerOffset'] 
			    : $context['offset'];
		} else {
			if (isset($context['commands']['innerelse']) && $context['commands']['block'] != "self" && !$context['isPseudo']) {
				// Replace the block interior with $commands['innerelse']
				$before   = substr($context['contents'], 0, $context['blockStart'] +  (strpos($context['contents'], ">",$context['blockStart']) - $context['blockStart']));
				$after    = substr($context['contents'],$context['blockEnd']);
				$context['contents'] = $before . ((false !== strpos($context['commands']['innerelse'],self::TAG_START_CHAR)) 
				    ? $this->compile($context['commands']['innerelse'],$iter_data)  // compile 'innerelse' text if necessary
				    : $context['commands']['innerelse']) 
				    . $after;
				$context['offset']   = (false !== $context['outerOffset']) 
			    	? $context['outerOffset'] 
			    	: $context['offset'];
			} else {
				// Replace the block with "" || $commands['else']
				if ($context['isPseudo']) {
				    $before   = substr($context['contents'],0,$context['blockStart'] - (strlen($context['commands']['block']) + 2));
				    $after    = substr($context['contents'],$context['blockEnd'] + strlen($context['commands']['block']) + 3);
				} else {
				    $before   = substr($context['contents'],0,$context['blockStart']);
				    $after    = substr($context['contents'],(("self" == $context['commands']['block']) 
					    ? $context['blockEnd'] + 2
					    : $context['blockEnd'] + strlen($context['commands']['block']) + 3));
				}
				$context['contents'] = $before . (isset($context['commands']['else'])
				    ? ((false !== strpos($context['commands']['else'],self::TAG_START_CHAR)) 
					    ? $this->compile($context['commands']['else'],$iter_data) 	// Compile 'else' text if necessary
					    : $context['commands']['else'])
				    : '') . $after;
	
				$context['offset'] = (false !== $context['outerOffset']) 
				    ? $context['outerOffset']
				    : $context['offset'];
			}
		}
		// Clear the relative conditional cache since the block is finished
		$this->relativeConditionalCache = array();
		
		return true;
	}
	
	public function assertionTagHandler() {
		
	}
	
	public function importTagHandler() {
		
	}
	
	protected function process_commands(&$commands,$value,&$rejected,$iter_data = array()) {
	    $foundNeverDate = false;
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
					if ($value == '0000-00-00 00:00:00') {
						$foundNeverDate = true;
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
				case 'map-function':
				    // Call the provided function, passing 'value' as the sole argument
				    // if $commands['map-function'] contains a '|', treat the left side as 
				    // a class, and the right side as the static function to call
				    if (strpos($commands['map-function'],'|')) {
				        list($className,$functionName) = explode('|',$commands['map-function']);
				        if (method_exists($className,$functionName)) {
				            $value = call_user_func(array($className,$functionName),$value);
				        } else {
				            $this->fatal("Invalid attributes '{$commands['map-function']}' passed as arguments to 'map-function' command");
				        }
				    } else {
				        if (function_exists($commands['map-function'])) {
				            $value = call_user_func($commands['map-function'],$value);
				        }else {
				            $this->fatal("Invalid attributes '{$commands['map-function']}' passed as arguments to 'map-function' command");
				        }
				    }
				    break;
				case 'type':
					switch ($commands['type']) {
					    case 'boolean':
					        list($strTrue,$strFalse) = (isset($commands['format']) 
					            ? explode('|',$commands['format']) 
					            : array('True','False'));
					        if (true == $value) { $value = $strTrue; }
					        else { $value = $strFalse; }
					        break;
						case 'date':
							if ($foundNeverDate && isset($commands['ifnever'])) { break; /* handled already */}
							$value = date((isset($commands['format'])
								? $commands['format']
								: 'Y-m-d H:i:s'),strtotime($value));
							break;
						case 'nicedate':
							$value = $this->nicedate($value);
							break;
						case 'timespan':
						    $value = $this->timespan($value,(isset($commands['includeFractional'])));
						    break;
						case 'slashdate':
							$value = strtotime($value);
							$value = date('m/d/Y',$value);
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
						case 'usmoney':
							list($whole,$decimal) = explode('.',$value);
							if (empty($decimal)) { $value .= '.00'; }
							else {
								$value = $whole .'.'. str_pad($decimal,2,'0',STR_PAD_RIGHT);
							}
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
				case 'addslashes':
				    $value = addslashes($value);
				    break;
				case 'stripslashes':
				    $value = stripslashes($value);
				case 'strtolower': 
					$value = strtolower($value);
					break;
				case 'strtoupper':
					$value = strtoupper($value);
					break;
				case 'urlencode':
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
							    // Call the private method and return its result
							    return $flashlight->$privateMethod();

							
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
	
    protected function condition_helper($needle,&$commands,&$rejected,$iter_data) {
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
			// If the user has specified alternative content, display it instead
			if (isset($commands['else']) || isset($commands['innerelse'])) { $rejected = false; return;}
			
			// Set to false as it does not exist
			$rejected = false;	// this idea of rejected is causing major headaches. is there a better way?
			$lhs = false;
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
				$this->fatal("Unknown operator <code>{$operator}</code> in: <code>{$needle}</code>");
		}
	}
	
    public static function nicedate($date) {
   
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
	       
	    } else {
	        $difference     = $unix_date - $now;
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
   
	    if ($now > $unix_date) {
	        return "$difference $periods[$j] ago";
	    } else {
	        return "in {$difference} {$periods[$j]}";
	    }
	}
	
	public static function timespan($timeInSeconds,$bIncludeFractional=false) {
	    $periods = array("microsecond","second","minute","hour","day","week","month");
	    $lengths = array("1000","60","60","24","7","4","12");
	    
	    if ($bIncludeFractional) {
	        $remainder = false;
    	    for($j = 1; $timeInSeconds >= $lengths[$j] && $j < count($lengths)-1; $j++) {
    	        $remainder      = $timeInSeconds % $lengths[$j];
    	        $remainderUnits = $j;
    	        $timeInSeconds /= $lengths[$j];
    	    }
    	    
    	    $roundedTime = round($timeInSeconds);
    	    if ($roundedTime != 1) {
    	        $periods[$j] .= 's';
    	    }
	    
	        $remainder;
	        if ($remainder != 1 && $periods[$remainderUnits] != '') {
	            $periods[$remainderUnits] .= 's';
	        }
	        return "{$roundedTime} {$periods[$j]} {$remainder} {$periods[$remainderUnits]}";
	    } else {
	        
	        for($j = 1; $timeInSeconds >= $lengths[$j] && $j < count($lengths)-1; $j++) {
    	        $timeInSeconds /= $lengths[$j];
    	    }
	        $roundedTime = round($timeInSeconds);
    	    if ($roundedTime != 1) {
    	        $periods[$j] .= 's';
    	    }
	        return "{$roundedTime} {$periods[$j]}";
	    }
	}
	
	public function set($key,$value) {
		$this->page_data[$key] = $value;
	}
	
	public function ref($key,&$value) {
		$this->page_data[$key] = $value;
	}
	
	protected function fatal($msg) {
	    die('<b>Tadpole:</b> (ERROR) '.$msg);
	}
	
}
