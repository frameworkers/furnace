<?php
class ImageUploader extends FPageModule {

	private $errorsEncountered = false;
	private $maxUploadedFileSizeBytes = 0;
	
	public function __construct($controller,$maxUploadedFileSizeBytes = 3000000) {
		parent::__construct($controller,dirname(__FILE__));
		$this->maxUploadedFileSizeBytes = $maxUploadedFileSizeBytes;
	}

	public function fileUploadRequested() {
		return isset( $_FILES['fiu-file']);
	}

	/**
	 * processUploadedFile
	 * 
	 * Perform the upload of a single file
	 * 
	 * @param string $uploadDir the full path to the directory where the uploaded file will reside
	 * @param string $forceUploadedFileName the desired name (w/o extension) for the uploaded file
	 * @return unknown_type
	 */
	public function processUploadedFile($fileInputElementName,$uploadDir,$forceUploadedFileName='') {
		if (!isset($_FILES) && isset($HTTP_POST_FILES)) {
			$_FILES = $HTTP_POST_FILES;
		}
		if (! isset($_FILES[$fileInputElementName])) {
			return false;
		}

		// Calculate the name + extension to use for the uploaded file
		$base = basename($_FILES[$fileInputElementName]['name']);
		$imageExt  = strtolower(substr($base,strrpos($base,'.')+1));
		$imageName = ('' != $forceUploadedFileName)
			? ($forceUploadedFileName . ".{$imageExt}")
			: $base;

		// Ensure that an image name was calculated
		if (empty($imageName)) {
			//$this->controller->flash("The name of the image was not found.","error");
			$this->errorsEncountered = true;
			return false;
		}
			
		// Actually upload and store the file
		if (is_uploaded_file($_FILES[$fileInputElementName]['tmp_name'])) {
			$newFile = $uploadDir . "/" . $imageName;
			if (move_uploaded_file($_FILES[$fileInputElementName]['tmp_name'],$newFile)) {
				//$this->controller->flash("Image file uploaded successfully.");
				return $imageName;
			} else {
				//$this->controller->flash("Error moving uploaded file to final location.","error");
				$this->errorsEncountered = true;
				return false;
			}
		} else {
			//$this->controller->flash("No uploaded file was detected.","error");
			return false;
		}
	}
	
	public function getUploadForm($uploadActionURL='',$uploadFormName='',$uploadFormId='',$fileInputElementName='fiu-file') {
		$this->controller->set('ImageUploader_uploadActionURL',$uploadActionURL);
		$this->controller->set('ImageUploader_uploadFormName',$uploadFormName);
		$this->controller->set('ImageUploader_uploadFormId',$uploadFormId);
		$this->controller->set('ImageUploader_fileInputElementName',$fileInputElementName);
		$this->controller->set('ImageUploader_maxUploadedFileSize',$this->maxUploadedFileSizeBytes);
		return $this->getView('UploadForm');
	}
	
	public function getAjaxMultiUploadForm($ajaxHandlerURL,$uploadFormName='',$uploadFormId='',$fileInputElementName='fiu-file') {
		$this->controller->set('ImageUploader_ajaxHandlerURL',$ajaxHandlerURL);
		$this->controller->set('ImageUploader_uploadFormName',$uploadFormName);
		$this->controller->set('ImageUploader_uploadFormId',$uploadFormId);
		$this->controller->set('ImageUploader_fileInputElementName',$fileInputElementName);
		$this->controller->set('ImageUploader_maxUploadedFileSize',$this->maxUploadedFileSizeBytes);
		return $this->getView('AjaxMultiUploadForm');
	}
	
	public function getUploadFormFileInputElement($bIncludeMaxFileSize,$fileInputElementName='fiu-file') {
		$s = '';
		if ($bIncludeMaxFileSize) {
			$s .= "<!-- MAX_FILE_SIZE must precede the file input field -->";
    		$s .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$this->maxUploadedFileSizeBytes.'" />';
		}
   		$s .= '<input name="'.$fileInputElementName.'" type="file" />';
   		
   		return $s;
	}
	
	
	public function errorsEncountered() {
		return $this->errorsEncountered;
	}
}
?>