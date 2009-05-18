<?php
/*
 * frameworkers-furnace
 * 
 * FObjectCollection.class.php
 * Created on May 17, 2009
 *
 * Copyright 2008-2009 Frameworkers.org. 
 * http://www.frameworkers.org/furnace
 */
 
  /*
  * Class: FAccountCollection
  * Represents a collection of <FAccount>-derived objects.
  */

abstract class FAccountCollection {
 	
 	// Variable: objectType
 	// The type of objects being manipulated.
 	private $objectType = '';
 	
 	// Variable: objectTypeTableName
	// The corresponding object table for this object type
 	protected $objectTypeTableName = '';
 	
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
 	
 	// Variable: paginationData
	// An array containing information required to construct pagination links
 	private $paginationData = array();
 	
 	// Variable: filter
 	// The primary selection criteria determining group membership
 	private $filter = '';
 	
 	public function __construct($type,$lookupTable,$filter='') {
 		$this->objectType = $type;
 		$this->objectTypeTableName = strtolower($type[0]).substr($type,1);
 		$this->setLookupTable($lookupTable);
 		//$theFilter = ("" == $filter) ? "WHERE 1 " : $filter;
 		$this->filter = $filter;
 		
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
 		$q = "SELECT COUNT(*) FROM `{$this->objectTypeTableName}` {$this->filter}";
 		return _db()->queryOne($q);
 	}

 	public function getPage($page_number=1,$per_page = 15,$key='objId',$sortOrder="default") { 		
 		$total   = $this->getCount();
 		$pages   = ceil($total/$per_page);
 		$offset  = ($page_number-1) * $per_page;
 		$results = array();
 		$fullKey = ('objId' == $key)
 			? "`{$this->objectTypeTableName}`.`objId` "
 			: "`{$key}` ";
 		
 		// Return an array of objects according to the pagination details given
		$q = "SELECT * FROM `{$this->objectTypeTableName}` "
			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` " 
			."{$this->filter} ORDER BY {$fullKey} " . (($sortOrder == "desc") ? " DESC " : " ASC ");
		_db()->setLimit($per_page,$offset);
		$result = _db()->query($q);
		while ($row = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
 			$results[/*$row[strtolower($key)]*/] = new $this->objectType($row);	
 		}
 		
 		// Set Pagination Data and Stats
		for ($i=1;$i<=$pages;$i++) {
			$this->paginationData['data'][] = array ("pageNumber"=>$i,"suffix"=>"?page={$i}&sortBy={$key}&sortOrder={$sortOrder}");
		}
		$this->paginationData['stats'] = array(
			"start" => $offset + 1,
			"end"   => min($total,$offset + $per_page),
			"total" => $total,
			"currentPage" => $page_number,
			"totalPages"  => $pages
		);
 		return $results;
 	}
 	
 	public function getSubset($firstIndex,$count,$key='objId',$sortOrder="default") {
 		// Return an array of objects according to the subset details given
		$q = "SELECT * FROM `{$this->objectTypeTableName}` "
			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			."{$this->filter} ORDER BY "
 			.(('objId' == $key)
 				? "`{$this->objectTypeTableName}`.`objId` "
 				: "`{$key}` ")
 			. (($sortOrder == "desc") ? " DESC " : " ASC ");
		_db()->setLimit($count,$firstIndex);
		$result = _db()->query($q);
		while ($row = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
 			$results[/*$row[strtolower($key)]*/] = new $this->objectType($row);	
 		}
 		return $results;
 	}
 	
 	public function getPaginationData() {
 		return $this->paginationData;
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
 		
 		// Since `objId` belongs to both tables, it must be disambiguated:
 		$fullKey = ('objId' == $key)
 			? "`{$this->objectTypeTableName}`.`objId` "
 			: "`{$key}` ";
 		
 		if ("object" == $returnType){
 			if ("*" == $uniqueValues) {
 				return $this->get_case1($uniqueValues,$fullKey,$sortOrder,$returnType);
 			} else if (is_array($uniqueValues)) {
 				if ($this->is_assoc($uniqueValues)) {
 					return $this->get_case10($uniqueValues,$fullKey,$sortOrder,$returnType);		
 				} else {
 					return $this->get_case7($uniqueValues,$fullKey,$sortOrder,$returnType);
 				}	
 			} else {
 				return $this->get_case4($uniqueValues,$fullKey,$sortOrder,$returnType);
 			}
 		}
 		
 		if ("collection" == $returnType) {
 			if ("*" == $uniqueValues) {
 				return $this->get_case2($uniqueValues,$fullKey,$sortOrder,$returnType);
 			} else if (is_array($uniqueValues)) {
 				if ($this->is_assoc($uniqueValues)) {
 					return $this->get_case11($uniqueValues,$fullKey,$sortOrder,$returnType);	
 				} else {
 					return $this->get_case8($uniqueValues,$fullKey,$sortOrder,$returnType);	
 				}
 			} else {
 				return $this->get_case5($uniqueValues,$fullKey,$sortOrder,$returnType);	
 			}
 		}
 		
 		if (is_array($returnType)) {
 			if ("*" == $uniqueValues) {
 				return $this->get_case3($uniqueValues,$fullKey,$sortOrder,$returnType);
 			} else if (is_array($uniqueValues)) {
 				if ($this->is_assoc($uniqueValues)) {
 					return $this->get_case12($uniqueValues,$fullKey,$sortOrder,$returnType);	
 				} else {
 					return $this->get_case9($uniqueValues,$fullKey,$sortOrder,$returnType);	
 				}
 			} else {
 				return $this->get_case6($uniqueValues,$fullKey,$sortOrder,$returnType);	
 			}
 		}
 		
 	}
 	
 	protected function get_case1(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$q = "SELECT * FROM `{$this->objectTypeTableName}` " 
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.$this->filter . " ORDER BY {$k} " .(($s == "desc") ? " DESC " : " ASC ");
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
 			$results[] = new $this->objectType($row);	
 		}
 		return $results;
 	}
 	
 	protected function get_case2(&$u_v,&$k,&$s,&$r) {
 		return $this;
 	}
 	
 	protected function get_case3(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 		foreach ($r as $unquoted) { 
 			$quotedValues[] = (('objId' == $unquoted) ? "`{$this->objectTypeTableName}`.`objId`" : "`{$unquoted}`"); 
 		}
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectTypeTableName}` "
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.$this->filter . " ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");
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
 		$q = "SELECT * FROM `{$this->objectTypeTableName}` " 
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "; 
 		
