<?php
	
class FModel {
	
	public $objects   = array();
	
	
	public $modelData = array();
	
	public $use_accounts = true;

	public function __construct($modelData) {
		
		$this->modelData = $modelData;
		ksort($this->modelData);
	
		// Generate representation of each object and object table
		foreach ($this->modelData as $model_object_name => $model_object_data) {
			// Build objects
			$this->objects[self::standardizeName($model_object_name)] = 
				new FObj( $model_object_name, $this->modelData );
				
			// Build database tables
			$this->tables[self::standardizeTableName($model_object_name)] = 
				self::generateObjectTable($this->objects[self::standardizeName($model_object_name)]);
		}
		
		// Generate lookup tables required by inter-object relationships
		$this->generateLookupTables();
	}
	
	public function export($format = "yml") {
		return self::exportAsYML();
	}
	
	protected function exportAsYML() {
	    
	    $data = array('primary' => '');
	    
		foreach ($this->objects as $object) {
		    if (false != $object->getExtension()) {
		        $data[$object->getExtension()] .= self::exportObjectAsYML($object);
		    } else {
                $data['primary'] .= self::exportObjectAsYML($object);
		    } 
		}
		return $data;
	}
	
	protected function exportObjectAsYML($object) {
		// begin object definition
		$r .= "{$object->getName()}:\r\n";
		// handle non-default inheritance
		if ($object->getParentClass() != "FBaseObject") {
			$r .= "  extends: {$object->getParentClass()}\r\n";
		}
		// write extension
		if ($object->getExtension()) {
		    $r .= "  extension: {$object->getExtension()}\r\n";
		}
		// write description
		$r .= "  description: {$object->getDescription()}\r\n";
		// export attributes
		$r .= "  attributes:\r\n";
		foreach ($object->getAttributes() as $attr) {
			$r .= "    {$attr->getName()}:\r\n"
				. "      desc: {$attr->getDescription()}\r\n"
				. "      type: {$attr->getType()}\r\n"
				. "      size: {$attr->getSize()}\r\n"
				. "      min:  {$attr->getMin()}\r\n"
				. "      max:  {$attr->getMax()}\r\n"
				. "      default: {$attr->getDefaultValue()}\r\n"
				. "      validation:\r\n";
				foreach ($attr->getValidation() as $validationInstruction => $instructionParameters) {
					if ("allowedValues" == $validationInstruction) {
						$r .= "        allowedValues:\r\n";
						foreach ($instructionParameters as $av) {
							$r .= "          - value: {$av['value']}\r\n"
								. "            label: {$av['label']}\r\n";
						}
						continue;
					}
					$r .= "        {$validationInstruction}: { ";
					$parms = array();
					foreach ($instructionParameters as $k=>$v) {
						if ($v == null) { continue; }	// skip empty parameters
						if (true  === $v) {$v = 'y';}	// handle boolean true
						if (false === $v) {$v = 'n';}	// handle boolean false
						if ($k == "pattern") {			// patterns need to be in quotes
							$parms[] = "{$k}: \"{$v}\"";
						} else {
							$parms[] = "{$k}: {$v}";
						}
					}
					$r .= implode(' , ', $parms) . " }\r\n";
				}
					
				if ($attr->isUnique()) {
					$r .= "      unique: yes\r\n";
				}
		}
		// pretty id 
		$r .= "  prettyId: {$object->getPrettyId()}\r\n";
		
		// sort parents by object type
		$parentsByType = array();
		foreach ($object->getParents() as $parent) {
			$parentsByType[$parent->getForeign()][] = $parent;
		}
		// export parents
		$r .= "  parents:\r\n";
		foreach ($parentsByType as $objectName => $parents) {
			$r .= "    {$objectName}:\r\n";
			foreach ($parents as $parent) {
				$r .= "      {$parent->getName()}:\r\n";
				$r .= "        desc: {$parent->getDescription()}\r\n";
				if ($parent->doesReflect() ) {
					$r .= "        reflects: {$parent->getForeign()}.{$parent->getReflectVariable()}\r\n";
				}
				$r .= "        required: " . (($parent->isRequired()) ? "yes" : "no" ) . "\r\n";
			}
		}
		
		// sort peers by object type
		$peersByType = array();
		foreach ($object->getPeers() as $peer) {
			$peersByType[$peer->getForeign()][] = $peer;
		}
		// export peers
		$r .= "  peers:\r\n";
		foreach ($peersByType as $objectName => $peers) {
			$r .= "    {$objectName}:\r\n";
			foreach ($peers as $peer) {
				$r .= "      {$peer->getName()}:\r\n";
				$r .= "        desc: {$peer->getDescription()}\r\n";
				if ($peer->doesReflect()) {
					$r .= "        reflects: {$peer->getForeign()}.{$peer->getReflectVariable()}\r\n";
				}
			}
		}
		
		// sort children by object type
		$childrenByType = array();
		foreach ($object->getChildren() as $child) {
			$childrenByType[$child->getForeign()][] = $child;
		}
		// export children
		$r .= "  children:\r\n";
		foreach ($childrenByType as $objectName => $children) {
			$r .= "    {$objectName}:\r\n";
			foreach ($children as $child) {
				$r .= "      {$child->getName()}:\r\n";
				$r .= "        desc: {$child->getDescription()}\r\n";
				if ($child->doesReflect() ) {
					$r .= "        reflects: {$child->getForeign()}.{$child->getReflectVariable()}\r\n";
				}
			}
		}
		// add a space between objects
		$r .= "\r\n";	
		
		// return the complete yml model definition
		return $r;
	}
	
	public function compilePhp() {
		$compiled = "\r\n";
		$compiled .= $this->generateApplicationModelClass();
		$compiled .= "\r\n";
		foreach ($this->objects as $object) {
			$compiled .= self::generateObjectClass($object);
			$compiled .= self::generateObjectCollectionClass($object);
			$compiled .= self::generateObjectResultFormatterClass($object);
		}
		return $compiled;
	}
	
	public function compileSql() {
		$sql = '';
		foreach ($this->tables as $table) {
			$sql .= "--\r\n-- Table Definition for: {$table->getName()}\r\n--\r\n";
			$sql .= $table->toSqlString();
			$sql .= "\r\n\r\n";
		}
		return $sql;
	}
	
	public function generateUserPhpFile($objectName,$path) {
		$object =& $this->objects[self::standardizeName($objectName)];
		if (!$object) { die("Object {$objectName} not found in model."); }
		// Generate the file contents
		$contents = "<?php\r\n"  
			. self::generateObjectClassShell($object) 
			. self::generateObjectCollectionClassShell($object)
			. "\r\n?>";
		// Write the contents to the file
		file_put_contents($path,$contents);
	}
	
