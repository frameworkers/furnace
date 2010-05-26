<?php
class FDatabaseModel {
    
    private $dsn;
    private $link;
    
    private $databases;
    private $primaryDatabase;
    private $objects;
    
    
    
    public function __construct($dsn) {
        $this->dsn = $dsn;
        $this->link = new FDatabase($this->dsn);
        if ($this->link) {
            
            // Ensure that the model has all the necessary tables
            $missingTables = $this->verifyTablesExist();
            if (count($missingTables) != 0) {
                $this->createTables($missingTables);
            }    
            
            $this->loadDatabases();
            
            // Get the objects defined in the model
            $this->loadObjects();
            
            
        } else {
            throw new FDatabaseException("Could not connect to specified database");
        }   
    }
    
    public function synchronizeModelWithSchema() {
        // Verify that the expected tables and fields exist in each database
    }
    
    private function verifyTablesExist() {
        $requiredTables = array(
            array('name'=>'app_database'),
            array('name'=>'app_table'),
            array('name'=>'app_column'),
            array('name'=>'app_object'),
            array('name'=>'app_attribute')
        );
        
        $existingTables = $this->link->queryAll("SHOW TABLES");
        $missingTables  = array();
        foreach ($requiredTables as $rt) {
            $found = false;
            foreach ($existingTables as $et) {
                if ($et[0] == $rt['name']) { $found = true; break; }
            }
            if (!$found) {
                $missingTables[] = $rt['name'];
            }
        }
        
        return $missingTables;
    }
    
    private function createTables($which = array(),$bForce = false) {
        // If count($which) is 0, create all tables
        // If bForce is true, overwrite existing tables of same name
    }
    
    private function loadModelFromYml($source) {
        
    }
    
    private function loadModelFromXml($source) {
        
    }
    
    private function loadModelFromDB($dsn) {
        
    }
    
    private function exportModelToYml() {
        foreach ($this->objects as $o) {
            $yml .= $o->exportAsYml();
        }
        echo $yml;
    }
    
    private function exportModelToXml() {
        
    }
    
    private function exportModelToSql() {
        
    }
    
    /* DATABASE FUNCTIONS */
    public function loadDatabases() {
        $q = "SELECT * FROM `app_database`";
        $databaseData = $this->link->queryAll($q,FDATABASE_FETCHMODE_ASSOC);
        foreach ($databaseData as $data ){
            $this->databases[$data['database_id'] ] = $data;
            if ($data['isPrimary'] == 'Y') {
                $this->primaryDatabase = $data;
            }
        }
    }
    
    public function getDatabases() {
        return $this->databases;
    }
    
    public function getDatabase($name) {
        return isset($this->databases[$name])
            ? $this->databases[$name]
            : false;
    }
    
    public function getPrimaryDatabase() {
        return $this->primaryDatabase;
    }
    
    public function getPrimaryDatabaseName() {
        return $this->primaryDatabase['database_id'];
    }
    
    /* OBJECT FUNCTIONS */
    
