<?php
namespace org\frameworkers\furnace\query;
use org\frameworkers\furnace as Furnace;
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
 * Query
 * 
 * Provides a standard mechanism for requesting information
 * from one or more application data sources.
 * 
 */
class Query {
    /**
     * The model object type to return in the {@link} Result
     * @var string
     */
    protected $targetObjectType;
    
    /**
     * The body of the query, composed of an array of {@link CriteriaGroup}
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
        $this->payload = array(new CriteriaGroup());
        if (isset($baseFilter) && $baseFilter instanceof CriteriaGroup) {
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
     * either a single {@link Criteria} object, or an {@link CriteriaGroup}
     * object, which will get appended to the query payload.
     * 
     * If the provided constraint is an {@link Criteria} object, the object
     * will be appended to the currently active {@link CriteriaGroup} in the 
     * payload stack.
     * 
     * If the provided constraint is an {@link CriteriaGroup} object, the
     * currently active {@link CriteriaGroup} in the payload stack will be 
     * 'closed' and the provided object will become the new active
     * {@link CriteriaGroup} in the payload stack. Future {@link Criteria}
     * objects will be appended here.
     * 
     * @param unknown_type $c
     */
    public function addConstraint($c) {
        if ($c instanceof CriteriaGroup) {
            $this->payload->push($c);
        } else if ($c instanceof Criteria) {
            $elmt =& current($this->payload);
            $elmt->push($c);
        }
    } 
    
    /**
     * Limit the number (and specify an offset) of results returned
     * 
     * When constructing an {@link Query} object against a large
     * data set, it is often the case that more matches may exist
     * than are desired at one time. This function provides a means to add limit
     * data to the query, allowing for such techniques as pagination.
     * 
     * The information provided to this function is wrapped in the {@link metadata}
     * attribute of this class. The actual computation of limits and offsets
     * is handled by the {@link DatasourceDriver} object interpreting this {@link Query}.
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
    	// Determine the table in which to look up the filter key
        if (in_array($var,array("uid","nid","created","modified"))) {
        	$filterKeyTable = 'nodes';
        } else {
        	$filterKeyTable = Furnace\Furnace::standardizeTableName($this->targetObjectType);
        	$var = ('id' == $var) ? "{$filterKeyTable}_{$var}" : $var;
        }
        
    	
        if (strtoupper($order) == "RANDOM") {
            $this->metadata['orderBy'] = "ORDER BY RAND() ";
        } else {
            $this->metadata['orderBy'] = "ORDER BY `{$filterKeyTable}`.`{$var}` ".strtoupper($order)." ";
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