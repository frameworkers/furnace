<?php
/*
 * frameworkers-foundation
 * 
 * FModel.class.php
 * Created on May 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FModel
  * 
  * A Foundation Framework Model
  */
class FModel {
	
	// Variable: data
 	// The modified raw YAML data;
 	public $data;
 	
 	// Array: objects
 	// An array of <FObj> objects defined in this model.
 	public $objects;
 	
 	// Array: obj_data
 	// A temporary array to hold raw parsed YAML object definitions.
 	public $obj_data;
 	
 	// Array: tables
 	// An array of <FSqlTable> objects required by the model.
 	public $tables;
 	
 	// Array: config
 	// An array of configuration key-value pairs (defaults specified)
 	public $config = array (
 		"AttributeVisibility" => "private"
 	);

 	/*
 	 * Function: __construct
 	 * 
 	 * Creates a new <FModel> object from the provided YAML data. 
 	 */
    public function __construct(&$arr) {
    	$this->data     = array();
 		$this->obj_data = array();
 		$this->objects  = array();
 		$this->tables   = array();
 		
 		$keys = array_keys($arr);
 		$vals = array_values($arr);
 		
 		// Extract Configuration variables
 		foreach ($keys as $configLabel) {
 			list($type,$name) = explode(" ",$configLabel);
 			if ( strtolower($type) == "config") {
 				// Save this configuration variable in config
 				$this->config[$name] = $arr[$configLabel];
 			}	
 		}
 		
 		// Extract Objects
		foreach ($keys as $objLabel /* object SomeObject: */) {
			list($type,$name) = explode(" ",$objLabel);
			
			$lc_name  = strtolower($name);				// lower case name
			$std_name = $this->standardizeName($name);	// standardized name
			
			if ( strtolower($type) == "object") {
				// Save this object's raw data in obj_data.
				$this->obj_data[$lc_name] = $arr[$objLabel];
				
				// Create an FObj to represent the object.
				$this->objects[$lc_name] = new FObj($std_name);
				
				// Create an FSqlTable object to represent the
				// object in the database
				$this->tables[$lc_name] = new FSqlTable($std_name);
			}	
		}
		
		/* At this point, $objects contains an array of skeletal FObj
		 * objects (one for each "object ClassName" YAML statement)
		 * indexed by the object's name. $obj_data contains the raw 
		 * array data for each of the objects, also indexed by name.
		 */
		 
		 // Discover Inter-Object Dependencies
		 foreach ($this->objects as $candidate) {
		 	$lc_cand_name = strtolower($candidate->getName()); 
		 	$ckeys = array_keys($this->obj_data[$lc_cand_name]);
		 	$cvals = array_values($this->obj_data[$lc_cand_name]);
		 	
		 	foreach ($ckeys as $dependStatement /* depends OtherObject: */) {
		 		list($type,$name) = explode(" ",$dependStatement);
		 		
		 		if (strtolower($type) == "depends") {
					$dependencyData = $this->obj_data[$lc_cand_name][$dependStatement];
					if (null == $dependencyData) {
						// There was no 'matches' data so just create the dependency with the
						// attribute name equal to the foreign object name
						$candidate->addDependency(
							strtolower($name),
							'',
							$this->standardizeAttributeName($name)
						);
						// Create a socket to service this dependency. This allows a child
			 			// object to call upon its parent.
			 			$s = new FObjSocket($this->standardizeAttributeName($name),$candidate->getName());
			 			$s->setForeign($this->standardizeName($name));
			 			$s->setQuantity("1");
			 			$s->setReflection(false);
			 			$s->setLookupTable($this->standardizeName($name));
			 			$s->setVisibility($this->config['AttributeVisibility']);
			 			$candidate->addSocket($s);
			 			// Create a column in the object table to store the relation
						$colname = $s->getName()."_id";
			 			$c = new FSqlColumn($s->getName()."_id","INT(11) UNSIGNED",false,false,"foreign key to {$s->getLookupTable()} table");
			 			$this->tables[$lc_cand_name]->addColumn($c);
			 			// Add delete information about this socket
						if ("FAccount" == $name) {
							// This case is processed specially. Do nothing here.
						} else {
				 			$this->objects[strtolower($name)]->addDeleteInfo($this->createDeleteInfo(
				 				$candidate->getName(),							/* foreign class */
				 				"depends",										/* relation type */
				 				$s->getName(),									/* php variable name */
				 				$candidate->getName(),							/* sql lookup table name */
				 				$colname));										/* sql column name */
						}
					} else {
						foreach ($dependencyData as $nameLine=>$matchData) {
							list($ignore,$socketName) = explode(" ",$nameLine);
							$candidate->addDependency(
								strtolower($name),
								(isset($matchData['matches']) 
									? strtolower($matchData['matches'])
									: ''),
								$this->standardizeAttributeName($socketName)
							);
							// Create a socket to service this dependency. This allows a child
				 			// object to call upon its parent.
				 			$s = new FObjSocket($this->standardizeAttributeName($socketName),$candidate->getName());
				 			$s->setForeign($this->standardizeName($name));
				 			$s->setQuantity("1");
				 			$s->setReflection(false);
				 			$s->setLookupTable($this->standardizeName($name));
				 			$s->setVisibility($this->config['AttributeVisibility']);
				 			$candidate->addSocket($s);
				 			// Create a column in the object table to store the relation
							$colname = $s->getName()."_id";
				 			$c = new FSqlColumn($colname,"INT(11) UNSIGNED",false,false,"foreign key");
				 			$this->tables[$lc_cand_name]->addColumn($c);
				 			// Add dependency and delete information about this socket
							if ("FAccount" == $name) {
								// this case is processed specially. Do nothing here.
							} else {
					 			$this->objects[strtolower($name)]->addDeleteInfo($this->createDeleteInfo(
					 				$candidate->getName(),							/* foreign class */
					 				"depends",										/* relation type */
					 				$s->getName(),									/* php variable name */
					 				$candidate->getName(),							/* sql lookup table name */
					 				$colname));										/* sql column name */
							}
						}
					}
		 		} else if (strtolower($type) == "extends") {
		 			// Non-default inheritance specified... capture class name:
		 			$candidate->setParentClass($this->standardizeName($name));	
		 		}
		 	}	
		 }
		
		/* At this point, all inter-object dependencies have been captured,
		 * along with their foreign matchVariable names. This is almost 
		 * enough to build a complete database from the model, but first, 
		 * we must understand which "foreign" relationships will require 
		 * cross reference lookup tables.
		 */
		 
		 // Discover Sockets
		 foreach ($this->objects as $candidate) {
		 	$lc_cand_name = strtolower($candidate->getName());
		 	$ckeys = array_keys($this->obj_data[$lc_cand_name]);
		 	$cvals = array_values($this->obj_data[$lc_cand_name]);
		 	
		 	foreach ($ckeys as $statement) {
		 		list($type,$name) = explode(" ",$statement);
		 		if (strtolower($type) == "attr") {
		 			if (isset($this->obj_data[$lc_cand_name][$statement]['foreign'])) {
		 				$foreignClass = $this->standardizeName($this->obj_data[$lc_cand_name][$statement]['foreign']);
		 				$s = new FObjSocket(
		 					$this->standardizeAttributeName($name),
		 					$candidate->getName(),
		 					$this->determineActualRemoteVariableName(
				 				$candidate->getName(),					/* the class */
				 				$this->standardizeAttributeName($name),	/* the socket name */
				 				$this->standardizeName($foreignClass)	/* the foreign class */
								/* return attr name where: foreign class has a dependency on class which matches socketname */
				 			)
				 		);
		 				$s->setForeign($foreignClass);
		 				$s->setDescription($this->obj_data[$lc_cand_name][$statement]['desc']);
		 				$s->setQuantity($this->obj_data[$lc_cand_name][$statement]['quantity']);
		 				$s->setVisibility(((isset($this->obj_data[$lc_cand_name][$statement]['visibility'])) 
		  					? $this->obj_data[$lc_cand_name][$statement]['visibility'] 
		  					: $this->config['AttributeVisibility']));
		 				
		 				// Set the reflection details, if required
		 				if (isset($this->obj_data[$lc_cand_name][$statement]['reflect'])) {
		 					list($ignore,$reflectVar) = explode(".",$this->obj_data[$lc_cand_name][$statement]['reflect']);
		 					$s->setReflection(true);
		 					$s->setReflectVariable(strtolower($reflectVar));	
		 				}
		 				// Set the lookup table, if required
		 				if ($s->getQuantity() == "M") {
		 					// Only M relationships could possibly require a lookup table... but not
		 					// all M relationships do -- only M:M relationships (and not M:1).
		 					// To determine whether a relationship is M:M, it is sufficient to determine
		 					// that it is not M:1 by verifying that no dependency on this object exists in 
		 					// the foreign object. If such a dependency were found, it would mean the foreign
		 					// object is related to exactly 1 of this object type, thus making the relationship
		 					// M:1.
		 					foreach ($this->objects[strtolower($foreignClass)]->getDependencies() as $dep){
		 						if ($dep['class'] == $lc_cand_name &&
		 							$dep['var'] == ("{$lc_cand_name}.".strtolower($name))){
		 							
		 							// Dependency detected. No need for a lookup table
		 							$s->setLookupTable($foreignClass);	
		 							break;	
		 						}
		 					}
		 					if ("" == $s->getLookupTable()) {
	 							// No dependency detected (from above).
	 							if ($s->doesReflect()){
	 								// Use the lookup table from an earlier specified object
	 								$fs = $this->objects[strtolower($foreignClass)]->getSockets();
	 								foreach ($fs as $ffs) {
	 									if (strtolower($ffs->getName()) == $s->getReflectVariable()) {
	 										// Found reflected socket, use same lookup table
	 										$s->setLookupTable($ffs->getLookupTable());	
	 										break;
	 									}		
	 								}
	 							} else {
	 								// No dependency and no reflection, create a new lookup table 
	 								// based on a FCFS naming scheme
	 								$lt_name = "{$candidate->getName()}_{$foreignClass}_{$s->getName()}";
	 								$lt = new FSqlTable($lt_name,true);
	 								$lc_pk1name = strtolower(substr($candidate->getName(),0,1)).substr($candidate->getName(),1);
	 								$lc_pk2name = strtolower(substr($foreignClass,0,1)).substr($foreignClass,1);
	 								if ($lc_pk1name == $lc_pk2name) {
	 									$lc_pk1name .= "1";
	 									$lc_pk2name .= "2";
	 								}
	 								$c1 = new FSqlColumn("{$lc_pk1name}_id","INT(11) UNSIGNED");
	 								$c2 = new FSqlColumn("{$lc_pk2name}_id","INT(11) UNSIGNED");
	 								// Add Columns
	 								$lt->addColumn($c1);
	 								$lt->addColumn($c2);
	 								// Add Primary Keys 
	 								$lt->addPrimaryKey($c1);
	 								$lt->addPrimaryKey($c2);
	 								
	 								$this->tables[strtolower($lt->getName())] = $lt;
	 								$s->setLookupTable($lt_name);
	 								
	 								// Add delete information about this socket
									if ("FAccount" == $name) {
										die("improper use of 'FAccount' class");
									} else {
							 			$candidate->addDeleteInfo($this->createDeleteInfo(
							 				$foreignClass,					/* foreign class */
							 				"lookup",						/* relation type */
							 				$s->getName(),					/* php variable name */
							 				$lt->getName(),					/* sql lookup table name */
							 				"{$lc_pk1name}_id",				/* sql column name */
							 				"{$lc_pk2name}_id"));			/* sql column name */
									}
		 						}
		 					}
		 				} else {
		 					// In the case that a relationship is anything other than M:M, the lookup 
		 					// table is simply the foreign class name.
		 					$lt = "{$foreignClass}";	
		 					$s->setLookupTable($lt);
		 				}
		 				if ($s->getLookupTable() == "") {
		 					die("Could not determine lookup table to use for {$candidate->getName()}.{$s->getName()}");	
		 				}
		 				// Add the socket to the object's sockets array.
		 				$candidate->addSocket($s);
		 				
		 			}/* end if isset 'foreign' */ else {
		 				// Create and flesh out a normal (local) attribute
		 				$a = new FObjAttr($this->standardizeAttributeName($name));
		 				$attr_candidate = $this->obj_data[$lc_cand_name][$statement];
		 				  $a->setType(((isset($attr_candidate['type'])) ? $attr_candidate['type'] : ""));
		  				$a->setDescription(((isset($attr_candidate['desc'])) ? $attr_candidate['desc'] : ""));
		  				$a->setSize(((isset($attr_candidate['size'])) ? $attr_candidate['size'] : ""));
		  				$a->setMin(((isset($attr_candidate['min'])) ? $attr_candidate['min'] : ""));
		  				$a->setMax(((isset($attr_candidate['max'])) ? $attr_candidate['max'] : ""));
		  				$a->setIsUnique(((isset($attr_candidate['unique'])) ? true : false));
		  				$a->setVisibility(((isset($attr_candidate['visibility'])) 
		  					? $attr_candidate['visibility'] 
		  					: $this->config['AttributeVisibility']));
		  				
		  				// Add the attribute to the object's attributes array.	
		  				$candidate->addAttribute($a);
		  				// Add the attribute to the table definition
		  				$col = new FSqlColumn($a->getName(),
		  					FSqlColumn::convertToSqlType($attr_candidate['type'],$attr_candidate),
		  					false,
		  					false,
		  					$a->getDescription());
		  				if (isset($attr_candidate['unique'])) {
		  					$col->setKey("UNIQUE");
		  				}
		  				$this->tables[$lc_cand_name]->addColumn($col);
		  				
		 			}
		 		}/* end if 'attr' */	
		 	}/* end foreach ckeys as statement */	
		 }/* end foreach objects */
		 
		 /* At this point, objects have been identified, all dependencies captured, all foreign relationships 
		  * have been modeled using FObjSocket objects, and all local attributes modeled using FObjAttr objects.
		  * All required lookup tables have been determined, and, essentially, the hard work is done. 
		  */ 
    }
    
