<?php
namespace org\frameworkers\foundry\application;
use org\frameworkers\furnace\config\Config;


class Application {
	
	public $name;
	public $rootDirectory;
	public $rootPassword;
	public $webAdminEnabled;
	public $configuration;
	
	public static $applications = array();
	
	public static function DiscoverInstalledApplications() {
		$appsDir = dirname(FURNACE_APP_PATH);
		
		if ($dh = opendir($appsDir)) {
			while (false !== ($candidate = readdir($dh))) {
				if (is_dir("{$appsDir}/{$candidate}") && $candidate[0] != '.' && $candidate != 'foundry') {
					self::$applications[] = new Application("{$appsDir}/{$candidate}");
				}
			}
		}
	}
	
	public function __construct($dir) {
		$this->name = basename($dir);
		$this->rootDirectory = $dir;
		
		// Obtain and store the application's configuration settings
		$foundryConfig = Config::Get('*');
		Config::Clear();
		require($dir . "/config/application.config.php");
		$this->rootPassword = Config::Get('rootPassword');
		$this->webAdminEnabled = Config::Get('enableWebAdministration');
		$this->configuration   = Config::Get('*');
		
		// Restore Foundry Configuration
		Config::Reload($foundryConfig);
	}
}