 		$q .= ($this->filter) 
 				? " {$this->filter} AND "
				: " WHERE {$k}='{$u_v}' "; 
 		$q .= "ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");
 		
 		$result = _db()->queryRow($q,FDATABASE_FETCHMODE_ASSOC);

		return ((null == $result)
			? false
			: new $this->objectType($result)
		);
 	}
 	
 	protected function get_case5(&$u_v,&$k,&$s,&$r) {
 		$cn = "{$this->objectType}Collection";
 		$newFilter = 
 			($this->filter)
 				? " {$this->filter} AND {$k}='{$u_v}' "
				: " WHERE {$key}='{$u_v}' ";
 		return new $cn($this->lookupTable, $newFilter);
 	}
 	
 	protected function get_case6(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 	 	foreach ($r as $unquoted) { 
 			$quotedValues[] = (('objId' == $unquoted) ? "`{$this->objectTypeTableName}`.`objId`" : "`{$unquoted}`"); 
 		}
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectTypeTableName}` " 
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.(($this->filter)
 				? " {$this->filter} AND "
				: " WHERE ")
 			."{$k}='{$u_v}' ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");
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
 		$q = "SELECT * FROM `{$this->objectTypeTableName}` "
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.(($this->filter)
 				? " {$this->filter} AND "
				: " WHERE ")
			."{$k} IN ('".implode("','",$u_v)."') ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");	
 		$result = _db()->query($q);
		while ($row = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
 			$results[] = new $this->objectType($row);	
 		}
 		return $results;
 	}
 	
 	protected function get_case8(&$u_v,&$k,&$s,&$r) {
 		$cn = "{$this->objectType}Collection";
 		return new $cn($this->lookupTable,(($this->filter) ? "{$this->filter} AND" : " WHERE ") . " {$k} IN ('".implode("','",$u_v)."') ");
 	}
 	
 	protected function get_case9(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 	 	foreach ($r as $unquoted) { 
 			$quotedValues[] = (('objId' == $unquoted) ? "`{$this->objectTypeTableName}`.`objId`" : "`{$unquoted}`"); 
 		}
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectTypeTableName}` "
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` " 
 			.(($this->filter)
 				? " {$this->filter} AND "
				: " WHERE ")
 			." {$k} IN ('".implode("','",$u_v)."') ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");
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
 		$q = "SELECT * FROM `{$this->objectTypeTableName}` "
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.(($this->filter)
 				? "{$this->filter} AND "
				: " WHERE ");
 		foreach ($u_v as $attr=>$val) {
 			$fullAttr = (('objId'==$attr)
 				?	"`{$this->objectTypeTableName}`.`objId`"
 				:   "`{$attr}`");
 			if( is_array($val)) { 
 				$q .= " {$fullAttr} " . implode($val," '") . "' ";
 			} else {
 				$q .= " {$fullAttr}='{$val}' ";
 			}
 		}
 		$q .= " ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");
 		$result = _db()->query($q);

 		while ($row = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
 			if ($k == "`{$this->objectTypeTableName}`.`objId`") {
 				$results[] = new $this->objectType($row);	
 			} else {
 				$results[$row[strtolower(trim($k,'`'))]] = new $this->objectType($row);	
 			}
 		}
 		return $results; 	
 	}
 	
 	protected function get_case11(&$u_v,&$k,&$s,&$r) {
 		$cn = "{$this->objectType}Collection";
 		$filter_parts = array();
 		foreach ($u_v as $attr=>$val) {
 			$fullAttr = (('objId'==$attr)
 				?	"`{$this->objectTypeTableName}`.`objId`"
 				:   "`{$attr}`");
 			if( is_array($val)) { 
 				$filter_parts[] = " {$fullAttr} " . implode($val," '") . "' ";
 			} else {
 				$filter_parts[] = " {$fullAttr}='{$val}' ";
 			}
 		}
 		$filter = ($this->filter) ? " {$this->filter} AND " : " WHERE ";
 		$filter .= implode(" AND ",$filter_parts);
 		return new $cn($this->lookupTable,$filter);
 	}
 	
 	protected function get_case12(&$u_v,&$k,&$s,&$r) {
 		$results = array();
 		$quotedValues = array();
 		foreach ($r as $unquoted) { $quotedValues[] = "`{$unquoted}`"; }
 		$q = "SELECT ".implode(",",$quotedValues) ." FROM `{$this->objectTypeTableName}` " 
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.(($this->filter)
 				? "{$this->filter} AND " 
 				: " WHERE ");
 		foreach ($u_v as $attr=>$val) {
 			$fullAttr = (('objId'==$attr)
 				?	"`{$this->objectTypeTableName}`.`objId`"
 				:   "`{$attr}`");
 			if( is_array($val)) { 
 				$q .= " {$fullAttr} " . implode($val," '") . "' ";
 			} else {
 				$q .= " {$fullAttr}='{$val}' ";
 			}
 		}
 		$q .= " ORDER BY {$k} " . (($s == "desc") ? " DESC " : " ASC ");
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
 	
 	public function setCustomFilter($filter) {
 		if ($this->filter) {
 			$this->filter .= " AND ( {$filter} ) ";
 		} else {
 			$this->filter = ("WHERE" == strtoupper(substr(ltrim($filter," "),0,5)))
 				? $filter
 				: "WHERE {$filter} ";
 		}
 	}
 	
 	public function advancedGet($filter) {
 		$q = "SELECT * FROM `{$this->objectTypeTableName}` "
 			."INNER JOIN `app_accounts` ON `{$this->objectTypeTableName}`.`objId`=`app_accounts`.`objectId` "
 			.(($this->filter)
 				? " {$this->filter} AND "
				: " WHERE ") . 
			" (" . $filter . " ) ";
 		$result = _db()->query($q);
 		while ($row = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
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
?>