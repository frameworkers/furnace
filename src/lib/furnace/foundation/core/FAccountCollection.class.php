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

 
abstract class FAccountCollection extends FObjectCollection {

    public function __construct($objectType,$lookupTable,$baseFilter = null) {
        
        parent::__construct($objectType,$lookupTable,$baseFilter);
        
        $this->query->addJoin('LEFT JOIN','app_accounts',
        	'app_accounts.faccount_id='.$this->objectTypeTable.'.faccount_id');
    }
}

    
/**
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
            case 'collection':
                return this;
            case 'objects':
                return (count($this->data) == 0) ? $this->runQuery('objects') : $this->data;
                break;
            case 'query':
                return $this->query->select();
            default:
                throw new FException("Unknown output method provided to FObjectCollection::output()");
                break;
        }
    }
    
    // Return the first object in the collection
    public function first() {
        if (count($this->data) == 0 ) {
            $this->data = $this->runQuery('objects');
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
        $this->query->limit  = $limit;
        $this->query->offset = $offset;
        return $this;
    }


    // Adds a filter to the collection
    // Examples:
    //    filter(key,value)               - key=value
    //    filter(key,comparator,value)    - key{comp}value where {$comp} is = != < <= > >=, etc
    //    filter(FCriteria)               - processes an FCriteria object to the query
    public function filter() {
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
                $this->query->addCondition(null, "{$k}='{$v}' ");
                break;
            case 3:
                // Process a key,comp,val filter
                $k = func_get_arg(0);
                $c = func_get_arg(1);
                $v = func_get_arg(2);
                if ($k == 'id') { $k = $this->getRealId(); }
                $this->query->addCondition(null, "{$k} {$c} {$v} ");
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
    
    public function each() {
        $this->data = $this->runQuery();
        return $this;
    }
    
    public function expand($attribute,$fieldList = null,$indexKey='id') {
        
        //TODO: ensure that any expanded attributes are appropriately expanded.. meaning use
        // fobjectcollection when appropriate, and faccount collection when appropriate

        
        if (count($this->data) == 0) { return $this; }
        $ot = $this->objectType;
        $fn = "get{$attribute}Info";
        $relationshipData = _model()->$ot->$fn();
        $loadAttribute    = "load{$attribute}";
        
        // Parent
        if ($relationshipData['role_l'] == "M1") {
            $keys = $this->getKeys($relationshipData['key_l']);
            
            //TODO: Handle the case in which the
            // parent class is of the same type as the child
            //
            //
            
            $q = "SELECT * "
                ."FROM  `{$relationshipData['table_l']}`,`{$relationshipData['table_f']}` "
                ."WHERE `{$relationshipData['table_l']}`.`{$relationshipData['column_l']}` = `{$relationshipData['table_f']}`.`{$relationshipData['column_f']}` "
                ."AND   `{$relationshipData['table_f']}`.`{$relationshipData['table_f']}_id` IN (\"".implode("\",\"",$keys)."\")";
                
            $result = _db($relationshipData['db_l'])->query($q,FDATABASE_FETCHMODE_ASSOC);
            while (false != ($unsortedParent = $result->fetchRow(FDATABASE_FETCHMODE_ASSOC))) {
                $this->data['o_'.$unsortedParent[$relationshipData['table_f'].'_id'] ]
                    ->$loadAttribute(new $relationshipData['object_f']($unsortedParent));
            }
        }
        
        // Pair
        
        // Peer
        if ($relationshipData['role_l'] == "MM") {
            $keys = $this->getKeys($relationshipData['key_l']);
            
            // START HERE
            // Need to add a special attribute to relationshipData (in FModel, below) for this type so that we can
            // capture all 3 relevant tables: table_l table_f and table_lookup. Then, we need to
            // do a join on the tables to get the complete information, taking care to account for
            // the case in which table_l and table_f are the same table.
        }
        
        // Child
        if ($relationshipData['role_l'] == "1M") {
            $keys = $this->getKeys($indexKey);
            $q = "SELECT * FROM `{$relationshipData['table_l']}` WHERE `{$relationshipData['column_l']}` IN (\"".implode("\",\"",$keys)."\")";
            $result = _db($relationshipData['db_l'])->query($q,FDATABASE_FETCHMODE_ASSOC);
            $loadAttribute = "load{$attribute}";
            while (false != ($unsortedChild = $result->fetchrow(FDATABASE_FETCHMODE_ASSOC))) {
                $parentId = $unsortedChild[$relationshipData['column_l'] ];
                $this->data["o_{$parentId}"]->$loadAttribute(new $relationshipData['object_f']($unsortedChild));    
            }
        }
        
        return $this;
    }
    
    private function getKeys($keyAttribute = 'id') {
        $fn   = "get{$keyAttribute}";
        $keys = array();
        foreach ($this->data as $o) {
            $key  = $o->$fn(true); // bIdOnly = true;
            $keys[$key] = $key; 
        }
        return $keys;
    }
    
    private function getRealId() {
        return $this->objectTypeTable . '_id';
    }


    public function get() {
        $num_args = func_num_args();
        switch ($num_args) {
            case 0:
                // Return the collection object, as-is
                break;
            case 1:
                // Get a single object using the provided objId
                $v = func_get_arg(0);
                $this->data = array($this->getSingleObjectByObjectId($v));
                break;
            case 2:
                // Get a single object using the provided key/value pair
                $k = func_get_arg(0);
                $v = func_get_arg(1);
                $this->data = array($this->getSingleObjectByAttribute($k,$v));
                break;
            default:
                throw new FException("Unexpected number of arguments for FObjectCollection::get()");
                return false;
        }
        return $this;

    }

    // This will return at most one object where objId=id
    private function getSingleObjectByObjectId( $id ) {
        $this->filter('id',$id);
        $result = _db()->queryRow($this->query->select(),FDATABASE_FETCHMODE_ASSOC);
        if (null == $result) { return false; } 
        else {
            $t = $this->objectType;  
            return new $t($result);
        }
    }

    // This will return at most one object where attr=val
    private function getSingleObjectByAttribute( $attr, $value ) {
        $this->filter($attr,$value);
        $result = _db()->queryRow($this->query->select(),FDATABASE_FETCHMODE_ASSOC);
        if (null == $result) { return false; }
        else {
            $t = $this->objectType;
            return new $t($result);
        }
    }
    
    private function runQuery($output = 'objects') {
        $result = _db()->queryAll($this->query->select(),FDATABASE_FETCHMODE_ASSOC);
        if (null == $result) { return false; }
        else {
            $response = array();
            $t = $this->objectType;
            foreach ( $result as $r ) {
                $response['o_'.$r[$this->objectTypeTable.'_id'] ] = new $t($r);
            }
            return $response;
        }
    }
    
}
    **/
?>