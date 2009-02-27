<?php
class DataController extends Controller {
	
	public function index() {
		if ($GLOBALS['fconfig_debug_level'] > 0 && 
			$GLOBALS['fconfig_debug_dsn'] == 'mysql://user:password@server/dbname') {
			die("No debug database specified. Please edit the 'fconfig_debug_dsn' variable in your application config file");
		} else if ($GLOBALS['fconfig_debug_level'] == 0 &&
				   $GLOBALS['fconfig_production_dsn'] == 'mysql://user:password@server/dbname') {
			die("No production database specified. Please edit the 'fconfig_production_dsn' variable in your application config file");	   	
		}
		
		$this->init();		// Load required files
		
		$d = new FDatabaseSchema();
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$d->discover($GLOBALS['fconfig_debug_dsn']);
		} else {
			$d->discover($GLOBALS['fconfig_production_dsn']);
		}
		$model = $this->getModel();
		
		$this->set('objects',$model->objects);
	}
	
	public function objects($name) {
		$this->init();		// Load required files
		
		$d = new FDatabaseSchema();
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$d->discover($GLOBALS['fconfig_debug_dsn']);
		} else {
			$d->discover($GLOBALS['fconfig_production_dsn']);
		}
		$model = $this->getModel();
		
		$object = false;
		foreach ($model->objects as $o) {
			if ($o->getName() == $name) {
				$object = $o;
			}
		}
		if (!$object) {
			$this->flash("Object type '{$name}' not found in the database.","error");
			$this->redirect("/fuel/data/");
		}
		
		$this->set('object',$object);
		
		// Create an FObjectCollection object to manage object retrieval
		$coll_class = "{$name}Collection";
		$objectCollection = new $coll_class();
		
		// Load objects based on pagination instructions
		if (isset($_GET['page'])) {
			$objects = $objectCollection->getPage($_GET['page'],10,$_GET['sortBy'],$_GET['sortOrder']);
		} else {
			$objects = $objectCollection->getPage(1,10,"objId","desc");
		}
		
		// Build data array from loaded objects
		$headers = array("objId");
		$object_datas = array();
		foreach ($objects as $o) {
			$object_datas[$o->getObjId()][] = "<a href=\"{$GLOBALS['fconfig_url_base']}fuel/data/object/{$name}/{$o->getObjId()}\">{$o->getObjId()}</a>";
		}
		foreach ($object->getAttributes() as $attr) {
			$attr_name    = $attr->getName();
			$attr_fn_name = "get{$attr->getFunctionName()}"; 
			$headers[] = $attr_name;
			foreach ($objects as $o) {
				$object_datas[$o->getObjId()][] = $o->$attr_fn_name();
			}
		}
		foreach ($object->getSockets() as $sock) {
			if ($sock->getQuantity() == "1") {
				$headers[] = "{$sock->getName()} ({$sock->getForeign()})";
				$sock_fn_name = "get{$sock->getFunctionName()}";
				foreach ($objects as $o) {
					$object_datas[$o->getObjId()][] = "<a href=\"{$GLOBALS['fconfig_url_base']}/fuel/data/object/{$sock->getForeign()}/{$o->$sock_fn_name()->getObjId()}\">{$o->$sock_fn_name()->getObjId()}</a>";
				}
			}
		}
		foreach ($objects as $o) {
			$object_datas[$o->getObjId()][] = "<a style=\"color:red;\"href=\"{$GLOBALS['fconfig_url_base']}/fuel/data/delete/{$name}/{$o->getObjId()}\" onclick=\"return confirm('This action can not be undone. Continue?');\">Delete!</a>";
		}
		
		$this->set('headers',$headers);
		$this->set('obj_data',$object_datas);
		$this->set('objectClass',$object);
		
		
		// Register pagination details
		$this->set('objPagination',$objectCollection->getPaginationData());
		$this->set('currentPage',(isset($_GET['page'])? $_GET['page'] : 1));
	}

	public function object($class,$id) {
		$this->init();		// Load required files
		
		$d = new FDatabaseSchema();
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$d->discover($GLOBALS['fconfig_debug_dsn']);
		} else {
			$d->discover($GLOBALS['fconfig_production_dsn']);
		}
		$model = $this->getModel();
		
		$object = false;
		foreach ($model->objects as $o) {
			if ($o->getName() == $class) {
				$object = $o;
			}
		}
		if (!$object) {
			$this->flash("Object type '{$class}' not found in the database.","error");
			$this->redirect("/fuel/data/");
		}
		
		// Create an FObjectCollection object to manage object retrieval
		$coll_class = "{$class}Collection";
		$objectCollection = new $coll_class();
		$the_object = $objectCollection->get($id);
		$this->set('objectClass',$object);
		$this->set('object',$the_object);
		
		$headers = array();
		$data    = array();
	}
	
	public function create() {
		if ($this->form) {
			
			$this->init();		// Load required files
		
			$d = new FDatabaseSchema();
			if ($GLOBALS['fconfig_debug_level'] > 0) {
				$d->discover($GLOBALS['fconfig_debug_dsn']);
			} else {
				$d->discover($GLOBALS['fconfig_production_dsn']);
			}
			$model = $this->getModel();
			
			$objectType = $this->form['fobjectType'];
			$object = false;
			foreach ($model->objects as $o) {
				if ($o->getName() == $objectType) {
					$object = $o;
				}
			}
			if (!$object) {
				$this->flash("Object type '{$objectType}' not found in the database.","error");
				$this->redirect("/fuel/data/");
			}
			
			$params = array();
			if ("FAccount" == $object->getParentClass()) {
				$params[]  = $this->form['username'];
				$params[]  = $this->form['password'];
				$params[]  = $this->form['emailAddress']; 
			}
			/**
			 * UNIQUE ATTRIBUTES
			 */
			foreach ($object->getAttributes() as $attr) {
				if ($attr->isUnique()) {
					$params[] = $attr->getName();
				}
			}
			/**
			 * SOCKETS
			 */
			foreach ($object->getSockets() as $sock) {
				if ($sock->getQuantity() == "1") {
					$params[] = $this->form[$sock->getName()."_id"];
				}
			}
			
			$obj = call_user_func_array(array($objectType,"Create"),$params);
			foreach ($object->getAttributes() as $attr) {
				$set = "set{$attr->getName()}";
				$obj->$set($this->form[$attr->getName()],false);
			}
			$obj->save();
			
			
			$this->flash("Created new {$objectType} object!");
			$this->redirect("/fuel/data/objects/{$objectType}");
		}
	}
	
	public function delete($class,$id) {
		call_user_func(array($class,"Delete"),$id);
		$this->flash("Deleted '{$class}' object with id {$id}.");
		$this->redirect("/fuel/data/objects/{$class}");
	}
	
	public function objectSave() {
		if ($this->form) {
			$this->init();
			$objectClass = $this->form['objectClass'];
			$objectId = $this->form['objectId'];
			
			$object = call_user_func(array($objectClass,"Retrieve"),$objectId);
			
			foreach ($this->form as $attr => $value) {
				list($prefix,$attrName) = explode("_",$attr);
				
				if ("attr" == $prefix) {
					$func = "set".FModel::standardizeAttributeName($attrName);
					$object->$func($value,false);
				}
			}
			$object->save();
			
			$this->flash("Changes saved!");
			$this->redirect("/fuel/data/object/{$objectClass}/{$objectId}");
		}
	}
	
	private function init() {
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/furnace/foundation/database/".$GLOBALS['fconfig_db_engine']."/FDatabase.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObj.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/building/FModel.class.php");
		require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/dbmgmt/FDatabaseSchema.class.php");
	}
	private function getModel() {
		return new FModel(
			FYamlParser::parse(
				file_get_contents($GLOBALS['fconfig_root_directory'] . "/app/model/model.yml")
			)
		);
	}
	private function getSchema() {
		$d = new FDatabaseSchema();
		if ($GLOBALS['fconfig_debug_level'] > 0) {
			$d->discover($GLOBALS['fconfig_debug_dsn']);
		} else {
			$d->discover($GLOBALS['fconfig_production_dsn']);
		}
		return $d;
	}
	
	
	
}
?>