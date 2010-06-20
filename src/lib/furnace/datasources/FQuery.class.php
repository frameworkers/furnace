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
    /**
     * The model object type to return in the {@link} FResult
     * @var string
     */
    protected $targetObjectType;
    
    /**
     * The body of the query, composed of an array of {@link FCriteriaGroup}
     * objects that together fully express the query.
     * @var array
     */
    protected $payload;
    
    /**
     * An array of extra information about the query
     * @var array
     */
    protected $metadata;
    
    /**
     * Constructor
     * 
     * @param string $targetObjectType  The model object type to return
     * @param mixed  $baseFilter        Initial filter criteria to apply
     */
    public function __construct($targetObjectType,$baseFilter = null) {
        $this->targetObjectType = $targetObjectType;
        $this->payload = array(new FCriteriaGroup());
        if (isset($baseFilter) && $baseFilter instanceof FCriteriaGroup) {
            $baseFilter->prior_op = null;
            $this->payload->push($baseFilter);
        }
        $this->metadata = array();
    }
    
    /**
     * Add a constraint to the query
     * 
     * The principle behind Furnace queries is to begin with the entire
     * dataset and to filter down to what is desired through the consecutive
     * application of 'constraint's. This function allows the callee to provide
     * either a single {@link FCriteria} object, or an {@link FCriteriaGroup}
     * object, which will get appended to the query payload.
     * 
     * If the provided constraint is an {@link FCriteria} object, the object
     * will be appended to the currently active {@link FCriteriaGroup} in the 
     * payload stack.
     * 
     * If the provided constraint is an {@link FCriteriaGroup} object, the
     * currently active {@link FCriteriaGroup} in the payload stack will be 
     * 'closed' and the provided object will become the new active
     * {@link FCriteriaGroup} in the payload stack. Future {@link FCriteria}
     * objects will be appended here.
     * 
     * @param unknown_type $c
     */
    public function addConstraint($c) {
        if ($c instanceof FCriteriaGroup) {
            $this->payload->push($c);
        } else if ($c instanceof FCriteria) {
            $elmt =& current($this->payload);
            $elmt->push($c);
        }
    } 
    
    /*
    public function closeConstraintGroup() {
        // Collapses an FCriteriaGroup object into a lower one
        $group = array_shift($this->payload);
        $elmt  =& current($this->payload->elements);
        $elmt->push($c);
    } 
    */
    
    /**
     * Limit the number (and specify an offset) of results returned
     * 
     * When constructing an {@link FQuery} object against a large
     * data set, it is often the case that more matches may exist
     * than are desired at one time. This function provides a means to add limit
     * data to the query, allowing for such techniques as pagination.
     * 
     * The information provided to this function is wrapped in the {@link metadata}
     * attribute of this class. The actual computation of limits and offsets
     * is handled by the {@link FDatasourceDriver} object interpreting this {@link FQuery}.
     * 
     * @param integer $limit  The maximum number of results to return
     * @param integer $offset The offset within the full result set to begin counting
     */
    public function setLimit($limit,$offset) {
        $this->metadata['limit']  = $limit;
        $this->metadata['offset'] = $offset;
    }
    
    /**
     * Control the ordering of the returned results
     * 
     * 
     * 
     * @param unknown_type $var
     * @param unknown_type $order
     */
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