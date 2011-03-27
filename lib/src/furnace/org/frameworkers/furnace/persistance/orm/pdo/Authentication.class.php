<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
namespace org\frameworkers\furnace\persistance\orm\pdo;

use org\frameworkers\furnace\persistance\session\Session;
use org\frameworkers\furnace\connections\Connections;
use org\frameworkers\furnace\response\Response;
use org\frameworkers\furnace\config\Config;

/**
 * An implementation of the furnace IAuthExtension interface providing
 * user authentication capabilities. Authentication information is 
 * persisted in the PHP Session object.
 * 
 * The following keys must be present in the array passed to ::init():
 * 
 *   connection       - the name of the connection (as defined in connections.config) to use
 *   table            - the name of the sql table containing identities
 *   identityColumn   - the name of the sql column containing the 'username' equivalent
 *   credentialColumn - the name of the sql column containing the 'password' equivalent
 *   identifierColumn - the name of the sql column containing the 'userId' equivalent
 *                      This may be the same as identityColumn, but must also be specified
 *   additionalColumns- An array of strings representing additional data columns to retrieve
 *   passwordSalt     - A constant random string to use when `salting` passwords for encryption
 *                      
 *
 */
class Authentication implements \org\frameworkers\furnace\interfaces\IAuthExtension {
	
	protected $identity;
	protected $connectionLabel;
	protected $table;
	protected $identityColumn;
	protected $credentialColumn;
	protected $identifierColumn;
	protected $additionalColumns;
	
	protected $passwordSalt;
	
	public function init($data) {
		$this->identityColumn    = $data['identityColumn'];
		$this->credentialColumn  = $data['credentialColumn'];
		$this->identifierColumn  = $data['identifierColumn'];
		$this->additionalColumns = $data['additionalColumns'];
		$this->table             = $data['table'];
		$this->connectionLabel   = $data['connection'];
		$this->passwordSalt      = $data['passwordSalt'];
	}	
	
	public function login($identity,$credential) {
		$colList = "`{$this->identifierColumn}`";
		if (is_array($this->additionalColumns) && !empty($this->additionalColumns)) {
			$colList .= ", `" . implode('`,`',$this->additionalColumns) . '`';
		}
		$sql = "SELECT {$colList} FROM `{$this->table}` "
			.  "WHERE  `{$this->table}`.`{$this->identityColumn}`='{$identity}' "
			.  "AND    `{$this->table}`.`{$this->credentialColumn}`='".$this->encrypt($credential)."' "
			.  "LIMIT 1";
		$result = Connections::Get($this->connectionLabel)->query($sql);

		// Authentication successful if exactly one result matched.
		if ($result->rowCount() == 1) {
			$now  = mktime();
			$data = $result->fetch();
			$auth = array(
				"_identity"   => $data[$this->identityColumn],
				"_identifier" => $data[$this->identifierColumn],
				"_additional" => array(),
				"_metadata"   => array(
					"created"     => $now,
					"activity"    => $now,
					"idleseconds" => 0
				)
			);
			$additional = array();
			foreach ($this->additionalColumns as $c) {
				$additional[$c] = $data[$c];
			}
			$auth['_additional'] = $additional;
			
			$_SESSION['_auth'] = $auth;
			return $auth['_identifier'];
		} else {
			return false;
		}
	}
	
	public function logout() {
		// Empty the session authentication information
		Session::Clear('_auth');
	}
	
	public function check($forceRedirect = false) {
		if (isset($_SESSION['_auth'])) {
			$now = mktime();
			$_SESSION['_auth']['_metadata']['idleseconds'] = 
				$now - $_SESSION['_auth']['_metadata']['activity'];
			$_SESSION['_auth']['_metadata']['activity'] = $now;
			return $_SESSION['_auth'];
		} else {
			if ($forceRedirect) {
				// Current page
				$afterLogin = str_replace('/','+',$_SERVER['REDIRECT_URL']);
				Response::Redirect(Config::Get('applicationLoginUrl') . '/' . $afterLogin);
			} else {
				return false;
			}
		}
	}
	
	public function getStatus() {
		return (isset($_SESSION['_auth']))
			? self::AUTHENTICATED
			: self::ANONYMOUS;
	}
	
	public function getIdentity() {
		return isset($_SESSION['_auth']['_identity'])
			? $_SESSION['_auth']['_identity']
			: false;
	}
	
	public function getIdentifier() {
		return isset($_SESSION['_auth']['_identifier'])
			? $_SESSION['_auth']['_identifier']
			: false;
	}
	
	public function getAdditional() {
		return isset($_SESSION['_auth']['_additional'])
			? $_SESSION['_auth']['_additional']
			: false;
	}
	
	public function getEntityObject() {
		return $this;
	}
	
	public function encrypt($clear) {
		return md5($clear . $this->passwordSalt);
	}
}