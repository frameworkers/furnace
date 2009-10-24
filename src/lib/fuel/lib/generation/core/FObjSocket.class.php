<?php
/*
 * frameworkers-foundation
 * 
 * FObjSocket.class.php
 * Created on May 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FObjSocket
  * 
  * Defines a relationship between two <FObj> objects.
  * 
  */
 class FObjSocket {
 
 	// Variable: name
 	// The name of this socket.
 	private $name; 
 	
 	// Variable: owner
 	// The name of the object that owns this socket.
 	private $owner;
 	
 	// Variable: foreign
 	// The name of the object at the far end of this socket.
 	private $foreign;
 	
 	// Variable: quantity
 	// A value describing the nature of the relationship (1,M).
 	private $quantity;
 	
 	// Variable: lookupTable
 	// The name of the database table serving as a lookup for this socket.
 	private $lookupTable;
 	
 	// Variable: reflect
 	// The name of the remote variable that reflects this socket.
 	private $bReflects;
 	
 	// Variable: reflectVariable
 	// A value describing the nature of the reflected relationship (1,M).
 	private $reflectVariable;
 	
 	// Variable: description
 	// A brief text description of the variable
 	private $description;
 	
 	// Variable: visibility
 	// Specify the visibility {public,protected,private} of the socket
 	private $visibility;
 	
 	// Variable: required
	// Valid in the case of M:1 only, this boolean variable specifies whether
	// the relationship must be present in order to instantiate an object,
	// or if it may be non-existant for some instances
	private $required;
 	
 	// Variable: customRemoteVariableName
	// The name to use when referring to the class on the far end of the socket.
	// This value is valid only when dealing with 1M sockets
	private $customRemoteVariableName;
 	
 	
 	/*
 	 * Function: __construct
 	 * 
 	 * Constructor. Creates a new <FObjSocket> object.
 	 * 
 	 */
 	public function __construct($name,$owner,$foreign,$data) {
 		$this->name    = FModel::standardizeAttributeName($name);
 		$this->owner   = FModel::standardizeName($owner);
 		$this->foreign = FModel::standardizename($foreign);
 		
 		// use data to fill in details about this socket
		$this->setDescription($data['desc']);
		$this->setQuantity($data['quantity']);
		$this->setReflection(isset($data['reflects']),$data['reflects']);
		$this->setVisibility(isset($data['visibility']) ? $data['visibility'] : "protected");
		$this->setRequired(("yes" == $data['required']) ? true : false);
 	}
 	
 	/*
  	 * Function: getName
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The socket's name
  	 */
 	public function getName() {
 		return $this->name;	
 	}
 	
 	/*
  	 * Function: getFunctionName
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The socket's name, capitalized for function use
  	 */
 	public function getFunctionName() {
 		return strtoupper(substr($this->name,0,1)) . substr($this->name,1);	
 	}
 	
 	/*
  	 * Function: getOwner
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The name of the class which owns this socket
  	 */
 	public function getOwner() {
 		return $this->owner;	
 	}
 	
 	/*
  	 * Function: getForeign
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The name of the class at the other end of the socket
  	 */
 	public function getForeign() {
 		return $this->foreign;	
 	}
 	
 	/*
  	 * Function: getQuantity
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The quantity (one of 1|M) representable by this socket
  	 */
 	public function getQuantity() {
 		return $this->quantity;	
 	}
 	
 	/*
  	 * Function: getLookupTable
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The name of the lookup table to use for this socket
  	 */
 	public function getLookupTable() {
 		return $this->lookupTable;	
 	}
 	
 	/*
  	 * Function: doesReflect
  	 * 
  	 * Returns:
  	 * 
  	 *  (boolean) - Whether or not this socket is a reflection
  	 */
 	public function doesReflect() {
 		return $this->bReflects;	
 	}
 	
 	/*
  	 * Function: getReflectVariable
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The name of the variable reflected by this socket
  	 */
 	public function getReflectVariable() {
 		return $this->reflectVariable;	
 	}
 	
 	/*
  	 * Function: getActualRemoteVariableName
  	 * 
  	 * If a custom remote variable name has been defined, then that is 
  	 * returned. If not, the value is the name of the owner
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The actual remote variable name
  	 */
 	public function getActualRemoteVariableName() {
 		if ("" == $this->customRemoteVariableName) {
 			return $this->owner;
 		} else {
 			return $this->customRemoteVariableName;
 		}
 	}
 	
 	/*
  	 * Function: getDescription
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The socket's description
  	 */
 	public function getDescription() {
 		return stripslashes($this->description);
 	}
 	
 	/*
  	 * Function: getVisibility
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The socket's visibility level (public,private,etc)
  	 */
 	public function getVisibility() {
 		return $this->visibility;	
 	}
 	
 	
 	/*
 	 * Function: isRequired
 	 * 
 	 * Returns:
 	 * 
 	 *  (boolean) - whether or not the socket is 'required'
 	 */
	public function isRequired() {
		return $this->required;
	}
	public function getRequired() {
		return $this->required;
	}
	
 	/*
  	 * Function: setForeign
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the foreign class name
  	 * 
  	 */
 	public function setForeign($value) {
 		$this->foreign = $value;	
 	}
 	
 	/*
  	 * Function: setQuantity
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - One of [1|M]. The value to set for the quantity representable 
  	 *  by this socket
  	 * 
  	 */
 	public function setQuantity($value) {
 		$this->quantity = $value;	
 	}
 	
 	/*
  	 * Function: setLookupTable
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The lookup table to use for this socket
  	 * 
  	 */
 	public function setLookupTable($value) {
 		$this->lookupTable = $value;
 	}
 	
 	/*
  	 * Function: setReflection
  	 * 
  	 * Parameters:
  	 * 
  	 *  bValue - (boolean) Whether or not this socket is a reflection
  	 *  variable - (optional) If it is a reflection, this is the reflected variable
  	 * 
  	 */
 	public function setReflection($bValue,$variable='') {
 		$this->bReflects = $bValue;	
 		if (false !== strpos($variable,".")) {
 			list($ignore,$reflectVar) = explode(".",$variable);
 		} else {
 			$reflectVar = $variable;
 		}
 		
 		$this->reflectVariable = FModel::standardizeAttributeName($reflectVar);
 	}
 	
 	/*
  	 * Function: setReflectVariable
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - (optional) If it is a reflection, this is the reflected variable
  	 * 
  	 */
 	public function setReflectVariable($value) {
 		
 		list($ignore,$reflectVar) = explode(".",$value);
 		
 		$this->reflectVariable = FModel::standardizeAttributeName($reflectVar);	
 	}
 	
 	/*
  	 * Function: setDescription
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The lookup table to use for the description
  	 * 
  	 */
 	public function setDescription($value) {
 		$this->description = $value;	
 	} 	
 	
 	/*
  	 * Function: setVisibility
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - Set a visibility level (public,private,etc) for this socket
  	 * 
  	 */
 	public function setVisibility($value) {
 		$this->visibility = $value;	
 	}
 
 	/*
 	 * Function: setRequired
 	 * 
 	 * Parameters:
 	 * 
 	 *  value - A boolean value (true/false) indicating whether the socket is required
 	 * 
 	 */
	public function setRequired($value) {
		$this->required = $value;
	}
 
 }
?>