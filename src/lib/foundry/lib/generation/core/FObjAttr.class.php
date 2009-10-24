<?php
/*
 * frameworkers-foundation
 * 
 * FObjAttr.class.php
 * Created on May 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
  /*
  * Class: FObjAttr
  * Defines an attribute of an Foundation Model Object (<FObj>). 
  * FObjAttr objects normally correspond to a column in the 
  * corresponding <FObj> object's table.
  * 
  */
  class FObjAttr {
  	
  	// Variable: name
  	// The unique name of this attribute.
  	private $name;
  	
  	// Variable: description
  	// A short description of the attribute.
  	private $description;
  	
  	// Variable: type
  	// A type designation for the attribute.
  	private $type;
  	
  	// Variable: size
  	// A size designation for the attribute.
  	private $size;
  	
  	// Variable: min
  	// A minimum value for the attribute.
  	private $min;
  	
  	// Variable: max
  	// A maximum value for the attribute.
  	private $max;
  	
  	// Variable: isUnique
  	// A boolean flag indicating whether or not the value is unique.
  	public $isUnique;
  	
  	// Variable: default
  	// A default value for the variable.
  	private $defaultValue;
  	
  	// Array: values
  	// An array of controlled values for the attribute.
	private $values;
	
	// Variable: visibility
	// Specify the visibility {public,private,protected} of the attribute
	private $visibility;
	
	// Array: validation
	// An array of information regarding the validation of this attribute
	private $validation;
  	
  	// Variable: functionName
  	// The unique name of this attribute as it would
  	// be used in a function definition. 
  	//
  	// Notes: 
  	// 
  	// The primary diference between this variable and the <name> 
  	// variable is that this variable begins with an uppercase 
  	// letter. for example, given a attribute named longVariableName, 
  	// its <functionName> would be LongVariableName, so that it could 
  	// be used in something like getLongVariableName or setLongVariableName.
  	private $functionName;
  	
  	/*
  	 * Function: __construct
  	 * 
  	 * Constructor. Creates a new <FObjAttr> object.
  	 */
  	public function __construct($name,$data) {
  		$this->name = FModel::standardizeAttributeName($name);
  		$this->functionName = ucfirst($this->name);
  		
  		// Use attrData to fill in details about this attr
		$this->setDescription($data['desc']);
		$this->setType($data['type']);
		$this->setSize($data['size']);
		$this->setMin($data['min']);
		$this->setMax($data['max']);
		$this->setIsUnique($data['unique'] === true);
		$this->setDefaultValue($data['default']);
		$this->setVisibility(isset($data['visibility']) ? $data['visibility'] : "private");
		$this->setValidation(isset($data['validation']) ? $data['validation'] : array());
  	}
  	
  	/*
  	 * Function: getName
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's name
  	 */
  	public function getName() {
  		return $this->name;	
  	}
  	
  	/*
  	 * Function: getFunctionName
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's name, capitalized for function use
  	 */
  	public function getFunctionName() {
  		return $this->functionName;	
  	}
  	
  	/*
  	 * Function: getDescription
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's description
  	 */
  	public function getDescription() {
  		return stripslashes($this->description);	
  	}
  	
  	/*
  	 * Function: getType
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's type
  	 */
  	public function getType() {
  		return $this->type;	
  	}
  	
  	/*
  	 * Function: getSize
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's size
  	 */
  	public function getSize() {
  		return $this->size;	
  	}
  	
  	/*
  	 * Function: getMin
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's minimum value
  	 */
  	public function getMin() {
  		return $this->min;	
  	}
  	
  	/*
  	 * Function: getMax
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's maximum value
  	 */
  	public function getMax() {
  		return $this->max;	
  	}
  	
  	/*
  	 * Function: isUnique
  	 * 
  	 * Returns:
  	 * 
  	 *  (bool) - Whether or not the attribute is unique
  	 */
  	public function isUnique() {
  		return $this->isUnique;	
  	}
  	
  	/*
  	 * Function: getDefaultValue
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's default value
  	 */
  	public function getDefaultValue() {
  		return $this->defaultValue;	
  	}
  	
  	/*
  	 * Function: getValues
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's defined values
  	 */
  	public function getValues() {
  		return $this->values;	
  	}
  	
  	/*
  	 * Function: getVisibility
  	 * 
  	 * Returns:
  	 * 
  	 *  (string) - The attribute's visibility level (public,private,etc)
  	 */
  	public function getVisibility() {
  		return $this->visibility;	
  	}
  	
  	/*
  	 * Function: getValidation
  	 * 
  	 * Returns:
  	 * 
  	 *  (array) - The attribute's validation information
  	 */
  	public function getValidation() {
  		return $this->validation;	
  	}
  	
  	public function setName($value) {
  		$this->name = $value;
  	}
  	
  	/*
  	 * Function: setDescription
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the description
  	 * 
  	 */
  	public function setDescription($value) {
  		$this->description = $value;	
  	}
  	
  	/*
  	 * Function: setType
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the type
  	 * 
  	 */
  	public function setType($value) {
  		$this->type = $value;	
  	}
  	
  	/*
  	 * Function: setSize
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the size
  	 * 
  	 */
  	public function setSize($value) {
  		$this->size = $value;	
  	}
  	
  	/*
  	 * Function: setMin
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the minimum value
  	 * 
  	 */
  	public function setMin($value) {
  		$this->min = $value;	
  	}
  	
  	/*
  	 * Function: setMax
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the maximum value
  	 * 
  	 */
  	public function setMax($value) {
  		$this->max = $value;	
  	}
  	
  	/*
  	 * Function: setIsUnique
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - boolean, whether or not the attribute is unique
  	 * 
  	 */
  	public function setIsUnique($value) {
  		$this->isUnique = $value;	
  	}
  	
  	/*
  	 * Function: setDefaultValue
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the default value
  	 * 
  	 */
  	public function setDefaultValue($value) {
  		if ("false" == strtolower($value)) {
  			$this->defaultValue = false;	
  		} else if ("true" == strtolower($value)) {
  			$this->defaultValue = true;	
  		} else {
  			$this->defaultValue = $value;	
  		}
  	}
  	
  	/*
  	 * Function: setValues
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the controlled values
  	 * 
  	 */
  	public function setValues($value) {
  		$this->values = $value;	
  	}
  	
  	/*
  	 * Function: setVisibility
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The value to set for the visibility level
  	 * 
  	 */
  	public function setVisibility($value) {
  		$this->visibility = $value;	
  	}
  	
  	/*
  	 * Function: setValidation
  	 * 
  	 * Parameters:
  	 * 
  	 *  value - The validation information to use for this attribute
  	 * 
  	 */
  	public function setValidation($value) {
  		if (empty($value)) { $this->validation = array(); }
  		else 
  			$this->validation = $value;	
  	}
  }
?>