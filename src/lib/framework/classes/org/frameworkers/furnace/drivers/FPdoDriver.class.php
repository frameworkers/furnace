<?php
namespace org\frameworkers\furnace\drivers;
use PDO;
use org\frameworkers\furnace as Furnace;
use org\frameworkers\furnace\exceptions as Exceptions;
use org\frameworkers\furnace\query as Query;
/**
 * Furnace Rapid Application Development Framework
 * 
 * @package   Furnace
 * @subpackage Datasources
 * @copyright Copyright (c) 2008-2010, Frameworkers.org
 * @license   http://furnace.frameworkers.org/license
 *
 */

/**
 * FPdoDriver
 * 
 * Provides an implementation of {@link FDatasourceDriver} for the
 * PDO database abstraction layer.
 * 
 */

class FPdoDriver extends FDatasourceDriver {
    
    /**
     * The connection handle to the database
     * @var pdo
     */
    private $pdo;
    
    private $query_tables = array();
    private $query_fields = array();
    private $query_values = array();
    private $query_aliases= array();
    private $query_conditions = array();
    private $query_joins  = array();
    private $query_meta   = array(
        'objectType'      => '',
        'objectTypeTable' => '',
        'currentTable' => '',
        'limit'        => '',
        'offset'       => '0',
        'groupBy'      => '',
        'orderBy'      => ''
    );
    
    // The last FQuery object interpreted
    private $query;
    

    /**
     * Initialize a connection to the data source
     * 
     * @param string $dsn  The data source name string
     * 
     */
    public function init($options = array()) {
    	try {
	        $this->pdo = new PDO(
	        	"{$options['driver']}:host={$options['host']};dbname={$options['database']}",
	        	$options['username'],
	        	$options['password']);
	        // Throw an exception on errors
	        $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    	} catch (PDOException $e) {
    		die($e->getMessage());
    	}
    }
    
    
    public function query($query) {
    	// Store the raw FQuery object
    	$this->query = $query;
    	
        // Parse the FQuery object
        $this->interpretFQuery($query);
        
        // Build an SQL query from the information
        $q = $this->buildQuery();
          
        // Run the actual query
        try {
        	_log()->log("FPdoDriver: {$q}",FF_DEBUG);
        	$r = $this->pdo->query($q);
        	$r->setFetchMode(PDO::FETCH_ASSOC);
        	
        	// Construct an FResult object from the results
			$result = new Query\Result($this);
        	while ($row = $r->fetch()) {
        		$result->add($row);
        	}
        	// Return the FResult object
			return $result;	
        } catch (PDOException $e) {
        	_log()->log("FPdoDriver: {$e->getMessage()}",FF_ERROR);
        	throw new Exceptions\DatabaseException($e->getMessage(),"\"{$q}\"");
        }
    }
    
    public function lastInsertId($options = array()) {

		$r = $this->pdo->lastInsertId();
		return $r;		
    }
    
    public function quote($unsafeValue) {
    	return $this->pdo->quote($unsafeValue);
    }
    
    public function exec($query) {}
    
    public function close($options = array()) {
    	$this->pdo = null;
    }
    
	public function rawExec($query,$options = array()) {
		try {
			_log()->log("FPdoDriver: {$query}",FF_DEBUG);
        	$r = $this->pdo->exec($query);
        	return $r;
		} catch (PDOException $e) {
			_log()->log("FPdoDriver: {$e->getMessage()}",FF_ERROR);
        	throw new Exceptions\DatabaseException($e->getMessage(),"\"{$q}\"");
		}
    }
    
    public function rawQuery($query,$options = array()) {
    	
    	return $this->pdo->query($query);

    	/*
        $mode = (isset($options['mode']) && $options['mode'] != 'assoc')
            ? PDO::FETCH_NUM
            : PDO::FETCH_ASSOC;
            
        $type = isset($options['type']) 
            ? $options['type']
            : 'all';

        try {
	        _log()->log("Mdb2Driver: {$query}",FF_DEBUG);
	        $r = new Query\Result($this);
	        switch (strtolower($type)) {
	            case 'one':	// Return only one value from the 1st row
	                $result = $this->pdo->query($query);
	                $result->setFetchMode($mode);
	                if ($result->rowCount() > 0) {
	                	$row = $result->fetch();
	                	if ($mode == PDO::FETCH_NUM) {
	                		$r->add(array(0 => $row[0]));
	                	} else {
	                		$keys = array_keys($row);
	                		$r->add(array($keys[0] => $row[$keys[0]]));
	                	}
	                }
	                break;
	            case 'row': // Return only the 1st row from the result
	                $result = $this->pdo->query($query);
	                $result->setFetchMode($mode);
	                if ($result->rowCount() > 0) {
	                	$row = $result->fetch();
	                	$r->add($row);
	                }
	                break;
	            default:	// Return all rows from the result
	                $result = $this->pdo->query($query);
	                $result->setFetchMode($mode);
	                while ($row = $result->fetch()) {
	                	$r->add($row);
	                }
	                break;
	        }    
	        return $r;

        } catch (PDOException $e) {
        	_log()->log("FPdoDriver: {$e->getMessage()}",FF_ERROR);
        	throw new Exceptions\DatabaseException($e->getMessage(),"\"{$q}\"");
        }
        */
    }
    
