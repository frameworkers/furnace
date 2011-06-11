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
namespace furnace\interfaces;


/**
 * Interface for user authentication implementations.
 */
interface IAuthExtension {
	
	const ANONYMOUS     = 'ANONYMOUS';
	const AUTHENTICATED = 'AUTHENTICATED';
	
	/**
	 * Attempt to authenticate a user based on the provided
	 * identity and credential information.
	 * 
	 * @param string $identity    a unique identifier (username)
	 * @param string $credential  an associated secret (password)
	 */
	public function login($identity, $credential);
	
	
	/**
	 * Terminate a user session.
	 */
	public function logout();
	
	/**
	 * Perform a test of whether or not a user is currently
	 * logged in. Should return one of:
	 *   self::ANONYMOUS or
	 *   self::AUTHENTICATED.
	 */
	public function check();
	
	/**
	 * Test whether or not a user is currently logged in, and
	 * redirect to the `applicationLoginUrl` configuration
	 * setting if none found.
	 */
	public function requireLogin();
	
	/**
	 * Return the unique (usually human readable) identity
	 * of the user (username)
	 */
	public function getIdentity();
	
	/**
	 * Return the unique (usually machine readable) identifier
	 * of the user (userid). This may in some implementations be
	 * identical to ::getIdentity().
	 */
	public function getIdentifier();
	
	/**
	 * Return any additional information about the currently
	 * authenticated user.
	 */
	public function getAdditional();
	
	/**
	 * Return an object oriented representation of the currently
	 * authenticated user.
	 */
	public function getEntityObject();
	
}
?>
