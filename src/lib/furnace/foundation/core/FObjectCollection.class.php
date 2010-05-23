<?php
class FCriteriaGroup {

    public $prior_op = null;
    public $elements = array();
    
    public function __construct($prior_op = null,$elements = null) {
        if (isset($prior_op)) {
            $this->prior_op = $prior_op;
        }
        if (isset($elements)) {
            $this->elements = $elements;
        }
    }

    public function push($element) {
        array_push($this->elements,$element);
    }

    public function pop() {
        return array_pop($this->elements);
    }
}


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
}

class FObjectCollection {

    public $objectType;
    public $baseFilter;
    public $query;

    public function __construct($objectType,$baseFilter) {
        $this->objectType = $objectType;
        $this->baseFilter = $baseFilter;
        $this->query      = new FQuery($this->objectType);
        $this->query->addConstraint($this->baseFilter);
    }
    
    protected function reset() {
        $this->query = new FQuery($this->objectType);
        $this->query->addConstraint($this->baseFilter);
        return $this;
    }

    public function output($formatter = null) {
        // Ensure that a valid formatter is available
        if (! $formatter instanceof FResultFormatter) {
            // Use the FObjectResultFormatter for $objectType by default
            $className = $this->objectType . 'ResultFormatter';
            $formatter = new $className();
        } 
        
        // Run the query to get the result (FResult object)
        $result = $this->runQuery();
        
        // Parse and return the formatted result
        return $formatter->format($result);
    }
    
    public function unique(/* variable arguments accepted */) {
        switch (func_num_args()) {
            case 1:
                $val = func_get_arg(0);
                $this->filter($val);
                $data = $this->output();
                break;
            case 2:
                $key = func_get_arg(0);
                $val = func_get_arg(1);
                $this->filter($key,$val);
                $data = $this->output();
                break;
            case 3:
                $key = func_get_arg(0);
                $val = func_get_arg(1);
                $formatter = func_get_arg(2);
                $this->filter($key,$val);
                $data = $this->output($formatter);
                break;
            default:
                throw new FObjectCollectionException(
                    "Unexpected number of arguments to FObjectCollection::unique()");
                break;
        }
        
        // Return the single matching object, or false if none match
        return (count($data) > 0) 
            ? $data[0]
            : false;
        break;
    }


    public function filter(/* variable arguments accepted */) {
        $crit = $this->computeKCV(func_get_args());
        $crit->prior_op = 'AND';
        $this->query->addConstraint($crit);
        return $this;
    }

    public function and_filter(/* variable arguments accepted */) {
        $crit = $this->computeKCV(func_get_args());
        $crit->prior_op = 'AND';
        $this->query->addConstraint($crit);
        return $this;
    }

    public function or_filter(/* variable arguments accepted */) {
        $crit = $this->computeKCV(func_get_args());
        $crit->prior_op = 'OR';
        $this->query->addConstraint($crit);
        return $this;
    }

    public function and_group() {
        $crit = new FCriteriaGroup("AND");
        $this->query->addConstraint($crit);
        return $this;
    }

    public function or_group() {
        $crit = new FCriteriaGroup("OR");
        $this->query->addConstraint($crit);
    }

    public function end_group() {
        $this->query->closeConstraintGroup(); 
        return $this;           
    }
    
    public function limit($limit, $offset = 0) {
        $this->query->setLimit($limit,$offset);
        return $this;
    }
    
    public function orderBy($var,$order='asc') {
        if (func_num_args() == 1 && strtoupper($var) == "RANDOM") {
            $this->query->orderBy(null,'RANDOM');
        } else {
            $this->query->orderBy($var,$order);
        }
        return $this;
    }
    
    public function count() {
        $this->query->countOnly();
        return $this->output(new FSingleValueResultFormatter());
    }
    
    public function first($formatter = null) {
        $result = $this->limit(1)->output($formatter);
        return $result[0];
    }
    
    
    public function get( /* variable arguments accepted */ ) {
        $this->reset();
        $num_args = func_num_args();
        switch ($num_args) {
            case 0:
                // Return the collection object as-is
                break;
            case 1:
                // Get a single object using the provided id
                $id = func_get_arg(0);
                $this->filter('id',$id);
                $data = $this->output();
                // Return the object, or false if none match
                return (count($data) > 0) 
                    ? $data[0]
                    : false;
                break;
            case 2:
                // Get a single object using the provided id and the provided formatter
                $id             = func_get_arg(0);
                $formatterClass = func_get_arg(1);
                $this->filter('id',$id);
                
                if (!class_exists($formatterClass)) {
                    require_once(FF_LIB_DIR . 
                    	"/furnace/datasources/formatters/{$formatterClass}.class.php");
                }
                $data = $this->output(new $formatterClass);
                // Return the object, or false if none match
                return (count($data) > 0) 
                    ? $data[0]
                    : false;
                break;
            default:
                throw new FObjectCollectionException(
                	"Unexpected number of arguments to FObjectCollection::get()");
                return false;
        }
        
        return $this;
    }
    
    
    protected function runQuery($source = 'default') {
        
        // Load the appropriate datasource driver
        $driver = _db($source);
        
        // Execute the FQuery
        return $driver->query($this->query);
    }
    

    protected function computeKCV($args) {
        switch (count($args)) {
            case 1:
                // Process a raw FCriteria object
                return new FCriteria(null,'id',$args[0],'=');
                break;
            case 2:
                // Process a key=val filter
                return new FCriteria(null,$args[0],$args[1]);
                break;
            case 3:
                // Process a key,comp,val filter
                return new FCriteria(null,$args[0],$args[2],$args[1]);
                break;
            default:
                throw new FObjectCollectionException(
                    "Unexpected number of arguments to FObjectCollection::computeKCV()");
                break;
        }
    }
}
?>