    /**
     * Interpret an FQuery object
     * 
     * Convert an {@link FQuery} object into a set of tables, fields,
     * conditions, limits, and anything else needed to
     * construct a representational SQL query from the object 
     * 
     * @param  FQuery $query The {@link} FQuery object to interpret
     * @return void
     */
    protected function interpretFQuery($query) {
        
        $this->resetQueryParams();    // Starting fresh with a new query
        $this->query_meta = $query->getMetadata();
        $this->query_meta['objectType']      = Furnace\Furnace::standardizeName($query->getTargetObjectType());
        $this->query_meta['objectTypeTable'] = Furnace\Furnace::standardizeTableName($query->getTargetObjectType());
        
        // User table name override
        if ('\org\frameworkers\furnace\core\models\User' == $query->getTargetObjectType()) {
        	$this->query_meta['objectTypeTable'] = 'users';
        }
        
        // Obtain the query payload
        $payload = $query->getPayload();
        
        // At a minimum, the target object's table will be required
        $this->addTable($this->query_meta['objectTypeTable']);
        
        // Each constraint group (FCriteriaGroup) represents a bracketed
        // conditional clause
        // Each constraint element (FCriteria) represents a condition
        // The necessary tables are extracted as needed based on the 
        // model
        $payload = $query->getPayload();
        foreach ($payload as $qg) {
            $this->processFCriteriaGroup($qg);
        }
    }
    
