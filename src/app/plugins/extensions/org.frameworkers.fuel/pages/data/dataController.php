<?php
class DataController extends Controller {
    
    private static $PER_PAGE = 10;
    
    public function __construct() {
        parent::__construct();
        $this->setActiveMenuItem('main','data');
        $this->prefix = _furnace()->req->route['prefix'];
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
		$this->extensionAddStylesheet('org.frameworkers.fuel','/pages/data/index/index.css',false);
    }
    
    public function objects($name,$page = 1) {
        
        $collectionClass = "{$name}Collection";
        $oc = new $collectionClass();
        $attrInfo = _model()->$name->attributeInfo();
        
        
        $objects = $oc->get()->limit(self::$PER_PAGE,$page-1)->output();
        $oarray  = array();
        foreach ($objects as $o) {
            $odata    = array('id' => $o->getId());
            foreach ($attrInfo as $aname => $adata) {
                $fn = "get{$aname}";
                $odata[$aname ] = $o->$fn();
            }
            
            $oarray[$o->getId()] = $odata;            
        }
        
        $headers = array('id');
        foreach ($attrInfo as $aname => $adata) {
            $headers[] = $aname;
        }
        
        $this->set('objectName',$name);
        $this->set('headers',$headers);
        $this->set('objects',$oarray); 
    }
}
?>