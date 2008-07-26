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
		$r = _db()->exec($q);
		if (MDB2::isError($r)) {
			FDatabaseErrorTranslator::translate($r->getCode(),$q);
		}
		return _db()->lastInsertID("FAccount","objId");
	}
}
?>