    protected function processFCriteriaGroup($group) {
        // Start a bracketed conditional clause
        $this->startConditionGroup($group->prior_op);
            
        // Process the group's elements
        foreach ($group->elements as $e) {
            // If the element is an FCriteriaGroup object, recurse
            if ($e instanceof Query\CriteriaGroup) {
                $this->processFCriteriaGroup($e);
            } 
            
            // Otherwise, process an individual FCriteria element
            else {
                $otClass     = $this->query_meta['objectType'];
                $otTableName = $this->query_meta['objectTypeTable'];
                $otModel     = "{$otClass}Model"; 
                
                // Obtain the real (fully-qualified) key, and remote key if it exists
                list($realK,$remoteK) = $this->parseKey($e->field);
                	
                $filterKeyTable = $this->query_meta['objectTypeTable'];
                
                // Join the nodes table (skip if Core\models\User since class !inherited from Object)
                if ('\org\frameworkers\furnace\core\models\User' != $this->query->getTargetObjectType()) {
                	$this->addTable('nodes');
                	$this->addCondition('AND','`nodes`.`nid`=`'.$filterKeyTable.'`.`'.$filterKeyTable.'_id`');
                }
                
                /*
                if (in_array($realK,array("username","password","emailAddress"))) {
                    $filterKeyTable = "app_accounts";
                    $this->addTable('app_accounts');
                    $this->addCondition('AND','`app_accounts`.`faccount_id`=`'.$this->query_meta['objectTypeTable'].'`.`faccount_id`');
                } else {
                    $filterKeyTable = $this->query_meta['objectTypeTable'];
                }
                */
                
                // If the requested key represents a local attribute, then 
                // the request can be satisfied by adding a simple condition to
                // the query object:
            	
                // Special case for looking up node information
             	if (in_array($realK,array("nid","uid", "created","modified")) 
             		&& '\org\frameworkers\furnace\core\models\User' != $this->query->getTargetObjectType()) {
                	
                	$this->addCondition($e->prior_op,
                		"`nodes`.`{$realK}`{$e->comp}'".addslashes($e->value)."' ");
                } 
                
                // Special case for looking up user information
                else if (in_array($realK,array("uid","users_id","created","modified"))
                	&& '\org\frameworkers\furnace\core\models\User' == $this->query->getTargetObjectType()) {
                	
                	if ($realK == "users_id") {$realK = "uid";}
                	$this->addCondition($e->prior_op,
                		"`users`.`{$realK}`{$e->comp}'".addslashes($e->value)."' ");
                }
                
                // Local attribute information
                else if ($e->field == 'id' || isset(_model()->objectTypes[$otClass]['fields'][$realK])) {
                    $this->addCondition($e->prior_op, 
                    	"`{$filterKeyTable}`.`{$realK}`{$e->comp}'".addslashes($e->value)."' ");
                }
                
                // External reference information
                else {
                	if ($info = _model()->objectTypes[$otClass]['references'][$realK]) {
						if ($info['cols'][1] == '-') {
							// Internal reference
							$this->addCondition('AND',"`{$info['table']}`.`{$info['cols'][0]}` = '{$e->value}'");	
						} else if (isset($info['cols'])) {
							// External Lookup Table
							$this->addTable($info['table']);  

							// Determine the column mapping
							list($tableBase,$ignore) = explode('_',$info['table']);
							list($colBase,$ignore)   = explode('_',$info['cols'][0]);
							$refKey = ('users' == $otTableName) 
								? 'uid'
								: "{$otTableName}_id";
							// v v v IS THIS 'IF' NECESSARY? v v v
							if ($tableBase == $colBase) { 
                				$this->addCondition('AND',"`{$info['table']}`.`{$info['cols'][1]}` = '{$e->value}'");
                				$this->addCondition('AND',"`{$info['table']}`.`{$info['cols'][0]}` = `{$filterKeyTable}`.`{$refKey}`");	
							} else {
								$this->addCondition('AND',"`{$info['table']}`.`{$info['cols'][1]}` = '{$e->value}'");
                				$this->addCondition('AND',"`{$info['table']}`.`{$info['cols'][0]}` = `{$filterKeyTable}`.`{$refKey}`");	
							}
						}		
                	} else {
                		throw new Exceptions\DatabaseException("Malformed query: unknown reference '{$e->field}'");
                	}
                }

                /*
                else {
                    $fn = "get{$realK}Info";
                    if (is_callable(array($otModel,$fn))) {
                        $info = _model()->$otClass->$fn();
                        if ($info['role_l'] == 'M1') {
                            // Filtering on a Parent relation
                            // If the remote key (rk) === false, it means that no 
                            // remote key was specified, and that we should default to the 
                            // id of the remote object
                            if (false === $remoteK) {
                                $remoteK = "{$info['table_l']}_id";
                            }
                            
                            $this->addTable($info['table_l'],array($remoteK));
                            
                            // FACCOUNT special handling
                            if ($info['base_f'] == "FAccount" && 
                               (($remoteK == "username") || ($remoteK=="password") || ($remoteK=="emailAddress"))) {
                               // Join app_accounts
                               $this->addTable('app_accounts');
                               $this->addCondition($e->prior_op, "( `app_accounts`.`{$remoteK}`{$e->comp} '".addslashes($e->value)."' AND `app_accounts`.`faccount_id`=`{$info['table_l']}`.`faccount_id`  ");
                            } else {
                                $this->addCondition($e->prior_op,
                                    "(`{$info['table_l']}`.`{$remoteK}` {$e->comp} '".addslashes($e->value)."' ");
                            } 
                            
                            $this->addCondition('AND',
                            	"`{$info['table_f']}`.`{$info['column_f']}`=`{$info['table_l']}`.`{$info['table_l']}_id`)");
                            
                        } else 
                        if ($info['role_l'] == 'MM') {
                            // Filtering on a Peer relation
                            // If the remote key (rk) === false, it means that no 
                            // remote key was specified, and that we should default to using the 
                            // id attribute of the remote object(s)
                            if (false === $remoteK) {
                                $remoteK = "{$info['table_l']}_id";
                            }
                            
                            // In a peer relationship, the linkage between objects is indirect, i.e.: 
                            // through their respective lookup table, so add both the foreign and the
                            // lookup tables (local has been added before this stage):
                            $this->addTable($info['table_l'],array($remoteK));
                            $this->addTable($info['table_m']);
                            
                            //TODO:FAccount handling, note: watch parens ()
                            
                            // 1) The linkage between the lookup table and the local object:
                            $this->addCondition('AND',"(`{$info['table_f']}`.`{$info['column_f']}`=`{$info['table_m']}`.`{$info['column_f']}`");
                            
                            // 2) The linkage between the lookup table and the foreign object:
                            $this->addCondition('AND',"`{$info['table_l']}`.`{$info['column_l']}`=`{$info['table_m']}`.`{$info['column_l']}`");
                           
                            // 3) Limit the results to those pertaining to the current object
                            // This has been handled already before reaching the 'filter' stage

                            // 4) Limit the results to those foreign objects whose remote key matches v
                            $this->addCondition('AND',"`{$info['table_l']}`.`{$remoteK}`{$e->comp}'".addslashes($e->value)."')");
                            
                            
                        } else 
                        if ($info['role_l'] == '1M') {
                            // Filtering on a Child relation
                            // If the remote key (rk) === false, it means that no 
                            // remote key was specified, and that we should default to the 
                            // id of the remote object
                            if (false === $remoteK) {
                                $remoteK = "{$info['table_l']}_id";
                            }
                            
                            // Add the remote object's table
                            $this->addTable($info['table_l'],array($remoteK));
                            
                            // FACCOUNT special handling
                            if ($info['base_f'] == "FAccount" && 
                               (($remoteK == "username") || ($remoteK=="password") || ($remoteK=="emailAddress"))) {
                               // Join app_accounts
                               $this->addTable('app_accounts');
                               $this->addCondition($e->prior_op, "( `app_accounts`.`{$remoteK}`{$e->comp} '".addSlashes($e->value)."' AND `app_accounts`.`faccount_id`=`{$info['table_l']}`.`faccount_id`  ");
                            } else {
                                $this->addCondition($e->prior_op,
                                    "(`{$info['table_l']}`.`{$remoteK}` {$e->comp} '".addslashes($e->value)."' ");
                            } 
                            
                            $this->addCondition('AND',
                            	"`{$info['table_f']}`.`{$info['table_f']}_id`=`{$info['table_l']}`.`{$info['column_l']}`)");
                        }
                        
                    } else {
                    	throw new Exceptions\DatabaseException("Malformed query: unknown attribute '{$e->field}'");
                    } 
                      
                }
                */
            }
        }
        
        // End the bracketed conditional clause
        $this->endConditionGroup();
    }

    
    protected function parseKey($k) {
        // Discover remote key for extended filter. An extended filter is one that filters
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
        
        
        // If k == 'id' it needs to be expanded to its full schema equivalent
        //   of '<tablename>_id'
        $otTableName = $this->query_meta['objectTypeTable'];
        $origK       = $k;
        if ($k == 'id') { $k = "{$otTableName}_id"; }
        
        return array($k,$rk);
    }
    
    
    
