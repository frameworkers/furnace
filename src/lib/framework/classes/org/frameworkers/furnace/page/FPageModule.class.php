<?php
namespace org\frameworkers\furnace\page;

class FPageModule {

	protected $modulePath;
	protected static $renderer;
	
	public function __construct() {
		$this->modulePath = FURNACE_APP_PATH
			. "/modules/"
			. dirname(str_replace('\\','/',get_class($this)));	

		// Initialize the static renderer
		if (!isset($this->renderer)) {
			$this->renderer = new namespace\FPageTemplate();
		}
		
		$this->renderer->reset();
	}
	
	public function set($key,$val) {
		$this->renderer->page_data[$key] = $val;
	}
	
	/**
	 * Fetch and process a module block
	 * 
	 * This function attempts to render the named block
	 * by calling the correspondingly named function of 
	 * the pageModule. The page module function can 
	 * programmatically cancel display of the block by
	 * simply returning false, in which case the call
	 * to {@link renderBlock} never occurrs.
	 * 
	 * The following assumptions are made:
	 *   1) an html file named <blockName>.html exists in
	 *      the module's /blocks directory
	 *   2) the function module::blockName() is callable
	 *   
	 * Any data provided to this function will be passed
	 * along as parameters to the module::blockName() call
	 * 
	 * @param $blockName
	 * @param $data
	 */
	public function getBlock($blockName,$data = array()) {
		if (is_callable(array($this,$blockName))) {
			if (is_array($data)) {
				$result = call_user_func_array(array($this,$blockName),$data);
			} else {
				$result = $this->$blockName($data);
			}
			// If the function did not explicitly return false,
			// render the block contents
			if (false !== $result) {
				return $this->renderBlock($blockName);
			}
		} else {
			throw new \Exception("getBlock '{$blockName}' requested but " 
				. get_class($this) . "::{$blockName}() is not callable");
		}
	}
	
	protected function renderBlock($which) {
		$modulePath = FURNACE_APP_PATH
			. "/modules/"
			. dirname(str_replace('\\','/',get_class($this)));
		$blockPath = "{$modulePath}/blocks/{$which}.html";
		if (is_file($blockPath)) {
			$contents = file_get_contents($blockPath);
			return $this->renderer->compile($contents);
		} else {
			// TODO: Log this error
			throw new \Exception("renderBlock requested but block '{$which}' does not exist");
		}
	}
	
	protected function error($message) {
		return "<div class=\"error\">{$message}</div>";
	}
	
}