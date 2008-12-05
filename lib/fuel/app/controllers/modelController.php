<?php

class ModelController extends Controller {
	
	public function index() {
		if (!file_exists($GLOBALS['fconfig_root_directory'] . 
			"/app/model/model.yml")) {
			file_put_contents($GLOBALS['fconfig_root_directory'] . 
			"/app/model/model.yml",file_get_contents(
				$GLOBALS['fconfig_root_directory'] .
					"/app/model/model.yml.example"));		
		}
		$this->set('modelcontents',
			file_get_contents(
				$GLOBALS['fconfig_root_directory'].
				"/app/model/model.yml"));
	}
	
	public function generate() {
		if (!$this->form) {
			$this->set('rootdir',$GLOBALS['fconfig_root_directory']);
			$bRootDirectorySet = 
				($GLOBALS['fconfig_root_directory'] != '' &&
				 $GLOBALS['fconfig_root_directory'] != '/path/to/project/root');
			$bModelExists = file_exists($GLOBALS['fconfig_root_directory'] . '/app/model/model.yml');
			$this->set('preflt',array(
				'modelFileExists' =>$bModelExists,
				'rootDirectorySet'=>$bRootDirectorySet));
			$this->set('allgood', ($bRootDirectorySet && $bModelExists));
		}	
	}
	
	public function saveModel() {
		file_put_contents(
			$GLOBALS['fconfig_root_directory'].
			"/app/model/model.yml",$this->form['contents']);
			
		$this->flash("model changes saved. Don't forget to "
			."<a class=\"ff\" href=\"/fuel/model/generate/\">regenerate your model objects</a>!");
		$this->redirect("{$GLOBALS['fconfig_url_base']}/fuel/model/");
	}
	
	public function generateObjects() {
		$output = array();
		// Import required files
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObj.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjAttr.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FObjSocket.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlColumn.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/core/FSqlTable.class.php");
		 require_once($GLOBALS['fconfig_root_directory'] . "/lib/fuel/lib/generation/building/FModel.class.php");
		 
		// Parse the YAML Model File
		 $model_data = FYamlParser::Parse($GLOBALS['fconfig_root_directory'] . "/app/model/model.yml");
		 
		 // Build a representation of the data
		 $model = new FModel($model_data);
		 
		 // Write the object code (individual and compiled)
		 $output[] =  "<h4>Generating PHP Object Code</h4><ul>";
		 $outputfile = fopen($GLOBALS['fconfig_root_directory'] . "/app/model/objects/compiled.php","w");
		 fwrite($outputfile,"<?php\r\n");
		 foreach ($model->objects as $obj) {
		 	$output[] = "<li>Writing class file: {$obj->getName()}</li>";
		 	$phpString = $obj->toPhpString();
			fwrite($outputfile,$phpString."\r\n\r\n");
			file_put_contents($GLOBALS['fconfig_root_directory'] . "/app/model/objects/{$obj->getName()}.class.php",
				"<?php\r\n{$phpString}");
		 }
		 fclose($outputfile); 
		 $output[] =  "</ul>";
		 $output[] =  "<h4>Generating SQL Schema File</h4><ul>";
		 
		 // Write the SQL Schema file
		 $sqlOutputFile = fopen($GLOBALS['fconfig_root_directory'] . "/app/model/model.sql","w");
		 foreach ($model->tables as $t) {
		 	$output[] =  "<li>Writing table definition for: {$t->getName()}</li>";
			fwrite($sqlOutputFile,$t->toSqlString()."\r\n\r\n");
		 }
		 $fAccount = <<<END
-- 
-- Table structure for table `FAccount`
-- 

CREATE TABLE `FAccount` (
  `objId` int(11) unsigned NOT NULL auto_increment COMMENT 'The unique id of this object in the database',
  `username` varchar(20) NOT NULL COMMENT 'The username associated with this account',
  `password` varchar(160) NOT NULL COMMENT 'The password for the account',
  `emailAddress` varchar(80) NOT NULL COMMENT 'The email address associated with this account',
  `status` varchar(20) NOT NULL COMMENT 'The status of this account',
  `secretQuestion` varchar(160) NOT NULL COMMENT 'The secret question for access to this account',
  `secretAnswer` varchar(160) NOT NULL COMMENT 'The secret answer for the secret question',
  `objectClass` varchar(50) NOT NULL COMMENT 'The class of the primary object associated with this account',
  `objectId` int(11) unsigned NOT NULL COMMENT 'The id of the primary object associated with this account',
  PRIMARY KEY  (`objId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

END;
		 fwrite($sqlOutputFile,$fAccount."\r\n\r\n");
		 fclose($sqlOutputFile);
		 
		 $output[] =  "</ul>";
		 $output[] =  "<h5>Finished.</h5>";
		 $this->set('results',implode($output));
	}
}
?>