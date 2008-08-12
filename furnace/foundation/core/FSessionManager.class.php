<?php
/*
 * frameworkers-foundation
 * 
 * FSessionManager.class.php
 * Created on June 21, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
class FSessionManager {
	public static function doLogin($username='',$password='') {
		// This function will prefer the values from POST over those provided
		// and will only process the provided arguments if POST variables 
		// do not exist
		 $un = (isset($_POST['username'])) 
		 	? addslashes($_POST['username'])
		 	: ((""== $username) ? addslashes($username) : '');
		 $pw = (isset($_POST['password'])) 
		 	? addslashes($_POST['password']) 
		 	: ((""== $password) ? addslashes($password) : '');
		 if ('' == $un || '' == $pw) {
		 	return false; 
		 }
		 $encrypted = md5($pw);
		 $q = "SELECT * FROM `FAccount` "
			. "WHERE `username`='{$un}' AND `password`='{$encrypted}' ";
		 _db()
		 	->setFetchMode(MDB2_FETCHMODE_ASSOC);
		 $r = _db()->queryRow($q);
		 if (is_array($r)) {
		 	// Populate session
			self::initSession($r);
			return true;
		 } else {
		 	return false;
		 }
	}
	
	public static function doLogout() {
		self::uninitSession();
	}
	
	private function initSession($data) {
		session_start();
		header("cache-control: private");
		$fws = array();
		$fws['created']  = mktime();
		$fws['activity'] = mktime();
		$fws['idleseconds'] = 0;
		$fws['username'] = $data['username'];
		$fws['accountid']= $data['objid'];
		$fws['objectId'] = $data['objectid'];
		$fws['objectClass'] = $data['objectclass'];
		$fws['status']   = $data['status'];
		$_SESSION['_fwauth'] = $fws;
		
		$_SESSION['username'] = $data['username'];
		$_SESSION['userid']   = $data['objectid'];
	}
	
	private function uninitSession() {
		$_SESSION = array();
		session_destroy();
	}	

 	public static function checkLogin() {
 		if (isset($_POST['username']) && isset($_POST['password'])) {
 			if (self::doLogin()) {
 				return self::getAccountObject();
 			} else {
 				return false;
 			}
 		} else {
			if (isset($_SESSION['_fwauth'])) {
				$now = mktime();
				$_SESSION['_fwauth']['idleseconds'] = 
					$now - $_SESSION['_fwauth']['activity'];
				$_SESSION['_fwauth']['activity'] = $now;
				return self::getAccountObject();
			} else {
				return false;
			}
 		}
	}

	public static function getAccountId() {
		if (isset($_SESSION['_fwauth'])) {
			return ($_SESSION['_fwauth']['accountid']);
		}
	}
	public static function getAccountObject() {
		if (isset($_SESSION['_fwauth'])) {
			return call_user_func(
				array($_SESSION['_fwauth']['objectClass'], 'Retrieve'),$_SESSION['_fwauth']['objectId']);
		} else {
			return false;
		}
	}
}
?>