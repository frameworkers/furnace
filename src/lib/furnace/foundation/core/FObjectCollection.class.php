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



class FCriteria {

    public $prior_op = null;
    public $field    = null;
    public $value    = null;
    public $comp     = '=';

    /**
     *
     * @param $prior_op: the boolean ('AND', 'OR', 'AND NOT', etc) to preceed this criteria
     * @param $field: the object attribute that this criteria applies to
     * @param $value: the value to use for comparison. If $value is an array, IN becomes the default comparison operator
     * @param $comp: the comparison operator (<,<=,=,>=,>,!=,NOT,IN) to use
     * @return unknown_type
     */

    public function __construct($prior_op = null,$field,$value,$comp='=') {
        $this->prior_op = $prior_op;
        $this->field    = $field;
        $this->value    = $value;
        $this->comp     = ('=' == $comp && is_array($value))
            ? "IN"
            : strtoupper($comp);
    }

    public function __toString() {
        switch ($this->comp) {
            	
            default:
                $s = " {$this->prior_op} `{$this->field}` {$this->comp} '{$this->value}' ";
                break;
        }

        return $s;
    }
}
 
/*
* Class: FObjectCollection
* Represents a collection of <FBaseObject> objects.
*/
 
abstract class FObjectCollection {
    
    protected $objectType;
    protected $objectTypeTable;
    protected $query;
    protected $output;
    
    public $data;
    
    
    

    public function __construct($objectType,$lookupTables = array(),$baseFilter = null,$data=array()) {
        $this->data            = $data;
        $this->objectType      = $objectType;
        $this->query      = new FQuery();
        
        // An array of tables was provided, add them all
        if (is_array($lookupTables) && !empty($lookupTables)) {
            $this->objectTypeTable = strtolower($this->objectType[0]).substr($this->objectType,1);
            foreach ($lookupTables as $lt) {
                $this->query->addTable($lt);
            }
        // A single scalar value was provided
        } else if (!is_array($lookupTables) && '' != $lookupTables) {
            $this->objectTypeTable = $lookupTables;
            $this->query->addTable($lookupTables);
        // The default (blank) was provided, use the objectType as table name
        } else {
            $this->query->addTable(FModel::standardizeTableName($objectType));
        }

        $this->output = 'objects';
        if ( $baseFilter ) {
            $this->query->addCondition(null,$baseFilter);
        }
    }

    // Specifies the type of data returned by 'get' calls
    // Options:
    //    object
    //    array
    //    collection
    //    query
    //    xml
    //    yml
    //    json
    public function output( $which = 'objects' ) {
        $this->output = $which;

        switch ($which) {
            case 'array':
                return $this->runQuery('array');
            case 'collection':
                return this;
            case 'objects':
                if (count($this->data) == 0) {
                    $this->runQuery('objects');
                }
                return array_values($this->data);     // strip o_* keys
                break;
            case 'query':
                return $this->query->select();
            default:
                throw new FException("Unknown output method provided to FObjectCollection::output()");
                break;
        }
    }
    
    public function orderBy($var,$order='asc') {
        $this->query->orderBy($var,$order);
        return $this;
    }
    
    // Return the first object in the collection
    public function first() {
        if (count($this->data) == 0) {
            $this->runQuery('objects');
        }
        
        if (count($this->data) == 0 ) {
            return false;                    // no objects in the collection
        } else {
            $keys = array_keys($this->data);
            return $this->data[$keys[0] ];   // always return the 1st object
        }
        
    }

    // Specifies a limit on the number of results to return, and optionally
    // specifies an offset (useful for pagination)
    public function limit( $limit, $offset = 0 ) {
        $this->query->setLimit($limit,$offset);
        return $this;
    }
    
    // Empty this collection of previously retrieved data
    public function purge() {
        $this->data = array();
    }