	private function generateApplicationModelClass() {
		$r .= "\tclass ApplicationModel {\r\n";
		foreach ($this->objects as $o) {
		    $r .= "\t\tpublic \${$o->getName()};\r\n";
		}
		$r .= "\t\tpublic function __construct() {\r\n";
	    foreach ($this->objects as $o) {
		    $r .= "\t\t\t\$this->{$o->getName()} = new {$o->getName()}Model();\r\n";
		}
        $r .= "\t\t}\r\n\r\n";
        
        $r .= "\t\tpublic function data(\$objectType) {\r\n"
		    	. "\t\t\tswitch (strtolower(\$objectType)) {\r\n";
		    foreach ($this->objects as $obj) {
		        $r .= "\t\t\t\tcase '".strtolower($obj->getName())."': "
		        	. "return new {$obj->getName()}Collection(); \r\n";
		    }
		    $r .= "\t\t\t\tdefault: throw new FException('Unknown model object ' . \$objectType);\r\n"
		    	. "\t\t\t\t\tbreak;\r\n"
		    	. "\t\t\t}\r\n"
		    	. "\t\t}\r\n\r\n";        
        
        $r .= "\t}\r\n\r\n";
		
		foreach ($this->objects as $o) {
		    $r .= "\tclass {$o->getName()}Model {\r\n"
		        . "\t\tpublic \$parentClass = \"{$o->getParentClass()}\";\r\n"
		        . "\t\t\r\n"
		        . "\t\tpublic function instance(\$data = array() ) {\r\n"
		        . "\t\t\t// return an instance of this object\r\n"
		        . "\t\t\treturn new {$o->getName()}(\$data);\r\n"
		        . "\t\t}\r\n\r\n"
		        . "\t\tpublic function get(/*vars*/) {\r\n"
		        . "\t\t\t// interface into the {$o->getName()}Collection class\r\n"
		        . "\t\t\t\$arg_list = func_get_args();\r\n"
		        . "\t\t\t\$c = new {$o->getName()}Collection();\r\n"
		        . "\t\t\treturn call_user_func_array(array(\$c,'get'),\$arg_list);\r\n"
		        . "\t\t}\r\n\r\n"
		        . "\t\tpublic function deleteInstance(\$id) {\r\n"
		        . "\t\t\treturn (is_object(\$id))\r\n"
		        . "\t\t\t\t? {$o->getName()}::Delete(\$id->getId())\r\n"
		        . "\t\t\t\t: {$o->getname()}::Delete(\$id);\r\n"
		        . "\t\t\t}\r\n\r\n"
		        . "\t\tpublic function validate(&\$object) {\r\n"
		        . "\t\t\t// validate a given {$o->getName()} object\r\n";
		        foreach ($o->getAttributes() as $attr) {
		            $validationLogic = FValidator::BuildValidationCodeForAttribute("\$object->get{$attr->getName()}()",$attr);
		            if (false !== $validationLogic) {
    		            $r .= "\t\t\ttry { // Validate '{$attr->getName()}'\r\n"
            				. "\t\t\t\t".$validationLogic
            				. "\t\t\t} catch (FValidationException \$fve) {\r\n"
            				. "\t\t\t\t\$object->validator->errors['{$attr->getName()}'] = \$fve->getMessage();\r\n"
            				. "\t\t\t\t\$object->validator->valid = false;\r\n"
            				. "\t\t\t}\r\n";
		            }
		        }
		    $r .= "\t\t\treturn \$object->validator->isValid();\r\n"
		    	. "\t\t}\r\n\r\n";
		    	
		    //TODO: pairs relationship info

		    foreach ($o->getParents() as $s) {
		        $r .= "\t\tpublic function get{$s->getName()}Info() {\r\n";
		        $r .= "\t\t\treturn " . $this->determineRelationshipInformation($s,'parent') . ";\r\n";
		        $r .= "\t\t}\r\n";
		    }
		    
		    foreach ($o->getPeers() as $s) {
		        $r .= "\t\tpublic function get{$s->getName()}Info() {\r\n";
		        $r .= "\t\t\treturn " . $this->determineRelationshipInformation($s,'peer') . ";\r\n";
		        $r .= "\t\t}\r\n";
		    }
		    	
		    foreach ($o->getChildren() as $s) {
		        $r .= "\t\tpublic function get{$s->getName()}Info() {\r\n";
		        $r .= "\t\t\treturn " . $this->determineRelationshipInformation($s,'child') . ";\r\n";
		        $r .= "\t\t}\r\n";
		    }
		    
		    $r .= $this->determineAttributeInformationFor($o);
		    
		    $r .= "\t\tpublic function parentsAsArray() {\r\n";
		    $r .= "\t\t\treturn array(";
		    if (count($o->getParents()) > 0) {
    		    $oParents = array();
    		    foreach ($o->getParents() as $s) {
    		        $oParents[] = "\"{$s->getName()}\" => array(\"name\"=>\"{$s->getName()}\",\"column\"=>\"".FModel::standardizeTableName($s->getName()).'_id'."\",\"foreign\"=>\"{$s->getForeign()}\",\"required\"=>\"{$s->getRequired()}\")";
    		    }
		        $r .=  implode(",",$oParents);
		    }
		    $r .= ");\r\n";
		    $r .= "\t\t}\r\n\r\n";
		    
		    
		    $r .= "\t}\r\n\r\n";
		    
		}
		
		return $r;
	}
	
	/**
	 * private function generateObjectValidatorClass(&$object) {
		$r .= "\r\n"
			. "\tclass F{$object->getName()}Validator extends FValidator {\r\n"
			. "\t\t\r\n"
			. "\t\t// Constructor\r\n"
			. "\t\tpublic function __construct() {\r\n"
			. "\t\t\tparent::__construct();\r\n"
			. "\t\t}\r\n\r\n"
			. "\t\t// isValid: tests the provided data for validity\r\n"
			. "\t\tpublic function isValid(\$data) {\r\n"
			. "\t\t\tforeach (\$data as \$k => \$v) {\r\n"
			. "\t\t\t\tswitch (\$k) {\r\n";
		if ("FAccount" == $object->getParentClass()) {
		    $r .= "\t\t\t\t\tcase 'username':     \$this->fAccountUsername(\$v); break;\r\n"
			    . "\t\t\t\t\tcase 'password':     \$this->fAccountPassword(\$v); break;\r\n"
			    . "\t\t\t\t\tcase 'emailAddress': \$this->fAccountEmailAddress(\$v); break;\r\n";
		}
		foreach ($object->getAttributes() as $attr) {
			if ($attr->getName() == "id") { continue; }
			$r .= "\t\t\t\t\tcase '{$attr->getName()}': \$this->{$attr->getName()}(\$v); break;\r\n";
		}
		$r .= "\t\t\t\t}\r\n"
			. "\t\t\t}\r\n"
			. "\t\t\treturn \$this->valid;\r\n"
			. "\t\t}\r\n\r\n";
		
		foreach ($object->getAttributes() as $attr) {
			$r .= "\t\tpublic function {$attr->getName()}(\$value) {\r\n"
				. "\t\t\ttry {\r\n"
				. "\t\t\t\t".FValidator::BuildValidationCodeForAttribute("\$value",$attr)
				. "\t\t\t\treturn true;\r\n"
				. "\t\t\t} catch (FValidationException \$fve) {\r\n"
				. "\t\t\t\t\$this->errors['{$attr->getName()}'] = \$fve->getMessage();\r\n"
				. "\t\t\t\t\$this->valid = false;\r\n"
				. "\t\t\t\treturn false;\r\n"
				. "\t\t\t}\r\n"
				. "\t\t}\r\n";
		}
			
		$r .= "\t} // end {$object->getName()}Validator\r\n\r\n";
		return $r;
	}
	 */
	
	private function generateObjectResultFormatterClass($object) {
	    $r .= "\tclass {$object->getName()}ResultFormatter extends FObjectResultFormatter {\r\n";
	    
	    $r .= "\t\tpublic function format(\$resultObject) {\r\n"
	    	. "\t\t\tif (! \$resultObject instanceof FResult) {\r\n"
	    	. "\t\t\t\tthrow new FResultFormatterException(\"input was not an FResult object\");\r\n"
	    	. "\t\t\t}\r\n"
	    	. "\t\t\tif (\$resultObject->status != FF_FRESULT_OK) { return false; }\r\n"
	    	. "\t\t\t\$objects = array();\r\n"
	    	. "\t\t\tforeach (\$resultObject->data as \$obj_data) {\r\n"
	    	. "\t\t\t\t\$objects[] = new {$object->getName()}(\$obj_data);\r\n"
	    	. "\t\t\t}\r\n"
	    	. "\t\t\treturn \$objects;\r\n"
	    	. "\t\t}\r\n";
	    $r .= "\t}\r\n\r\n";
	    
	    return $r;
	}
	
