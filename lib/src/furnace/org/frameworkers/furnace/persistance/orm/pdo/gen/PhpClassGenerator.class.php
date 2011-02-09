<?php
namespace org\frameworkers\furnace\persistance\orm\pdo\gen;

use org\frameworkers\furnace\persistance\orm\pdo\model\Model;

use org\frameworkers\furnace\persistance\orm\pdo\model\Inflection;
use org\frameworkers\furnace\persistance\orm\pdo\model\Object;
use org\frameworkers\furnace\persistance\orm\pdo\model\Relation;
use vendors\tadpole\TadpoleEngine;

define("T","\t");
define("NL","\r\n");


class PhpClassGenerator {
	
	const PHP_FILE_OPEN  = "<?php\r\n";
	const PHP_FILE_CLOSE = "\r\n?>";
	
	public static function GenerateCustomClassForObject(Object $o) {
		$str  = "<?php".NL.NL;
		$str .= "namespace app\models\orm;".NL.NL;
		$str .= "class {$o->className} extends \app\models\orm\managed\\{$o->className} {".NL;
		$str .= NL;
		$str .= "}".NL.NL;
		
		$str .= "class {$o->className}Collection extends \app\models\orm\managed\\{$o->className}Collection {".NL;
		$str .= NL;
		$str .= "}".NL.NL;
		
		return $str;
	}
	
	public static function GenerateManagedClassForObject(Object $o, Model $m) {
		$compiled  = self::PHP_FILE_OPEN;
		$compiled .= self::genFileProlog();
		$compiled .= self::genObjClassComments($o);
		$compiled .= self::genObjClassOpener($o);
		$compiled .= self::genObjAttrDeclarations($o);
		$compiled .= self::genObjRelDeclarations($o);
		$compiled .= self::genObjConstructor($o);
		$compiled .= self::genObjPrimaryKeysGetter($o);
		$compiled .= self::genObjAttrGetters($o);
		$compiled .= self::genObjRelGetters($o);
		
		
		$compiled .= self::genObjAttrSetters($o);
		$compiled .= self::genObjRelSetters($o);
		
		$compiled .= self::genObjMergeFunction($o);
		$compiled .= self::genObjSaveFunction($o);
		$compiled .= self::genObjUpdateFunction($o);
		$compiled .= self::genObjDeleteFunction($o,$m);
		
		
		$compiled .= self::genObjLoadFunction($o);
		$compiled .= self::genObjSearchFunction($o);
		
		$compiled .= self::genObjClassCloser($o);
		$compiled .= NL;
		$compiled .= self::genObjDatasetClass($o);
		$compiled .= self::PHP_FILE_CLOSE;
		return $compiled;
	}
	
	protected static function genFileProlog() {
		$str .= 'namespace app\models\orm\managed;'.NL.NL;
		$str .= 'use \org\frameworkers\furnace\persistance\orm\pdo\core\Object;'.NL;
		$str .= 'use \org\frameworkers\furnace\persistance\orm\pdo\Dataset;'.NL;
		$str .= 'use \org\frameworkers\furnace\connections\Connections;'.NL;
		$str .= 'use \org\frameworkers\furnace\persistance\orm\exceptions\ORMLoadException;'.NL;
		$str .= 'use \org\frameworkers\furnace\persistance\orm\exceptions\ORMSaveException;'.NL;
		
		
		return $str.NL;
	}
	
	protected static function genObjClassComments($o){
		return '';
	}
	
	protected static function genObjClassOpener($o) {
		return "class {$o->className} extends Object {".NL;
	}
	
	protected static function genObjAttrDeclarations(Object $o) {
		$str = '';
		foreach ($o->attributes as $attr) {
			$str .= T."protected \${$attr->name};".NL;	
		}
		return NL.$str.NL;
	}
	
