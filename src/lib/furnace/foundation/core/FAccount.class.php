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
		
		public function hasRole($namedRole) {
			if (is_array($this->roles)){
				return isset($this->roles[$namedRole]);
			} else {
				$this->roles = self::getRolesForId($this->roles);
				return isset($this->roles[$namedRole]);
			}
		}
		
		public function getFAccountId() {
			return $this->faccount_id;
		}
		public function setUsername($value,$bValidate = false) {
			// Set the provided value
			$this->username = $value;
			$this->_dirtyTable['username'] = $value;
			if ($bValidate) {
				$this->validator->fAccountUsername($this->username);
			}
		}

		public function setPassword($value,$bValidate = false) {
			// Set the provided value
			$this->password = $value;
			$this->_dirtyTable['password'] = $value;
			if ($bValidate) {
				$this->validator->fAccountPassword($this->password);
			}
		}

		public function setEmailAddress($value,$bValidate = false) {
			// Set the provided value
			$this->emailAddress = $value;
			$this->_dirtyTable['emailAddress'] = $value;
			if ($bValidate) {
				$this->validator->fAccountEmailAddress($this->emailAddress);
			}
		}

		public function setStatus($value,$bValidate = false) {
			// Set the provided value
			$this->status = $value;
			$this->_dirtyTable['status'] = $value;
			if ($bValidate) {
				$this->validator->fAccountStatus($this->status);
			}
		}
		
		public function setSecretQuestion($value,$bValidate = false) {
			// Set the provided value
			$this->secretQuestion = $value;
			$this->_dirtyTable['secretQuestion'] = $value;
			if ($bValidate) {
				$this->validator->fAccountSecretQuestion($this->secretQuestion);
			}
		}

		public function setSecretAnswer($value,$bValidate = false) {
			// Set the provided value
			$this->secretAnswer = $value;
			$this->_dirtyTable['secretAnswer'] = $value;
			if ($bValidate) {
				$this->validator->fAccountSecretAnswer($this->secretAnswer);
			}
		}

		public function setObjectClass($value,$bValidate = false) {
			// Set the provided value
			$this->objectClass = $value;
			$this->_dirtyTable['objectClass'] = $value;
			if ($bValidate) {
				$this->validator->fAccountObjectClass($this->objectClass);
			}
		}

		public function setObjectId($value,$bValidate = false) {
			// Set the provided value
			$this->objectId = $value;
			$this->_dirtyTable['objectId'] = $value;
			if ($bValidate) {
				$this->validator->fAccountObjectId($this->objectId);
			}
		}
		
		public function setCreated($value,$bValidate = false) {
			// Set the provided value
			$this->created = $value;
			$this->_dirtyTable['created'] = $value;
			if ($bValidate) {
				$this->validator->fAccountCreated($this->created);
			}
		}
		
		public function setModified($value,$bValidate = false) {
			// Set the provided value
			$this->created = $value;
			$this->_dirtyTable['modified'] = $value;
			if ($bValidate) {
				$this->validator->fAccountModified($this->modified);
			}
		}
		
		public function setLastLogin($value,$bValidate = false) {
			// Set the provided value
			$this->created = $value;
			$this->_dirtyTable['lastLogin'] = $value;
			if ($bValidate) {
				$this->validator->fAccountLastLogin($this->lastLogin);
			}
		}

		public function save($data = array(), $bValidate = true) {
			
 			if ($this->objId == 0) {
 				
				// Create an 'FAccount' (app_accounts + app_roles) for this object
				$newAccount  = true;
				$accountInfo = FAccountManager::Create($this->username,$this->password,$this->emailAddress);
				$this->faccount_id  = $accountInfo['faccount_id'];
				$this->roles        = $accountInfo['faccount_id'];
				$faccount_id        = $accountInfo['faccount_id'];
				$this->password     = $accountInfo['encryptedPassword'];
				if (false === $faccount_id) { return false; }	
				
			} else {
				
				// Update the FAccount attributes for this object
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
				_db()->exec($q);
				
				// unset the entries in the dirty table
				unset($this->_dirtyTable['username']);
				unset($this->_dirtyTable['password']);
				unset($this->_dirtyTable['emailAddress']);
				unset($this->_dirtyTable['status']);
				unset($this->_dirtyTable['secretQuestion']);
				unset($this->_dirtyTable['secretAnswer']);
				unset($this->_dirtyTable['objectClass']);
				unset($this->_dirtyTable['objectId']);
				unset($this->_dirtyTable['created']);
				unset($this->_dirtyTable['modified']);
				unset($this->_dirtyTable['lastLogin']);
				
				// unset the entries in the data array
				unset($data['username']);
				unset($data['password']);
				unset($data['emailAddress']);
				unset($data['status']);
				unset($data['secretQuestion']);
				unset($data['secretAnswer']);
				unset($data['objectClass']);
				unset($data['objectId']);
				unset($data['created']);
				unset($data['modified']);
				unset($data['lastLogin']);
			}			
			
			// Call FBaseObject::save to handle everything else
			if (parent::save($data,$bValidate) ) {
				
				if ($newAccount) {
					// If this was a *new* account, store (associate) the faccount_id with the specific object 
					$this->faccount_id = $accountInfo['faccount_id'];
					$this->objectClass = $this->fObjectType;
					$this->objectId    = $this->objId;
					$q = "UPDATE `{$this->fObjectTableName}` SET `faccount_id`={$this->faccount_id} WHERE `objId`={$this->objId} LIMIT 1";
					_db()->exec($q);
					$q = "UPDATE `app_accounts` SET `objectClass`='{$this->fObjectType}', `objectId`={$this->objId} WHERE `objId`={$this->faccount_id} LIMIT 1";
					_db()->exec($q);
				}
				// All set. Return true
				return true;
			} else {
				// Something went wrong in the underlying ::save call
				return false;
			}
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
			$q = "UPDATE `app_roles` SET `{$namedRole}`='1' WHERE `accountId`='{$this->getFAccountId()}' ";
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			$this->roles[$namedRole] = true;
		}
		
		public function grantRoles($namedRoles) {
			if (array() == $namedRoles) {return false;}
			$roles = array();
			foreach ($namedRoles as $role) {
				$roles[] = " `{$role}`='1' ";
			}
			
			$q = "UPDATE `app_roles` SET " . implode(",",$roles) 
			  . " WHERE `accountId`='{$this->getFAccountId()}' ";
			$r = _db()->exec($q);
			if (is_array($this->roles)) {
				foreach ($namedRoles as $role) {
					$this->roles[$role] = true;
				}
			}
		}
		
		public function denyRole($namedRole) {
			$q = "UPDATE `app_roles` SET `{$namedRole}`='0' WHERE `accountId`='{$this->getFAccountId()}' ";
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			if (is_array($this->roles) && isset($this->roles[$namedRole])) {
				unset($this->roles[$namedRole]);
			}
		}

		public static function Create($username,$password,$data) {

			/**
			 * DEPRECATED
			 */
			/*$now = date('Y-m-d G:i:s');
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
			*/
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
			$results  = _db()->queryAll($q,FDATABASE_FETCHMODE_ASSOC);
			$response = array();
			foreach ($results as $r) {
				$response[] = $r['objectId'];
			}
			return $response;
		}
		
		public static function Delete($objId,$acctId,$class) {
			
			//$q = "SELECT `objId` FROM `app_accounts` WHERE `objectId`='{$objId}' AND `objectClass`='{$this->fObjectType}' LIMIT 1";
			//$fAccountId = _db()->queryOne($q);
			$fAccountId   = $acctId;
			
			
			// Call FBaseObject::Delete
			parent::Delete($objId,$class);
			
			// Delete the `app_roles` entry associated with this account
			$q = "DELETE FROM `app_roles` WHERE `accountId`='{$fAccountId}' ";
			$r = _db()->exec($q);
			// Delete the `app_accounts` entry itself
			$q = "DELETE FROM `app_accounts` WHERE  `objId`='{$fAccountId}' ";
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