	/*
 	 * Function: generateObjectClass
 	 * 
 	 * This helper function generates the Php object class
 	 * for the given object.
 	 * 
 	 * Parameters:
 	 * 
 	 *  (FObj) - the <FObj> instance to use as a data source
 	 * 
 	 * Returns:
 	 *  
 	 *  (string) - The generated php class
 	 */
	private function generateObjectClassShell(&$object) {
		$r .= "\tclass {$object->getName()} extends F{$object->getName()} {\r\n";
		$r .= "\t\t// User extensions to the F{$object->getName()} class go here\r\n\r\n";
		$r .= "\t}\r\n\r\n";
		return $r;
	}
	private function generateObjectClass(&$object) {
		$r .= "\tclass F{$object->getName()} extends {$object->getParentClass()} {\r\n\r\n";
		
		$r .= "\t\t//Description: {$object->getDescription}\r\n\r\n";
		
		// add inherited attributes
		if ("FAccount" == $object->getParentClass()) {
			$r .= "\t\t/**\r\n"
				. "\t\t * INHERITED ATTRIBUTES (from FAccount)\r\n"
				. "\t\t * \r\n"
				. "\t\t *  - username \r\n"
				. "\t\t *  - password \r\n"
				. "\t\t *  - emailAddress \r\n"
				. "\t\t *  - created \r\n"
				. "\t\t *  - modified \r\n"
				. "\t\t *  - lastLogin \r\n"
				. "\t\t */\r\n\r\n";
		}
		// add attributes
		foreach ($object->getAttributes() as $a) {
 			$r .= "\t\t// Variable: {$a->getName()}\r\n";
 			$r .= "\t\t// {$a->getDescription()}\r\n"
				. "\t\t{$a->getVisibility()} \${$a->getName()};\r\n\r\n";	
 		}
 		
		// add parents
 		foreach ($object->getParents() as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// add peers
 		foreach ($object->getPeers() as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// add children
 		foreach ($object->getChildren() as $s) {
 			$r .= "\t\t// Variable: {$s->getName()}\r\n"
 				. "\t\t// [[{$s->getForeign()}". (($s->getQuantity() == "M") ? "Collection":"")."]] {$s->getDescription()}\r\n"
 				. "\t\t{$s->getVisibility()} \${$s->getName()};\r\n\r\n";	
 		}
 		
 		// add static (defined) variables
 		foreach ($object->getAttributes() as $a) {
 		    $vc = $a->getValidation();
 		    if (isset($vc['allowedValues'])) {
 		        $aupper = strtoupper($a->getName());
 		        $r .= "\t\t//Allowed values for '{$a->getName()}': \r\n";
 		        foreach ($vc['allowedValues'] as $d) {
 		            $dupper = strtoupper(str_replace(array(' ',"'"),'',$d['label']));
 		            $dupper = str_replace("-","_",$dupper);
 		            $r .= "\t\tpublic static \${$aupper}_{$dupper} = \"{$d['value']}\";\r\n";
 		        }
 		        $r .= "\r\n";
 		    }
 		}
 		
 		// constructor
		$r .= "\t\tpublic function __construct(\$data=array()) {\r\n";	
		$r .= "\t\t\t\$this->id          = isset(\$data['".FModel::standardizeTableName($object->getName())."_id']) ? \$data['".FModel::standardizeTableName($object->getName())."_id'] : 0;\r\n";
		$r .= "\t\t\t\$this->validator   = new FValidator();\r\n";
		$r .= "\t\t\t\$this->_dirtyTable = array();\r\n\r\n";

		
		
		if ("FAccount" == $object->getParentClass()) {			
			$r .= "\t\t\t// Initialize required inherited attributes:\r\n"
				. "\t\t\t\$this->username     = \$data['username'];\r\n"
				. "\t\t\t\$this->password     = \$data['password'];\r\n"
				. "\t\t\t\$this->emailAddress = \$data['emailAddress'];\r\n"
				. "\t\t\tif (\$this->id > 0) {\r\n"
				. "\t\t\t\t// This object exists in the database and should have FAccount data:\r\n"
				. "\t\t\t\tif (!isset(\$data['faccount_id'])) {die('insufficient information provided to create {$object->getName()} object');}\r\n"
				. "\t\t\t\tparent::__construct(\$data);\r\n"
				. "\t\t\t}\r\n";
		}
		
		$r .= "\t\t\t\$this->fObjectType      =  '{$object->getName()}';\r\n";
		$r .= "\t\t\t\$this->fObjectTableName =  '".FModel::standardizeTableName($object->getName())."';\r\n";
		$r .= "\t\t\t\$this->fObjectModelData =& \$GLOBALS['fApplicationModel']->{$object->getName()};\r\n";
		

 		$r .= "\r\n\t\t\t// Initialize local attributes...\r\n";
	    foreach ($object->getAttributes() as $a) {
 			$r .= "\t\t\t\$this->{$a->getName()} = isset(\$data['{$a->getName()}']) ? \$data['{$a->getName()}'] : \"{$a->getDefaultValue()}\";\r\n";
 		}
 		$r .= "\r\n\t\t\t// Initialize parent ids...\r\n";
	    foreach ($object->getParents() as $s) {
			$r .= "\t\t\t\$this->{$s->getName()} = isset(\$data['{$s->getName()}_id']) ? \$data['{$s->getName()}_id'] : 0;\r\n";
 		}

 		$r .= "\t\t} // end constructor\r\n\r\n";
 		
 		$r .= "\t\tpublic static function _getObjectClassName() {\r\n"
 			. "\t\t\treturn \"{$object->getName()}\";\r\n"
 			. "\t\t}\r\n";
 			
 			
 	    /**
 	     * THE GET FUNCTION
 	     */
 		$r .= "\t\tpublic function get( \$attribute, \$bIdOnly = false ) {\r\n";
 		$r .= "\t\t\tswitch ( strtolower(\$attribute) ) {\r\n";
 		
 		// Local attributes
	    foreach ($object->getAttributes() as $a) {
	 		$r .= "\t\t\t\tcase '".strtolower($a->getName())."':\r\n";
 			if ($a->getType() == "string" || $a->getType() == "text") {
 				$r .= "\t\t\t\t\treturn stripslashes(\$this->{$a->getName()});\r\n";
 			} else {
 				$r .= "\t\t\t\t\treturn \$this->{$a->getName()};\r\n";
 			}
 		}
 		
 		// Parents
	    foreach ($object->getParents() as $s) {
			$r .= "\t\t\t\tcase '".strtolower($s->getName())."': \r\n"
				. "\t\t\t\t\tif (is_object(\$this->{$s->getName()}) ) {\r\n"
				. "\t\t\t\t\t\treturn (\$bIdOnly) ? \$this->{$s->getName()}->getId() : \$this->{$s->getName()};\r\n"
				. "\t\t\t\t\t} else {\r\n"
				. "\t\t\t\t\t\tif (\$bIdOnly) {\r\n"
				. "\t\t\t\t\t\t\treturn \$this->{$s->getName()};\r\n"
				. "\t\t\t\t\t\t} else {\r\n"
				. "\t\t\t\t\t\t\t\$c = new {$s->getForeign()}Collection();\r\n"
				. "\t\t\t\t\t\t\t\$this->{$s->getName()} = \$c->unique(\$this->{$s->getName()});\r\n"
				. "\t\t\t\t\t\t\treturn \$this->{$s->getName()};\r\n"
				. "\t\t\t\t\t\t}\r\n"
				. "\t\t\t\t\t}\r\n";
	    }
	    
	    // Peers
	    foreach ($object->getPeers() as $s) {
			$r .= "\t\t\t\tcase '".strtolower($s->getName())."':\r\n"
			    . "\t\t\t\t\t\$c = new {$s->getForeign()}Collection();\r\n"
			    . "\t\t\t\t\treturn \$c->filter('{$s->getReflectVariable()}',\$this->id);\r\n";
		}
		
		// Children
	    foreach ($object->getChildren() as $s) {
 			$r .= "\t\t\t\tcase '".strtolower($s->getName())."':\r\n"
 			    . "\t\t\t\t\t\$c = new {$s->getForeign()}Collection();\r\n"
 			    . "\t\t\t\t\treturn \$c->filter('{$s->getReflectVariable()}',\$this->id);\r\n";
 		} 
 		
 		
 		
 		$r .= "\t\t\t\tdefault: throw new FObjectException('No such attribute');\r\n"
 			. "\t\t\t}\r\n";
 		$r .= "\t\t}\r\n";	
 		/**
 		 * END GET FUNCTION 
 		 */
 		
 		// Deprecated Getters
		foreach ($object->getAttributes() as $a) {
	 		$r .= "\t\tpublic function get{$a->getFunctionName()}() {\r\n";
 			$r .= "\t\t\treturn \$this->get('{$a->getName()}');\r\n";
 			$r .= "\t\t}\r\n\r\n";
 		}
		foreach ($object->getParents() as $s) {
			$r .= "\t\tpublic function get{$s->getFunctionName()}(\$bIdOnly = false) {\r\n";
			$r .= "\t\t\treturn \$this->get('{$s->getName()}',\$bIdOnly);\r\n";
			$r .= "\t\t}\r\n\r\n";	
 		}
 		foreach ($object->getPeers() as $s) {
			$r .= "\t\tpublic function get{$s->getFunctionName()}() {\r\n";
			$r .= "\t\t\treturn \$this->get('{$s->getName()}');\r\n";
		    $r .= "\t\t}\r\n\r\n";
 		}
 		foreach ($object->getChildren() as $s) {
 			$r .= "\t\tpublic function get{$s->getFunctionName()}() {\r\n";
 			$r .= "\t\t\treturn \$this->get('{$s->getName()}');\r\n";
			$r .= "\t\t}\r\n\r\n";
 		}
 		
 		
 		// Transitive Getters
 		$r .= $this->generateTransitiveRelationships($object);
 		
 		
 		// Setters
		foreach ($object->getAttributes() as $a) {
			$r .= "\t\tpublic function set{$a->getFunctionName()}(\$value) {\r\n"
				. "\t\t\t\$this->{$a->getName()} = \$value;\r\n"
				. "\t\t\t\$this->_dirtyTable['{$a->getName()}'] = \$value;\r\n"
 				. "\t\t}\r\n\r\n";	
		}
		// setters to allow for 'parent' reassignment
		foreach ($object->getParents() as $s) {
			$r .= "\t\tpublic function set{$s->getFunctionName()}(\$value) {\r\n";
			$r .= "\t\t\t\$rawValue = (is_object(\$value)) ? \$value->getId() : \$value;\r\n";
			if ($s->isRequired()) {
				// Verify that the value being set is not '0' for required parent attributes
				$r .= "\t\t\tFValidator::Numericality(\$rawValue,0,null,null,null,true,'{$s->getName()}_id');\r\n";
			}			
			$r .= "\t\t\t\$this->{$s->getName()} = is_object(\$value) ? \$value : \$rawValue;\r\n"
				. "\t\t\t\$this->_dirtyTable['{$s->getName()}'] = \$rawValue;\r\n"
				. "\t\t}\r\n\r\n";
		}
		// adders and removers (for peer relationships)
		foreach ($object->getPeers() as $s) {
			$r .= "\t\tpublic function add{$s->getFunctionName()}(\$ids) {\r\n"
				. "\t\t\t// Only perform this action if the object has a valid id...\r\n"
				. "\t\t\tif (\$this->id == 0) { return false; }\r\n\r\n";
			$owner   = FModel::standardizeAttributeName($s->getOwner());
			$foreign = FModel::standardizeAttributeName($s->getForeign());
			if ($s->getOwner() == $s->getForeign()) {
				$r .= "\t\t\t\$q = \"INSERT INTO `{$s->getLookupTable()}` (`{$owner}1_id`,`{$owner}2_id`) VALUES \";\r\n";
			} else {
				$r .= "\t\t\t\$q = \"INSERT INTO `{$s->getLookupTable()}` (`{$owner}_id`,`{$foreign}_id`) VALUES  \";\r\n";
			}
			$r .= "\t\t\tif (is_array(\$ids)) {\r\n"
				. "\t\t\t\t\$subq = array();\r\n"
				. "\t\t\t\tforeach (\$ids as \$id) { \$subq[] = \"({\$this->getId()},{\$id})\";}\r\n"
				. "\t\t\t\t\$q .= implode(\",\",\$subq);\r\n"
				. "\t\t\t} else {\r\n"
				. "\t\t\t\t\$q .= \"({\$this->getId()},{\$ids})\";\r\n"
				. "\t\t\t}\r\n"
				. "\t\t\t_db()->exec(\$q);\r\n"
				. "\t\t}\r\n"
				. "\t\t\r\n"
				. "\t\tpublic function remove{$s->getFunctionName()}(\$ids) {\r\n"
				. "\t\t\t// Only perform this action if the object has a valid id...\r\n"
				. "\t\t\tif (\$this->id ==0) { return false; }\r\n\r\n";
			if ($s->getOwner() == $s->getForeign()) {
				$r .= "\t\t\t\$q = \"DELETE FROM `{$s->getLookupTable()}` WHERE ((`{$owner}1_id`={\$this->getId()} AND `{$owner}2_id` IN (\".implode(',',\$ids).\")) OR (`{$owner}2_id`={\$this->getId()} AND `{$owner}1_id` IN (\".implode(',',\$ids).\"))  \";\r\n";
			} else {
				$r .= "\t\t\t\$q = \"DELETE FROM `{$s->getLookupTable()}` WHERE `{$owner}_id`={\$this->getId()} AND `{$foreign}_id` IN (\".implode(',',\$ids).\") \";\r\n";
			}
			$r .= "\t\t\t_db()->exec(\$q);\r\n"
				. "\t\t}\r\n\r\n";
			
		}
		
		// DECODER
		// Decode (map) allowed values for attributes
 		foreach ($object->getAttributes() as $a) {
 		    $vc = $a->getValidation();
 		    if (isset($vc['allowedValues'])) {
 		        $r .= "\t\tpublic static function Decode".self::standardizeName($a->getName())."(\$val) {\r\n";
 		        $r .= "\t\t\tswitch (\$val) {\r\n";
 		        $aupper = strtoupper($a->getName());
 		        $r .= "\t\t\t\t//Allowed values for '{$a->getName()}': \r\n";
 		        foreach ($vc['allowedValues'] as $d) {
 		            $dupper = strtoupper(str_replace(array(' ',"'"),'',$d['label']));
 		            $dupper = str_replace("-","_",$dupper);
 		            $r .= "\t\t\t\tcase self::\${$aupper}_{$dupper}:  return \"{$d['label']}\";\r\n";
 		        }
 		        $r .= "\t\t\t\tdefault: return 'Unknown';\r\n"
 		        	. "\t\t\t}\r\n"
 		        	. "\t\t}\r\n";
 		        $r .= "\r\n";
 		    }
 		}
		
		
		// LOADER
		//TODO: pairs loader
	    foreach ($object->getParents() as $s) {
	        $name = self::standardizeName($s->getName());
		    $r .= "\t\tpublic function load{$name}(\$object){\r\n"
		        . "\t\t\t\$this->{$s->getName()} = \$object;\r\n"
		        . "\t\t}\r\n\r\n";
		}
	    foreach ($object->getPeers() as $s) {
	        $name = self::standardizeName($s->getName());
		    $r .= "\t\tpublic function load{$name}(\$objects){\r\n"
		        . "\t\t\tif (!is_array(\$objects)) { \$objects = array(\$objects); }\r\n"
		        . "\t\t\t\$this->{$s->getName()} = \$objects;\r\n"
		        . "\t\t}\r\n\r\n";
		}
		foreach ($object->getChildren() as $s) {
		    $name = self::standardizeName($s->getName());
		    $r .= "\t\tpublic function load{$name}(\$objects){\r\n"
		        . "\t\t\tif (is_array(\$objects)) { \$this->{$s->getName()}->data = array_merge(\$this->{$s->getName()}->data, \$objects); }\r\n"
		        . "\t\t\telse { \$this->{$s->getName()}->data[] = \$objects; }\r\n"
		        . "\t\t}\r\n\r\n";
		}
		
		// Save
 		$r .= "\t\tpublic function save( \$data = array(), \$bValidate = true ) {\r\n";
 		
 		$r .= "\t\t\treturn parent::save(\$data,\$bValidate); \r\n";
		$r .= "\t\t}\r\n\r\n";
 		

 		/*
 		// Retrieve
 		$r .= "\t\tpublic static function Retrieve() {\r\n";
		$r .= "\t\t\t\$collection = new {$object->getName()}Collection();\r\n\r\n"
		    . "\t\t\t\$num_args = func_num_args();\r\n"
			. "\t\t\tswitch (\$num_args) {\r\n"
			. "\t\t\t\tcase 0: \r\n"
			. "\t\t\t\t\treturn \$collection;\r\n"
			. "\t\t\t\tcase 1: \r\n"
			. "\t\t\t\t\t\$v = func_get_arg(0);\r\n"
			. "\t\t\t\t\treturn \$collection->get(\$v);\r\n"
			. "\t\t\t\tcase 2: \r\n"
			. "\t\t\t\t\t\$k = func_get_arg(0);\r\n"
			. "\t\t\t\t\t\$v = func_get_arg(1);\r\n"
			. "\t\t\t\t\treturn \$collection->get(\$k,\$v);\r\n"
			. "\t\t\t\tdefault: \r\n"
			. "\t\t\t\t\tthrow new FException(\"Unexpected number of arguments for {$object->getName()}::Retrieve()\");\r\n"
			. "\t\t\t\t\treturn false;\r\n"
			. "\t\t\t}\r\n"
			. "\t\t}\r\n\r\n";
		*/
 
 		
 		// Delete
 		$r .= "\t\tpublic function destroy() {\r\n"
 			. "\t\t\tself::Delete(\$this->id);\r\n"
 			. "\t\t}\r\n\r\n"; 
		$r .= "\t\tpublic static function Delete(\$id) {\r\n";

		if ("FAccount" == $object->getParentClass()) {
			$r .= "\t\t\t// Delete the FAccount data associated with this object\r\n";
			$r .= "\t\t\t\$q = \"SELECT `faccount_id` FROM `".self::standardizeTableName($object->getName())."` WHERE `".self::standardizeTableName($object->getName())."`.`".self::standardizeTableName($object->getName())."_id` = '{\$id}'\"; \r\n";
			$r .= "\t\t\t\$acct_id = _db()->queryOne(\$q);\r\n";
			$r .= "\t\t\t\$q = \"DELETE FROM `app_accounts` WHERE `faccount_id`= \$acct_id\";\r\n";
			$r .= "\t\t\t\$q2= \"DELETE FROM `app_roles`    WHERE `faccount_id`= \$acct_id\";\r\n";
			$r .= "\t\t\t_db()->exec(\$q);\r\n";
			$r .= "\t\t\t_db()->exec(\$q2);\r\n\r\n";
		} else {
			$deleteInfo = $this->determineDeleteInformationFor($object);
			foreach ($deleteInfo as $info) {
			
    			// Delete objects that have a required parent dependency on this object 
    			// (aka: delete children of this object for whom this object is a required parent)
    			if ($info['type'] == 'parent' && $info['required']) {
    			    $r .= "\t\t\t// Delete '{$info['class']}' objects that have a required parent dependency on this object\r\n";
    			    $r .= "\t\t\t\$q = \"SELECT `{$info['sqltable']}`.`{$info['sqltable']}_id` FROM `{$info['sqltable']}` WHERE "
 						. "`{$info['sqltable']}`.`{$info['sqlcol']}`={\$id} \";\r\n"
 						. "\t\t\t\$r = _db()->query(\$q);\r\n"
 						. "\t\t\twhile (\$data = \$r->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {\r\n"
 						. "\t\t\t\t{$info['class']}::Delete(\$data['{$info['sqltable']}_id']);\r\n"
 						. "\t\t\t}\r\n";
    			}
    			// Clear references to this object in objects that have a non-required parent dependency
    			else if ($info['type'] == 'parent') {
    			    $r .= "\t\t\t// Clear '{$info['class']}' references to this object for objects with a non-required parent relationship to this object\r\n";
 					$r .= "\t\t\t\$q = \"UPDATE `{$info['sqltable']}` SET `{$info['sqltable']}`.`{$info['sqlcol']}`='0' WHERE "
 						. "`{$info['sqltable']}`.`{$info['sqlcol']}`={\$id} \";\r\n"
 						. "\t\t\t_db()->exec(\$q);\r\n";
    			}
    			// Clear entries in all lookup tables with references to this object
			    else if ($info['type'] == 'lookup') {
			        $r .= "\t\t\t// Clear '{$info['class']}' entries in all lookup tables with references to this object\r\n";
 					if ($object->getName() == $info['class']) {
 						$r .= "\t\t\t\$q = \"DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{\$id}' OR `{$info['sqlcol2']}`='{\$id}'\";\r\n";
 					} else {
 						$r .= "\t\t\t\$q = \"DELETE FROM `{$info['sqltable']}` WHERE `{$info['sqltable']}`.`{$info['sqlcol']}`='{\$id}'\";\r\n";
 					}
 					$r .= "\t\t\t_db()->exec(\$q);\r\n";
			        
			    } else {
			        die("Non-standard delete information provided for object {$object->getName()}");
			    }
			}
			
			// Delete the object itself
			$r .= "\t\t\t// Delete the object itself\r\n"
				. "\t\t\t\$q = \"DELETE FROM `".self::standardizeTableName($object->getName())."` WHERE `".self::standardizeTableName($object->getName())."`.`".self::standardizeTableName($object->getName())."_id`='{\$id}'\";\r\n"
 				. "\t\t\t_db()->exec(\$q);\r\n"
 				. "\t\t\treturn true;\r\n";
		}
		$r .= "\t\t}\r\n\r\n";
		
		// Add toXML
 		
 		// Add toYML
 		
 		// Add toJSON
 		$r .= "\t\tpublic function toJSON() {\r\n"
 			. "\t\t\t\$data = array('id' => \$this->id);\r\n";
 		if ("FAccount" == $object->getParentClass()) {
 		    $r .= "\t\t\t\$data['username'] = \$this->username;\r\n";
 		    $r .= "\t\t\t\$data['emailAddress'] = \$this->emailAddress;\r\n";
 		}
 		foreach ($object->getAttributes() as $attr) {
 		    $r .= "\t\t\t\$data['{$attr->getName()}'] = \$this->{$attr->getName()};\r\n";
 		}
 		$r .= "\t\t\treturn json_encode(\$data);\r\n"
 			. "\t\t}\r\n\r\n";

 		
		$r .="\t} // end {$object->getName()}\r\n\r\n";
		// return the class string
		return $r;
	}
	
	private function generateObjectCollectionClassShell(&$object) {
		$r .= "\tclass {$object->getName()}Collection extends F{$object->getName()}Collection {\r\n";
		$r .= "\t\t// User extensions to the F{$object->getName()}Collection class go here\r\n\r\n";
		$r .= "\t}\r\n\r\n";
		return $r;
	}
	private function generateObjectCollectionClass(&$object) {
		if ("FAccount" == $object->getParentClass()) {
			$r = "\tclass F{$object->getName()}Collection extends FAccountCollection {\r\n";
		} else {
			$r = "\tclass F{$object->getName()}Collection extends FObjectCollection {\r\n";
		}
 		
 		// Add Constructor
 		$r .= "\t\tpublic function __construct(\$lookupTable=\""
 				.self::standardizeTableName($object->getName())."\","
 				."\$filter='') {\r\n"
 			. "\t\t\tparent::__construct(\"".self::standardizeName($object->getName())."\",\$lookupTable,\$filter);\r\n"; 		
 		$r .= "\t\t}\r\n";
 		
 		// Add DestroyObject
 		$r .= "\t\tpublic function destroyObject(\$objectId) {\r\n"
 			. "\t\t\t//TODO: Implement this\r\n"
 			. "\t\t}\r\n\r\n";
 			
 			
 		
 		$r .= "\t} // end {$object->getName()}Collection\r\n\r\n";
 		// return the class string
 		return $r;
	}
	
	private function generateObjectValidatorClassShell(&$object) {
		$r .= "\tclass {$object->getName()}Validator extends F{$object->getName()}Validator {\r\n";
		$r .= "\t\t// User extensions to the F{$object->getName()}Validator class go here\r\n\r\n";
		$r .= "\t}\r\n\r\n";
		return $r;
	}
	private function generateObjectValidatorClass(&$object) {
		$r .= "\r\n"
			. "\tclass F{$object->getName()}Validator extends FValidator {\r\n"
			. "\t\t\r\n"
			. "\t\t// Constructor\r\n"
			. "\t\tpublic function __construct() {\r\n"
			. "\t\t\tparent::__construct();\r\n"
			. "\t\t}\r\n\r\n"
			. "\t\t// isValid: tests the provided data for validity\r\n"
			. "\t\tpublic function isValid(\$data) {\r\n"
			. "\t\t\tforeach (\$data as \$k => \$v) {\r\n"
			. "\t\t\t\tswitch (\$k) {\r\n";
		if ("FAccount" == $object->getParentClass()) {
		    $r .= "\t\t\t\t\tcase 'username':     \$this->fAccountUsername(\$v); break;\r\n"
			    . "\t\t\t\t\tcase 'password':     \$this->fAccountPassword(\$v); break;\r\n"
			    . "\t\t\t\t\tcase 'emailAddress': \$this->fAccountEmailAddress(\$v); break;\r\n";
		}
		foreach ($object->getAttributes() as $attr) {
			if ($attr->getName() == "id") { continue; }
			$r .= "\t\t\t\t\tcase '{$attr->getName()}': \$this->{$attr->getName()}(\$v); break;\r\n";
		}
		$r .= "\t\t\t\t}\r\n"
			. "\t\t\t}\r\n"
			. "\t\t\treturn \$this->valid;\r\n"
			. "\t\t}\r\n\r\n";
		
		foreach ($object->getAttributes() as $attr) {
			$r .= "\t\tpublic function {$attr->getName()}(\$value) {\r\n"
				. "\t\t\ttry {\r\n"
				. "\t\t\t\t".FValidator::BuildValidationCodeForAttribute("\$value",$attr)
				. "\t\t\t\treturn true;\r\n"
				. "\t\t\t} catch (FValidationException \$fve) {\r\n"
				. "\t\t\t\t\$this->errors['{$attr->getName()}'] = \$fve->getMessage();\r\n"
				. "\t\t\t\t\$this->valid = false;\r\n"
				. "\t\t\t\treturn false;\r\n"
				. "\t\t\t}\r\n"
				. "\t\t}\r\n";
		}
			
		$r .= "\t} // end {$object->getName()}Validator\r\n\r\n";
		return $r;
	}
	
	
	private function determineRelationshipInformation($socket_l,$type) {

	    $socket_f = $this->getRemoteSocketFor($socket_l);
	    
	    $object_l = $socket_l->getOwner();
	    $object_f = $socket_l->getForeign();
	    $base_l   = $this->objects[$socket_l->getOwner()]->getParentClass();
	    $base_f   = $this->objects[$socket_l->getFOreign()]->getParentClass();
	    $table_m  = '';    // Lookup table (for M:M only)
	    
	    switch ($type) {
	        case 'peer'   : $role_l = 'MM'; $role_f = 'MM'; break;
	        case 'parent' : $role_l = 'M1'; $role_f = '1M'; break;
	        case 'child'  : $role_l = '1M'; $role_f = 'M1'; break;
	        default:
	            die("type '{$type}' not implemented -- FModel::determineRelationshipInformation");
	    }
	    
	    $db_l  = 'default';
	    $db_f  = 'default';
	    
	    switch ($role_l) {
	        case "1M":    // CHILD
	            $table_l  = FModel::standardizeTableName($socket_f->getOwner());
	            $table_f  = FModel::standardizeTableName($socket_l->getOwner());
	            $key_l    = $socket_f->getName();
	            $key_f    = 'id';
	            $column_l = FModel::standardizeTableName($socket_f->getName()).'_id';
	            $column_f = FModel::standardizeTableName($socket_l->getName()).'_id';
	            break;
	        case "M1":    // PARENT
	            $table_l  = FModel::standardizeTableName($socket_f->getOwner());
	            $table_f  = FModel::standardizeTableName($socket_l->getOwner());
	            $key_l    = 'id';
	            $key_f    = ($object_l == $object_f) ? $socket_l->getName() : $socket_f->getName();
	            $column_l = FModel::standardizeTableName($socket_f->getOwner()).'_id';
	            $column_f = FModel::standardizeTableName($socket_l->getName()).'_id';
	            break;
	        case "MM":    // PEER
	            $table_l  = FModel::standardizeTableName($socket_f->getOwner());
	            $table_f  = FModel::standardizeTableName($socket_l->getOwner());
	            $table_m  = $socket_f->getLookupTable();
	            
	            if ($socket_l->getOwner() == $socket_f->getOwner()) {
	                // sort names to determine who is _1 and who is _2
	                $key_l    = '';
	                $key_f    = '';
	                $column_l = '';
	                $column_f = '';
	            } else {
	                $key_l    = 'id';
	                $key_f    = 'id';
	                $column_l = FModel::standardizeTableName($socket_f->getOwner()).'_id';
	                $column_f = FModel::standardizeTableName($socket_l->getOwner()).'_id';
	            }
	            break;
	        default:
	            die("role {$role_l} not implemented yet --FModel::determineRelationshipInformation");
	    }
	    
	    return "array('object_l'=>'{$object_l}','base_l'=>'{$base_l}','object_f'=>'{$object_f}','base_f'=>'{$base_f}','role_l'=>'{$role_l}','role_f'=>'{$role_f}','db_l'=>'{$db_l}','db_f'=>'{$db_f}','table_l'=>'{$table_l}','table_f'=>'{$table_f}','table_m'=>'{$table_m}','key_l'=>'{$key_l}','key_f'=>'{$key_f}','column_l'=>'{$column_l}','column_f'=>'{$column_f}')";
	}
	
	
    private function determineAttributeInformationFor($object) {
        // Generate a string representation of an array of attribute information that can
        // be used by [input: ] tags and others who need model information about an attribute
        $r .= "\t\tpublic function attributeInfo(\$name = '') {\r\n";
        $r .= "\t\t\t\$attrInfos = array(\r\n";
        if ("FAccount" == $object->getParentClass()) {
 			$r .= "\t\t\t\t'username'     => array('type'=>'string','size'=>20,'column'=>'username','name'=>'username'),\r\n"
 				. "\t\t\t\t'password'     => array('type'=>'password','size'=>20,'column'=>'password','name'=>'password'),\r\n"
 				. "\t\t\t\t'emailAddress' => array('type'=>'string','size'=>80,'column'=>'emailAddress','name'=>'emailAddress'),\r\n\r\n";
 		}
        foreach ($object->getAttributes() as $attr) {
            $components = array();
            // Provide basic information
			switch ($attr->getType()) {
				case "text":
					$components[]   = "'type'=>'text'";
					break;
				case "integer":
					$components[]   = "'type'=>'integer'";
					break;
				case "string":
					$components[]   = "'type'=>'string'";
					$components[]   = "'size'=>{$attr->getSize()}"; 
					break;
				case "date":
					$components[]	= "'type'=>'date'";
					break;
				case "datetime":
					$components[]	= "'type'=>'datetime'";
					break;
				default:
					break;
            }
            // If the attribute has a list of allowed values, add them here
			$validationData = $attr->getValidation();
			if (isset($validationData['allowedValues'])) {
				$valueData = $validationData['allowedValues'];
				$values = array();
				foreach ($valueData as $vd) {
					$values[] = "array('value'=>\"".addslashes($vd['value'])."\",'label'=>\"".addslashes($vd['label'])."\")";
				}
				$components[] = "'allowedValues'=>array(".implode(",",$values).")";
			}
			// Set the column name here
			$components[] = "'column'=>'{$attr->getName()}'";
			// Set the attribute name here
			$components[] = "'name'=>'{$attr->getName()}'";
			
			$r .= "\t\t\t\t'{$attr->getName()}' => array(".implode(',',$components)."),\r\n";
        }
        $r .= "\t\t\t);\r\n";
        $r .= "\t\t\tif(\$name != '') {\r\n"
        	. "\t\t\t\treturn ((isset(\$attrInfos[\$name]))\r\n"
        	. "\t\t\t\t\t? \$attrInfos[\$name]\r\n"
        	. "\t\t\t\t\t: false);\r\n"
        	. "\t\t\t} else { \r\n"
        	. "\t\t\t\treturn \$attrInfos;\r\n"
        	. "\t\t\t}\r\n"
        	. "\t\t}\r\n\r\n";

		return $r;    
	}
	
	private function generateTransitiveRelationships(&$object) {
	    // Transitive relationships exist between any two objects
	    // which are indirectly linked to one another via a third object.
	    // For the time being, the transitive relationships will be 
	    // associated from parent --> child in one of three situations:
	    // 
	    // 1) "Parent of my child"
	    //
	    // 2) "Child of my child"
	    //
	    // 3) "Peer of my child"
	    //
	    //
	    $r = "\t\tpublic function transitiveGet(\$relation) {\r\n"
	    	."\t\t\tswitch (strtolower(\$relation)) {\r\n";
	    
        // Examine the relationships of this object's children
        // to discover any transitive relationships
        foreach ($object->getChildren() as $child) {
            // All relationships (parent, peer, child) that this
            // child object has will have a transitive relationship with
            // this object's parent. This transitive relationship will be one
            // of the three types identified above, depending on the
            // type of relationship (parent,peer,child) between this
            // child object and its foreign endpoint.
            foreach ($this->objects[$child->getForeign()]->getParents() as $childParent) {
                // Type (1) transitive relationship between $object and $childParent
                if ($childParent->getName() != $child->getReflectVariable()) {
                    $r .= "\t\t\t\tcase '".strtolower($child->getName().'.'.$childParent->getName())."':\r\n";
                    $r .= $this->createType1TransitiveRelationship($object,$child,$childParent);
                    $r .= "\t\t\t\t\tbreak;\r\n";
                }
            }
            
            foreach ($this->objects[$child->getForeign()]->getChildren() as $childChild) {
                // Type (2) transitive relationship between $object and $childChild
                $r .= "\t\t\t\tcase '".strtolower($child->getName().'.'.$childChild->getName())."':\r\n";
                $r .= $this->createType2TransitiveRelationship($object,$child,$childChild);
                $r .= "\t\t\t\t\tbreak;\r\n";
            }
            
            foreach ($this->objects[$child->getForeign()]->getPeers() as $childPeer) {
                // Type (3) transitive relationship between $object and $childPeer
                $r .= "\t\t\t\tcase '".strtolower($child->getName().'.'.$childPeer->getName())."':\r\n";
                $r .= $this->createType3TransitiveRelationship($object,$child,$childPeer);
                $r .= "\t\t\t\t\tbreak;\r\n";
            }
        }
        
        $r .= "\t\t\t\tdefault:\r\n"
        	. "\t\t\t\t\treturn false;\r\n";
        $r .= "\t\t\t}\r\n";
        $r .= "\t\t}\r\n\r\n";
        return $r;
	}

	
	private function createType1TransitiveRelationship($parentObject,$intermediarySocket,$childSocket) {
	    // Create an FObjectCollection representing the objects in this transitive relation
	    $ot = self::standardizeTableName($parentObject->getName());
	    $it = self::standardizeTableName($intermediarySocket->getForeign());
	    $ct = self::standardizeTableName($childSocket->getForeign());
	    $in = $intermediarySocket->getReflectVariable();
	    $cn = $childSocket->getName();
	    return "\t\t\t\t\treturn new {$childSocket->getForeign()}Collection(array('{$ct}','{$it}','{$ot}'),"
	         . "\"`{$ot}`.`{$ot}_id` = \$this->id AND "
	         . "  `{$it}`.`{$cn}_id` = `{$ct}`.`{$ct}_id` AND "
	         . "  `{$it}`.`{$in}_id` = `{$ot}`.`{$ot}_id` \");\r\n";


	    /* "get the parents of my children"
	     * "get all members who have left comments on this blog entry
	     * be = obj
	     * bec=intermediate
	     * mem=child
	    SELECT * FROM `blogEntryComment`,`blogEntry`,`member` WHERE 
		`blogEntryComment`.`entry_id` = `blogEntry`.`objId` AND
		`blogEntryComment`.`author_id`= `member`.`objId`    AND
		`blogEntry`.`objId`=25
		*/
	}
	
	private function createType2TransitiveRelationship($parentObject,$intermediarySocket,$childSocket) {
	    // Create an FObjectCollection representing the objects in this transitive relation
	    $ot = self::standardizeTableName($parentObject->getName());
	    $it = self::standardizeTableName($intermediarySocket->getForeign());
	    $ct = self::standardizeTableName($childSocket->getForeign());
	    $in = $intermediarySocket->getReflectVariable();
	    $cn = $childSocket->getReflectVariable();
	    return "\t\t\t\t\treturn new {$childSocket->getForeign()}Collection(array('{$ct}','{$it}','{$ot}'),"
	         . "\"`{$ot}`.`{$ot}_id` = \$this->id AND "
	         . "  `{$it}`.`{$in}_id` = `{$ot}`.`{$ot}_id` AND "
	         . "  `{$ct}`.`{$cn}_id` = `{$it}`.`{$it}_id` \");\r\n";
	         
	    /* "get the children of my children"
	     * "get all action items for all of my projects"
	     * ot=organization
	     * it=project
	     * ct=actionItem
	     * 
	     * SELECT *
		 *	FROM `organization` , `project` , `actionItem`
		 *	WHERE `organization`.`organization_id` =1
		 *	AND `project`.`organization_id` = `organization`.`organization_id`
		 *	AND `actionItem`.`project_id` = `project`.`project_id`
	     */
	} 
	
	private function createType3TransitiveRelationship($parentObject,$intermediarySocket,$childSocket) {
	    // Create an FObjectCollection representing the objects in this transitive relation
	    
	    
	    /* "get the peers of my children"
	     * "get all projects for all of my developers"
	     * 
	     * ot=organization
	     * it=user (developer)
	     * ct=project
	     * lt=project_user_users (lookup table)
	     * 
	     * SELECT *
	     *  FROM `organization`,`user`,`project`,`project_user_users` 
	     *  WHERE `organization`.`organization_id` =1
	     *  AND `user`.`organization_id` = `organization`.`organization_id`
	     *  AND `
	     *  
	     *  select * from organization,developer,project,developer_project_projects where
organization.organization_id=1
and developer.organization_id=organization.organization_id
and developer_project_projects.developer_id = developer.developer_id
and developer_project_projects.project_id = project.project_id
	     * 
	     * 
	     * 
	     */
	    $ot = self::standardizeTableName($parentObject->getName());
	    $it = self::standardizeTableName($intermediarySocket->getForeign());
	    $ct = self::standardizeTableName($childSocket->getForeign());
	    $cc = self::standardizeName($childSocket->getForeign());
	    $lt = self::standardizeTableName($childSocket->getLookupTable(),true);
	    $in = $intermediarySocket->getReflectVariable();
	    $cn = $childSocket->getReflectVariable();
	    // {ct} must go LAST in the list of tables because of the case where {$ct} extends FAccount. In
	    // this case {$ct} will be automatically left joined with app_accounts to obtain the full
	    // representation of each child object
	    return "\t\t\t\t\t\$collection = new {$childSocket->getForeign()}Collection(array('{$it}','{$ot}','{$lt}','{$ct}'),"
	         . "\"`{$ot}`.`{$ot}_id` = \$this->id AND "
	         . "  `{$it}`.`{$in}_id` = `{$ot}`.`{$ot}_id` AND "
	         . "  `{$lt}`.`{$it}_id` = `{$it}`.`{$it}_id` AND "
	         . "  `{$lt}`.`{$ct}_id` = `{$ct}`.`{$ct}_id` \");\r\n"
	         . "\t\t\t\t\t\$all = \$collection->get()->output('array');\r\n"
	         . "\t\t\t\t\t\$rtn = array();\r\n"
	         . "\t\t\t\t\tforeach (\$all as \$elem) {\r\n"
	         . "\t\t\t\t\t\tif (!isset(\$rtn[\"o_{\$elem['{$ct}_id']}\"])) {\$rtn[\"o_{\$elem['{$ct}_id']}\"] = new {$cc}(\$elem); }\r\n"
	         . "\t\t\t\t\t}\r\n"
	         . "\t\t\t\t\treturn new {$childSocket->getForeign()}Collection('{$ct}',null,\$rtn);\r\n";
	}
	
	
	private function determineDeleteInformationFor(&$object) {
		$delete_info = array();
		foreach ($object->getChildren() as $child) {
			$remoteSocket = $this->getRemoteSocketFor($child);
			$delete_info[] = array(
				'type'      => 'parent',
				'required'  => (($remoteSocket->isRequired()) ? "yes" : "no"),
				'class'     => $remoteSocket->getOwner(),
				'sqltable'  => self::standardizeTableName($remoteSocket->getOwner()),
				'sqlcol'    => strtolower(substr($remoteSocket->getName(),0,1))
					. substr($remoteSocket->getName(),1)
					. "_id"
			);
		}
		foreach ($object->getPeers() as $peer) {
			$remoteSocket = $this->getRemoteSocketFor($peer);
			$peerInfo = array(
				'type'      => 'lookup',
				'class'     => $remoteSocket->getOwner(),
				'sqltable'  => self::standardizeTableName($remoteSocket->getLookupTable(),true));
			if ($remoteSocket->getOwner() == $peer->getOwner()) {
				$peerInfo['sqlcol']  = strtolower(substr($remoteSocket->getOwner(),0,1))
					.substr($remoteSocket->getOwner(),1)
					."1_id";
				$peerInfo['sqlcol2']  = strtolower(substr($remoteSocket->getOwner(),0,1))
					.substr($remoteSocket->getOwner(),1)
					."2_id";
			} else {
				$peerInfo['sqlcol']  = strtolower(substr($peer->getOwner(),0,1))
					.substr($peer->getOwner(),1)
					."_id";
			}
			$delete_info[] = $peerInfo;
		}
		return $delete_info;
	}
	
	private function getRemoteSocketFor(&$socket) {
		$foreignObject = $this->objects[$socket->getForeign()];
		foreach ($foreignObject->getParents() as $remote) {
			if ($remote->getName() == $socket->getReflectVariable()) {
				return $remote;
			} 
		}
		foreach ($foreignObject->getPeers() as $remote) {
			if ($remote->getName() == $socket->getReflectVariable()) {
				return $remote;
			} 
		}
		foreach ($foreignObject->getChildren() as $remote) {
			if ($remote->getName() == $socket->getReflectVariable()) {
				return $remote;
			} 
		}
		return false;
	}
	
	private function generateObjectTable(&$object) {
		$table = new FSqlTable($object->getName(),false);
		// Special processing for those objects which extend the built-in FAccount class
	 	if ("FAccount" == $object->getParentClass() ) {
			// Create a column in the object table to store the relation to the `app_accounts` table
			$colname = "faccount_id";
 			$c = new FSqlColumn("faccount_id","INT(11) UNSIGNED",false,false,"link to FAccount data for this {$object->getName()}");
 			$table->addColumn($c);	
		}
		foreach ($object->getParents() as $parent) {
			$table->addColumn(
				new FSqlColumn(
					self::standardizeAttributeName($parent->getName())."_id",
					FSqlColumn::convertToSqlType(
						"integer",array("min"=>0)),
				false,
				false,
				"id of " . (($parent->isRequired())? "" : "(optional) ") . "{$parent->getName()} ")
			); 
		}
		foreach ($object->getAttributes() as $attr) {
			$col = new FSqlColumn(
				$attr->getName(),
				FSqlColumn::convertToSqlType($attr->getType(),
					array("min" => "{$attr->getMin()}",
						  "max" => "{$attr->getMax()}",
						  "size"=> "{$attr->getSize()}")),
				false,
				false,
				$attr->getDescription()
			);
			$col->setDefaultValue($attr->getDefaultValue());
			if ($attr->isUnique()) {
				$col->setKey("UNIQUE");
			}
			$table->addColumn($col);
		}
		// return the generated table
		return $table;
	}
	
	private function generateLookupTables() {
		// Iterate through all objects
		foreach ($this->objects as $object) {
			// If the object has any peer relationships...
			if (count($object->getPeers()) > 0) {
				// Iterate through the peer relationships
				foreach ($object->getPeers() as $peer) {
					// If the lookup table does not already exist...
					if (!isset($this->tables[$peer->getLookupTable()])) {
						$table = new FSqlTable($peer->getLookupTable(),true);
						// If the two object types are the same...
						if ($peer->getOwner() == $peer->getForeign()) {
							$col1 = new FSqlColumn(
								self::standardizeAttributeName($peer->getForeign())."1_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
							$col2 = new FSqlColumn(
								self::standardizeAttributeName($peer->getForeign())."2_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
						} else {
							$ownerObjectName   = $peer->getOwner();
							$foreignObjectName = $peer->getForeign();
							$col1 = new FSqlColumn(
								self::standardizeAttributeName($peer->getOwner())."_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
							$col2 = new FSqlColumn(
								self::standardizeAttributeName($peer->getForeign())."_id",
								"INT(11) UNSIGNED",
								false,
								false,
								"");
						}
						// add the columns to the table
						$col1->setKey("PRIMARY");
						$col2->setKey("PRIMARY");
						$table->addColumn($col1);
						$table->addColumn($col2);
						// add the table to the list of tables
						$this->tables[$peer->getLookupTable()] = $table;
					}
				}
			}
		}
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
 	public static function standardizeName($name) {
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
  	public static function standardizeAttributeName($name) {
  		// 1. Replace all '_' with ' ';
		// 2. Replace all '-' with ' ';
  		// 3. Capitalize all words
  		// 4. Concatenate words
  		// 5. Make the first letter lowercase
  		//
  		// Turns: long_variable_name
  		// into:  longVariableName
  		$s = str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ",$name))));
  		return strtolower($s[0]) . substr($s,1);
  	} 

  	public static function standardizeTableName($name,$bIsLookupTable = false) {
  		if ("app_accounts" == $name || "app_roles" == $name || "app_logs" == $name) { return $name; }
  		if ($bIsLookupTable) {
  			// A lookup table needs to maintain its '_' characters (which are
			// otherwise illegal. Standardize the 3 components, but leave the 
			// '_' separators untouched.
			list($t1,$t2,$v) = explode("_",$name);
			return self::standardizeAttributeName($t1)
				.  "_"
				.  self::standardizeAttributeName($t2)
				.  "_"
				.  self::standardizeAttributeName($v);
  		} else {
  			$stdName = self::standardizeAttributeName($name);
	  		if (isset($GLOBALS['furnace']->config->data['hostOS']) &&
	  			strtolower($GLOBALS['furnace']->config->data['hostOS']) == 'windows') {
	  				
	  			// Windows has a case-insensitive file system, and the default 
				// settings of MySQL on windows force tablenames to be strictly lowercase
	  			return strtolower($stdName);	
	  		} else {
	  			// In all other environments, just return the standardized name
				return $stdName;
	  		}
  		}
  	}
  	
	public function getModelData() {
		return $this->modelData;
	}

}
	
	
	
	
?>