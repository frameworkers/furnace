<?php
class ImageManager extends FPageModule {
	
	// Variable: repositoryPath
	// The full path to the base directory of the image repository
	private $repositoryPath;
	
	// Variable: directoryCharset
	// The list of valid characters for use in generating file ids and 
	// the image repository directory layout
	private static $directoryCharset   = "0123456789abcdefghijklmnopqrstuvwxyz";
	
	// Variable: directoryDepth
	// The level of directory nesting to employ within the image repository.
	// Note that fileIdLength must be at least this long in order to properly
	// map to a location in the directory structure
	private static $directoryDepth     = 3;
	
	// Variable: fileIdLength
	// The length of all fileIds in the image repository. Note that this value
	// must be at least as long as directoryDepth in order to properly map
	// images to a location in the directory structure
	private static $fileIdLength       = 6;
	
	
	// Maximum dimensions for the largest side of each of the image variants
	private static $squareImageSize    = 75;
	private static $thumbnailImageSize = 100;
	private static $smallImageSize     = 240;
	private static $mediumImageSize    = 500;
	private static $largeImageSize     = 900;
	
	
	
	public function __construct($repositoryPath) {
		$this->repositoryPath = $repositoryPath;
	}
	
	public function get($resource,$useName='') {
		list($base,$ext) = explode('.',$resource,2);
		$full_path = $this->getFullPathToImage($base,$ext);
		
		if (file_exists($full_path)) {
			switch ($ext) {
				case 'png':
				case 'x-png':
					header('Content-Type: image/png');
					$im = imagecreatefrompng($full_path);
					imagepng($im);
					break;
				case 'gif':
					header('Content-Type: image/gif');
					$im = imagecreatefromgif($full_path);
					imagegif($im);
					break;
				case 'pjpeg':
				case 'jpeg':
				case 'jpg':
				default:
					header('Content-Type: image/jpeg');
					$im = imagecreatefromjpeg($full_path);
					imagejpeg($im);
					break;
			}
			
			// Cleanup
			imagedestroy($im);
		}
	}
	
	public function delete($resource) {
		//TODO: implement deleteImageFile
		list ($base,$ext) = explode('.',$resource,2);
		$full_path = $this->getFullPathToImage($base,$ext);
		if (file_exists($full_path) && is_file($full_path)) {
			unlink($full_path);
		}
	}
	
	
	public function upload($inputElementName, $outputType = 'php') {
		
		$handle   = $this->generateUniqueId();
		$fileData = $this->attemptFileUpload($inputElementName,$handle);
		
		if (isset($fileData['error'])) {
			die("error occurred: {$fileData['error']}");
		} else {
			$this->generateSecondaryImages($fileData);
		}
		
		// Determine the output type and format $fileData accordingly
		switch (strtolower($outputType)) {
			case 'json':
				return json_encode($fileData);
				break;
			case 'php':
			default:
				return $fileData;
				break;
		}	
	}
	
	
	
	
	
	/**
	 * UTILITY FUNCTIONS
	 */
	
	private function generateUniqueId() {
		// Determine range of valid characters
		$id = '';
		$maxIdx = strlen(self::$directoryCharset) -1;
		
		// Generate random string
		for ( $i = 0; $i < self::$fileIdLength; $i++ ) {
			$id .= self::$directoryCharset[mt_rand(0,$maxIdx)];
		}
		
		//TODO: Check for uniqueness
		
		return $id;
	}
	

	
	
	
	
	private function getFullPathToImage($id, $ext = 'jpg') {
		if (!isset($id[self::$directoryDepth - 1])) {
			return false;	// malformed id, not long enough
		}
		
		// Start with the path to the repository
		$path = $this->repositoryPath;
		
		// Append paths from the directory structure
		for($i = 0; $i < self::$directoryDepth; $i++ ) {
			$path .= "/{$id[$i]}";	
		}
		
		// Finish with the file id and extension
		$path .= "/{$id}.{$ext}";
		
		return $path;
	}
	
	
	
	
	
