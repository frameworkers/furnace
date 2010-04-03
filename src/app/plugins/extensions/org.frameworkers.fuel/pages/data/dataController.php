<?php
class DataController extends Controller {
    
    private static $PER_PAGE = 10;
    
    public function __construct() {
        parent::__construct();
        $this->setActiveMenuItem('main','data');
        $this->setTitle('Foundry :: Data');
        $this->set('pageTitle','Data');
    }
    
    public function index() {
        $this->init();		// Load required files
		
		$d = new FDatabaseSchema();
		$d->discover('default');
		$model = $this->getModel();
		
		$tables = array();
		$notices= array();
		
		
		// Analyze differences DATABASE vs MODEL
		foreach ($model->tables as $mt) {
			$tables[strtolower($mt->getName())] = array("name"=> $mt->getName(),"found"=> false,"status"=> false);

			foreach ($d->getTables() as $dt) {
				if (strtolower($dt->getName()) == strtolower($mt->getName())) {
					$tables[strtolower($mt->getName())] = array('name'=> $mt->getName(),'found' => true,'table'=>$mt,'status'=>'ok');
					// Now that we found the matching table, check the fields for differences
					if (count($mt->getColumns()) != count($dt->getColumns())){
						$tables[strtolower($mt->getName())]['status'] = 'fieldsDiffer';
						break;
					}
					foreach ($mt->getColumns() as $mtc) {
						$found = false;
						foreach ($dt->getColumns() as $dtc) {
							if (strtolower($mtc->getName()) == strtolower($dtc->getName())){
								$found=true;	
								break;
							}
						}
						if (!$found || ( 
							($dtc->getColType()      != $mtc->getColType()) ||
						    ($dtc->isNull()          != $mtc->isNull())     ||
						    ($dtc->getDefaultValue() != $mtc->getDefaultValue())
						)) {
							$tables[strtolower($mt->getName())]['status'] = 'fieldsDiffer';
							break;
						}
					}	
					break;
				}
			}
		}
		
		// Analyze differences MODEL vs DATABASE
		foreach ($d->getTables() as $dt) {
			
			$bFound = false;
			foreach ($model->tables as $mt) {
				if (strtolower($mt->getName()) == strtolower($dt->getName())) {
					$tables[strtolower($mt->getName())]['found'] = true;
					$bFound = true;
					break;
				}
			}
			if (!$bFound) {
				$notices[] = $dt;
			}
		}
		
		$existingTableNames = array();
		foreach ($d->getTables() as $t) {
			$existingTableNames[] = $t->getName();
		}
		$this->set("existingTableNames",$existingTableNames);
		$this->set("tables",$tables);
		$this->set("notices",$notices);
		$this->set("theModel",$model);
		$this->extensionAddStylesheet('org.frameworkers.fuel','index.css',true);
    }
    
    public function objects($name,$page = 1) {
        
        $collectionClass = "{$name}Collection";
        $oc = new $collectionClass();
        $attrInfo   = _model()->$name->attributeInfo();
        $parentInfo = _model()->$name->parentsAsArray(); 
        
        
        $objectCollection = $oc->get()->limit(self::$PER_PAGE,$page-1);
        //foreach ($parentInfo as $pdata) {
        //    $objectCollection->each()->expand($pdata['name']);
        //}
        
        
        $objects = $objectCollection->output();
        $oarray  = array();
        foreach ($objects as $o) {
            $odata    = array('id' => "<a href='{$this->prefix}/data/edit/{$name}/{$o->getId()}'>{$o->getId()}</a>");
            foreach ($attrInfo as $aname => $adata) {
                $fn = "get{$aname}";
                $odata[$aname ] = $o->$fn();
            }
            foreach ($parentInfo as $pdata) {
                $fn = "get{$pdata['name']}";
                $odata[$pdata['name'] ] = $o->$fn(true);
            }
            $oarray[$o->getId()] = $odata;
        }
        
        $headers = array('id');
        foreach ($attrInfo as $aname => $adata) {
            $headers[] = $aname;
        }
        foreach ($parentInfo as $pdata) {
            $headers[] = $pdata['name'];
        }
        
        $this->set('objectName',$name);
        $this->set('headers',$headers);
        $this->set('objects',$oarray); 
    }
    
    public function create($name) {
        $attrInfo   = _model()->$name->attributeInfo();
        $parentInfo = _model()->$name->parentsAsArray();
        $this->set('object',_model()->$name);
        $this->set('objectName',$name);
        $this->set('attrs',$attrInfo);
        $this->set('parents',$parentInfo);
        $this->set('attr','password');
        
    }
    
    public function doCreate() {
        if ($this->form) {
            $ot = $this->form['objectType'];
            
            $object = new $ot();
            if ($object->save($this->form)) {
                $this->flash("New {$ot} object created");
            } else {
                $this->flash($object->validator,"error");
                _storeUserInput($this->form);
            }
            
            $this->redirect("{$this->prefix}/data/objects/{$ot}");
        } else {
            die("You must use POST");
        }
    }
    
    public function edit($ot,$id) {
        if (false !== ($object = _model()->$ot->get($id))) {
            $this->set('object',$object);
            $this->set('objectName',$ot);
            $this->set('id',$id);
            
            $this->set('attrs',_model()->$ot->attributeInfo());
            $parents = _model()->$ot->parentsAsArray();
            $this->set('parentInfo',$parents);
            
        } else {
            $this->noexist();
        }
    }
    
    public function doEdit() {
        if ($this->form) {
            $ot = $this->form['objectType'];
            $oid= $this->form['objectId'];
            
            if (false !== ($object = _model()->$ot->get($oid))) {
                if ($object->save($this->form)) {
                    $this->flash("Object updated");
                } else {
                    $this->flash($object->validator,"error");
                    _storeUserInput($this->form);
                }
                $this->redirect("{$this->prefix}/data/objects/{$ot}");
            } else {
                $this->noexist();
            }      
        } else {
            die("You must use POST");
        }
    }
    
    public function ajaxShowChildren($ot,$id,$var,$cnt,$offset=0) {
        $object   = _model()->$ot->instance($id);
        $fn = "get{$var}";

        $children  = $object->$fn()->limit($cnt,$offset)->output();
        header('Content-type: application/json');
        header('Content-type: text/plain');
        $output = '[';
        foreach ($children as $c) {
            $kids[] .= $c->toJSON();
        }
        $output .= implode(',',$kids).']';
        echo $output;
        die();
    }
}
?>