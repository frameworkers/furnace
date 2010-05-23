<?php
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package    Furnace
 * @subpackage Datasources
 * @copyright  Copyright (c) 2008-2010, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

/**
 * FQuery
 * 
 * Provides a standard mechanism for requesting information
 * from one or more application data sources.
 * 
 */
class FQuery {
    
    protected $targetObjectType;
    protected $payload;
    protected $metadata;
    
    public function __construct($targetObjectType,$baseFilter = null) {
        $this->targetObjectType = $targetObjectType;
        $this->payload = array(new FCriteriaGroup());
        if (isset($baseFilter) && $baseFilter instanceof FCriteriaGroup) {
            $baseFilter->prior_op = null;
            $this->payload->push($baseFilter);
        }
        $this->metadata = array();
    }
    
    public function addConstraint($c) {
        if ($c instanceof FCriteriaGroup) {
            $this->payload->push($c);
        } else if ($c instanceof FCriteria) {
            $elmt =& current($this->payload);
            $elmt->push($c);
        }
    } 
    
    public function closeConstraintGroup() {
        // Collapses an FCriteriaGroup object into a lower one
        $group = array_shift($this->payload);
        $elmt  =& current($this->payload->elements);
        $elmt->push($c);
    } 
    
    public function setLimit($limit,$offset) {
        $this->metadata['limit']  = $limit;
        $this->metadata['offset'] = $offset;
    }
    
    public function orderBy($var,$order) {
        if (strtoupper($order) == "RANDOM") {
            $this->metadata['orderBy'] = "ORDER BY RAND() ";
        } else {
            $this->metadata['orderBy'] = "ORDER BY `{$var}` ".strtoupper($order)." ";
        }
    }
    
    public function countOnly() {
        $this->metadata['countOnly'] = true;
    }
    
    public function getTargetObjectType() {
        return $this->targetObjectType;
    }

    public function getPayload() {
        return $this->payload;
    }
    
    public function getMetadata() {
        return $this->metadata;
    }
}
?>