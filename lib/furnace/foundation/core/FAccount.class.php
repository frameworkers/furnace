<?php
/*
 * frameworkers-foundation
 * 
 * FAccount.class.php
 * Created on June 17, 2008
 *
 * Copyright 2008 Frameworkers.org. 
 * http://www.frameworkers.org
 */
 
 /*
  * YML Model
  * 
object FAccount:
  attr Username:
    desc: The username associated with this account
    type: string
    size: 20
    min:  5
    unique: yes
  attr Password:
    desc: The password for the account
    type: string
    size: 160
  attr EmailAddress:
    desc: The email address associated with this account
    type: string
    size: 80
  attr Status:
    desc: The status of this account
    type: string
    size: 20
  attr SecretQuestion:
    desc: The secret question for access to this account
    type: string
    size: 160
  attr SecretAnswer:
    desc: The secret answer for the secret question
    type: string
    size: 160
  attr ObjectClass:
    desc: The class of the primary object associated with this account
    type: string
    size: 50
  attr ObjectId:
    desc: The id of the primary object associated with this account
    type: integer
    min: 0
  *
  */

class FAccountAttrs {
		 const USERNAME = "username";
		 const PASSWORD = "password";
		 const EMAILADDRESS = "emailAddress";
		 const STATUS = "status";
		 const SECRETQUESTION = "secretQuestion";
		 const SECRETANSWER = "secretAnswer";
		 const OBJECTCLASS = "objectClass";
		 const OBJECTID = "objectId";
	}

/*
 * Class: FAccount
 * Provides a common base class for user-defined objects requiring
 * some sort of login (user accounts). 
 * 
 * Extends:
 * 
 *  FBaseObject
 */
class FAccount extends FBaseObject {
		// Variable: username
		// The username associated with this account
		public $username;

		// Variable: password
		// The password for the account
		public $password;

		// Variable: emailAddress
		// The email address associated with this account
		public $emailAddress;

		// Variable: status
		// The status of this account
		public $status;

		// Variable: secretQuestion
		// The secret question for access to this account
		public $secretQuestion;

		// Variable: secretAnswer
		// The secret answer for the secret question
		public $secretAnswer;

		// Variable: objectClass
		// The class of the primary object associated with this account
		public $objectClass;

		// Variable: objectId
		// The id of the primary object associated with this account
		public $objectId;
		
		// Variable: roles
		// The roles granted to this user account
		public $roles;

		public function __construct($data) {
			if (isset($data['objid'])) {$data['objId'] = $data['objid'];}
			if (!isset($data['objid']) || $data['objid'] <= 0) {
				throw new FException("Invalid <code>objId</code> value in object constructor.");
			}
			$this->objId = $data['objId'];
			$this->username = $data['username'];
			$this->password = $data['password'];
			$this->emailAddress = $data['emailaddress'];
			$this->status = $data['status'];
			$this->secretQuestion = $data['secretquestion'];
			$this->secretAnswer = $data['secretanswer'];
			$this->objectClass = $data['objectclass'];
			$this->objectId = $data['objectid'];
			
			// Get Roles
			$q = "SELECT * FROM `app_roles` WHERE `accountId`='{$this->objId}' ";
			$r = _db()->queryRow($q);
			$this->roles = array();
			foreach ($r as $role=>$value) {
				if ("accountid" == $role) {continue;}
				if (1 == $value) {
					$this->roles[$role] = $value;
				}
			}
		}

		public function getUsername() {
			return $this->username;
		}

		public function getPassword() {
			return $this->password;
		}

		public function getEmailAddress() {
			return $this->emailAddress;
		}

		public function getStatus() {
			return $this->status;
		}

		public function getSecretQuestion() {
			return $this->secretQuestion;
		}

		public function getSecretAnswer() {
			return $this->secretAnswer;
		}

		public function getObjectClass() {
			return $this->objectClass;
		}

		public function getObjectId() {
			return $this->objectId;
		}

		public function getRoles() {
			return $this->roles;
		}
		public function setUsername($value,$bSaveImmediately = true) {
			$this->username = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('username');
			}
		}

		public function setPassword($value,$bSaveImmediately = true) {
			$this->password = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('password');
			}
		}

		public function setEmailAddress($value,$bSaveImmediately = true) {
			$this->emailAddress = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('emailAddress');
			}
		}

		public function setStatus($value,$bSaveImmediately = true) {
			$this->status = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('status');
			}
		}

		public function setSecretQuestion($value,$bSaveImmediately = true) {
			$this->secretQuestion = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('secretQuestion');
			}
		}

		public function setSecretAnswer($value,$bSaveImmediately = true) {
			$this->secretAnswer = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('secretAnswer');
			}
		}

