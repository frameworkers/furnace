<?php
class ImageUploader extends FPageModule {

	private $errorsEncountered = false;
	
	public function __construct($controller) {
		parent::__construct($controller,dirname(__FILE__));
	}

	public function fileUploadRequested() {
		return isset( $_FILES['image_file']);
	}

	public function processUploadedFile($uploadDir,$forceUploadedFileName='') {
		if (!isset($_FILES) && isset($HTTP_POST_FILES)) {
			$_FILES = $HTTP_POST_FILES;
		}
		if (! isset($_FILES['image_file'])) {
			return false;
		}
			
		$imageName = ('' != $forceUploadedFileName)
		? $forceUploadedFileName
		: basename($_FILES['image_file']['name']);
			
		if (empty($imageName)) {
			$this->controller->flash("The name of the image was not found.","error");
			$this->errorsEncountered = true;
			return false;
		}
			
		if (is_uploaded_file($_FILES['image_file']['tmp_name'])) {
			$newFile = $uploadDir . "/" . $imageName;
			if (move_uploaded_file($_FILES['image_file']['tmp_name'],$newFile)) {
				$this->controller->flash("Image file uploaded successfully.");
				return $imageName;
			} else {
				$this->controller->flash("Error moving uploaded file to final location.","error");
				$this->errorsEncountered = true;
				return false;
			}
		} else {
			$this->controller->flash("No uploaded file was detected.","error");
		}
	}

	public function getUploadForm($uploadActionURL='',$uploadFormName='',$uploadFormId='') {
		$this->controller->set('ImageUploader_uploadActionURL',$uploadActionURL);
		$this->controller->set('ImageUploader_uploadFormName',$uploadFormName);
		$this->controller->set('ImageUploader_uploadFormId',$uploadFormId);
		return $this->getView('UploadForm');
	}
	
	public function errorsEncountered() {
		return $this->errorsEncountered;
	}
}
?>