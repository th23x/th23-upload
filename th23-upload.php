<?php
/*
Plugin Name: th23 Upload

Description: Resize images on upload to maximum dimensions, saving space and bandwidth. Watermark images upon upload or manually via Media Library
Plugin URI: https://github.com/th23x/th23-upload

Author: Thorsten (th23)
Author URI: https://thorstenhartmann.de
Author IMG: https://thorstenhartmann.de/avatar.png

License: GPL-3.0
License URI: https://github.com/th23x/th23-upload/blob/main/LICENSE

Version: 2.0.0

Requires at least: 4.2
Tested up to: 6.8.1
Requires PHP: 8.0

Text Domain: th23-upload
Domain Path: /lang
*/

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

class th23_upload {

	// Initialize class-wide variables
	public $plugin = array(); // plugin (setup) information
	public $options = array(); // plugin options (user defined, changable)
	public $data = array(); // data exchange between plugin functions

	function __construct() {

		// Setup basics
		$this->plugin['slug'] = 'th23-upload';
		$this->plugin['file'] = __FILE__;
		$this->plugin['basename'] = plugin_basename($this->plugin['file']);
		$this->plugin['dir_url'] = plugin_dir_url($this->plugin['file']);
		$this->plugin['version'] = '2.0.0';
		// plugin specific log file
		$this->plugin['log'] = 'th23-upload.log';
		// allowed watermark image file extensions
		$this->plugin['watermark_types'] = array('jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
		// image types which can be handled by editor ie resized / watermarked (extension => mime type)
		$this->plugin['images_types'] = array('jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg');

		// Load plugin options
		$this->options = (array) get_option($this->plugin['slug']);

		// Localization
		add_action('init', array(&$this, 'localize'));

		// Detect update
		if(empty($this->options['version']) || $this->options['version'] != $this->plugin['version']) {
			// load class and trigger required actions
			$plugin_dir_path = plugin_dir_path($this->plugin['file']);
			if(file_exists($plugin_dir_path . '/th23-upload-upgrade.php')) {
				require($plugin_dir_path . '/th23-upload-upgrade.php');
				$upgrade = new th23_upload_upgrade($this);
				$upgrade->start();
				// reload options - at least option version should have changed
				$this->options = (array) get_option($this->plugin['slug']);
			}
		}

		// == customization: from here on plugin specific ==

		// important: include upload background work on frontend, as image upload via block editor, REST API or plugins using wp upload functionality do NOT load admin (is_admin)

		// -- Image size --

		// Adjust WP default image resizing on upload
		add_action('init', array(&$this, 'adjust_wp_default'));

		// Resize images on upload
		add_filter('wp_handle_upload', array(&$this, 'resize_images'));

		// -- Watermark --

		// Load watermark image editor extensions for Imagick and GD
		add_filter('wp_image_editors', array(&$this, 'extend_image_editors'));

		// Watermark images upon upload
		add_filter('wp_generate_attachment_metadata', array(&$this, 'add_watermark'), 10, 3);

	}

	// Error logging
	// note: $force offers an option to log issues occuring upon background image processing also in live / non-debug environments
	// todo: log file check with requirements and viewer on plugin settings page in admin
	function log($msg, $force = false) {
		// get at least plugin name from main file data, eg when logging on frontend
		if(empty($this->plugin['data'])) {
			$this->plugin['data'] = get_file_data($this->plugin['file'], array('Name' => 'Plugin Name'));
		}
		// upon debugging mode, write any issues into default log
		if(!empty(WP_DEBUG) && !empty(WP_DEBUG_LOG)) {
			error_log($this->plugin['data']['Name'] . ': ' . print_r($msg, true));
		}
		// write to own log file, even in live environment, if protected via htaccess from external access
		if($force && !empty($this->plugin['log'])) {
			// rule to prevent external access to log file
			$rule = array(
				'# th23 Upload - begin',
				'<FilesMatch "' . str_replace('.', '\.', $this->plugin['log']) . '">',
				'Require local',
				'</FilesMatch>',
				'# th23 Upload - end',
			);
			// check for existing main .htaccess file
			$htaccess = ABSPATH . '.htaccess';
			$content = (is_file($htaccess)) ? @file_get_contents($htaccess) : '';
			// check if .htaccess file has plugin specific rule included
			if(false === strpos($content, $rule[0])) {
				@file_put_contents($htaccess, PHP_EOL . implode(PHP_EOL, $rule), FILE_APPEND);
			}
			// re-check and log if requirements met
			if(is_file($htaccess) && !empty($content = @file_get_contents($htaccess)) && false !== strpos($content, $rule[0])) {
				@file_put_contents(ABSPATH . 'wp-content/' . $this->plugin['log'], PHP_EOL . '[' . gmdate("j-F-Y H:i:s T") . '] ' . $this->plugin['data']['Name'] . ': ' . print_r($msg, true), FILE_APPEND);
			}
		}
	}

	// Localization
	function localize() {
		load_plugin_textdomain('th23-upload', false, dirname($this->plugin['basename']) . '/lang');
	}

	// == customization: from here on plugin specific ==

	// String handling helper: Replace last occurance of $search in $subject by $replace
	function str_lreplace($search, $replace, $subject) {
		$pos = strrpos($subject, $search);
		if($pos !== false) {
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}
		return $subject;
	}

	// Adjust WP default image resizing on upload
	function adjust_wp_default() {
		if(!empty($this->options['wp_default'])) {
			// disable hard width/height limit at 2560px upon upload
			add_filter('big_image_size_threshold', '__return_false');
			// prevent auto-creation of additional image sizes on upload (inaccessible via settings)
			add_filter('intermediate_image_sizes_advanced', array(&$this, 'adjust_wp_default_sizes'));
		}
	}
	function adjust_wp_default_sizes($sizes) {
		unset($sizes['1536x1536']);
		unset($sizes['2048x2048']);
		return $sizes;
	}

	// Resize images on upload
	// todo: consider option to resize existing images, BUT this might break existing image usage if file names change!
	function resize_images($file_data) {

		// only image types the editor can handle
		// note: PNG difficult due to transparency / alpha channel, GIF difficult due to potential animation
		if(!in_array($file_data['type'], $this->plugin['images_types'])) {
			return $file_data;
		}

		// load default image editor
		$image_editor = wp_get_image_editor($file_data['file']);
		if(is_wp_error($image_editor)) {
			$this->log('failed to resize image "/' . esc_attr(str_replace(ABSPATH, '', $file_data['file'])) . '"', true);
			return $file_data;
		}

		// check if dimensions of uploaded image exceed allowed
		// note: works also if one dimension is left empty, only the other is than a constraint to be checked
		$sizes = $image_editor->get_size();
		if((empty($this->options['max_width']) || empty($sizes['width']) || $sizes['width'] <= $this->options['max_width']) && (empty($this->options['max_height']) || empty($sizes['height']) || $sizes['height'] <= $this->options['max_height'])) {
			return $file_data;
		}

		// resize by using default image editor, by default keeps aspect ration of source image, and ignores max value if 0
		if(is_wp_error($image_editor->resize($this->options['max_width'], $this->options['max_height'], false))) {
			$this->log('failed to resize image "/' . esc_attr(str_replace(ABSPATH, '', $file_data['file'])) . '"', true);
			return $file_data;
		}

		// use given quality for new image - if not falls back to image editor default
		if(!empty($this->options['resize_quality']) && 0 < $this->options['resize_quality'] && 101 > $this->options['resize_quality']) {
			$image_editor->set_quality($this->options['resize_quality']);
		}

		$file_data_org = $file_data;

		// insert suffix for resized image to file (absolute server path) and url - before last occurance of "." as delimiter to the file extension (JPG or JPEG)
		if(!empty($this->options['resize_suffix'])) {
			$file_data['file'] = $this->str_lreplace('.', $this->options['resize_suffix'] . '.', $file_data['file']);
			$file_data['url'] = $this->str_lreplace('.', $this->options['resize_suffix'] . '.', $file_data['url']);
		}

		// save resized image (potentially under new name including suffix)
		if(is_wp_error($image_editor->save($file_data['file']))) {
			$this->log('failed to resize image "/' . esc_attr(str_replace(ABSPATH, '', $file_data_org['file'])) . '"', true);
			return $file_data_org;
		}

		// remove originally uploaded image (in case a suffix is added, to avoid duplicate)
		if(!empty($this->options['resize_suffix']) && !wp_delete_file($file_data_org['file'])) {
			$this->log('failed to remove original image "/' . esc_attr(str_replace(ABSPATH, '', $file_data_org['file'])) . '"', true);
		}

		return $file_data;

	}

	// Get validated wartermark image path, url, dir and file(name) in array
	function get_watermark($file = null, $nocache = false) {
		// cache validated watermark array for script execution
		if(!isset($this->data['watermark']) || $nocache) {
			$upload_dir = wp_get_upload_dir();
			$path = '/' . $this->plugin['slug'] . '/';
			// no dedicated file, try saved option
			if(!isset($file)) {
				$file = (isset($this->options['watermarks_image'])) ? $this->options['watermarks_image'] : '';
			}
			// basic information about upload folder
			$this->data['watermark'] = array(
				// ensure all dir/path returned end with "/"
				'dir_base' => $upload_dir['basedir'] . '/',
				'dir_expected' => $upload_dir['basedir'] . $path,
			);
			// check if (at least) dir exists (or can be created)
			if((!file_exists($upload_dir['basedir'] . $path)) ? wp_mkdir_p($upload_dir['basedir'] . $path) : true) {
				$this->data['watermark']['dir'] = $upload_dir['basedir'] . $path;
				$this->data['watermark']['url'] = $upload_dir['baseurl'] . $path;
			}
			// check existence of watermark image file
			if(is_file($upload_dir['basedir'] . $path . $file)) {
				$this->data['watermark']['dir_file'] = $upload_dir['basedir'] . $path . $file;
				$this->data['watermark']['url_file'] = $upload_dir['baseurl'] . $path . $file;
				$this->data['watermark']['file'] = $file;
			}
		}
		return $this->data['watermark'];
	}

	// Load watermark image editor extensions for Imagick and GD
	function extend_image_editors($editors) {
		if(is_array($editors)) {
			global $th23_upload_path;
			if(is_file($th23_upload_path . 'th23-upload-image-editors.php')) {
				require_once($th23_upload_path . 'th23-upload-image-editors.php');
				if(in_array('WP_Image_Editor_GD', $editors)) {
					array_unshift($editors, 'th23_image_editor_gd');
				}
				if(in_array('WP_Image_Editor_Imagick', $editors)) {
					array_unshift($editors, 'th23_image_editor_imagick');
				}
			}
		}
		return $editors;
	}

	// Load WP filesystem
	// note: various legacy php file functions are "discouraged" by wp plugin team, eg "rename" (move) and "copy" (copy)
	function filesystem() {
		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		return (WP_Filesystem()) ? $wp_filesystem : false;
	}

	// Add watermark
	// todo: prevent default creation of sub-size images and directly create them watermarked to save server CPU time and memory
	function add_watermark($metadata, $attachment_id, $context) {

		if(!in_array($context, array('create', 'th23-upload'))) {
			return $metadata;
		}

		// watermarking enabled? on image upload?
		if(empty($this->options['watermarks']) || ('create' == $context && empty($this->options['watermarks_upload']))) {
			if('th23-upload' == $context) {
				$metadata['th23-upload'] = array('result' => 'error', 'msg' => esc_html__('Watermarks disabled!', 'th23-upload'));
			}
			return $metadata;
		}

		// handle only possible image types
		if(empty($attachment = get_post($attachment_id)) || empty($attachment->post_type) || 'attachment' != $attachment->post_type || empty($attachment->post_mime_type) || !in_array($attachment->post_mime_type, $this->plugin['images_types'])) {
			if('th23-upload' == $context) {
				$metadata['th23-upload'] = array('result' => 'error', 'msg' => esc_html__('No valid image!', 'th23-upload'));
			}
			return $metadata;
		}

		// get watermark dir, url, file
		$watermark = $this->get_watermark();

		// get path of image within upload folder (only saved with full filename in attachment meta array)
		$upload_path = dirname($metadata['file']) . '/';

		// clean up
		// note: auto-scaling / auto-rotating of images by WP core on upload leaving behind a dormant "original_image" that is accessible unmarked
		// note: upon initial upload before using the file anywhere (context = "create") its safe to "mess around" with the file naming as well, ie remove added suffix "-rotated" / "-scaled"
		if(!empty($metadata['original_image'])) {

			// remove "original_image" physically and from meta
			$original_image = $watermark['dir_base'] . $upload_path . $metadata['original_image'];
			if($original_image != $watermark['dir_base'] . $metadata['file']) {
				// remove "original_image" from server, if it's not the main attachment file
				if(!wp_delete_file($original_image)) {
					$this->log('failed to remove original image "' . esc_attr($upload_path . $metadata['original_image']) . '"', true);
				}
			}
			unset($metadata['original_image']);

			// remove any added suffix from scaling / rotating
			if('create' == $context && (false !== strpos($metadata['file'], '-rotated.') || false !== strpos($metadata['file'], '-scaled.'))) {
				$src = $metadata['file'];
				$dst = str_replace(array('-rotated.', '-scaled.'), '.', $metadata['file']);
				// rename file on server
				if(!empty($filesystem = $this->filesystem()) && $filesystem->move($watermark['dir_base'] . $src, $watermark['dir_base'] . $dst, true)) {
					// update both post meta entries for attachments (_wp_attached_file and within attachment meta entry)
					update_post_meta($attachment_id, '_wp_attached_file', $dst, $src);
					$metadata['file'] = $dst;
				}
				else {
					$this->log('failed to remove "-rotated" / "-scaled" from "' . esc_attr($src) . '"', true);
				}
			}

		}

		// keep track of which sizes of the image have been watermarked and the unmarked original
		if(empty($watermarks_meta = get_post_meta($attachment->ID, 'th23-upload-watermarks', true))) {
			$watermarks_meta = array('unmarked' => '', 'marked' => array());
		}

		// create unmarked backup with the suffix "_no-watermark" from current full file, if this is not marked
		if((empty($watermarks_meta['unmarked']) || !is_file($watermarks_meta['unmarked'])) && empty($watermarks_meta['marked']['full'])) {
			$unmarked = $this->str_lreplace('.', '_no-watermark.', $metadata['file']);
			if(!empty($filesystem = $this->filesystem()) && $filesystem->copy($watermark['dir_base'] . $metadata['file'], $watermark['dir_base'] . $unmarked, true)) {
				$watermarks_meta['unmarked'] = $unmarked;
			}
		}
		if(empty($watermarks_meta['unmarked'])) {
			if('th23-upload' == $context) {
				$metadata['th23-upload'] = array('result' => 'error', 'msg' => esc_html__('Failed to create backup!', 'th23-upload'));
			}
			else {
				$this->log('failed to create unmarked copy of "' . esc_attr($metadata['file']) . '", failed to watermark attachment ID "' . esc_attr($attachment_id) . '"', true);
			}
			return $metadata;
		}

		// ensure we have a sizes array (potentially not always created on upload, due to disabled auto-resizing)
		$sizes = (isset($metadata['sizes']) && is_array($metadata['sizes'])) ? $metadata['sizes'] : array();

		// add "full" version of the image
		$sizes['full'] = array(
			'file' => str_replace($upload_path, '', $metadata['file']),
			'width' => $metadata['width'],
			'height' => $metadata['height'],
		);

		// find sizes to apply watermark
		if(!empty($this->options['watermarks_sizes']) && is_array($this->options['watermarks_sizes'])) {

			// check watermark image
			if(!is_file($watermark['dir_file'])) {
				if('th23-upload' == $context) {
					$metadata['th23-upload'] = array('result' => 'error', 'msg' => esc_html__('Failed watermarking image!', 'th23-upload'));
				}
				else {
					$this->log('missing watermark "' . esc_attr($watermark['dir_file']) . '", failed to watermark attachment ID "' . esc_attr($attachment_id) . '"', true);
				}
				return $metadata;
			}

			// loop through sizes and try to apply
			foreach($this->options['watermarks_sizes'] as $size) {
				// check size still needs to be watermarked and file for size specified
				if(empty($watermarks_meta['marked'][$size]) && !empty($sizes[$size]['file'])) {
					// todo: add option for different watermarks per image size - move watermark selection here
					$image_src = $watermark['dir_base'] . $upload_path . $sizes[$size]['file'];
					$image = wp_get_image_editor($image_src);
					if(!is_wp_error($image) && is_callable(array($image, 'th23_upload_add_watermark'))) {
						if($image->th23_upload_add_watermark($watermark['dir_file'])) {
							if(!is_wp_error($watermarked = $image->save($image_src))) {
								// note: WP Image Editor "save" function saves image forcing lowercase letters for file extension, ie creating from "P8453.JPG" an watermarked copy named "P8453.jpg" instead of overwriting original
								if($watermarked['path'] == $image_src || (!empty($filesystem = $this->filesystem()) && $filesystem->move($watermarked['path'], $image_src, true))) {
									$watermarks_meta['marked'][$size] = 'marked';
									continue;
								}
							}
						}
					}
					if('th23-upload' == $context) {
						$metadata['th23-upload'] = array('result' => 'error', 'msg' => esc_html__('Failed watermarking image!', 'th23-upload'));
					}
					else {
						$this->log('failed to watermark attachment ID "' . esc_attr($attachment_id) . '", image "' . esc_attr($image_src) . '"', true);
					}
				}
			}

		}

		// store watermark info (unmarked/marked) as meta
		update_post_meta($attachment_id, 'th23-upload-watermarks', $watermarks_meta);

		return $metadata;

	}

}

// === INITIALIZATION ===

$th23_upload_path = plugin_dir_path(__FILE__);

// Load additional admin class, if required...
if(is_admin() && file_exists($th23_upload_path . 'th23-upload-admin.php')) {
	require($th23_upload_path . 'th23-upload-admin.php');
	$th23_upload = new th23_upload_admin();
}
// ...or initiate plugin directly
else {
	$th23_upload = new th23_upload();
}

?>
