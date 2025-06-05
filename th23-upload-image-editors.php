<?php

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

// Imagick specific additions to Image Editor
class th23_image_editor_imagick extends WP_Image_Editor_Imagick {

	// Image Editor functions required, ie checked for existence and thus embedded "transparently" in extension
	public static function test($args = array()) { return parent::test($args); }
	public static function supports_mime_type($mime_type) { return parent::supports_mime_type($mime_type); }

	// Load image into editor (extension)
	public function __construct($file) {
		global $th23_upload;
		if(empty($th23_upload)) {
			return new WP_Error('th23_upload_image_editor', __('Failed to load image editor, main plugin missing', 'th23-upload'));
		}
		$th23_upload->data['file_opened'] = $file;
		// use unmarked version as basis
		// note: _no-watermark file will not be overwritten, as -e{13} is added to filename
		if(is_file($unmarked = $th23_upload->str_lreplace('.', '_no-watermark.', $file))) {
			$file = $unmarked;
		}
		// trigger handling of backups and watermarks after saving new image version
		add_action('updated_postmeta', array(&$th23_upload, 'edit_image'), 10, 4);
		$th23_upload->data['file_used'] = $file;
		parent::__construct($file);
	}

	// Save image edited (extension)
	// note: saving (updated) attachment meta data happens after "save" is executed
	public function save($destfilename = null, $mime_type = null) {
		global $th23_upload;
		$th23_upload->data['file_edited'] = $destfilename;
		return parent::save($destfilename, $mime_type);
	}

	// Add watermark to image
	public function th23_upload_add_watermark($watermark_src) {
		global $th23_upload;

		try {
			// get watermark image
			$watermark = new Imagick();
			$watermark->readImage($watermark_src);
			// get watermark size
			$watermark_width = $watermark->getImageWidth();
			$watermark_height = $watermark->getImageHeight();
		} catch (ImagickException $e) {
			$th23_upload->log('Imagick failed to open watermark "/' . esc_attr(str_replace(ABSPATH, '', $watermark_src)) . '"');
			return false;
		}

		// get watermark size
		list($maxcover, $padding, $max_width, $max_height) = $th23_upload->watermark_size($this->size['width'], $this->size['height']);

		// resize watermark, if needed
		try {
			if($watermark_width > $max_width) {
				$watermark->scaleImage($max_width, 0);
				$watermark_width = $watermark->getImageWidth();
				$watermark_height = $watermark->getImageHeight();
			}
			if($watermark_height > $max_height) {
				$watermark->scaleImage(0, $max_height);
				$watermark_width = $watermark->getImageWidth();
				$watermark_height = $watermark->getImageHeight();
			}
		} catch (ImagickException $e) {
			$th23_upload->log('Imagick failed to resize watermark, using original size');
		}

		// get watermark position
		list($x, $y) = $th23_upload->watermark_position($this->size['width'], $this->size['height'], $watermark_width, $watermark_height, $padding);

		// place watermark on image
		try {
			$this->image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);
		} catch (ImagickException $e) {
			$th23_upload->log('Imagick failed to place watermark on image');
			return false;
		}

		// free up memory, main image will be taken care of by Image Editor class
		$watermark->destroy();
		return true;

	}

}

// GD specific additions to Image Editor
class th23_image_editor_gd extends WP_Image_Editor_GD {

	// Image Editor functions required, ie checked for existence and thus embedded "transparently" in extension
	public static function test($args = array()) { return parent::test($args); }
	public static function supports_mime_type($mime_type) { return parent::supports_mime_type($mime_type); }

	// Load image into editor (extension)
	public function __construct($file) {
		global $th23_upload;
		if(empty($th23_upload)) {
			return new WP_Error('th23_upload_image_editor', __('Failed to load image editor, main plugin missing', 'th23-upload'));
		}
		$th23_upload->data['file_opened'] = $file;
		// use unmarked version as basis
		// note: _no-watermark file will not be overwritten, as -e{13} is added to filename
		if(is_file($unmarked = $th23_upload->str_lreplace('.', '_no-watermark.', $file))) {
			$file = $unmarked;
		}
		// trigger handling of backups and watermarks after saving new image version
		add_action('updated_postmeta', array(&$th23_upload, 'edit_image'), 10, 4);
		$th23_upload->data['file_used'] = $file;
		parent::__construct($file);
	}