 	/*
 	 * Function: standardizeName
 	 * 
 	 * This function is private. It takes a string and 
 	 * standardizes it according to framework naming 
 	 * conventions.
 	 * 
 	 * Parameters:
 	 * 
 	 *  name - The name string to standardize.
 	 * 
 	 * Returns:
 	 * 
 	 *  (string) The standardized name.
 	 */
 	private function standardizeName($name) {
  		// 1. Replace all '_' with ' ';
  		// 2. Capitalize all words
  		// 3. Concatenate words
  		//
  		// Turns: long_object_name
  		// into:  LongObjectName
  		return 
  			str_replace(" ","",ucwords(str_replace("_"," ",$name)));
  	}
  	
  	/*
  	 * Function: standardizeAttributeName
  	 * 
  	 * This funtion is like <standardizeName> in that it takes
  	 * a string and standardizes it according to framework
  	 * naming conventions for object attributes.
  	 * 
  	 * Parameters:
  	 * 
  	 *  name - The attribute name string to standardize.
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) The standardized attribute name
  	 */
  	private function standardizeAttributeName($name) {
  		// 1. Replace all '_' with ' ';
  		// 2. Capitalize all words
  		// 3. Concatenate words
  		// 4. Make the first letter lowercase
  		//
  		// Turns: long_variable_name
  		// into:  longVariableName
  		$s = str_replace(" ","",ucwords(str_replace("_"," ",$name)));
  		return strtolower(substr($s,0,1)) . substr($s,1);
  	} 	

