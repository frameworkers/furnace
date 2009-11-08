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
	
		$encrypted = self::EncryptPassword($pw);		
		
		$now = date('Y-m-d G:i:s');
		
		// Add entry in app_accounts
		$q = "INSERT INTO `app_accounts` (`username`,`password`,`emailAddress`,`created`,`modified`) "
			."VALUES ('{$un}','{$encrypted}','{$em}','{$now}','{$now}')"; 
		$r = _db()->exec($q);
		if (MDB2::isError($r)) {
			FDatabaseErrorTranslator::translate($r->getCode());
		}
		$faccountId = _db()->lastInsertID("app_accounts","objId");
				
		// Add entry in app_roles
		$q = "INSERT INTO `app_roles` (`accountId`) VALUES ('{$faccountId}')";
		$r = _db()->exec($q);
		if (MDB2::isError($r)) {
			FDatabaseErrorTranslator::translate($r->getCode());
		}
		return array("faccount_id"=>$faccountId,"encryptedPassword"=>$encrypted);
	}
	
    public static function ChangePassword($user,$pw) {
	    $user->setPassword(self::EncryptPassword($pw));
	    $user->save();
	}
	
	public static function EncryptPassword($pw) {
	    if (isset(_furnace()->config['password_salt'])) {
	        $salted = _furnace()->config['password_salt'] . $pw;
	    } else {
	        $salted = $pw;
	    }
		return md5($salted);
	}
	
	public static function Delete($username) {
		$q = "SELECT `objId` FROM `app_accounts` WHERE `username` = '{$username}' ";
		$id= _db()->queryOne($q);
		FAccount::Delete($id);
	}
	
	public static function DeleteByAccountId($id) {
		FAccount::Delete($id);
		//TODO: this no longer matches with the method signature!
	}
}
?>