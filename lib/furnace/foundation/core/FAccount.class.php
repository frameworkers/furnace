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
 * Class: FAccount
 * Provides a common base class for user-defined objects requiring
 * some sort of login (user/member accounts). 
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
		
		// Variable: created 
		// The date/time this account was created
		public $created;
		
		// Variable: modified
		// The last time this account was modified
		public $modified;
		
		// Variable: lastLogin
		// The last time this account logged in
		public $lastLogin;
		
		// Variable: faccount_id
		// The objId of the app_account entry for this account
		protected $faccount_id;
		
		
		public function __construct($data) {
			
			$this->faccount_id = $data['faccount_id'];
			$this->created     = $data['created'];
			$this->modified    = $data['modified'];
			$this->lastLogin   = $data['lastLogin'];
			$this->objectClass = $data['objectClass'];
			$this->objectId    = $data['objectId'];
			$this->roles       = $data['faccount_id'];
			
			
			
			/*
			if (isset($data['objid'])) {$data['objId'] = $data['objid'];}
			if ($data['objId'] <= 0) {
				throw new FException("Invalid <code>objId</code> value in object constructor.");
			}
			$this->objId = $data['objId'];
			$this->username = $data['username'];
			$this->password = $data['password'];
			$this->emailAddress = $data['emailAddress'];
			$this->status = $data['status'];
			$this->secretQuestion = $data['secretQuestion'];
			$this->secretAnswer = $data['secretAnswer'];
			$this->objectClass = $data['objectClass'];
			$this->objectId = $data['objectId'];
			$this->created  = $data['created'];
			$this->modified = $data['modified'];
			$this->lastLogin= $data['lastLogin'];
			
			// Get Roles
			$q = "SELECT * FROM `app_roles` WHERE `accountId`='{$this->objId}' ";
			$r = _db()->queryRow($q,FDATABASE_FETCHMODE_ASSOC);
			$this->roles = array();
			foreach ($r as $role=>$value) {
				if ("accountId" == $role) {continue;}
				if (1 == $value) {
					$this->roles[$role] = $value;
				}
			}
			*/
		}
		
		public static function getRolesForId($id) {
			// Get Roles for the provided account id
			$q = "SELECT * FROM `app_roles` WHERE `accountId`='{$id}' ";
			$r = _db()->queryRow($q,FDATABASE_FETCHMODE_ASSOC);
			$roles = array();
			foreach ($r as $role=>$value) {
				if ("accountId" == $role) {continue;}
				if (1 == $value) {
					$roles[$role] = $value;
				}
			}
			return $roles;
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
		
		public function getCreated() {
			return $this->created;
		}
		
		public function getModified() {
			return $this->modified;
		}
		
		public function getLastLogin() {
			return $this->lastLogin;
		}

		public function getRoles() {
			if (is_array($this->roles)){
				return $this->roles;
			} else {
				$this->roles = self::getRolesForId($this->roles);
				return $this->roles; 
			}
		}
		
		public function getFAccountId() {
			return $this->faccount_id;
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
		
		public function setCreated($value,$bSaveImmediately = true) {
			$this->created = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('created');
			}
		}
		
		public function setModified($value,$bSaveImmediately = true) {
			$this->created = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('modified');
			}
		}
		
		public function setLastLogin($value,$bSaveImmediately = true) {
			$this->created = $value;
			if ($bSaveImmediately) {
				$this->faccount_save('lastLogin');
			}
		}

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
				. "`objectId`='{$this->objectId}', "
				. "`created`='{$this->created}', "
				. "`modified`=NOW(), "
				. "`lastLogin`='{$this->lastLogin}' ";
				$q .= "WHERE `objId`='{$this->faccount_id}'";
			} else {
				$q = "UPDATE `app_accounts` SET `{$attribute}`='{$this->$attribute}', `modified`=NOW() WHERE `objId`='{$this->faccount_id}' ";
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
			$now = date('Y-m-d G:i:s');
			$q = "INSERT INTO `app_accounts` (`username`,`created`,`modified`) VALUES ('{$username}','{$now}','{$now}')"; 
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			$objectId = _db()->lastInsertID("app_accounts","objId");
			$data = array("faccount_id"=>$objectId,"username"=>$username,"created"=>$now,"modified"=>$now);
			
			$q = "INSERT INTO `app_roles` (`accountId`) VALUES ('{$objectId}')";
			$r = _db()->exec($q);
			
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			return new FAccount($data);
		}
		
		public static function Retrieve($objId) {
			_db()->setFetchMode(FDATABASE_FETCHMODE_ASSOC);
			$q = "SELECT * FROM `app_accounts` WHERE `objId`='{$objId}' LIMIT 1 ";
			$r = _db()->queryRow($q);
			return new FAccount($r);
		}
		
		public static function RetrieveByRole($objectClass,$role,$value=true) {
			
			if ($value) {$value = '1';} else {$value = '0';}
			
			$q = "SELECT `app_accounts`.`objectId` FROM `app_accounts`, `app_roles`
				WHERE `app_accounts`.`objId`=`app_roles`.`accountId` 
				AND `app_roles`.`{$role}`='{$value}' 
				AND `app_accounts`.`objectClass`='{$objectClass}'";
			$results  = _db()->queryAll($q);
			$response = array();
			foreach ($results as $r) {
				$response[] = $r['objectId'];
			}
			return $response;
		}
		
		public static function Delete($objId) {
			// Delete the `app_roles` entry associated with this account
			$q = "DELETE FROM `app_roles` WHERE `accountId`='{$objId}' ";
			$r = _db()->exec($q);
			// Delete the `app_accounts` entry itself
			$q = "DELETE FROM `app_accounts` WHERE `objId`='{$objId}' ";
			$r = _db()->exec($q);
		}
		
		public static function DefineRole($name,$defaultAttribution) {
			if (true == $defaultAttribution) {
				$default = 1;
			} else {
				$default = 0;
			}
			$q = "ALTER TABLE `app_roles` ADD COLUMN `{$name}` INT(11) DEFAULT {$default} ";
			$r = _db()->exec($q);
		}
		
		public static function DeleteRole($name) {
			$q = "ALTER TABLE `app_roles` DROP COLUMN `{$name}` ";
			$r = _db()->exec($q);
		}
	}
/*
	class FAccountCollection extends FObjectCollection {
		public function __construct($lookupTable="app_accounts",$filter="WHERE 1") {
			parent::__construct("FAccount",$lookupTable,$filter);
		}
		public function destroyObject($objectId) {
			//TODO: Implement this
		}
	}
*/
?>