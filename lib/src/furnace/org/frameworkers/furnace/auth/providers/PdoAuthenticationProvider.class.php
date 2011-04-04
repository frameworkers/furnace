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
namespace org\frameworkers\furnace\auth\providers;

use \org\frameworkers\furnace\interfaces\IAuthExtension;
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
class PdoAuthenticationProvider extends AbstractAuthenticationProvider implements IAuthExtension {
	
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
	
	public function getEntityObject() {
		return $this;
	}
	
	public function encrypt($clear) {
		return md5($clear . $this->passwordSalt);
	}
}