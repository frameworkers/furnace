<?php
namespace org\frameworkers\furnace\query;

class CriteriaGroup {

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