    /**
     * Build an SQL query from a set of parameters
     * 
     * Takes the parameters generated by {@link interpretFQuery} 
     * and constructs an SQL query representation.
     * 
     * @param array $params The parameters to use when making the query
     * @return string  The resulting SQL query
     * @see interpretFQuery
     */
    protected function buildQuery() {
        
        // Build Query String
        $qstring = "SELECT ";
        
        $fieldList = array();
        
        if (isset($this->query_meta['countOnly']) && $this->query_meta['countOnly'] === true) {
            $qstring .= "COUNT(*) ";     
        } else {

            // Field List
            foreach ($this->query_tables as $table) {
                if (!is_array($this->query_fields[$table])) {
                    $fieldList[] =  " `{$table}`.* ";
                } else {
                    foreach ($this->query_fields[$table] as $field) {
                        if (is_array($field)) { // Handle an aliased field
                            $fieldList[] = " `{$table}`.`{$field[0]}` AS `{$field[1]}` ";
                        } else {
                            $fieldList[] = " `{$table}`.`{$field}` ";
                        }
                    }
                }
            }
            foreach ($this->query_joins as $j) {
                if (!is_array($this->query_fields[$j['target']])) {
                    $fieldList[] = " `{$j['target']}`.* ";
                } else {
                    foreach ($this->query_fields[$j['target']] as $field) {
                        if (is_array($field)) { // Handle an aliased field
                            $fieldList[] = " `{$table}`.`{$field[0]}` AS `{$field[1]}` ";
                        } else {
                            $fieldList[] = " `{$table}`.`{$field}` ";
                        }
                    }
                }
            }
        }

        $qstring .= implode(',',$fieldList) . " FROM ";

        // Table List
        $qstring .= '`' . implode('`,`',$this->query_tables) .'`';

        // JOIN Clauses, if any
        if (sizeof($this->query_joins) > 0) {
            $joinArray = array();
            foreach ($this->query_joins as $j) {
                $joinArray[] = "{$j['joinType']} `{$j['target']}` ON {$j['on']} ";
            }
            $qstring .= ' '.implode(' ',$joinArray).' ';
        }
        
        // WHERE Clauses, if any
        if (sizeof($this->query_conditions) > 0){
            $qstring   .= " WHERE ";
            $groupStart = false;
            
            for($i = 0, $ccount = count($this->query_conditions); $i < $ccount; $i++) {
                // Handle a condition group start
                if ($this->query_conditions[$i]['cond'] == '(') {
                    // Check for an empty group
                    if (isset($this->query_conditions[$i+1]) && $this->query_conditions[$i+1]['cond'] == ')') {
                        $qstring  .= (($i == 0) ? '' : $this->query_conditions[$i]['pOp'] ) . ' ( 1 ) ';
                        $i++; // consume the group closer
                        continue;
                    }
                    $qstring .= (($i == 0) ? '' : $this->query_conditions[$i]['pOp'] ) . ' (';
                    $groupStart = true;
                }
                // Handle a condition group end
                else if ($this->query_conditions[$i]['cond'] == ')') {
                    $qstring .= ') ';
                }
                // Handle a basic condition
                else {
                    $qstring .= (null == $this->query_conditions[$i]['pOp'])
                        ? (($i > 0 && !$groupStart) ? " AND " : "" )    . " {$this->query_conditions[$i]['cond']} "
                        : $this->query_conditions[$i]['pOp' ] . " {$this->query_conditions[$i]['cond']} ";
                    if ($groupStart) $groupStart = false;
                }
            }

        }

        if (isset($this->query_meta['orderBy']) && strlen($this->query_meta['orderBy']) > 0) {
            $qstring .= $this->query_meta['orderBy'] . " ";
        }
        
        if ($this->query_meta['limit'] > 0) {
        	$qstring .= " LIMIT {$this->query_meta['offset']},{$this->query_meta['limit']} ";
        }

        // Return the SQL string representation of the query components
        //echo "\r\n<br/>query: {$qstring}\r\n";
        return $qstring;      
    }
    
