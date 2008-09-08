<?php
/*
 * frameworkers-foundation
 * 
 * FObjectCollection.class.php
 * Created on May 20, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
  /*
  * Class: FObjectCollection
  * Represents a collection of <FBaseObject> objects.
  */
 
 abstract class FObjectCollection {
 	
 	// Variable: objectType
 	// The type of objects being manipulated.
 	private $objectType = '';
 	
 	// Variable: objectId
 	// The id of the owner object (if any)
 	private $objectId = '';
 	
 	// Variable: relationType
 	// The type of relationship (1M, or MM) between this object and
 	// the foreign object.
 	private $relationType = '';
 	
 	// Variable: returnType
 	// The type of values ('object','collection',array(FObjAttr[,..]))
 	// to return
 	private $returnType = '';
 	
 	// Variable: lookupTable
 	// The table to consult when doing MM lookups
 	private $lookupTable = '';
 	
 	// Variable: filter
 	// The primary selection criteria determining group membership
 	private $filter = '';
 	
 	public function __construct($type,$lookupTable,$filter='') {
 		$this->objectType = $type;
 		$this->setLookupTable($lookupTable);
 		$theFilter = ("" == $filter) ? "WHERE 1 " : $filter;
 		$this->filter = $theFilter . " ";
 		// Determine the primary limiting criteria for this group based
 		// on the relationType. 1M relations will take advantage of the 
 		// *_id attribute to perform lookups, while MM relations will
 		// use the lookupTable.
// 		if ("1M" == $this->relationType) {
// 			$this->filter = "WHERE `{$this->ownerType}_id` = '{$this->objectId}' ";
// 		} else {
// 			$this->filter = "WHERE `objId` IN SELECT ( `{$this->objectType}_id` FROM `{$this->lookupTable}` WHERE `{$this->ownerType}_id`='{$this->objectId}') ;";
// 		}
 	}
 	
 	public function setLookupTable($tableName) {
 		$this->lookupTable = $tableName;
 		// Determine 1M vs MM based on tableName. Only MM relationships
 		// have an '_' in their tableName
 		if (false === strpos($tableName,"_")) {
 			$this->relationType = '1M';
 		} else {
 			$this->relationType = 'MM';
 		}
 	}
 	
 	public function setOwnerId($objId) {
 		$this->objectId = $objId;	
 	} 	
 	
 	public function getLookupTable() {
 		return $this->lookupTable;
 	}
 	
 	public function getFilter() {
 		return $this->filter;
 	}
 	
 	public function getCount() {
 		$q = "SELECT COUNT(*) FROM {$this->objectType} {$this->filter}";
 		return _db()->queryOne($q);
 	}
 	
 	public function add($objId) {
 		// If the relationship is one to many, this function is 
 		// superfluous because the relationship will have already been
 		// established in the object's constructor.
 		
 		// If the relationship is many to many, an entry needs to be 
 		// added in the lookup table linking these two objects.
 		if ("MM" == $this->relationType) {
 			// Look at the table name to determine variable order
 			list($first,$second) = explode("_",$this->lookupTable);
 			
 			$q = "INSERT INTO `{$this->lookupTable}` VALUES(";
 			$q .= (($first == $this->objectType)
 				? "'{$objId}','{$this->objectId}'"
 				: "'{$this->objectId}','{$objId}'"
 			);
 			$q .= ") ;";
 			_db()->exec($q);
 		}
 	}
 	
 	public function remove($objId,$bAlsoDelete) {
 		// If the relationship is one to many, $bAlsoDelete is ignored and
 		// the child object is completely deleted from the database, as 
 		// are references to it in all lookup tables.
 		if ("1M" == $this->relationType) {
 			$this->destroyObject($objId);
 		}
 		
 		// If the relationship is many to many, the entry in the lookup table
 		// is deleted. If $bAlsoDelete is set, then the object itself (and
 		// all other lookup table references to it) are deleted as in the 1M
 		// case above.
 		if ("MM" == $this->relationType) {
 			$q = "DELETE FROM `{$this->lookupTable}` "
 				. "WHERE `{$this->objectType}_id` = '{$objId}' ; ";
 			if ($bAlsoDelete) {
 				$this->destroyObject($objId);
 			}
 		}
 	}
 	
 	public function get($uniqueValues="*",$returnType="object",$key="objId",$sortOrder="default") {
 		// This function provides a rich variety of options allowing
 		// great end-user flexibility. 12 scenarios have been defined 
 		// based on the 12 unique combinations of $uniqueValues (*,scalar,
 		// numeric array, associative array) and $returnType ("object",
 		// "collection",array(attribute[,...])). Each case is identified
 		// in the matrix below.
 		//
 		// 			RETURN TYPE
 		// U_V TYPE| object		collection		array
 		//	*			I			II			III
 		// scalar		IV			V			VI
 		// num_arr		VII			VIII		IX
 		// assoc_arr	X			XI			XII
 		//
 		// This function determines which case is applicable and passes
 		// control to the corresponding helper function.
 		
 		if ("object" == $returnType){
 			if ("*" == $uniqueValues) {
 				return $this->get_case1($uniqueValues,$key,$sortOrder,$returnType);
 			} else if (is_array($uniqueValues)) {
 				if ($this->is_assoc($uniqueValues)) {
 					return $this->get_case10($uniqueValues,$key,$sortOrder,$returnType);		
 				} else {
 					return $this->get_case7($uniqueValues,$key,$sortOrder,$returnType);
 				}	
 			} else {
 				return $this->get_case4($uniqueValues,$key,$sortOrder,$returnType);
 			}
 		}
 		
 		if ("collection" == $returnType) {
 			if ("*" == $uniqueValues) {
 				return $this->get_case2($uniqueValues,$key,$sortOrder,$returnType);
 			} else if (is_array($uniqueValues)) {
 				if ($this->is_assoc($uniqueValues)) {
 					return $this->get_case11($uniqueValues,$key,$sortOrder,$returnType);	
 				} else {
 					return $this->get_case8($uniqueValues,$key,$sortOrder,$returnType);	
 				}
 			} else {
 				return $this->get_case5($uniqueValues,$key,$sortOrder,$returnType);	
 			}
 		}
 		
 		if (is_array($returnType)) {
 			if ("*" == $uniqueValues) {
 				return $this->get_case3($uniqueValues,$key,$sortOrder,$returnType);
 			} else if (is_array($uniqueValues)) {
 				if ($this->is_assoc($uniqueValues)) {
 					return $this->get_case12($uniqueValues,$key,$sortOrder,$returnType);	
 				} else {
 					return $this->get_case9($uniqueValues,$key,$sortOrder,$returnType);	
 				}
 			} else {
 				return $this->get_case6($uniqueValues,$key,$sortOrder,$returnType);	
 			}
 		}
 		
 	}
 	
 	protected function get_case1(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$q = "SELECT * FROM `{$this->objectType}` " . $this->filter;
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
 			$results[$row[strtolower($k)]] = new $this->objectType($row);	
 		}
 		return $results;
 	}
 	
 	protected function get_case2(&$u_v,&$k,&$s,&$r) {
 		return $this;
 	}
 	
 	protected function get_case3(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 		foreach ($r as $unquoted) { $quotedValues[] = "`{$unquoted}`"; }
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectType}` " . $this->filter;
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow()) {
 			$t = array();
 			$count=0;
 			foreach ($row as $col) {
 				$t[$r[$count++]] = $col;
 			}
 			$results[] = $t;
 		}
 		return $results;
 	}
 	
 	protected function get_case4(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$q = "SELECT * FROM `{$this->objectType}` " . $this->filter . "AND `{$k}`='{$u_v}' ";
 		$result = _db()->queryRow($q);
 		//while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
 		//	$results[] = new $this->objectType($row);
 		//}
		return ((null == $result)
			? false
			: new $this->objectType($result)
		);
 	}
 	
 	protected function get_case5(&$u_v,&$k,&$s,&$r) {
 		$cn = "{$this->objectType}Collection";
 		$newFilter = $this->filter . " AND `{$k}`='{$u_v}' ";
 		return new $cn($this->lookupTable, $newFilter);
 	}
 	
 	protected function get_case6(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 		foreach ($r as $unquoted) { $quotedValues[] = "`{$unquoted}`"; }
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectType}` " . $this->filter
 			."AND `{$k}`='{$u_v}' ";
 		$result = _db()->queryRow($q);
 		while ($row = $result->fetchRow()) {
 			$t = array();
 			$count=0;
 			foreach ($row as $col) {
 				$t[$r[$count++]] = $col;
 			}
 			$results[] = $t;
 		}
 		return $results;
 	}
 	
 	protected function get_case7(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$q = "SELECT * FROM `{$this->objectType}` " . $this->filter . "AND `{$k}` IN ('".implode("','",$u_v)."') ";	
 		$result = _db()->query($q);
		while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
 			$results[$row[strtolower($k)]] = new $this->objectType($row);	
 		}
 		return $results;
 	}
 	
 	protected function get_case8(&$u_v,&$k,&$s,&$r) {
 		$cn = "{$this->objectType}Collection";
 		return new $cn($this->lookupTable,$this->filter . " AND `{$k}` IN ('".implode("','",$u_v)."') ");
 	}
 	
 	protected function get_case9(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 		foreach ($r as $unquoted) { $quotedValues[] = "`{$unquoted}`"; }
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectType}` " . $this->filter 
 			."AND `{$k}` IN ('".implode("','",$u_v)."') ";
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow()) {
 			$t = array();
 			$count=0;
 			foreach ($row as $col) {
 				$t[$r[$count++]] = $col;
 			}
 			$results[] = $t;
 		}
 		return $results;
 	}
	
 	protected function get_case10(&$u_v,&$k,&$s,&$r) {
 		$q = "SELECT * FROM `{$this->objectType}` " . $this->filter;
 		foreach ($u_v as $attr=>$val) {
 			$q .= "AND `{$attr}`='{$val}' ";
 		}
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
 			$results[$row[strtolower($k)]] = new $this->objectType($row);	
 		}
 		return $results; 	
 	}
 	
 	protected function get_case11(&$u_v,&$k,&$s,&$r) {
 		$cn = "{$this->objectType}Collection";
 		$filter = $this->filter;
 		foreach ($u_v as $attr=>$val) {
 			$filter .= "AND `{$attr}`='{$val}' ";
 		}
 		return new $cn($this->lookupTable,$filter);
 	}
 	
 	protected function get_case12(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 		foreach ($r as $unquoted) { $quotedValues[] = "`{$unquoted}`"; }
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectType}` " . $this->filter;
 		foreach ($u_v as $attr=>$val) {
 			$q .= "AND `{$attr}`='{$val}' ";
 		}
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow()) {
 			$t = array();
 			$count=0;
 			foreach ($row as $col) {
 				$t[$r[$count++]] = $col;
 			}
 			$results[] = $t;
 		}
 		return $results;
 	}
	
	public function advancedGet($filter) {
 		$q = "SELECT * FROM `{$this->objectType}` " . $this->filter . " AND " . $filter;
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
 			$results[] = new $this->objectType($row);
 		}	
 		return (count($results) >0)
 			? $results
 			: false;
 	} 	
 	
 	protected abstract function destroyObject($objId);
 	
 	
 	protected function is_assoc($array) {
 		return is_array($array) && 
 			count($array) !== array_reduce(
 				array_keys($array), 'is_assoc_callback', 0);
 	}
 	
 	
 }
function is_assoc_callback($a, $b) {
 		return $a === $b ? $a + 1 : 0;
}
?>