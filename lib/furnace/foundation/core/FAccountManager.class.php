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
 /*
  * Class: FAccountManager
  * 
  * Provides a unified interface for managing <FAccount> objects.
  */
class FAccountManager extends FAccount {
		
	/*
	 * Function: Create
	 * 
	 * This static function simplifies the process of creating an FAccount
	 * object in a database. 
	 * 
	 * Parameters:
	 * 
	 *  un - The username for the account
	 *  pw - The password (in cleartext) for the account. This password
	 *       will be encrypted before storage.
	 *  em - The email address for the account
	 * 
	 * Returns:
	 * 
	 *  (integer) - The unique id of the newly created account
	 */
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