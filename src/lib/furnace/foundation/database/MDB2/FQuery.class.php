<?php

class FQuery {
    var $tables = array();		// The tables this query operates on
    var $fields = array(); 		// The fields this query operates on
    var $values = array();		// The values corresponding to the fields;
    var $aliases = array(); 	// The aliases defined in this query ('AS' clauses)
    var $conditions = array();	// The conditionals defined ('WHERE' clauses)
    var $joins   = array();     // The joins defined ('JOIN' clauses)

    var $currentTable = '';	// The working table (prepended to field names)

    var $groupBy = '';		// The 'GROUP BY' clause)	//TBD
    var $orderBy = '';		// The 'ORDER BY' clause)	//TBD

    var $limit  = '';		// The limit value		    //TBD
    var $offset = '';		// The offset value		    //TBD


    public function __construct() {
         
    }

    public function addTable($name) {
        // hashing on the name prevents the same table from being added 2x
        $this->tables[$name] = $name;
    }

    public function addCondition($previousOp, $condition) {
        $this->conditions[] = array("pOp" => $previousOp, "cond" => $condition);
    }
    
    public function addJoin($joinType,$target,$on) {
        $this->joins[] = "{$joinType} {$target} ON {$on} ";
    }
    
    public function setLimit($limit,$offset) {
        $this->limit  = $limit;
        $this->offset = $offset;
        _db()->setLimit($this->limit,$this->offset);
    }
    
    public function orderBy($var,$order) {
        if (strtoupper($order) == "RANDOM") {
            $this->orderBy = "ORDER BY RANDOM(`{$var}`) ";
        } else {
            $this->orderBy = "ORDER BY `{$var}` ".strtoupper($order)." ";
        }
    }

    public function select() {
        // Build Query String
        $qstring = "SELECT ";

        // Field List
        if (sizeof($this->fields) == 0) { $qstring .= " * "; }
        else {
            for ($i = 0; $i < sizeof($this->fields); $i++) {
                // Check for aliases
                $aliasValue = '';
                foreach($this->aliases as $f=>$a){
                    if ($f == $this->fields[$i]){
                        $aliasValue = $a;
                        break;
                    }
                }
                $fieldValue = $this->fields[$i] . ((empty($aliasValue))?  "" : " AS $aliasValue");
                if ($i < sizeof($this->fields) - 1) {
                    $qstring .= $fieldValue . ', ';
                } else {
                    $qstring .= $fieldValue;
                }
            }
        }

        $qstring .= " FROM ";

        // Table List
        $qstring .= '`' . implode('`,`',$this->tables) .'`';

        // JOIN Clauses, if any
        if (sizeof($this->joins) > 0) {
            $qstring .= ' '.implode(' ',$this->joins).' ';
        }
        
        // WHERE Clauses, if any
        if (sizeof($this->conditions) > 0){
            $qstring .= " WHERE ";
            for($i = 0, $ccount = count($this->conditions); $i < $ccount; $i++) {
                $qstring .= (null == $this->conditions[$i]['pOp'])
                ? (($i > 0) ? " AND " : "" )    . " {$this->conditions[$i]['cond']} "
                : $this->conditions[$i]['pOp' ] . " {$this->conditions[$i]['cond']} ";
            }
        }

        if (strlen($this->orderBy) > 0) {
            $qstring .= $this->orderBy . " ";
        }
        
// LIMIT Queries in MDB2 are handled via a separate call to MDB2->setLimit()
// if ($this->limit > 0) {
//     $qstring .= " LIMIT {$this->limit},{$this->offset} ";
// }

        // Return the SQL string representation of the query components
        return $qstring;
    }    
}
?>