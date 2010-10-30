<?php
namespace org\frameworkers\furnace\query;

class Criteria {

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