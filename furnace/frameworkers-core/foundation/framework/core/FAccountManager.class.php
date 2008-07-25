<?php
/*
 * frameworkers-foundation
 * 
 * FAccountManager.class.php
 * Created on June 21, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
class FAccountManager extends FAccount {
		
	public static function Create($un,$pw,$em) {
		$encrypted = md5($pw);
		$q = "INSERT INTO `FAccount` "
			."(`username`,`password`,`emailAddress`,`objectClass`,`objectId`) "
			."VALUES ('{$un}','{$encrypted}','{$em}','{$class}','{$id}')"; 
		$r = FDatabase::singleton(Config::PROJECT_DB_DSN)->exec($q);
		if (MDB2::isError($r)) {
			FDatabaseErrorTranslator::translate($r->getCode(),$q);
		}
		return FDatabase::singleton(Config::PROJECT_DB_DSN)
			->lastInsertID("FAccount","objId");
	}
}
?>