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

		public function __construct($data) {
			if (isset($data['objid'])) {$data['objId'] = $data['objid'];}
			if (!isset($data['objId']) || $data['objId'] <= 0) {
				throw new FException("Invalid <code>objId</code> value '' in object constructor.");
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

		public function setUsername($value,$bSaveImmediately = true) {
			$this->username = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::USERNAME);
			}
		}

		public function setPassword($value,$bSaveImmediately = true) {
			$this->password = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::PASSWORD);
			}
		}

		public function setEmailAddress($value,$bSaveImmediately = true) {
			$this->emailAddress = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::EMAILADDRESS);
			}
		}

		public function setStatus($value,$bSaveImmediately = true) {
			$this->status = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::STATUS);
			}
		}

		public function setSecretQuestion($value,$bSaveImmediately = true) {
			$this->secretQuestion = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::SECRETQUESTION);
			}
		}

		public function setSecretAnswer($value,$bSaveImmediately = true) {
			$this->secretAnswer = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::SECRETANSWER);
			}
		}

		public function setObjectClass($value,$bSaveImmediately = true) {
			$this->objectClass = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::OBJECTCLASS);
			}
		}

		public function setObjectId($value,$bSaveImmediately = true) {
			$this->objectId = $value;
			if ($bSaveImmediately) {
				$this->save(FAccountAttrs::OBJECTID);
			}
		}

		public function save($attribute = '') {
			if('' == $attribute) {
				$q = "UPDATE `FAccount` SET " 
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
				$q = "UPDATE `FAccount` SET `{$attribute}`='{$this->$attribute}' WHERE `objId`='{$this->objId}' ";
			}
			_db()->exec($q);
		}

		public static function Create($username) {
			$q = "INSERT INTO `FAccount` (`username`) VALUES ('{$username}')"; 
			$r = _db()->exec($q);
			if (MDB2::isError($r)) {
				FDatabaseErrorTranslator::translate($r->getCode());
			}
			$objectId = _db()->lastInsertID("FAccount","objId");
			$data = array("objId"=>$objectId,"username"=>$username);
			return new FAccount($data);
		}
		public static function Retrieve($objId) {
			_db()->setFetchMode(MDB2_FETCHMODE_ASSOC);
			$q = "SELECT * FROM `FAccount` WHERE `objId`='{$objId}' LIMIT 1 ";
			$r = _db()->queryRow($q);
			return new FAccount($r);
		}
	}

	class FAccountCollection extends FObjectCollection {
		public function __construct($lookupTable="FAccount",$filter="WHERE 1") {
			parent::__construct("FAccount",$lookupTable,$filter);
		}
		public function destroyObject($objectId) {
			//TODO: Implement this
		}
	}
?>