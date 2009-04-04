<?php
/*
 * frameworkers-foundation
 * 
 * FObj.class.php
 * Created on May 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * Class: FObj
  * Defines a basic Foundation Model Object. <FObj> objects
  * form the basis for a data-driven application and usually (unless
  * they are declared as abstract) correspond to a database table. 
  * 
  */
 class FObj {
 	
 	// Variable: name
 	// The name of this object class.
 	private $name;
 	
 	// Array: attributes
 	// An array of <FObjAttr> objects for this object.
 	private $attributes;
 	
 	// Array: sockets
 	// An array of <FObjSocket> objects representing parents
 	private $parents;
 	
 	// Array: sockets
 	// An array of <FObjSocket> objects representing peers
 	private $peers;
 	
 	// Array: sockets
 	// An array of <FObjSocket> objects representing children
 	private $children;
 	
 	// Boolean: isAbstract
 	// A boolean flag (true/false), with true indicating an abstract object.
 	private $isAbstract;
 	
 	// Variable: parentClass
 	// The class this class inherits from. The default is <FBaseObject>.
 	private $parentClass;
 	
 	/*
 	 * Function: __construct
 	 * 
 	 * Constructor. Creates a new <FObj> object. <FObj> objects may be 
 	 * declared as "abstract" by passing a value of "true" as the second
 	 * parameter.
 	 * 
 	 * Parameters:
 	 * 
 	 *  name - the name of this object.
 	 *  isAbstract - Boolean flag (true = abstract) (default = false).
 	 */
 	public function __construct($name,&$modelData){
 		
 		// Initialize data structures
 		$this->name = FModel::standardizeName($name);
 		$this->attributes = array();
 		$this->parents = $this->peers = $this->children = array();
 		$this->isAbstract  = false;
 		$this->parentClass = "FBaseObject";
 		
 		// Store the model data
		$objectData =& $modelData[$name];
		
		// Handle non-default inheritance
		if (isset($objectData['extends'])) {
			$this->parentClass = $objectData['extends'];
		}
		
		// Add attributes
		if (isset($objectData['attributes'])) {
			foreach ($objectData['attributes'] as $attrName => $attrData) {
				$this->addAttribute(new FObjAttr($attrName,$attrData));
			}
		}
		
		// Add parent sockets
		if (isset($objectData['parents'])) {
			foreach ($objectData['parents'] as $foreignObjectName => $sockets) {
				foreach ($sockets as $socketName => $socketData) {
					$this->addParent(new FObjSocket($socketName,$this->name,$foreignObjectName,$socketData));
				}
			}
		}
		
		// Add peer sockets
		if (isset($objectData['peers'])) {
			foreach ($objectData['peers'] as $foreignObjectName => $sockets) {
				foreach ($sockets as $socketName => $socketData) {
					$this->addPeer(new FObjSocket($socketName,$this->name,$foreignObjectName,$socketData),$modelData);
				}
			}
		}
		
		// Add child sockets
		if (isset($objectData['children'])) {
			foreach ($objectData['children'] as $foreignObjectName => $sockets) {
				foreach ($sockets as $socketName => $socketData) {
					$this->addChild(new FObjSocket($socketName,$this->name,$foreignObjectName,$socketData));
				}
			}
		}
 	}	
 	
 	/* 
 	 * Function: getName
 	 * 
 	 * Get the name of this object.
 	 * 
 	 * Returns: 
 	 * 
 	 * (string) The name of this object.
 	 */
 	public function getName() {
 		return $this->name;	
 	}
 	 	
 	/*
 	 * Function: getParentClass
 	 * 
 	 * Get the parent object of this object
 	 * 
 	 * Returns: 
 	 * 
 	 * (string) The name of the parent class.
 	 */
 	public function getParentClass() {
 		return $this->parentClass;	
 	}
 	
 	/*
 	 * Function: setIsAbstract
 	 * 
 	 * Sets whether or not this object is abstract.
 	 * 
 	 * Parameters:
 	 * 
 	 *  bIsAbstract - (boolean) true=isAbstract.
 	 */
 	public function setIsAbstract($bIsAbstract) {
 		$this->isAbstract = $bIsAbstract;	
 	}
 	
 	/*
 	 * Function: setParentClass
 	 * 
 	 * Sets the name of the parent class.
 	 * 
 	 * Parameters: 
 	 * 
 	 *  value - (string) The name of the parent class.
 	 */
 	public function setParentClass($value) {
 		$this->parentClass = $value;	
 	}
 	
 	/*
 	 * Function: addAttribute
 	 * 
 	 * Adds an <FObjAttr> object to the object's <attributes> array.
 	 * 
 	 * Parameters: 
 	 * 
 	 *  attr - The <FObjAttr> object to add.
 	 * 
 	 * Returns: 
 	 * 
 	 *  (boolean) Whether the attribute was successfully added.
 	 */
 	public function addAttribute($attr) {
 		$this->attributes[] = $attr;
 	}	
 	
 	/*
 	 * Function: getAttributes
 	 * 
 	 * Returns the object's <attributes> array.
 	 * 
 	 * Parameters: 
 	 * 
 	 *  none
 	 * 
 	 * Returns: 
 	 * 
 	 * (array) The object's <attributes> array.
 	 */
 	public function getAttributes() {
 		return $this->attributes;	
 	}
 	
 	public function getAttribute($name) {
 		foreach ($this->attributes as $a) {
 			if (strtolower($name) == strtolower($a->getName())) {
 				return $a;
 			}
 		}
 	}
 	
 	public function deleteAttribute($name) {
 		for ($i = 0; $i < count($this->attributes); $i++) {
 			if (strtolower($this->attributes[$i]->getName()) == strtolower($name)) {
 				unset($this->attributes[$i]);
 				return true;
 			}
 		}
 		return false;
 	}
 	
 	public function addParent($socket) {
 		// Compute the lookup table for this socket
		$socket->setLookupTable($socket->getOwner());
		
		// Add the socket to the list of parents
 		$this->parents[] = $socket;	
 	}
 	
 	public function addPeer($socket) {
 		// Compute the lookup table for this socket based on alphabetic ordering 
		// of the owner and foreign object names. 
		$sortedNames = array($this->getName(),$socket->getForeign());
		sort($sortedNames);
		if ($this->getName() == $sortedNames[0]) {
			$socket->setLookupTable("{$sortedNames[0]}_{$sortedNames[1]}_{$socket->getName()}");
		} else {
			$socket->setLookupTable("{$sortedNames[0]}_{$sortedNames[1]}_{$socket->getReflectVariable()}");
		}
		
		// Add the socket to the list of peers
 		$this->peers[] = $socket;	
 	}
 	
 	public function addChild($socket) {
 		// Compute the lookup table
		$socket->setLookupTable($socket->getForeign());
		
		// Add the socket to the list of children
 		$this->children[] = $socket;	
 	}
 	
 	public function getParents() {
 		return $this->parents;	
 	}
 	
 	public function getParent($name) {
 		for ($i =0; $i < count($this->parents); $i++) {
 			if (strtolower($this->parents[$i]->getName()) == strtolower($name)) {
 				return $this->parents[$i];
 			}
 		}
 		return false;
 	}
 	
 	public function getPeers() {
 		return $this->peers;	
 	}
 	
 	public function getPeer($name) {
 		for ($i =0; $i < count($this->peers); $i++) {
 			if (strtolower($this->peers[$i]->getName()) == strtolower($name)) {
 				return $this->peers[$i];
 			}
 		}
 		return false;
 	}
 	
 	public function getChildren() {
 		return $this->children;	
 	}
 	
 	public function deleteParent($socketName) {
 		for ($i = 0; $i < count($this->parents); $i++ ) {
 			if (isset($this->parents[$i]) && strtolower($this->parents[$i]->getName()) == strtolower($socketName)) {
 				unset($this->parents[$i]);
 				return true;
 			}
 		}
 		return false;
 	}
 	
 	public function deletePeer($socketName) {
 		for ($i = 0; $i < count($this->peers); $i++ ) {
 			if (isset($this->peers[$i]) && strtolower($this->peers[$i]->getName()) == strtolower($socketName)) {
 				unset($this->peers[$i]);
 				return true;
 			}
 		}
 		return false;
 	}
 	
 	public function deleteChild($socketName) {
 		for ($i = 0; $i < count($this->children); $i++ ) {
 			if (isset($this->children[$i]) && strtolower($this->children[$i]->getName()) == strtolower($socketName)) {
 				unset($this->children[$i]);
 				return true;
 			}
 		}
 		return false;
 	}

 }

?>