  	/*
  	 * Function: createDeleteInfo
  	 * 
  	 * This function converts the passed parameters into an associate array. The array
  	 * 
  	 * Parameters:
  	 * 
  	 *  class - The name of the class which contains the socket
  	 *  type -  The relationship type. One of (depends | lookup)
  	 *  phpvar - the name of the attribute that represents the socket
  	 *  sqltable - The SQL Table to use in lookups
  	 *  sqlcol - The sql column to use in lookups
  	 *  sqlcol2 - (optional) The second primary key name (in the case of 'lookup')
  	 * 
  	 * Returns:
  	 * 
  	 *  (array) An array composed of the passed in parameters
  	 */
  	public function createDeleteInfo($class,$type,$phpvar,$sqltable,$sqlcol,$sqlcol2='') {
  		return array(
  			"class"=> $class,
  			"type" => $type,
  			"phpvar" => $phpvar,
  			"sqltable" => $sqltable,
  			"sqlcol" => $sqlcol,
  			"sqlcol2"=> $sqlcol2);
  	}

  	/*
  	 * Function: determineActualRemoteVariableName
  	 * 
  	 * This function determines the name of the remote attribute in a socket pair. If the 
  	 * remoteClass has a dependency on the requestingClass, and that dependency matches 
  	 * socketName, this function will return the name of the remote attribute representing
  	 * the dependency.  
  	 * 
  	 * Parameters:
  	 * 
  	 *  requestingClass - The name of the class requesting the name resolution (local class)
  	 *  socketName - ?
  	 *  remoteClass - The name of the class with a dependency on the requestingClass 
  	 * 
  	 * Returns: 
  	 * 
  	 *  (string) - The attribute name where: remoteClass has a dependency on requestingClass which
  	 *  matches socketName.
  	 * 
  	 */
  	private function determineActualRemoteVariableName($requestingClass,$socketName,$remoteClass) {
  		/* return attr name where: remoteClass has a dependency on requestingClass which matches socketName */
		
		$data =& $this->obj_data[strtolower($remoteClass)];
		
		foreach ($data as $label => $subdata) {
			if ("depends {$requestingClass}" == $label){
				foreach ($subdata as $attrLabel => $attrData) {
					list($ignore,$attrName) = explode(" ",$attrLabel);
					list($ignore,$match) = explode(".",$attrData['matches']);
					$lc_match = strtolower($match);
					if ($lc_match == strtolower($socketName)) {
						$std_attr = $this->standardizeName($attrName);
						return strtolower(substr($std_attr,0,1).substr($std_attr,1));
					}
				}
			}
		}	
  	}
}
?>