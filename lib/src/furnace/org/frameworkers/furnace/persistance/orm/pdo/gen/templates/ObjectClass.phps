<?php
use org\frameworkers\furnace\persistance\orm\pdo\core\Object;
/**
 * Class: [object.className]
 * 
 * @namespace: [object.typeNS]\managed
 **/
class F[object.className] extends Object {
	
	<$attrs>[object.attributes;block=$attrs;noerror]
	public $[@.name];</$attrs>
	
	<$rels>[object.relations;block=$rels;noerror]
	[assert:!@.lookupObject;content='public $[@.name]\;']</$rels>
	
	const OBJECTTYPE  = '[object.className]';
	const OBJECTTABLE = '[object.tableName]';
	
	<$constants>[constants;block=$constants;noerror]
	const [@.key] = [@.val];</$constants>
	
	public function __construct($data = array() ) {
		parent::__construct($data);
	}
	
	
	<$attrs>[object.fields;block=$attrs;noerror]
	public function get[@.name;ucwords]() {
		return $this->[@.name];
	}
	</$attrs>
	
	<$rels>[object.relations;block=$rels;noerror][assert:@.type='Belongs To';block=$rels]
	public function get[@.name;ucwords]() {
		$obj = Connections::Get()->from('[object.tableName]')
	}</$rels>

	<$attrs>[object.attributes;block=$attrs;noerror]
	public function set[@.name;ucwords]($newValue) {
		$this->[@.name] = $newValue;
		return true;
	}
	</$attrs>
	
	
	public static function Load($id) {
		
	}
	
	public static function Search() {
		
	}
	
	public static function Delete($id) {
		
	}
}