/*
 * THESE SHOULD NEVER BE CALLED.
 * Once an account has been associated with a unique object, it is mated for life.
 * The values of 'objectClass' and 'objectId' must be invariant across the lifetime
 * of an object. They are used by faccount_save to properly access object's faccount
 * attributes.
 * 
		public function setObjectClass($value,$bSaveImmediately = true) {
			$this->objectClass = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('objectClass');
			}
		}

		public function setObjectId($value,$bSaveImmediately = true) {
			$this->objectId = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('objectId');
			}
		}
*
* 
*/

		public function faccount_save($attribute = '') {
			if('' == $attribute) {
				$q = "UPDATE `app_accounts` SET " 
				. "`username`='{$this->username}', "
				. "`password`='{$this->password}', "
				. "`emailAddress`='{$this->emailAddress}', "
				. "`status`='{$this->status}', "
				. "`secretQuestion`='{$this->secretQuestion}', "
				. "`secretAnswer`='{$this->secretAnswer}', "
				. "`objectClass`='{$this->objectClass}', "
				. "`objectId`='{$this->objectId}' ";
				$q .= "WHERE `objId`='{$this->objId}'";
			} else {
				$q = "UPDATE `app_accounts` SET `{$attribute}`='{$this->$attribute}' WHERE `objectId`='{$this->objectId}' AND `objectClass`='{$this->objectClass}' ";
			}
			_db()->exec($q);
		}
		
		public function requireRole($namedRole,$failPage='/') {
			if (isset($this->roles[$namedRole])) {
				return true;
			} else {
				header("Location: {$failPage}");
				exit;
			}
		}
		
		public function requireRoles($namedRoles,$failPage='/') {
			foreach ($namedRoles as $role) {
				if (! isset($this->roles[$role])) {
					header("Location: {$failPage}");
					exit;
				}
			}
			return true;
		}
		
		public function grantRole($namedRole) {
			$q = "UPDATE `app_roles` SET `{$namedRole}`='1' WHERE `accountId`='{$this->getObjId()}' ";
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			$this->roles[$namedRole] = true;
		}
		
		public function denyRole($namedRole) {
			$q = "UPDATE `app_roles` SET `{$namedRole}`='0' WHERE `accountId`='{$this->getObjId()}' ";
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			unset($this->roles[$namedRole]);
		}

		public static function Create($username) {
			$q = "INSERT INTO `app_accounts` (`username`) VALUES ('{$username}')"; 
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			$objectId = _db()->lastInsertID("app_accounts","objId");
			$data = array("objid"=>$objectId,"username"=>$username);
			
			$q = "INSERT INTO `app_roles` (`accountId`) VALUES ('{$objectId}')";
			$r = _db()->exec($q);
			
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			return new FAccount($data);
		}
		
		public static function Retrieve($objId) {
			_db()->setFetchMode(MDB2_FETCHMODE_ASSOC);
			$q = "SELECT * FROM `app_accounts` WHERE `objId`='{$objId}' LIMIT 1 ";
			$r = _db()->queryRow($q);
			return new FAccount($r);
		}
		
		public static function Delete($objId) {
			// Delete the `app_roles` entry associated with this account
			$q = "DELETE FROM `app_roles` WHERE `accountId`='{$objId}' ";
			$r = _db()->exec($q);
			// Delete the `app_accounts` entry itself
			$q = "DELETE FROM `app_accounts` WHERE `objId`='{$objId}' ";
			$r = _db()->exec($q);
		}
		
		public static function DefineRole($name,$defaultAttribution,$description='') {
			if (true == $defaultAttribution) {
				$default = 1;
			} else {
				$default = 0;
			}
			$q = "ALTER TABLE `app_roles` ADD COLUMN `{$name}` INT(11) DEFAULT {$default} COMMENT '{$description}' ";
			$r = _db()->exec($q);
		}
		
		public static function DeleteRole($name) {
			$q = "ALTER TABLE `app_roles` DROP COLUMN `{$name}` ";
			$r = _db()->exec($q);
		}
	}

	class FAccountCollection extends FObjectCollection {
		public function __construct($lookupTable="app_accounts",$filter="WHERE 1") {
			parent::__construct("FAccount",$lookupTable,$filter);
		}
		public function destroyObject($objectId) {
			//TODO: Implement this
		}
	}
?>