	private function attemptFileUpload($inputElementName, $fileId) {
		
		// Create an array to hold basic file data, or errors, if encountered
		$fileData = array();
		
		// Merge the two possible locations for HTTP file uploads
		if (!isset($_FILES) && isset($HTTP_POST_FILES)) {
				$_FILES = $HTTP_POST_FILES;
		}
			
		// Ensure that data exists for the provided inputElementName
		if (! isset($_FILES[$inputElementName])) {
			$fileData['error'] = "No data for input element '{$inputElementName}' ";
			return $fileData;
		}
		
		// Calculate the name + extension to use for the uploaded file
		$fileData['fileId']    = $fileId;
		$fileData['filename']  = basename($_FILES[$inputElementName]['name']);
		$extensionStart = strrpos($fileData['filename'],'.');
		$fileData['basename']  = substr($fileData['filename'],0,$extensionStart);
		$fileData['extension'] = strtolower(substr($fileData['filename'],$extensionStart + 1));
		
		// Compute the image name for the canonical image
		$imageName  = "{$fileId}.{$fileData['extension']}";
		
		// Calculate the upload directory for the canonical version of the file
		$fileData['uploadPath'] = $uploadPath = $this->getFullPathToImage($fileId,$fileData['extension']);
		
				
		// Actually upload and store the file
		if (is_uploaded_file($_FILES[$inputElementName]['tmp_name'])) {
			if (move_uploaded_file($_FILES[$inputElementName]['tmp_name'],$fileData['uploadPath'])) {				
				return $fileData;
			} else {
				$fileData['error'] = "Error moving file to final location";
				return $fileData;
			}
		} else {
			$fileData['error'] = "No uploaded file detected";
			return $fileData;
		}
	}
	
	
	
	
	