    public function loadObjects() {
        $q = "SELECT * FROM `app_object`,`app_database`,`app_table` "
        	."WHERE `app_object`.`table_id` = `app_table`.`table_id` "
        	."AND `app_table`.`database_id` = `app_database`.`database_id` ";
        	
        $objectData = $this->link->queryAll($q,FDATABASE_FETCHMODE_ASSOC);
        foreach ($objectData as $data) {
            
            // Create an FObj object and an FSqlTable object
            $o = new FObj($data['object_id'],$data);
            $t = new FSqlTable($this->getPrimaryDatabaseName(),$data['table_id'],$data);
            
            // Get all the attributes for the object and columns for the table
            $q = "SELECT * FROM `app_object`,`app_attribute`,`app_validation`,`app_column` "
            	."WHERE `app_attribute`.`object_id` = `app_object`.`object_id` "
            	."AND `app_attribute`.`column_id` = `app_column`.`column_id` "
            	."AND `app_attribute`.`validation_id` = `app_validation`.`validation_id` "
            	."AND `app_object`.`object_id` = '{$data['object_id']}' ";
            	
            
            $attributeData = $this->link->queryAll($q,FDATABASE_FETCHMODE_ASSOC);

            foreach ($attributeData as $adata) {
                $c = new FSqlColumn(
                    $this->getPrimaryDatabaseName(),
                    $t->getName(),
                    $adata['column_id'],
                    $adata
                );
                $a = new FObjAttr(
                    $o->getName(),
                    $c->getName(),
                    $adata['attribute_id'],
                    $adata);
                $a->setColumn($c);
                $o->addAttribute($a);
                $t->addColumn($c);
            }
            $o->setTable($t);
            
            // Get all the relationships for which the object is the local endpoint
            $q = "SELECT * FROM `app_object`,`app_relationship` "
            	."WHERE `app_relationship`.`object_id_l` = `app_object`.`object_id`"
            	."AND   `app_relationship`.`object_id_l` = '{$o->getName()}' ";
            $relationshipData = $this->link->queryAll($q,FDATABASE_FETCHMODE_ASSOC);
            foreach ($relationshipData as $rdata) {
                $s = new FObjSocket($rdata['name_l'],$rdata);
                
                // Determine where to put it in the object:
                switch ($s->getQuantity()) {
                    case '11': break;
                    case '1M': $o->addChild($s); break;
                    case 'M1': $o->addParent($s);break;
                    case 'MM': $o->addPeer($s);  break;
                    default:
                        throw new FException("Invalid quantity '{$s->getQuantity()}' 
                        	specified for socket {$o->getName()}::{$s->getName()}");
                }
            }
            
            $this->objects[$o->getName()] = $o;
        }
    }
    
    public function save() {
        // Empty app_database, app_table, app_object, app_attribute, app_validation, app_column
        
        // Save app_database information
        foreach ($this->databases as $data) {
            $this->saveDatabase($data);
        }
        
        // Save model object information
        foreach ($this->objects as $o) {
            $this->saveObject($o);
        }
    }
    
    public function saveObject($fObj) {

        // First save the object
        
        // Then save its attributes (& columns, & validation)
        
        // Finally save its relationships
        
           
    }
    
    public function getObjects() {
        return $this->objects;
    }
    
    public function getObject($name) {
        if (isset($this->objects[$name])) {
            return $this->objects[$name];
        } else {
            return false;
        }
    }
    
    public function deleteObject($fObj) {
        
    }
    
    private function createObject(&$object) {
        // Insert a row into 'app_table' for this object
        $q1 = "INSERT INTO `app_table` VALUES " . $object->toModelSqlValuesString();
        
        // Create a unique id attribute for this object
        $oIdCol  = new FSqlColumn(
            $this->getPrimaryDatabaseName(),
            $object->getTable()->getName(),
            $object->getTable()->getName().'_id',
            array('isNull'    => false,
                  'isAutoinc' => true,
                  'isUnsigned'=> true,
                  'col_type'  => 'INT(11)',
                  'key'       => 'PRIMARY')
            );
        
        $oIdAttr = new FObjAttr(
            $object->getName(),
            $oIdCol->getName(),
            'id');
            
        $oIdAttrValidation = new FObjAttrValidation("{$object->getName()}.{$oIdAttr->getName()}",
            array(
            
                'vNumericMin' => 0,
                'vIsRequired' => 'Y'
            )
        );
        
        // Insert a row into 'app_attributes' for the 'id' attribute of this object
        $q2= "INSERT INTO `app_attribute` VALUES " . $oIdAttr->toModelSqlValuesString();
        
        // Insert a row into the 'app_validation' for the 'id' attribute of this object
        $q3 = "INSERT INTO `app_validation` VALUES " . $oIdAttrValidation->toModelSqlValuesString();
        
        // Insert a row into 'app_columns' for the 'id' attribute of the object
        $q4= "INSERT INTO `app_column` VALUES " . $oIdCol->toModelSqlValuesString();
        
        try {
            $this->link->exec($q1);
            $this->link->exec($q2);
            $this->link->exec($q3);
            $this->link->exec($q4);
        } catch (FDatabaseException $fde) {
            echo $fde;
            die();
        }
        
        return true;

    }
    private function createAttribute(&$object,$attribute) {
        // Insert a row into the 'app_attributes' table for the attribute
        
        // Insert a row into the 'app_columns' table for the attribute
    }
    
    private function createPairRelationship(&$object,$socket) {
        
    }
    
    private function createParentRelationship(&$object,$socket) {
    
    }
    
    private function createPeerRelationship(&$object,$socket) {
        
    }
    
    private function createChildRelationship(&$object,$socket) {
        
    }
    
}
?>