    // Adds a filter to the collection
    // Examples:
    //    filter(key,value)               - key=value
    //    filter(key,comparator,value)    - key{comp}value where {$comp} is = != < <= > >=, etc
    //    filter(FCriteria)               - processes an FCriteria object to the query
    public function filter(/* variable arguments accepted */) {
        
        /*
         * Algorithm (10,000 feet):
         * 
         * 1) Determine whether local or extended filtering is being requested
         * 
         * 2) Determine whether data has already been retrieved
         * 
         * 3) Perform the appropriate filter action
         */
        
        /*
         * Algorithm (1,000 feet):
         * 
         * 1) Determine whether local or extended filtering is being requested 
         * 	a. filtering is 'local' if _model()->$objectType->attributeInfo($k) is not false
         *  b. filtering is 'extended' if !(1.a) and _model()->$objectType->get{$k}Info() is callable
         *  
         * 2) Determine whether data has already been retrieved
         *  a. data has already been retrieved if count($this->data) > 0
         *  
         * 3) Perform the appropriate filter action
         *  When no data has yet been retrieved, both local and extended filtering are implemented 
         *  through (possibly multiple) calls to $this->query->addCondition()
         *  
         *  If data exists, however,
         *   i) local filtering is implemented by examining the corresponding values for each of the
         *      objects in $this->data
         *   ii)extended filtering is implemented by first expand()'ing the corresponding remote key
         *      and then continuing as in (2.a.i)
         */
        
          
        
        
        
        $num_args = func_num_args();
        switch ($num_args) {
            case 1:
                // Process an FCriteria object
                break;
            case 2:
                // Process a key=val filter
                $k = func_get_arg(0);
                $v = func_get_arg(1);
                
                // BEGIN 'k' PRE-PROCESSING
                // This is necessary when 'extended'
                // filters are being used. An extended filter is one that filters
                // on the value of an attribute of a *relative* of the current object.
                //
                // Example: 
                //   objects: bug, release
                //   rels:    bug has a M:1 (child-parent) relationship to release called 'target'
                //
                //   to filter those bugs targeted for a particular release:
                // 
                //   $bugCollection->filter('target',31) where 31 is the id of the release. This isn't
                //   always ideal (most times the id is internal) so, to allow searching on the 'name'
                //   of the release, for example:
                //
                //   $bugCollection->filter('target[name]','0.8.1-beta');
                //
                //   using the example above,
                //     rk=name     (remote key)
                //      k=target   (key)
                //
                //
                $rk = false;
                if (false !== ($rkstart = strpos($k,'['))) {
                    if (false !== ($rkend = strpos($k,']',$rkstart))) {
                        $rk = substr($k,$rkstart+1,($rkend-($rkstart+1)));
                        $k = substr($k,0,$rkstart);
                    }
                }
                // Finally, if k == 'id' it needs to be expanded to its full schema equivalent
                //   of '<tablename>_id'
                $origK = $k;
                if ($k == 'id') { $k = $this->getRealId(); }
                //
                // END 'k' PRE-PROCESSING
                
                
                
                if (empty($this->data)) {
                    
                    // If the requested key represents a local attribute, then 
                    // the request can be satisfied by adding a simple condition to
                    // the query object:
                    $ot  = $this->objectType;
                    $otm = "{$ot}Model";  
                    
                    
                    if ($origK == 'id' || _model()->$ot->attributeInfo($k)) {   
                        $this->query->addCondition(null, "`{$this->objectTypeTable}`.`{$k}`='{$v}' ");
                    }
                    
                    // If the requested key represents an external relationship, then...
                    else {
                        $fn = "get{$k}Info";;
                        if (is_callable(array($otm,$fn))) {
                            $info = _model()->$ot->$fn();
                            if ($info['role_l'] == 'M1') {
                                // Filtering on a Parent relation
                                // If the remote key (rk) === false, it means that no 
                                // remote key was specified, and that we should default to the 
                                // id of the remote object
                                if (false === $rk) {
                                    $rk = "{$info['table_l']}_id";
                                }
                                
                                $this->query->addTable($info['table_l']);
                                $this->query->addCondition('AND',
                                	"`{$info['table_f']}`.`{$info['column_f']}`=`{$info['table_l']}`.`{$info['table_l']}_id`");
                                $this->query->addCondition('AND',
                                    "`{$info['table_l']}`.`{$rk}` = '{$v}' ");
                            }
                            if ($info['role_l'] == 'MM') {
                                // Filtering on a Peer relation
                                die("Extended filtering on PEER relations not supported yet");
                            }
                            if ($info['role_l'] == '1M') {
                                // Filtering on a Child relation
                                die("Extended filtering on CHILD relations not supported yet");
                            }
                        }
                    }
                
                
                } else {
                    // Filter the existing data points
                    $keys = array_keys($this->data);
                    $count= 0;
                    $fn   = "get{$k}";
                    foreach ($this->data as $d) {
                        if ($d->$fn() != $v) {
                            unset($this->data[$keys[$count]]);
                        }
                        $count++;
                    }
                }
                break;
            case 3:
                // Process a key,comp,val filter
                $k = func_get_arg(0);
                $c = func_get_arg(1);
                $v = func_get_arg(2);
                if ($k == 'id') { $k = $this->getRealId(); }
                if (empty($this->data)) {
                    // Add a condition to the query
                    $this->query->addCondition(null, "`{$this->objectTypeTable}`.`{$k}` {$c} {$v} ");
                } else {
                    // Filter the existing data points
                }
                break;
            default:
                throw new FException("Unexpected number of arguments for FObjectCollection::filter()");
                break;
        }
        return $this;
    }
    
