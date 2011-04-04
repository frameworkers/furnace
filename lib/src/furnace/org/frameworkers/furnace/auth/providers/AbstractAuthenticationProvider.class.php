<?php
namespace org\frameworkers\furnace\auth\providers;

use org\frameworkers\furnace\interfaces\IAuthExtension;
use org\frameworkers\furnace\config\Config;
use org\frameworkers\furnace\response\Response;

abstract class AbstractAuthenticationProvider implements IAuthExtension {
	
	
	// Syntactic sugar on top of ::check()
	public function requireLogin() {
		return $this->check(true);
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
}