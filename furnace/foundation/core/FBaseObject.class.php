<?php
/*
 * frameworkers-foundation
 * 
 * FBaseObject.class.php
 * Created on May 20, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
  /*
  * Class: FBaseObject
  * An abstract base class for all objects generated from a Frameworkers
  * model.
  */
 abstract class FBaseObject {
 	
 	protected $objId;
 	
 	public function getObjId() {
 		return $this->objId;
 	}
 	
 }
 
?>