    public function filterIn($key, $allowedValues, $bQuoteValues = false, $bNegate = false) {
        if ($key == 'id') { $key = $this->getRealId(); }
        $negate = ($bNegate) ? "NOT " : "";
        if ($bQuoteValues) {
            $this->query->addCondition(null, "( {$key} {$negate} IN (\"" . implode('","',$allowedValues) . '") )');
        } else {
            $this->query->addCondition(null, "( {$key} {$negate} IN (" . implode(',',$allowedValues) . ") )");
        }
        return $this;
    }
    
    public function filterOr(/*variable args accepted*/) {
        $num_args = func_num_args();
        switch ($num_args) {
            case 1:
                // Process an FCriteria object
                break;
            case 2:
                // Process a key=val filter
                $k = func_get_arg(0);
                $v = func_get_arg(1);
                if ($k == 'id') { $k = $this->getRealId(); }
                
                if (empty($this->data)) {
                    // Add a condition to the query
                    $this->query->addCondition('OR', "{$k}='{$v}' ");
                } else {
                    // Filter the existing data points
                    /**
                     * THIS IS IMPOSSIBLE... right?
                     * 
                     * 
                     *
                     *
                    $keys = array_keys($this->data);
                    $count= 0;
                    $fn   = "get{$k}";
                    foreach ($this->data as $d) {
                        if ($d->$fn() != $v) {
                            unset($this->data[$keys[$count]]);
                        }
                        $count++;
                    }
                    */
                    throw new FException("Unsupported use of ->filterOr()");
                    break;
                }
                break;
            case 3:
                // Process a key,comp,val filter
                $k = func_get_arg(0);
                $c = func_get_arg(1);
                $v = func_get_arg(2);
                if ($k == 'id') { $k = $this->getRealId(); }
                if (empty($this->data)) {
                    // Add a condition to the query
                    $this->query->addCondition(null, "{$k} {$c} {$v} ");
                } else {
                    // Filter the existing data points
                    throw new FException("Unsupported use of ->filterOr()");
                    break;
                }
                break;
            default:
                throw new FException("Unexpected number of arguments for FObjectCollection::filter()");
                break;
        }
        return $this;
    }
    
    public function each() {
        $this->runQuery();
        return $this;
    }
    
