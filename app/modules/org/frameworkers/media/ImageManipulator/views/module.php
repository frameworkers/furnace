<?php
class ImageManipulator extends FPageModule {
	
	public function __construct($controller) {
		parent::__construct($controller,dirname(__FILE__));
	}
	
	// Scale imagePath so that the size of the largest dimension is <= $maxDimension
	public static function limitMaxDimension($imagePath,$maxDimension,$destPath=null) {
		$height = $width = $maxDimension;
		
		// Get new dimensions
		list($width_orig, $height_orig,$image_type) = getimagesize($imagePath);
		$imageType = image_type_to_mime_type($image_type);
		
		// See if anything needs to be done
		if ($width_orig <= $maxDimension && $height_orig <= $maxDimension) {
			return;
		}
	
		$ratio_orig = $width_orig/$height_orig;
	
		if ($width/$height > $ratio_orig) {
		   $width = $height*$ratio_orig;
		} else {
		   $height = $width/$ratio_orig;
		}
	
		// Resample
		$newImage = imagecreatetruecolor($width, $height);
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
		imagecopyresampled($newImage, $source, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		
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