	/**
     * Reset the internal query parameter data structure
     * 
     * @return void
     */
    protected function resetQueryParams() {
        $this->query_tables  = array();
        $this->query_fields  = array();
        $this->query_values  = array();
        $this->query_aliases = array();
        $this->query_conditions = array();
        $this->query_joins   = array();
        $this->query_meta    = array(
            'objectType'   => '',
            'currentTable' => '',
            'limit'        => '',
            'offset'       => '0',
            'groupBy'      => '',
            'orderBy'      => ''
        );
    }
    
    protected function addTable($name,$fields = '*') {
        // hashing on the name prevents the same table from being added 2x
        $this->query_tables[$name] = $name;
        $this->query_fields[$name] = $fields;
    }
    
    protected function addFields($table,$fields) {
        if (isset ($this->query_fields[$table])) {
            $this->query_fields[$table] = (is_array($this->query_fields['table']))
                ? array_merge($this->query_fields[$table],$fields)
                : $fields;
        }
    }

    protected function addCondition($previousOp, $condition) {
        // If this is the very first condition, or the first in a group,
        // the pOp is *always* null
        $current = end($this->query_conditions);
        if (!$current || ($current && $current['cond'] == '(')) {
            $previousOp = null;
        }
        
        $this->query_conditions[] = array("pOp" => $previousOp, "cond" => $condition);
    }
    
    protected function startConditionGroup($previousOp) {
        $this->query_conditions[] = array("pOp" => $previousOp,"cond" => "(");
    }
    
    protected function endConditionGroup() {
        $this->query_conditions[] = array("pOp" => null,"cond" => ")");
    }
    
    protected function addJoin($joinType,$target,$on,$fields = '*') {
        $this->query_joins[] = array('joinType' => $joinType,
                               'target'   => $target,
                               'on'       => $on);                    
        $this->query_fields[$target] = $fields;
    }
    
    public function setLimit($limit,$offset) {
        $this->query_meta['limit']  = $limit;
        $this->query_meta['offset'] = $offset;
    }
    
    public function orderBy($var,$order) {
        if (strtoupper($order) == "RANDOM") {
            $this->query_meta['orderBy'] = "ORDER BY RAND() ";
        } else {
        	if (in_array($var,array("uid","nid","created","modified"))) {
        		$this->query_meta['orderBy'] = "ORDER BY `nodes`.`{$var}` " . strtoupper($order). " ";
        	} else {
            	$this->query_meta['orderBy'] = "ORDER BY `{$var}` ".strtoupper($order)." ";
        	}
        }
    }
}
?>