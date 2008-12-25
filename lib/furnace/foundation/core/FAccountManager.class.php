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
	 *  cl - The class of object this account will be associated with
	 *  id - The unique id of the 'cl' object this acct will be associated with
	 * 
	 * Returns:
	 * 
	 *  (integer) - The unique id of the newly created account
	 */
	public static function Create($un,$pw,$em) {
		$encrypted = md5($pw);
		
		$account = FAccount::Create($un);
		$account->setPassword($encrypted);
		$account->setEmailAddress($em);
		
		//$account->save(); // why doesn't this work?
		return $account->getObjId();
	}
	
	public static function Delete($username) {
		$q = "SELECT `objId` FROM `app_accounts` WHERE `username` = '{$username}' ";
		$id= _db()->queryOne($q);
		FAccount::Delete($id);
	}
	
	public static function DeleteByAccountId($id) {
		FAccount::Delete($id);
	}
}
?>