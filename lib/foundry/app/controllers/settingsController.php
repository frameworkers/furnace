<?php
class settingsController extends Controller {
	
	
	public function application() {
		
	}
	
	public function saveApplicationSettings() {
		if ($this->form) {
			// Load the application settings file
			$pathToAppSettings = _furnace()->rootdir . '/app/config/app.yml';
			/*,FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES*/
			$contents = file($pathToAppSettings);
			foreach ($this->form as $k => $v) {
				$klen = strlen($k);
				$lineCounter= 0;
				foreach ($contents as $line) {
					if ('#' == $line[0]) {$lineCounter++;continue;}
					else {
						if (substr($line,0,$klen) == $k) {
							//replace the value
							$contents[$lineCounter] = "{$k}: {$v}\r\n";
							continue;
						}
					}
					$lineCounter++;
				}
			}
			file_put_contents($pathToAppSettings,$contents);
			$this->flash("Settings saved.");
			$this->redirect("/_furnace/settings/application");
		} else {
			$this->internalRedirect("/_error/http404");
		}
	}
	
	public function routes() {
		
	}
	
}
?>