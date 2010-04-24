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

    public function addTable($name,$fields = '*') {
        // hashing on the name prevents the same table from being added 2x
        $this->tables[$name] = $name;
        $this->fields[$name] = $fields;
    }
    
    public function addFields($table,$fields) {
        if (isset ($this->fields[$table])) {
            $this->fields[$table] = (is_array($this->fields['table']))
                ? array_merge($this->fields[$table],$fields)
                : $fields;
        }
    }

    public function addCondition($previousOp, $condition) {
        if (count($this->conditions) == 0 ) {
            // For the first condition, pOp is *always* null.
            $this->conditions[] = array("pOp" => null, "cond" => $condition);
        } else {
            $this->conditions[] = array("pOp" => $previousOp, "cond" => $condition);
        }
    }
    
    public function startConditionGroup($previousOp) {
        $this->conditions[] = array("pOp" => $previousOp,"cond" => "(");
    }
    public function endConditionGroup() {
        $this->conditions[] = array("pOp" => null,"cond" => ")");
    }
    
    public function addJoin($joinType,$target,$on,$fields = '*') {
        $this->joins[] = array('joinType' => $joinType,
                               'target'   => $target,
                               'on'       => $on);                    
        $this->fields[$target] = $fields;
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
        $fieldList = array();
        foreach ($this->tables as $table) {
            if (!is_array($this->fields[$table])) {
                $fieldList[] =  " `{$table}`.* ";
            } else {
                foreach ($this->fields[$table] as $field) {
                    if (is_array($field)) { // Handle an aliased field
                        $fieldList[] = " `{$table}`.`{$field[0]}` AS `{$field[1]}` ";
                    } else {
                        $fieldList[] = " `{$table}`.`{$field}` ";
                    }
                }
            }
        }
        foreach ($this->joins as $j) {
            if (!is_array($this->fields[$j['target']])) {
                $fieldList[] = " `{$j['target']}`.* ";
            } else {
                foreach ($this->fields[$j['target']] as $field) {
                    if (is_array($field)) { // Handle an aliased field
                        $fieldList[] = " `{$table}`.`{$field[0]}` AS `{$field[1]}` ";
                    } else {
                        $fieldList[] = " `{$table}`.`{$field}` ";
                    }
                }
            }
        }
        
//        if (sizeof($this->fields) == 0) { $qstring .= " * "; }
//        else {
//            for ($i = 0; $i < sizeof($this->fields); $i++) {
//                // Check for aliases
//                $aliasValue = '';
//                foreach($this->aliases as $f=>$a){
//                    if ($f == $this->fields[$i]){
//                        $aliasValue = $a;
//                        break;
//                    }
//                }
//                $fieldValue = $this->fields[$i] . ((empty($aliasValue))?  "" : " AS $aliasValue");
//                if ($i < sizeof($this->fields) - 1) {
//                    $qstring .= $fieldValue . ', ';
//                } else {
//                    $qstring .= $fieldValue;
//                }
//            }
//        }

        $qstring .= implode(',',$fieldList) . " FROM ";

        // Table List
        $qstring .= '`' . implode('`,`',$this->tables) .'`';

        // JOIN Clauses, if any
        if (sizeof($this->joins) > 0) {
            $joinArray = array();
            foreach ($this->joins as $j) {
                $joinArray[] = "{$j['joinType']} `{$j['target']}` ON {$j['on']} ";
            }
            $qstring .= ' '.implode(' ',$joinArray).' ';
        }
        
        // WHERE Clauses, if any
        if (sizeof($this->conditions) > 0){
            $qstring   .= " WHERE ";
            $groupStart = false;
            for($i = 0, $ccount = count($this->conditions); $i < $ccount; $i++) {
                // Handle a condition group start
                if ($this->conditions[$i]['cond'] == '(') {
                    $qstring .= (($i == 0) ? '' : $this->conditions[$i]['pOp'] ) . ' (';
                    $groupStart = true;
                }
                // Handle a condition group end
                else if ($this->conditions[$i]['cond'] == ')') {
                    $qstring .= ') ';
                }
                // Handle a basic condition
                else {
                    $qstring .= (null == $this->conditions[$i]['pOp'])
                        ? (($i > 0 && !$groupStart) ? " AND " : "" )    . " {$this->conditions[$i]['cond']} "
                        : $this->conditions[$i]['pOp' ] . " {$this->conditions[$i]['cond']} ";
                    if ($groupStart) $groupStart = false;
                }
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