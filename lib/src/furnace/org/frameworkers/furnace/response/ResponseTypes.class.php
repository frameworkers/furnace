<?php
namespace org\frameworkers\furnace\response;
use org\frameworkers\furnace\core\StaticObject;

class ResponseTypes extends StaticObject {
	
	public static $types;
	
	public static function Register( $label, $mime, $options) {
		
		if (null == self::$types) {
			self::$types = array();
		}
		
		self::$types[$label] = array("mime" => $mime, "options" => $options);
	}
	
	public static function MimeFor( $label ) {
		return (isset(self::$types[$label]))
			? self::$types[$label]['mime']
			: null;
	}
	
	public static function ClassFor( $label ) {
		
		return (isset(self::$types[$label]))
			? self::$types[$label]['options']['class']
			: null;
	}
	
	public static function EngineFor( $label ) {
		return (isset(self::$types[$label]))
			? self::$types[$label]['options']['engine']
			: null;
	}
	
	public static function CreateResponse( &$context ) {
		if (isset(self::$types[$context->responseType])) {
			return Response::Create( $context );
		} else {
			return null;
		}			
	}
	
	public static function TypeExists( $label ) {
		return isset ( self::$types[$label] );
	}
}