	private function generateSecondaryImages(&$fileData) {
		$canonicalPath = $this->getFullPathToImage($fileData['fileId'],$fileData['extension']);
		
		// Generate unique ids for the variants
		$sqId = $this->generateUniqueId();
		$tnId = $this->generateUniqueId();
		$smId = $this->generateUniqueId();
		$lgId = $this->generateUniqueId();
		
		// Generate variants
		self::createSquareThumbnail(
			$this->getFullPathToImage($sqId,$fileData['extension']),
			$canonicalPath,
			self::$squareImageSize );										// Square
		self::limitMaxDimension($canonicalPath,self::$thumbnailImageSize,
			$this->getFullPathToImage($tnId,$fileData['extension']));		// Thumbnail
		self::limitMaxDimension($canonicalPath,self::$smallImageSize,
			$this->getFullPathToImage($smId,$fileData['extension']));		// Small
		self::limitMaxDimension($canonicalPath,self::$largeImageSize,
			$this->getFullPathToImage($lgId,$fileData['extension']));		// Large

		// Finally, scale the original to medium size
		self::limitMaxDimension($canonicalPath,self::$mediumImageSize,
		    $this->getFullPathToImage($fileData['fileId'],$fileData['extension']));  // Medium
			
		// Save the ids in the fileData structure
		$fileData['sqId'] = $sqId;
		$fileData['tnId'] = $tnId;
		$fileData['smId'] = $smId;
		$fileData['mId']  = $fileData['fileId'];
		$fileData['lgId'] = $lgId;
	}
	
	
	
	
	
	
	
// Scale imagePath so that the size of the largest dimension is <= $maxDimension
	public static function limitMaxDimension($imagePath,$maxDimension,$destPath=null) {
		$height = $width = $maxDimension;
		$skipResampling  = false;
		
		// Get new dimensions
		list($width_orig, $height_orig,$image_type) = getimagesize($imagePath);
		$imageType = image_type_to_mime_type($image_type);
		
		// See if anything needs to be done
		if ($width_orig <= $maxDimension && $height_orig <= $maxDimension) {
			if (null == $destPath) {
				return;
			} else {
				$skipResampling = true;
			}
		}
		
		$ratio_orig = $width_orig/$height_orig;
	
		if ($width/$height > $ratio_orig) {
		   $width = $height*$ratio_orig;
		} else {
		   $height = $width/$ratio_orig;
		}
	
		// Resample
		if ($skipResampling) {
			$newImage = imagecreatetruecolor($width_orig,$height_orig);
		} else {
			$newImage = imagecreatetruecolor($width, $height);
		}
		switch($imageType) {
			case "image/gif":
				$source=imagecreatefromgif($imagePath);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source=imagecreatefromjpeg($imagePath);
				break;
			case "image/png":
			case "image/x-png":
				$source=imagecreatefrompng($imagePath);
				break;
		}
		if (!$source) {
			die("<b>ImageManipulator:</b> {$imagePath} is not a valid jpeg,gif, or png image.");
		}
		
		if ($skipResampling) {
			imagecopy($newImage,$source,0,0,0,0,$width_orig,$height_orig);
		} else {
			imagecopyresampled($newImage, $source, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		}
		
		// Save
		$writePath = (null == $destPath) 
			? $imagePath	// Overwrite the input file
			: $destPath;	// Write to a new file
		switch($imageType) {
			case "image/gif":
				imagegif($newImage,$writePath);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage,$writePath,90);
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$writePath);
				break;
		}
		imagedestroy($source);
		chmod($writePath, 0777);
	}
	
	
	
	
	

	
	// Create a thumbnail from a crop specified by
	//	width: width of the crop selection
	//	height: height of the crop selection
	//	x1: x location of the upper left corner
	//	y1: y location of the upper left corner
	// 	scale: the scale factor to use (1.0 = 1:1)
	public function createThumbnailFromCrop($tnImagePath, $fsImagePath, $x1,$y1,$width,$height, $scale){
		list($imagewidth, $imageheight, $imageType) = getimagesize($fsImagePath);
		$imageType = image_type_to_mime_type($imageType);

		$newImageWidth  = ceil($width  * $scale);
		$newImageHeight = ceil($height * $scale);
		$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
		switch($imageType) {
			case "image/gif":
				$source=imagecreatefromgif($fsImagePath);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source=imagecreatefromjpeg($fsImagePath);
				break;
			case "image/png":
			case "image/x-png":
				$source=imagecreatefrompng($fsImagePath);
				break;
		}
		if (!$source) {
			die("<b>ImageManipulator:</b> {$fsImagePath} is not a valid jpeg,gif, or png image.");
		}
		imagecopyresampled($newImage,$source,0,0,$x1,$y1,$newImageWidth,$newImageHeight,$width,$height);
		switch($imageType) {
			case "image/gif":
				imagegif($newImage,$tnImagePath);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage,$tnImagePath,90);
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$tnImagePath);
				break;
		}
		imagedestroy($source);
		chmod($tnImagePath, 0777);
		return $tnImagePath;
	}
	
	
	

	
	
	
	
	// Create a thumbnail image of width w and height h
	public static function createSquareThumbnail($tnImagePath, $fsImagePath, $w) {
		// Get the dimensions of the original image
		list($fsWidth,$fsHeight,$imageType) = getimagesize($fsImagePath);
		$imageType = image_type_to_mime_type($imageType);
		
		// Compute the offset of the upper left corner of the thumbnail (assuming a centered thumbnail of w*h)
		if ($fsHeight > $fsWidth ) {
			$xOffset = 0;
			$yOffset = 0.5 * ($fsHeight - $fsWidth);
			$bigWidth= $fsWidth;
			$fsCopyWidth  = $fsWidth;
			$fsCopyHeight = $fsWidth; 
		} else {
			$xOffset = 0.5 * ($fsWidth - $fsHeight);
			$yOffset = 0;
			$bigWidth= $fsHeight;
			$fsCopyWidth  = $fsHeight;
			$fsCopyHeight = $fsHeight; 
		}
		
		// Create the image resource for the thumbnail
		$newImage = imagecreatetruecolor($w,$w);
		
		// Load the source image
		switch($imageType) {
			case "image/gif":
				$source=imagecreatefromgif($fsImagePath);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source=imagecreatefromjpeg($fsImagePath);
				break;
			case "image/png":
			case "image/x-png":
				$source=imagecreatefrompng($fsImagePath);
				break;
		}
		if (!$source) {
			die("<b>ImageManipulator:</b> {$fsImagePath} is not a valid jpeg,gif, or png image.");
		}
		
		// Copy the contents of the image into the thumbnail
		imagecopyresampled($newImage,$source,
			0,			/* x location in dest image to insert copy */
			0,			/* y location in dest image to insert copy */
			$xOffset,	/* x location in source to begin copying from */
			$yOffset,	/* y location in source to begin copying from */
			$w, 		/* width  of new image */
			$w, 		/* height of new image */
			$fsCopyWidth,	/* width  of the source image to copy from */
			$fsCopyHeight);	/* height of the source image to copy from */

		// Save the thumbnail
		switch($imageType) {
			case "image/gif":
				imagegif($newImage,$tnImagePath);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage,$tnImagePath,90);
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$tnImagePath);
				break;
		}
		imagedestroy($source);
		chmod($tnImagePath, 0777);
		return $tnImagePath;
	}
}
?>