	// Save image edited (extension)
	// note: saving (updated) attachment meta data happens after "save" is executed
	public function save($destfilename = null, $mime_type = null) {
		global $th23_upload;
		$th23_upload->data['file_edited'] = $destfilename;
		return parent::save($destfilename, $mime_type);
	}

	// Add watermark onto image
	public function th23_upload_add_watermark($watermark_src) {
		global $th23_upload;

		// get watermark information
		if(empty($watermark_info = getimagesize($watermark_src))) {
			$th23_upload->log('GD failed to open watermark "' . $watermark_src . '"');
			return false;
		}

		// get watermark size
		list($watermark_width, $watermark_height) = $watermark_info;
		// detect image type - needing different GD functions to handle
		$watermark_mime = (empty($watermark_info['mime'])) ? '' : $watermark_info['mime'];
		if('image/jpeg' == $watermark_mime) {
			$image_create_func = 'imagecreatefromjpeg';
			$image_save_func = 'imagejpeg';
		}
		elseif('image/png' == $watermark_mime) {
			$image_create_func = 'imagecreatefrompng';
			$image_save_func = 'imagepng';
		}
		elseif('image/gif' == $watermark_mime) {
			$image_create_func = 'imagecreatefromgif';
			$image_save_func = 'imagegif';
		}
		else {
			$th23_upload->log('GD detected no valid image file for watermark "' . $watermark_src . '"');
			return false;
		}

		// load watermark image
		if(empty($watermark = $image_create_func($watermark_src))) {
			$th23_upload->log('GD failed to open watermark "' . $watermark_src . '"');
			return false;
		}

		// get watermark size
		list($maxcover, $padding, $max_width, $max_height) = $th23_upload->watermark_size($this->size['width'], $this->size['height']);

		// determine need to resize and scale to use - keeping aspect ratio
		$scale = 1;
		$width_scale = $max_width / $watermark_width;
		if($width_scale < 1) {
			$scale = $width_scale;
		}
		$height_scale = $max_height / $watermark_height;
		if($height_scale < 1 && $height_scale < $width_scale) {
			$scale = $height_scale;
		}

		// resize watermark, if needed
		if($scale < 1) {
			$new_width = round($watermark_width * $scale);
			$new_height = round($watermark_height * $scale);

			if(empty($new_watermark = imagecreatetruecolor($new_width, $new_height))) {
				$th23_upload->log('GD failed to resize watermark, using original size');
				$new_watermark = $watermark;
			}
			else {
				if('image/jpeg' != $watermark_mime) {
					// keep transparency for PNG and GIF
					imagealphablending($new_watermark, false);
					imagesavealpha($new_watermark, true);
					$transparent = imagecolorallocatealpha($new_watermark, 255, 255, 255, 127);
					imagefilledrectangle($new_watermark, 0, 0, $new_width, $new_height, $transparent);
				}
				if(empty(imagecopyresampled($new_watermark, $watermark, 0, 0, 0, 0, $new_width, $new_height, $watermark_width, $watermark_height))) {
					$th23_upload->log('GD failed to resize watermark, using original size');
					$new_watermark = $watermark;
				}
				else {
					$watermark_width = $new_width;
					$watermark_height = $new_height;
				}
			}
		}
		else {
			$new_watermark = $watermark;
		}
		// free up memory
		imagedestroy($watermark);

		// get watermark position
		list($x, $y) = $th23_upload->watermark_position($this->size['width'], $this->size['height'], $watermark_width, $watermark_height, $padding);

		// place watermark on image
		if('image/jpeg' != $watermark_mime) {
			// keep transparency for PNG and GIF upon placing watermark
			imagealphablending($this->image, true);
		}
		if(!imagecopy($this->image, $new_watermark, $x, $y, 0, 0, $watermark_width, $watermark_height)) {
			$th23_upload->log('GD failed to place watermark on image');
			return false;
		}

		// free up memory, main image will be taken care of by Image Editor class
		imagedestroy($new_watermark);
		return true;

	}

}

?>
