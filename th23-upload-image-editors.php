<?php

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

// Imagick specific additions to Image Editor
class th23_image_editor_imagick extends WP_Image_Editor_Imagick {

	// Parent Image Editor functions required, thus embedded "transparently" in extension
	public static function test( $args = [] ) { return parent::test( $args ); }
    public static function supports_mime_type( $mime_type ) { return parent::supports_mime_type( $mime_type ); }

	// Add watermark to image
	public function th23_upload_add_watermark($watermark_src) {

		global $th23_upload;
		if(empty($th23_upload)) {
			return;
		}

		try {
			// get watermark image
			$watermark = new Imagick();
			$watermark->readImage($watermark_src);
			// get watermark size
			$watermark_width = $watermark->getImageWidth();
			$watermark_height = $watermark->getImageHeight();
		} catch (ImagickException $e) {
			$th23_upload->log('Imagick failed to open watermark "' . $watermark_src . '"');
			return false;
		}

		// ensure maxcover between 1 to 100 (%)
		$maxcover = (int) $th23_upload->options['watermarks_maxcover'];
		if($maxcover < 1 || $maxcover > 100) {
			$maxcover = 100;
		}

		// get distance to keep from image border
		$padding = (int) $th23_upload->options['watermarks_padding'];

		// calculate max watermark size
		$max_width = round(($this->size['width'] - (2 * $padding)) * $maxcover / 100);
		$max_height = round(($this->size['height'] - (2 * $padding)) * $maxcover / 100);

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

		// ensure position between 1 (top left) to 9 (bottom right, default)
		$position = (int) $th23_upload->options['watermarks_position'];
		if($position < 1 || $position > 9) {
			$position = 9;
		}

		// determine coordinates of watermark on image
		if(1 == $position) {
			$x = 0 + $padding;
			$y = 0 + $padding;
		}
		elseif(2 == $position) {
			$x = round(($this->size['width'] / 2) - ($watermark_width / 2));
			$y = 0 + $padding;
		}
		elseif(3 == $position) {
			$x = $this->size['width'] - $padding - $watermark_width;
			$y = 0 + $padding;
		}
		elseif(4 == $position) {
			$x = 0 + $padding;
			$y = round(($this->size['height'] / 2) - ($watermark_height / 2));
		}
		elseif(5 == $position) {
			$x = round(($this->size['width'] / 2) - ($watermark_width / 2));
			$y = round(($this->size['height'] / 2) - ($watermark_height / 2));
		}
		elseif(6 == $position) {
			$x = $this->size['width'] - $padding - $watermark_width;
			$y = round(($this->size['height'] / 2) - ($watermark_height / 2));
		}
		elseif(7 == $position) {
			$x = 0 + $padding;
			$y = $this->size['height'] - $watermark_height - $padding;
		}
		elseif(8 == $position) {
			$x = round(($this->size['width'] / 2) - ($watermark_width / 2));
			$y = $this->size['height'] - $watermark_height - $padding;
		}
		elseif(9 == $position) {
			$x = $this->size['width'] - $padding - $watermark_width;
			$y = $this->size['height'] - $watermark_height - $padding;
		}

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

	// Parent Image Editor functions required, thus embedded "transparently" in extension
	public static function test( $args = [] ) { return parent::test( $args ); }
    public static function supports_mime_type( $mime_type ) { return parent::supports_mime_type( $mime_type ); }

	// Add watermark onto image
	public function th23_upload_add_watermark($watermark_src) {

		global $th23_upload;
		if(empty($th23_upload)) {
			return;
		}

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

		// ensure maxcover between 1 to 100 (%)
		$maxcover = (int) $th23_upload->options['watermarks_maxcover'];
		if($maxcover < 1 || $maxcover > 100) {
			$maxcover = 100;
		}

		// get distance to keep from image border
		$padding = (int) $th23_upload->options['watermarks_padding'];

		// calculate max watermark size
		$max_width = round(($this->size['width'] - (2 * $padding)) * $maxcover / 100);
		$max_height = round(($this->size['height'] - (2 * $padding)) * $maxcover / 100);

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

		// ensure position between 1 (top left) to 9 (bottom right, default)
		$position = (int) $th23_upload->options['watermarks_position'];
		if($position < 1 || $position > 9) {
			$position = 9;
		}

		// determine coordinates of watermark on image
		if(1 == $position) {
			$x = 0 + $padding;
			$y = 0 + $padding;
		}
		elseif(2 == $position) {
			$x = round(($this->size['width'] / 2) - ($watermark_width / 2));
			$y = 0 + $padding;
		}
		elseif(3 == $position) {
			$x = $this->size['width'] - $padding - $watermark_width;
			$y = 0 + $padding;
		}
		elseif(4 == $position) {
			$x = 0 + $padding;
			$y = round(($this->size['height'] / 2) - ($watermark_height / 2));
		}
		elseif(5 == $position) {
			$x = round(($this->size['width'] / 2) - ($watermark_width / 2));
			$y = round(($this->size['height'] / 2) - ($watermark_height / 2));
		}
		elseif(6 == $position) {
			$x = $this->size['width'] - $padding - $watermark_width;
			$y = round(($this->size['height'] / 2) - ($watermark_height / 2));
		}
		elseif(7 == $position) {
			$x = 0 + $padding;
			$y = $this->size['height'] - $watermark_height - $padding;
		}
		elseif(8 == $position) {
			$x = round(($this->size['width'] / 2) - ($watermark_width / 2));
			$y = $this->size['height'] - $watermark_height - $padding;
		}
		elseif(9 == $position) {
			$x = $this->size['width'] - $padding - $watermark_width;
			$y = $this->size['height'] - $watermark_height - $padding;
		}

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
