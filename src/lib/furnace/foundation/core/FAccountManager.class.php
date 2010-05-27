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
		$r = _db()->rawExec($q);
		if (MDB2::isError($r)) {
			FDatabaseErrorTranslator::translate($r->getCode());
		}
		$faccountId = _db()->lastInsertID("app_accounts","faccount_id");
				
		// Add entry in app_roles
		$q = "INSERT INTO `app_roles` (`faccount_id`) VALUES ('{$faccountId}')";
		$r = _db()->rawExec($q);
		if (MDB2::isError($r)) {
			FDatabaseErrorTranslator::translate($r->getCode());
		}
		return array("faccount_id"=>$faccountId,"encryptedPassword"=>$encrypted);
	}
	
    public static function ChangePassword($user,$pw) {
	    $user->setPassword(self::EncryptPassword($pw));
	    $user->setNewPasswordKey('');
	    $user->save();
	}
	
	public static function EncryptPassword($pw) {
	    if (isset(_furnace()->config->data['password_salt'])) {
	        $salted = _furnace()->config->data['password_salt'] . $pw;
	    } else {
	        $salted = $pw;
	    }
		return md5($salted);
	}
	
    public static function GenerateForgotPasswordKey($emailAddress) {
	    $q = "SELECT * FROM `app_accounts` WHERE `emailAddress`='{$emailAddress}' ";
	    $r = _db()->rawQuery($q,array('type'=>'row'));
	    
	    if ($r) {
	        // Generate the key
	        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	        for ($i=0;$i<22;$i++) {
	            $npk .= $chars[rand(0,61)];
	        }
	        // Associate it with the account
	        $q = "UPDATE `app_accounts` SET `newPasswordKey`='{$npk}' WHERE `faccount_id`={$r['faccount_id']} LIMIT 1";
	        _db()->rawExec($q);
	        
	        return $npk;
	    }
	    return false;
	}
	
	public static function EmailAddressExists($emailAddress,$bGetObject=false) {

	    $q = "SELECT * FROM `app_accounts` WHERE `emailAddress`='{$emailAddress}' LIMIT 1";
	    $r = _db()->rawQuery($q,array('type'=>'row'));
	    
	    if ($r) {
	        if ($bGetObject) { 
	            $c = $r['objectClass'];
	            $o = call_user_func_array(array($r['objectClass'],'Retrieve'),array($r['objectId']));
	            return $o;
	        } else {
	            return true;
	        }
	    }
	    else { 
	        return false; 
	    }
	}
	
	public static function Delete($username) {
		$q = "SELECT * FROM `app_accounts` WHERE `username` = '{$username}' ";
		$data = _db()->rawQuery($q,array('type'=>'row'));
		if ($data) {
		    // Call the actual object's delete function
		    call_user_func_array(array($r['objectClass'],'Delete'),array($r['objectId']));
		}
	}
	
	public static function DeleteByAccountId($id) {
	    $q = "SELECT * FROM `app_accounts` WHERE `faccount_id` = '{$id}' ";
		$data = _db()->rawQuery($q,array('type'=>'row'));
		if ($data) {
		    // Call the actual object's delete function
		    call_user_func_array(array($r['objectClass'],'Delete'),array($r['objectId']));
		}
	}
}
?>