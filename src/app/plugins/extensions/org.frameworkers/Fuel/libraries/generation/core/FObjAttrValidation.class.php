<?php
class FObjAttrValidation {
    
    private $name;
    
    private $vIsNumeric;
    private $vNumericMin;
    private $vNumericMax;
    private $vNumericExact;
    private $vNumericNegate;
    
    private $vIsRequired;
    private $vLengthMin;
    private $vLengthMax;
    private $vLengthExact;
    private $vLengthNegate;
    
    
    public function __construct($name,$data = array()) {
        $this->name           = $name;
        $this->vIsNumeric     = $data['vIsNumeric'];
  		$this->vNumericMin    = $data['vNumericMin'];
  		$this->vNumericMax    = $data['vNumericMax'];
  		$this->vNumericExact  = $data['vNumericExact'];
  		$this->vNumericNegate = $data['vNumericNegate'];
  		
  		$this->vIsRequired      = $data['vRequired'];
  		$this->vLengthMin     = $data['vLengthMin'];
  		$this->vLengthMax     = $data['vLengthMax'];
  		$this->vLengthExact   = $data['vLengthExact'];
  		$this->vLengthNegate  = $data['vLengthNegate'];
    }
    
    public function toModelSqlValuesString() {
        $r .= '('
        	."'{$this->name}',"
        	
     		.(($this->vIsNumeric) ? "'Y'" : "'N'").','
     		.(is_null($this->vNumericMin) ? 'NULL' : $this->vNumericMin).','
     		.(is_null($this->vNumericMax) ? 'NULL' : $this->vNumericMax).','
     		.(is_null($this->vNumericExact) ? 'NULL' : $this->vNumericExact).','
     		.(($this->vNumericNegate) ? "'Y'" : "'N'").','
     		
     		.(($this->vIsRequired) ? "'Y'" : "'N'").','
     		.(is_null($this->vLengthMin) ? 'NULL' : $this->vLengthMin).','
     		.(is_null($this->vLengthMax) ? 'NULL' : $this->vLengthMax).','
     		.(is_null($this->vLengthExact) ? 'NULL' : $this->vLengthExact).','
     		.(($this->vLengthNegate) ? "'Y'" : "'N'").','

     		.')';
     		
         return $r;
    }
    
    
}
?>