	protected static function genObjRelDeclarations(Object $o) {
		$str = '';
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {
				$str .= T."protected \${$rel->name};".NL;
			}
		}
		return NL.$str.NL;
	}
	
	protected static function genObjConstructor(Object $o) {
		$str  = T."public function __construct(\$data = array()) {".NL;
		$str .= T.T."if (!empty(\$data)) { \$this->merge(\$data); }".NL;
		$str .= T."}".NL.NL;
		return $str;
	}
	
	protected static function genObjPrimaryKeysGetter(Object $o) {
		$str = '';
		$pks = array();
		foreach ($o->attributes as $attr) {
			if ($attr->isPrimary) {
				$pks[] = "\$this->{$attr->name}";
			}
		}
		//TODO: Add primary relations to this array too
		$str .= T."public function get_id() {".NL;
		$str .= T.T."return " . implode('/',$pks) . ';' . NL;
		$str .= T."}".NL.NL;
		
		$str .= T."public function toUrl() {".NL;
		$str .= T.T."return " . implode('/',$pks) . ';' . NL;
		$str .= T."}".NL.NL;
		return $str;
	}
	
	protected static function genObjAttrGetters(Object $o) {
		$str = '';
		foreach ($o->attributes as $attr) {
			$str .= T."public function get".ucwords($attr->name)."() {".NL;
			$str .= T.T."return \$this->{$attr->name};".NL;
			$str .= T."}".NL.NL; 
		}
		return $str;
	}
	
	protected static function genObjRelGetters(Object $o) {
		$str = '';
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {
				$str .= T."public function get".ucwords($rel->name)."() {".NL;
				$str .= T.T."if(is_object(\$this->{$rel->name})) { return \$this->{$rel->name}; }".NL;
				$str .= T.T."else { return \$this->{$rel->name} = {$rel->remoteObjectClassName}::Load(\$this->{$rel->name}); }".NL;
				$str .= T."}".NL.NL;
			}
			if ($rel->type == Relation::ORM_RELATION_HASMANY) {
				$str .= T."public function get".ucwords($rel->name)."() {".NL;
				$str .= T.T."\$c = new \\app\\models\\orm\\{$rel->remoteObjectClassName}Collection();".NL;
				$str .= T.T."// Apply filters".NL;
				$str .= T.T."return \$c->equal('{$rel->remoteKeyColumn->name}',\$this->get_id());".NL;
				$str .= T."}".NL.NL;
			}
		}
		return $str;
	}
	
	protected static function genObjAttrSetters(Object $o) {
		$str = '';
		foreach ($o->attributes as $attr) {
			$str .= T."public function set".ucwords($attr->name)."(\$value) {".NL;
			/*** Don't forget to set primary key (_primaryKeys) if the attr is a primary one**/
			//TODO: set the primary key data for primary keys
			$str .= T.T."\$this->{$attr->name} = \$value;".NL;
			$str .= T.T."\$this->_dirtyTable[] = '{$attr->name}';".NL;
			$str .= T.T."return \$this;".NL;
			$str .= T."}".NL.NL; 
		}
		return $str;
	}
	
	protected static function genObjRelSetters(Object $o) {
		$str  = '';
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {
				$str .= T."public function set".ucwords($rel->name)."(\$idOrObject) {".NL;
				$str .= T.T."// handle object ".NL;
				$str .= T.T."if (is_object(\$idOrObject)) {".NL;
				$str .= T.T.T."die('tbd');".NL;
				$str .= T.T."}".NL;
				$str .= T.T."// handle id ".NL;
				$str .= T.T."else {".NL;
				$str .= T.T.T."if (\$o = {$rel->remoteObjectClassName}::Load(\$idOrObject)) {".NL;
				$str .= T.T.T.T."\$this->{$rel->name}  = \$o;".NL;
				$str .= T.T.T.T."\$this->_dirtyTable[] = '{$rel->name}'; ".NL;
				$str .= T.T.T."}".NL;
				$str .= T.T."}".NL;
				$str .= T.T."return \$this;".NL;
				$str .= T."}".NL.NL;
			}
		}
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_HASMANY) {
				$singularInflection = Inflection::ToSingular($rel->name);
				
				$str .= T."public function add".ucwords($singularInflection)."(\$idOrObject) {".NL;
				$str .= T.T."// Can't add anything unless this object itself".NL;
				$str .= T.T."// has been persisted:".NL;
				$str .= T.T."if (empty(\$this->_primaryKeys)) { return false; } ".NL.NL;
				$str .= T.T."// The provided value is an object".NL;
				$str .= T.T."if (\$idOrObject instanceof Object) {".NL;
				if (empty($rel->lookupObject)) {
					$str .= T.T.T."// The relation is not M-M, so simply set the field and save".NL;
					$str .= T.T.T."\$idOrObject->set".ucwords($rel->reciprocalRelation->name)."(\$this->{$rel->reciprocalRelation->localKeyColumn->name}); ".NL;
					$str .= T.T.T."return \$idOrObject->save();".NL.NL;
				}
				//TODO: same as above, but for M-M
				$str .= T.T."// The provided value is an id".NL;
				$str .= T.T."} else { ".NL;
				if (empty($rel->lookupObject)) {
					$str .= T.T.T."if (false != (\${$singularInflection} = {$rel->remoteObjectClassName}::Load(\$idOrObject))) {".NL;
					$str .= T.T.T.T."\${$singularInflection}->set".ucwords($rel->reciprocalRelation->name)."(\$this->{$rel->reciprocalRelation->localKeyColumn->name}); ".NL;
					$str .= T.T.T.T."return \${$singularInflection}->save();".NL;
					$str .= T.T.T."}".NL;
				}
				//TODO: same as above, but for M-M
				$str .= T.T."}".NL;
				$str .= T.T."return \$this;".NL;
				$str .= T."}".NL.NL;
			}
		}
		return $str;		
	}
	
	protected static function genObjMergeFunction(Object $o) {
		
		/*****
		 * COPY PASTE WARNING!
		 * Changes made here need to propagate to both section
		 *****/
		
		$str  = T."public function merge(\$data,\$mergedDataNeedsSaving = false) {".NL;
		$str .= T.T."if (is_array(\$data)) {".NL;
		$str .= T.T.T."// store primary key information".NL;
		foreach ($o->attributes as $attr) {
			if ($attr->isPrimary) {
				$str .= T.T.T."if(isset(\$data['{$attr->name}'])) {".NL
					 .  T.T.T.T."\$this->_primaryKeys['{$attr->name}'] = \$data['{$attr->name}'];".NL
					 .  T.T.T.T."if (\$mergedDataNeedsSaving) {".NL
					 .  T.T.T.T.T."\$this->_dirtyTable[] = '{$attr->name}';".NL
					 .  T.T.T.T."}".NL
					 .  T.T.T."}".NL;		
			}
			//TODO: add primary relations to this list
		}
		$str .= NL.T.T.T."// store attributes".NL;
		foreach ($o->attributes as $attr) {
			$str .= T.T.T."if(isset(\$data['{$attr->name}'])) {".NL
				 .  T.T.T.T."\$this->{$attr->name} = \$data['{$attr->name}'];".NL
				 .  T.T.T.T."if (\$mergedDataNeedsSaving) {".NL
				 .  T.T.T.T.T."\$this->_dirtyTable[] = '{$attr->name}';".NL
				 .  T.T.T.T."}".NL
				 .  T.T.T."}".NL;		
		}
		$str .= NL.T.T.T."// store any `Belongs To` relations".NL;
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {
				$str .= T.T.T."if(isset(\$data['{$rel->localKeyColumn->name}'])) {".NL
					 .  T.T.T.T."\$this->{$rel->name} = \$data['{$rel->localKeyColumn->name}'];".NL
					 .  T.T.T."}".NL;
				//TODO: add belongsto changes to the dirty list if $mergedChangesNeedSaving	
			}
		}
		$str .= NL.T.T."} else if (\$data instanceof \stdClass) {".NL;
		$str .= T.T.T."// store primary key information".NL;
		foreach ($o->attributes as $attr) {
			if ($attr->isPrimary) {
				$str .= T.T.T."if(isset(\$data->{$attr->name})) {".NL
					 .  T.T.T.T."\$this->_primaryKeys['{$attr->name}'] = \$data->{$attr->name};".NL
					 .  T.T.T.T."if (\$mergedDataNeedsSaving) {".NL
					 .  T.T.T.T.T."\$this->_dirtyTable[] = '{$attr->name}';".NL
					 .  T.T.T.T."}".NL
					 .  T.T.T."}".NL;
				//TODO: add primary relations to this list		
			}
		}
		$str .= NL.T.T.T."// store attributes".NL;
		foreach ($o->attributes as $attr) {
			$str .= T.T.T."if(isset(\$data->{$attr->name})) {".NL
				 .  T.T.T.T."\$this->{$attr->name} = \$data->{$attr->name};".NL
				 .  T.T.T.T."if (\$mergedChangesNeedSaving) {".NL
				 .  T.T.T.T.T."\$this->_dirtyTable[] = '{$attr->name}';".NL
				 .  T.T.T.T."}".NL
				 .  T.T.T."}".NL;		
		}
		$str .= NL.T.T.T."// store any `Belongs To` relations".NL;
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {
				$str .= T.T.T."if(isset(\$data->{$rel->localKeyColumn->name})) {\$this->{$rel->name} = \$data->{$rel->localKeyColumn->name}; }".NL;	
				//TODO: add belongsto changes to the dirty list if $mergedChangesNeedSaving
			}
		}
		
		$str .= NL.T.T."}".NL;
		$str .= T."}".NL.NL;
		return $str;
	}
	
	protected static function genObjLoadFunction(Object $o) {
		$str = '';
		$str .= T."public static function Load(";
		$pkAttrs = array();
		foreach ($o->primaryKeyAttributes as $attr) {
			$pkAttrs[] = "\${$attr->name}";
		}
		foreach ($o->primaryKeyRelations as $rel) {
			$pkAttrs[] = "\${$rel->name}";
		}
		$str .= implode(', ',$pkAttrs);
		$str .= ") {".NL;
		
		$str .= T.T."\$raw = Connections::Get()->select()".NL
			  . T.T.T."->from('{$o->table->name}')".NL;
		foreach ($o->primaryKeyAttributes as $attr) {
			$str .= T.T.T."->equal('{$attr->column->name}',\${$attr->name})".NL;
		}
		foreach ($o->primaryKeyRelations as $rel) {
			$str .= T.T.T."->equal('{$rel->localKeyColumn->name}',\${$rel->name})".NL;
		}
		$str .= T.T.T."->first();".NL.NL;
		
		$str .= T.T."if (\$raw instanceof \stdClass) {".NL;
		$str .= T.T.T."\$obj = new \\app\\models\\orm\\{$o->className}();".NL;
		$str .= T.T.T."\$obj->merge(\$raw);".NL;	
		$str .= T.T.T."return \$obj;".NL;
		$str .= T.T."} else {".NL;
		$str .= T.T.T."throw new ORMLoadException('Unable to load requested object');".NL;
		$str .= T.T."}".NL;
		$str .= T."}".NL.NL;
		return $str;
	}
	
	protected static function genObjDeleteFunction(Object $o,Model $m) {
		$str = '';
		$str .= T."public static function Delete(";
		$args = array();
		foreach ($o->primaryKeyAttributes as $attr) {
			$args[] = "\${$attr->name}";
		}
		foreach ($o->primaryKeyRelations as $rel) {
			$args[] = "\${$rel->name}";
		}
		$str .= implode(',',$args) . ") {".NL;
		$str .= T.T."// Load the object".NL;
		$str .= T.T."\$o = {$o->className}::Load(";
		$args = array();
		foreach ($o->primaryKeyAttributes as $attr) {
			$args[] = "\${$attr->name}";
		}
		foreach ($o->primaryKeyRelations as $rel) {
			$args[] = "\${$rel->name}";
		}
		$str .= implode(',',$args) . ");".NL.NL;
		$str .= T.T."// Delete all instances of all objects that 'belong to' this object".NL;
		foreach ($m->objects as $remoteObject) {
			if ($remoteObject->className == $o->className) { continue; }
			foreach ($remoteObject->relations as $rel) {
				if ($rel->type == Relation::ORM_RELATION_BELONGSTO 
					&& $rel->remoteObjectClassName == $o->className) {
					// If the 'belongs to' relation is required, then the dependent
					// (remote) object instance must also be deleted.
					if ($rel->isRequired || $rel->isPrimary) {
						$str .= T.T."\$collection = new {$rel->localObjectClassName}Collection();".NL;
						$str .= T.T."\$results    = \$collection".NL;
						$str .= T.T.T."->equal('{$rel->remoteKeyColumn->name}',\$o->get{$rel->remoteKeyAttr->name}())".NL;
						$str .= T.T.T."->toArray();".NL;
						$str .= T.T."foreach (\$results as \$obj) {".NL;
						$o2 = $m->objects[$rel->localObjectClassName];
						$args = array();
						foreach ($o2->primaryKeyAttributes as $attr) {
							$args[] = "\$obj->{$attr->column->name}";
						}
						foreach ($o2->primaryKeyRelations as $rel) {
							$args[] = "\$obj->{$rel->localKeyColumn->name}";
						}
						$str .= T.T.T."{$rel->localObjectClassName}::Delete(".implode(',',$args).");".NL;
						$str .= T.T."}".NL.NL;
					}
					
					// If the 'belongs to' relation is not required, then it is sufficient
					// simply to nullify the link between the two.
					else {
						
					}						
				}
			}
		}
		$str .= T.T."// Delete references from all objects that 'share' this object".NL;
		$str .= T.T."// Delete the object itself".NL;
		$str .= T.T."\$c = new {$o->className}Collection();".NL;
		$str .= T.T."\$c".NL;
		foreach ($o->primaryKeyAttributes as $attr) {
			$str .= T.T.T."->equal('{$attr->column->name}',\${$attr->name})".NL;
		}
		foreach ($o->primaryKeyRelations as $rel) {
			$str .= T.T.T."->equal('{$rel->localKeyColumn->name}',\${$rel->name})".NL;
		}
		$str .= T.T.T."->delete();".NL;
		$str .= T."}".NL.NL;
		
		return $str;
	}
	
	protected static function genObjSearchFunction(Object $o) {
		$str = '';
		$str .= T."public static function Search() {".NL;
		$str .= T.T."return new \\app\\models\\orm\\{$o->className}Collection();".NL;
		$str .= T."}".NL.NL;
		return $str;		
	}
	
	protected static function genObjSaveFunction(Object $o) {
		$str = NL;
		$str .= T."public function save(\$data = array()) {".NL;
		$str .= T.T."// Merge incoming data with the present object ".NL
			   .T.T."\$this->merge(\$data,true);".NL
			   .T.T."// Determine whether we are CREATEing or UPDATEing".NL
			   .T.T."\$canUpdate = !empty(\$this->_primaryKeys);".NL.NL
			   .T.T."// Update an existing object".NL
			   .T.T."if (\$canUpdate) {".NL
			   .T.T.T."return \$this->update();".NL
			   .T.T."}".NL.NL
			   .T.T."// Save a new object".NL
			   .T.T."else {".NL.NL
			   .T.T.T."\$v_rels = array();".NL;
			   
		$fieldsStrings = array();
		$valuesStrings = array();
		foreach ($o->relations as $rel) {
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO && $rel->isRequired) {
				$str .= T.T.T."\$v_rels['{$rel->name}'] = is_object(\$this->{$rel->name}) ? \$this->{$rel->name}->get_id() : \$this->{$rel->name}; ".NL;
				
				$fieldsStrings[] = "`{$rel->localKeyColumn->name}` ";
				$valuesStrings[] = "'{\$v_rels['{$rel->name}']}' ";
			}
		}
		foreach ($o->attributes as $attr) {
			if (!$attr->isPrimary && !$attr->isAutoinc) {
				$fieldsStrings[] = "`{$attr->column->name}` ";
				$valuesStrings[] = "'{\$this->{$attr->name}}' ";		
			}
		}
		$str .= NL;
		$str .= T.T.T."// Ensure that all required 'belongs to' relations have nonzero ".NL
			  . T.T.T."// values ".NL;
		$str .= T.T.T."foreach (\$v_rels as \$rel) {".NL
			  . T.T.T.T."if ( empty(\$rel) ) { return false; } ".NL
			  . T.T.T."} ".NL;
		$str .= T.T.T."// Build the insert SQL string".NL
			  . T.T.T."\$sql  = \"INSERT INTO `{$o->table->name}` \";".NL;
		$str .= T.T.T."\$sql .= \" ( " . implode(',', $fieldsStrings) . ") \"; ".NL
			   .T.T.T."\$sql .= \"VALUES \"; ".NL
			   .T.T.T."\$sql .= \" ( " . implode(',', $valuesStrings) . ") \"; ".NL.NL
			   .T.T.T."// Execute the SQL string".NL
			   .T.T.T."Connections::Get()->exec( \$sql ); ".NL.NL
			   
			   /***
			    * TODO:
			    * ONLY DO THE FOLLOWING IF THERE IS A PRIMARY,AUTOINCREMENTING KEY
			    * FOR THE OBJECT!
			    */
			   .T.T.T."// Obtain and store the last insert id".NL
			   .T.T.T."\$this->{$o->primaryKeyAttributes[0]->name} = Connections::Get()->lastInsertId();".NL.NL
			   /****/
			   /***
			    * TODO:
			    * SHOULD BE AN ITERATION THROUGH ALL PRIMARY KEY ATTRIBUTES TESTING 
			    * EACH TO ENSURE != FALSE
			    */
			   .T.T.T."return (\$this->{$o->primaryKeyAttributes[0]->name} > 0);".NL
			   /****/
			   
			   .T.T."}".NL
			   .T."}".NL.NL;
			   
		return $str;
	}
	
	protected static function genObjUpdateFunction(Object $o) {
		$str  = NL;
		$str .=  T."public function update() {".NL
				.T.T."// Build update statement based upon what needs saving".NL
				.T.T."\$updateStrings = array();".NL
				.T.T."foreach (\$this->_dirtyTable as \$dirtyAttrName) {".NL;
		$str  .= T.T.T."\$dirtyAttrCol = \$dirtyAttrName;".NL;
		
		// For BELONGSTO relations, override the column name used with the relation's local column name
		foreach ($o->relations as $rel) {	
			if ($rel->type == Relation::ORM_RELATION_BELONGSTO) {	
				$str .= T.T.T."if (\$dirtyAttrName == '{$rel->name}') { \$dirtyAttrCol = '{$rel->localKeyColumn->name}'; } ".NL;		
			}
		}
		$str  .= T.T.T."\$updateStrings[] = \" `{\$dirtyAttrCol}`='{\$this->\$dirtyAttrName}' \"; ".NL
				.T.T."}".NL.NL
				.T.T."// Build primary key conditions ".NL
				.T.T."\$pkStrings = array();".NL
				.T.T."foreach (\$this->_primaryKeys as \$pk => \$pkv) {".NL
				.T.T.T."\$pkStrings[] = \" `{\$pk}`='{\$pkv}' \"; ".NL
				.T.T."}".NL.NL
				.T.T."// Build the SQL string".NL
				.T.T."\$sql  = \"UPDATE `{$o->table->name}` SET \"; ".NL
				.T.T."\$sql .= implode(',',\$updateStrings); ".NL
				.T.T."\$sql .= \"WHERE \"; ".NL
				.T.T."\$sql .= implode(' AND ',\$pkStrings); ".NL
				.T.T."\$sql .= \"LIMIT 1 \"; ".NL.NL
				.T.T."Connections::Get()->exec( \$sql ); ".NL.NL
				.T.T."// Empty (reset) the dirty table ".NL
				.T.T."\$this->_dirtyTable = array(); ".NL
				.T."}".NL.NL;
				
		return $str;
	}
	
	protected static function genObjClassCloser(Object $o) {
		return "} // end of class {$o->className}.".NL;
	}
	
	protected static function genObjDatasetClass(Object $o) {
		$str  = "class {$o->className}Collection extends Dataset {".NL;
		
		$str .= T."public function __construct(\$connectionLabel = 'default') {".NL;
		$str .= T.T."parent::__construct(Connections::Get(\$connectionLabel));".NL;
		$str .= T.T."\$this->sqlBuilder->table('{$o->table->name}');".NL;
		$str .= T."}".NL.NL;
		$str .= "}".NL.NL;
		return $str;
	}
}