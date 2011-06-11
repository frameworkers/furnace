<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage auth\providers
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
namespace furnace\auth\providers;

use furnace\interfaces\IAuthExtension;
use furnace\core\Config;
use furnace\response\Response;

abstract class AbstractAuthenticationProvider implements IAuthExtension {
	
	
	// Syntactic sugar on top of ::check()
	public function requireLogin() {
		return $this->check(true);
	}

	public function check($forceRedirect = false) {
        $key = Config::Get('sess.auth.key');

		if (isset($_SESSION[$key])) {
			$now = mktime();
			$_SESSION[$key]['metadata']['idleseconds'] = 
				$now - $_SESSION[$key]['metadata']['activity'];
			$_SESSION[$key]['metadata']['activity'] = $now;
			return $_SESSION[$key];
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
		return (isset($_SESSION[Config::Get('sess.auth.key')]))
			? self::AUTHENTICATED
			: self::ANONYMOUS;
	}
	
	public function getIdentity() {
        $key = Config::Get('sess.auth.key');
		return isset($_SESSION[$key]['identity'])
			? $_SESSION[$key]['identity']
			: false;
	}
	
	public function getIdentifier() {
        $key = Config::Get('sess.auth.key');
		return isset($_SESSION[$key]['identifier'])
			? $_SESSION[$key]['identifier']
			: false;
	}
	
	public function getAdditional() {
        $key = Config::Get('sess.auth.key');
		return isset($_SESSION[$key]['additional'])
			? $_SESSION[$key]['additional']
			: false;
	}
}