    public function expand($attribute,$fieldList = null,$indexKey='id') {
        
        // Ensure a working set of objects already exists
        if (count($this->data) == 0) { 
            $this->runQuery();    // Attempt to retrieve some objects
                                  // (this makes calling ->each() first optional)
            if (count($this->data) == 0) {
                                  // If there are STILL no objects, return
                return $this;
            }
        }
        $ot = $this->objectType;
        $fn = "get{$attribute}Info";
        $relationshipData = _model()->$ot->$fn();
        $loadAttribute    = "load{$attribute}";
        $setAttribute     = "set{$attribute}";
        $getAttribute     = "get{$attribute}";
        
        // Parent
        if ($relationshipData['role_l'] == "M1") {

            // Parent and child are of different types
            if ($relationshipData['table_l'] != $relationshipData['table_f']) {
                $keys = $this->getKeys($relationshipData['key_l']);
                $q = "SELECT * "
                    ."FROM  `{$relationshipData['table_l']}`,`{$relationshipData['table_f']}` "
                    . (($relationshipData['base_f'] == 'FAccount') ? ",`app_accounts` " : '')
                    ."WHERE `{$relationshipData['table_l']}`.`{$relationshipData['column_l']}` = `{$relationshipData['table_f']}`.`{$relationshipData['column_f']}` "
                    ."AND   `{$relationshipData['table_f']}`.`{$relationshipData['table_f']}_id` IN (\"".implode("\",\"",$keys)."\") "
                    . (($relationshipData['base_f'] == 'FAccount') ? "AND {$relationshipData['table_l']}.faccount_id=app_accounts.faccount_id " : '');

                $result = _db($relationshipData['db_l'])->query($q,FDATABASE_FETCHMODE_ASSOC);
                while (false != ($unsortedParent = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC))) {
                    $this->data['o_'.$unsortedParent[$relationshipData['table_f'].'_id'] ]
                        ->$setAttribute(new $relationshipData['object_f']($unsortedParent));
                }
            
            // Parent and child are of the same type
            } else {
                $keys = $this->getKeys($relationshipData['key_f']);
                $q = "SELECT * "
                    ."FROM  `{$relationshipData['table_l']}` "
                    . (($relationshipData['base_f'] == 'FAccount') ? ",`app_accounts` " : '')
                    ."WHERE `{$relationshipData['table_l']}`.`{$relationshipData['table_l']}_id` IN (\"".implode("\",\"",$keys)."\") "
                    . (($relationshipData['base_f'] == 'FAccount') ? "AND {$relationshipData['table_l']}.faccount_id=app_accounts.faccount_id " : '');


                $result = _db($relationshipData['db_l'])->query($q,FDATABASE_FETCHMODE_ASSOC);
                while (false != ($unsortedParent = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC))) {
                    foreach ($this->data as &$do) {
                        if ($do->$getAttribute(true) == $unsortedParent[$relationshipData['table_l'].'_id']) {
                            $do->$setAttribute(new $relationshipData['object_f']($unsortedParent));
                            break;
                        }
                    }
                }
            } 
        }
        
        // Pair
        if ($relationshipData['role_l'] == '11') {
            die("Furnace: FObjectCollection::expand() pair relations not implemented yet");
        }
        
        // Peer
        if ($relationshipData['role_l'] == "MM") {
            $keys = $this->getKeys($relationshipData['key_l']);
            
            // START HERE
            // Need to add a special attribute to relationshipData (in FModel, below) for this type so that we can
            // capture all 3 relevant tables: table_l table_f and table_lookup. Then, we need to
            // do a join on the tables to get the complete information, taking care to account for
            // the case in which table_l and table_f are the same table.
            
            if ($relationshipData['table_l'] != $relationshipData['table_f']) {
                $keys = $this->getKeys();
                $q = "SELECT * FROM `{$relationshipData['table_l']}`,`{$relationshipData['table_m']}` "
                .(($relationshipData['base_f'] == 'FAccount') ? ',`app_accounts` ' : '')
                ."WHERE `{$relationshipData['table_m']}`.`{$relationshipData['column_f']}` IN (\"".implode("\",\"",$keys)."\") "
                ."AND `{$relationshipData['table_l']}`.`{$relationshipData['column_l']}`=`{$relationshipData['table_m']}`.`{$relationshipData['column_l']}` "
                .(($relationshipData['base_f'] == 'FAccount') ? "AND {$relationshipData['table_l']}.faccount_id=app_accounts.faccount_id " : '');
                $result = _db($relationshipData['db_l'])->query($q,FDATABASE_FETCHMODE_ASSOC);
                
                while (false != ($unsortedPeer = $result->fetchrow(FDATABASE_FETCHMODE_ASSOC))) {
                    $peerId = $unsortedPeer[$relationshipData['column_f'] ];
                    $this->data["o_{$peerId}"]->$loadAttribute(array("o_{$unsortedPeer[$relationshipData['column_l'] ]}" => new $relationshipData['object_f']($unsortedPeer)));   
                }
  
            } else {
                die("Furnace: FObjectCollection::expand() same-type peers not implemented yet"); 
            }
        }
        
        // Child
        if ($relationshipData['role_l'] == "1M") {
            
            $keys = $this->getKeys($indexKey);
            $q = "SELECT * FROM `{$relationshipData['table_l']}` "
                .(($relationshipData['base_f'] == 'FAccount') ? ',`app_accounts` ' : '')
                ."WHERE `{$relationshipData['column_l']}` IN (\"".implode("\",\"",$keys)."\")"
                .(($relationshipData['base_f'] == 'FAccount') ? "AND {$relationshipData['table_l']}.faccount_id=app_accounts.faccount_id " : '');
            $result = _db($relationshipData['db_l'])->query($q,FDATABASE_FETCHMODE_ASSOC);

            while (false != ($unsortedChild = $result->fetchrow(FDATABASE_FETCHMODE_ASSOC))) {
                $parentId = $unsortedChild[$relationshipData['column_l'] ];
                $this->data["o_{$parentId}"]->$loadAttribute(array("o_{$unsortedChild[$relationshipData['column_l'] ]}" => new $relationshipData['object_f']($unsortedChild)));    
            }
        }
        
        return $this;
    }
    
    protected function getKeys($keyAttribute = 'id') {
        $fn   = "get{$keyAttribute}";
        $keys = array();
        foreach ($this->data as $o) {
            $key  = $o->$fn(true); // bIdOnly = true;
            $keys[$key] = $key; 
        }
        return $keys;
    }
    
    protected function getRealId() {
        return $this->objectTypeTable . '_id';
    }


    public function get(/* variable arguments accepted */) {
        $num_args = func_num_args();
        switch ($num_args) {
            case 0:
                // Return the collection object, as-is
                break;
            case 1:
                // Get a single object using the provided id
                // Since this will always result in at most 1 object, return
                // the object directly
                $v = func_get_arg(0);
                if (false !== ($obj = $this->getSingleObjectByObjectId($v))) {
                    $this->data = array("o_{$obj->getId()}" => $obj);
                    return $obj;
                } else {
                    return false;
                }
                break;
            case 2:
                // Get a single object using the provided key/value pair
                $k = func_get_arg(0);
                $v = func_get_arg(1);
                if (false !== ($obj = $this->getSingleObjectByAttribute($k,$v))) {
                    $this->data = array("o_{$obj->getId()}" => $obj);
                }
                break;
            default:
                throw new FException("Unexpected number of arguments for FObjectCollection::get()");
                return false;
        }
        return $this;

    }

    // This will return at most one object where objId=id
    protected function getSingleObjectByObjectId( $id ) {
        $this->filter('id',$id);
        $result = _db()->queryRow($this->query->select(),FDATABASE_FETCHMODE_ASSOC);
        if (null == $result) { return false; } 
        else {
            $t = $this->objectType;  
            return new $t($result);
        }
    }

    // This will return at most one object where attr=val
    protected function getSingleObjectByAttribute( $attr, $value ) {
        $this->filter($attr,$value);
        $result = _db()->queryRow($this->query->select(),FDATABASE_FETCHMODE_ASSOC);
        if (null == $result) { return false; }
        else {
            $t = $this->objectType;
            return new $t($result);
        }
    }
    
    protected function runQuery($output = 'objects') {
        $result = _db()->queryAll($this->query->select(),FDATABASE_FETCHMODE_ASSOC);
        if (null == $result) { return false; }
        else {
            switch ($output) {
                case 'array':
                    return $result;
                case 'objects':
                default:
                    $response = array();
                    $t = $this->objectType;
                    foreach ( $result as $r ) {
                        $response['o_'.$r[$this->objectTypeTable.'_id'] ] = new $t($r);
                    }
                    $this->data = $response;
                    break;
            }
        }
    }
}
?>