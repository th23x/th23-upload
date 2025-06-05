<?php

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

class th23_upload_admin extends th23_upload {

	// Extend class-wide variables
	public $i18n;
	private $admin;

	function __construct() {

		parent::__construct();

		// Setup basics (additions for backend)
		$this->plugin['dir_path'] = plugin_dir_path($this->plugin['file']);
		$this->plugin['settings'] = array(
			'base' => 'options-general.php',
			'permission' => 'manage_options',
		);
		// icons "square" 48 x 48px (footer) and "horizontal" 36px height (header, width irrelevant) / both (resized if larger)
		$this->plugin['icon'] = array('square' => 'img/icon-square.png', 'horizontal' => 'img/icon-horizontal.png');
		$this->plugin['support_url'] = 'https://github.com/th23x/th23-upload/issues';
		$this->plugin['requirement_notices'] = array();

		// Load and setup required th23 Admin class
		if(file_exists($this->plugin['dir_path'] . '/inc/th23-admin-class.php')) {
			require($this->plugin['dir_path'] . '/inc/th23-admin-class.php');
			$this->admin = new th23_admin_v171($this);
		}
		if(!empty($this->admin)) {
			add_action('init', array(&$this, 'setup_admin_class'));
			// alternative update source for non-WP.org hosted plugin
			// important: remove following two lines for WP.org-hosted plugin
			$this->plugin['update_url'] = 'https://github.com/th23x/th23-upload/releases/latest/download/update.json';
			add_filter('site_transient_update_plugins', array(&$this->admin, 'update_download'));
		}
		else {
			add_action('admin_notices', array(&$this, 'error_admin_class'));
		}

		// Load plugin options
		// note: earliest possible due to localization only available at "init" hook
		add_action('init', array(&$this, 'init_options'));

		// Check requirements
		add_action('init', array(&$this, 'requirements'), 100);

		// Install/ uninstall
		add_action('activate_' . $this->plugin['basename'], array(&$this, 'install'));
		add_action('deactivate_' . $this->plugin['basename'], array(&$this, 'uninstall'));

		// == customization: from here on plugin specific ==

		// Register and load admin related JS and CSS
		add_action('admin_init', function() {
			wp_register_script('th23-upload-admin-js', $this->plugin['dir_url'] . 'th23-upload-admin.js', array('jquery'), $this->plugin['version'], true);
			wp_register_style('th23-upload-admin-css', $this->plugin['dir_url'] . 'th23-upload-admin.css', array(), $this->plugin['version']);
		});
		add_action('admin_enqueue_scripts', array(&$this, 'load_admin_js_css'));

		// Add link to additional size and upload settings on Settings / Media page
		add_action('admin_init', array(&$this, 'add_media_settings'));

		// Add restore button for (editable) images on attachment edit screen (AJAX)
		add_filter('attachment_fields_to_edit', array(&$this, 'restore_attachment'), 10, 2);
		add_action('admin_init', array(&$this, 'restore_attachment_image'));
		add_action('wp_ajax_th23_upload_restore', array(&$this, 'ajax_restore_attachment'));

		// Remove unmarked and original copy of image upon attachment deletion
		add_action('delete_attachment', array(&$this, 'delete_backups'));

		// Handle watermarks (AJAX)
		add_action('wp_ajax_th23_upload_watermark_upload', array(&$this, 'ajax_watermark_upload'));
		add_action('wp_ajax_th23_upload_watermark_delete', array(&$this, 'ajax_watermark_delete'));
		add_action('wp_ajax_th23_upload_watermark_add', array(&$this, 'ajax_watermark_add'));
		add_action('wp_ajax_th23_upload_watermark_remove', array(&$this, 'ajax_watermark_remove'));

		// Mark / unmark image attachments in Media Library (overview page)
		// todo: add remove / add watermark on edit attachment page and media popup eg via added field or in own tab - see "get_attachment_fields_to_edit" function and "attachment_fields_to_edit" hook
		add_filter('media_row_actions', array(&$this, 'add_watermark_actions'), 10, 2);

		// -- Media Library --

		// Add link to filtered attachments in row actions on posts / pages overview
		add_filter('post_row_actions', array(&$this, 'add_media_link'), 10, 2);
		add_filter('page_row_actions', array(&$this, 'add_media_link'), 10, 2);

		// Add filter to display only attachments of specified parent post / page in media library
		add_filter('posts_where', array(&$this, 'filter_apply'));
		add_action('restrict_manage_posts', array(&$this, 'filter_show'));
		add_action('post_updated', array(&$this, 'filter_cache'), 10, 3);
		add_action('save_post_attachment', array(&$this, 'filter_cache'), 10, 3);

		// Enhance default "attached to" column and add "filesize" column in media library
		add_filter('manage_media_columns', array(&$this, 'media_columns'));
		add_filter('manage_upload_sortable_columns', array(&$this, 'media_columns_sortable'));
		add_filter('request', array(&$this, 'media_columns_orderby'));
		add_action('manage_media_custom_column', array(&$this, 'media_columns_content'), 10, 2);
		add_action('updated_postmeta', array(&$this, 'filesize_cache'), 10, 3);
		add_action('wp_ajax_th23_upload_filesize', array(&$this, 'ajax_filesize_cleanup'));
		add_action('wp_ajax_th23_upload_cleanup', array(&$this, 'ajax_cleanup'));

	}

	// Setup th23 Admin class
	function setup_admin_class() {

		// enhance plugin info with generic plugin data
		// note: make sure function exists as it is loaded late only, if at all - see https://developer.wordpress.org/reference/functions/get_plugin_data/
		if(!function_exists('get_plugin_data')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		$this->plugin['data'] = get_plugin_data($this->plugin['file']);

		// admin class is language agnostic, except translations in parent i18n variable
		// note: need to populate $this->i18n earliest at init hook to get user locale
		$this->i18n = array(
			// reviewer: to keep consistency some admin language strings are used in sync with core
			'Settings' => __('Settings'),
			/* translators: parses in version number */
			'Version %s' => __('Version %s'),
			/* translators: parses in plugin name */
			'Copy from %s' => __('Copy from %s', 'th23-upload'),
			'Support' => __('Support'),
			'Done' => __('Done'),
			'Settings saved.' => __('Settings saved.'),
			'+' => __('+'),
			'-' => __('-'),
			'Save Changes' => __('Save Changes'),
			/* translators: parses in author */
			'By %s' => __('By %s'),
			'View details' => __('View details'),
			'Visit plugin site' => __('Visit plugin site'),
			'Error' => __('Error'),
			/* translators: 1: option name, 2: opening a tag of link to support/ plugin page, 3: closing a tag of link */
			'Invalid combination of input field and default value for "%1$s" - please %2$scontact the plugin author%3$s' => __('Invalid combination of input field and default value for "%1$s" - please %2$scontact the plugin author%3$s', 'th23-upload'),
			/* translators: parses in repository url for non-WP.org hosted plugin */
			'Updated via %s' => __('Updated via %s', 'th23-upload'),
			/* translators: parses in plugin information source url */
			'Failed to load plugin information from %s' => __('Failed to load plugin information from %s', 'th23-upload'),
		);

	}
	function error_admin_class() {
		/* translators: parses in names of 1: class which failed to load */
		echo '<div class="notice notice-error"><p style="font-size: 14px;"><strong>' . esc_html($this->plugin['data']['Name']) . '</strong></p><p>' . esc_html(sprintf(__('Failed to load %1$s class', 'th23-upload'), 'th23 Admin')) . '</p></div>';
	}

	// Load plugin options
	function init_options() {

		// Settings: Screen options
		// note: default can handle boolean, integer or string
		$this->plugin['screen_options'] = array(
			'hide_description' => array(
				'title' => __('Hide settings descriptions', 'th23-upload'),
				'default' => false,
			),
		);

		// Settings: Define plugin options
		$this->plugin['options'] = array();

		// max_width

		$description = __('Resizing images upon upload to maximum allowed dimensions. Aspect ratio of the original image will be preserved. The image will not be cropped.', 'th23-upload');
		$description .= '<br />' . __('Note: Only for JPG / JPEG images, as PNG (due to transparency) and GIF (due to animation) are difficult to handle', 'th23-upload');

		$this->plugin['options']['max_width'] = array(
			'section' => __('Image Size', 'th23-upload'),
			'section_description' => $description,
			'title' => __('Max width', 'th23-upload'),
			'description' => __('Limit for image width in pixels, set to "0" for no limit', 'th23-upload'),
			'default' => 1500,
			/* translators: "px" unit symbol / shortcut for pixels eg after input field */
			'unit' => __('px', 'th23-upload'),
			'attributes' => array(
				'class' => 'small-text',
			),
		);

		// max_height

		$this->plugin['options']['max_height'] = array(
			'title' => __('Max height', 'th23-upload'),
			'description' => __('Limit for image height in pixels, set to "0" for no limit', 'th23-upload'),
			'default' => 1500,
			/* translators: "px" unit symbol / shortcut for pixels eg after input field */
			'unit' => __('px', 'th23-upload'),
			'attributes' => array(
				'class' => 'small-text',
			),
		);

		// resize_quality

		$this->plugin['options']['resize_quality'] = array(
			'title' => __('Resize quality', 'th23-upload'),
			'description' => __('Quality for resized image, between 100 (excellent, large file) and 1 (poor, small file)', 'th23-upload'),
			'default' => 95,
			'attributes' => array(
				'class' => 'small-text',
			),
		);

		// resize_suffix

		$description = __('Optional, extension to the file name for resized files', 'th23-upload');
		$description .= '<br />' . __('Example: "_resized" will change file name from "image.jpg" to "image_resized.jpg"', 'th23-upload');

		$this->plugin['options']['resize_suffix'] = array(
			'title' => __('Resize suffix', 'th23-upload'),
			'description' => $description,
			'default' => '_resized',
		);

		// wp_default

		$description = __('By default WordPress limits image dimensions on uploads, automatically resizing larger ones to max 2560px width/height. Also additional image sizes are auto-generated taking up space on server.', 'th23-upload');
		$description .= '<br />' . __('Note: Disabling the default behaviour is recommended to make full use of the plugins capabilities!', 'th23-upload');

		$this->plugin['options']['wp_default'] = array(
			'title' => __('Default resizing', 'th23-upload'),
			'description' => $description,
			'element' => 'checkbox',
			'default' => array(
				'single' => 1,
				0 => '',
				1 => __('Disable default image resizing on upload', 'th23-upload'),
			),
		);

		// watermarks

		$section_description = __('Adding watermarks to images to ensure they can be identified belonging to this site.', 'th23-upload');
		$section_description .= '<br />' . __('Note: All setting changes only apply to newly uploaded or edited images and their auto-generated sizes', 'th23-upload');

		$description = __('Warning: Do not modify watermarked images in the built-in image editor - remove watermark first, edit image and re-add watermark afterwards!', 'th23-upload');
		$description .= '<br />' . __('Note: A copy of your originally uploaded image with maximum allowed dimensions is kept unaccessible to users, so you can restore an unmarked version later. Auto-generated unscaled / unrotated copies of your uploaded images will not be kept, as these would otherwise be accessible without watermark.', 'th23-upload');

		$this->plugin['options']['watermarks'] = array(
			'section' => __('Watermark', 'th23-upload'),
			'section_description' => $section_description,
			'title' => __('Enable watermarks', 'th23-upload'),
			'description' => $description,
			'element' => 'checkbox',
			'default' => array(
				'single' => 0,
				0 => '',
				1 => __('Add watermarks to JPG attachments', 'th23-upload'),
			),
			'attributes' => array(
				'data-childs' => '.option-watermarks_upload,.option-watermarks_restore,.option-watermarks_sizes,.option-watermarks_image,.option-watermarks_position,.option-watermarks_padding,.option-watermarks_maxcover,.option-watermarks_mass_actions',
			),
			'save_after' => 'recheck_requirements',
		);

		// watermarks_upload

		$this->plugin['options']['watermarks_upload'] = array(
			'title' => __('Upload', 'th23-upload'),
			'description' => __('Disabling this option will still leave you the chance to add watermarks for individual images in the media gallery', 'th23-upload'),
			'element' => 'checkbox',
			'default' => array(
				'single' => 1,
				0 => '',
				1 => __('Automatically apply watermark upon upload', 'th23-upload'),
			),
		);

		// watermarks_restore

		$description = __('Disable default backup of images upon editing, to ensure no unmarked images are accessible', 'th23-upload');
		$description .= '<br />' . __('Important: This also affects images not watermarked and prevents "dormant" additional files ending in "-e1234567890123" taking up unnecessary space.', 'th23-upload');

		$this->plugin['options']['watermarks_restore'] = array(
			'title' => __('Restore', 'th23-upload'),
			'description' => $description,
			'element' => 'checkbox',
			'default' => array(
				'single' => 1,
				0 => '',
				1 => __('Disable default image revisions', 'th23-upload'),
			),
		);

		// watermarks_sizes

		// note: list limited, only properly registered by WP core functions, excluding eg those handled by plugins deliberately outside WP core functions (ie excluded by using "intermediate_image_sizes_advanced" filter), excluding image sizes exceeding defined maximum ("max_width" and "max_width" settings)
		$description = __('Select image sizes that the watermark should be applied to', 'th23-upload');
		$description .= '<br />' . __('Warning: Manual cropping of images done will be lost upon watermarking', 'th23-upload');
		$description .= '<br />' . __('Recommendation: Select all un-cropped image sizes, esp the WP defaults "full", "large", "medium_large" and "medium"', 'th23-upload');
		$description .= '<br />' . __('Note: List indicates maximum dimensions in pixels (width x height) for each and is limited to properly registered sizes, that are smaller than the maximum upload dimensions', 'th23-upload');

		$this->plugin['options']['watermarks_sizes'] = array(
			'title' => __('Image sizes', 'th23-upload'),
			'description' => $description,
			'element' => 'checkbox',
			'default' => array(
				'multiple' => array(''),
			),
		);

		foreach($this->get_image_sizes() as $size => $details) {
			// only show image sizes, which are not exceeding the max dimensions
			if($details['active']) {
				$cropped = ($details['crop']) ? __('cropped to', 'th23-upload') : __('max', 'th23-upload');
				$width = (empty($details['width'])) ? __('unlimited', 'th23-upload') : (int) $details['width'];
				$height = (empty($details['height'])) ? __('unlimited', 'th23-upload') : (int) $details['height'];
				/* translators: "px" unit symbol / shortcut for pixels eg after input field */
				$this->plugin['options']['watermarks_sizes']['default'][$size] = $size . ': ' . $cropped . ' ' . $width . ' x ' . $height . ' ' . __('px', 'th23-upload');
			}
		}

		// watermarks_image

		$description = __('Click to select image used as watermark', 'th23-upload');
		$description .= '<br />' . __('Note: Ideally PNG file with transparent background and not too big in size / dimensions', 'th23-upload');

		$this->plugin['options']['watermarks_image'] = array(
			'title' => __('Watermark', 'th23-upload'),
			'description' => $description,
			'render' => 'watermark_image',
			'default' => '',
			'element' => 'hidden',
		);

		// watermarks_position

		$this->plugin['options']['watermarks_position'] = array(
			'title' => __('Position', 'th23-upload'),
			'description' => __('Position of watermark on the image', 'th23-upload'),
			'element' => 'radio',
			'default' => array(
				'single' => 9,
				'1' => __('top left', 'th23-upload'),
				'2' => __('top center', 'th23-upload'),
				'3' => __('top right', 'th23-upload'),
				'4' => __('mid left', 'th23-upload'),
				'5' => __('mid center', 'th23-upload'),
				'6' => __('mid right', 'th23-upload'),
				'7' => __('bottom left', 'th23-upload'),
				'8' => __('bottom center', 'th23-upload'),
				'9' => __('bottom right', 'th23-upload'),
			),
		);

		// watermarks_padding

		$this->plugin['options']['watermarks_padding'] = array(
			'title' => __('Offset', 'th23-upload'),
			'description' => __('Distance of watermark from image borders in pixels', 'th23-upload'),
			'default' => 10,
			/* translators: "px" unit symbol / shortcut for pixels eg after input field */
			'unit' => __('px', 'th23-upload'),
			'attributes' => array(
				'class' => 'small-text',
			),
		);

		// watermarks_maxcover

		$this->plugin['options']['watermarks_maxcover'] = array(
			'title' => __('Maximum coverage', 'th23-upload'),
			'description' => __('Maximum width / height of image covered by watermark in % - watermark will be shrinked, if required', 'th23-upload'),
			'default' => 30,
			/* translators: "%" unit symbol / shortcut for percent eg after input field */
			'unit' => __('%', 'th23-upload'),
			'attributes' => array(
				'class' => 'small-text',
			),
		);

		// watermarks_mass_actions
		// note: place holder for adding / removing watermark for all JPGs, not storing any value, see function "watermark_mass_actions"

		$this->plugin['options']['watermarks_mass_actions'] = array(
			'title' => __('Mass actions', 'th23-upload'),
			'render' => 'watermark_mass_actions',
			'element' => 'hidden',
			'default' => '',
		);

	}

	// Re-trigger requirements checks after plugin option updates saved [watermarks]
	function recheck_requirements($plugin_options) {
		$this->plugin['requirement_notices'] = array();
		$this->requirements();
		return $plugin_options;
	}

	// Get information about available image sizes [watermarks_sizes]
	// note: does NOT include image sizes hidden through usage of filter "intermediate_image_sizes_advanced", eg th23 Social, th23 Featured which are "hiding" their image sizes from normal WP handling
	function get_image_sizes() {

		// note: WP introduced intermediate image sizes without being accessible in admin area to handle large files (see https://wordpress.org/support/topic/scaled-jpg-innecesary-sufix-and-added-2-image-sizes/)
		$additional_sizes = wp_get_additional_image_sizes();

		// prefill to include "full" as image size
		$sizes = array(
			'full' => array(
				'width' => (!empty($this->options['max_width']) ? (int) $this->options['max_width'] : 0),
				'height' => (!empty($this->options['max_height']) ? (int) $this->options['max_height'] : 0),
				'crop' => false,
				'active' => true,
			),
		);

		// reverse array as default WP sorts from smallest to biggest size
		$image_sizes = array_reverse(get_intermediate_image_sizes(), true);
		foreach($image_sizes as $size) {

			if(in_array($size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
				$sizes[$size]['width'] = get_option($size . '_size_w');
				$sizes[$size]['height'] = get_option($size . '_size_h');
				$sizes[$size]['crop'] = (bool) get_option($size . '_crop');
			}
			elseif(isset($additional_sizes[$size])) {
				$sizes[$size]['width'] = $additional_sizes[$size]['width'];
				$sizes[$size]['height'] = $additional_sizes[$size]['height'];
				$sizes[$size]['crop'] = $additional_sizes[$size]['crop'];
			}

			// only include default image sizes to be selected as "active" which are bigger than the defined max dimensions
			if((!empty($this->options['max_width']) && $sizes[$size]['width'] >= $this->options['max_width']) || (!empty($this->options['max_height']) && $sizes[$size]['height'] >= $this->options['max_height'])) {
				$sizes[$size]['active'] = false;
			}
			else {
				$sizes[$size]['active'] = true;
			}

		}

		return $sizes;

	}

	// Show current watermark image and upload input field in plugin settings [watermarks_image]
	function watermark_image($default, $current_watermark) {
		$html = '';

		// current image and placeholder
		$placeholder = '';
		$image = '';
		$watermark = $this->get_watermark($current_watermark);
		if(!empty($watermark['url_file'])) {
			$placeholder = ' class="hidden"';
			$image = '<img src="' . esc_url($watermark['url_file']) . '" />';
		}
		$html .= '<div id="th23-upload-watermark-image">';
		$html .= '<div id="th23-upload-watermark-placeholder"' . $placeholder . '>' . esc_html__('Select watermark', 'th23-upload') . '</div>';
		$html .= $image . '</div>';

		// image selection
		// todo: horizontal image selection, scrollable if larger than width available
		$html .= '<div><ul id="th23-upload-watermark-selection" class="hidden">';
		$html .= '<input id="th23-upload-watermark-file" name="th23-upload-watermark-file" type="file" />';
		$html .= '<input id="th23-upload-watermark-nonce" type="hidden" value="' . esc_attr(wp_create_nonce('th23-upload-nonce')) . '" />';
		$html .= '<li class="th23-upload-watermark-item upload-button"><label for="th23-upload-watermark-file" class="button-secondary">' . esc_html__('Upload watermark', 'th23-upload') . '</label></li>';
		// list all images within watermark upload folder
		if(!empty($watermark['dir']) && !empty($files = preg_grep('/\.(' . implode('|', array_keys($this->plugin['watermark_types'])) . ')$/i', scandir($watermark['dir'])))) {
			foreach($files as $filename) {
				$html .= $this->watermark_item_html($watermark['url'], $filename);
			}
		}
		$html .= '</ul></div>';

		return $html;
	}
	function watermark_item_html($watermark_url, $filename) {
		return '<li class="th23-upload-watermark-item"><div><img src="' . esc_url($watermark_url . $filename) . '" data-file="' . esc_attr($filename) . '" /><div class="caption">' . esc_attr($filename) . '</div><div class="delete" data-file="' . esc_attr($filename) . '">' . esc_html__('Delete', 'th23-upload') . '</div></div></li>';
	}

	// Show watermark mass actions in plugin settings [watermarks_mass_actions]
	function watermark_mass_actions($default, $current_value) {
		$html = '';

		// get IDs of all suitable attachments separated by comma
		$attachments = get_posts(array(
			'fields' => 'ids',
		    'post_type' => 'attachment',
			'post_status' => 'any',
			'post_mime_type' => $this->plugin['images_types'],
			'numberposts' => -1,
		));
		$html .= '<input type="hidden" id="th23-upload-attachments" value="' . esc_attr(implode(',', $attachments)) . '">';

		// mass action buttons
		$nonce = wp_create_nonce('th23-upload-nonce');
		$html .= '<div id="th23-upload-mass-trigger">';
		$html .= '<div id="th23-upload-mass-buttons">';
		$html .= '<input type="button" class="button-secondary" value="' . esc_attr__('Watermark all JPG attachments', 'th23-upload') . '" data-action="th23_upload_watermark_add" data-nonce="' . esc_attr($nonce) . '" /> ';
		$html .= '<input type="button" class="button-secondary" value="' . esc_attr__('Remove watermark from all JPG attachments', 'th23-upload') . '" data-action="th23_upload_watermark_remove" data-nonce="' . esc_attr($nonce) . '" /> ';
		if(!empty($this->options['watermarks_restore'])) {
			$html .= '<input type="button" class="button-secondary" value="' . esc_attr__('Clean up all JPG attachments', 'th23-upload') . '" data-action="th23_upload_cleanup" data-nonce="' . esc_attr($nonce) . '" /> ';
		}
		$html .= '</div>';
		$html .= '<p><span class="description">';
		$html .= esc_html__('Mass add / remove watermark for all JPG images in the media gallery.', 'th23-upload');
		if(!empty($this->options['watermarks_restore'])) {
			$html .= ' ' . esc_html__('Or clean up by removing dormant image files left behind after image edits.', 'th23-upload');
		}
		$html .= '<br />' . esc_html__('Please confirm below before clicking above buttons.', 'th23-upload');
		if(!empty($this->options['watermarks_restore'])) {
			$html .= '<br />' . esc_html__('Warning: Deleting of images during cleanup might cause broken links in case such images have been linked hard coded before!', 'th23-upload');
		}
		/* translators: parses in number of images to process */
		$html .= '<br />' . sprintf(esc_html__('Note: This can take a long time and heavily utilize your server as %d JPG attachments have to processed', 'th23-upload'), count($attachments));
		$html .= '</span></p>';
		$html .= '<p class="th23-upload-mass-confirm"><label><input id="th23-upload-mass-confirm" value="yes" type="checkbox" />' . esc_html__('Confirm starting mass action', 'th23-upload') . '</label></p>';
		$html .= '</div>';

		// progress bar
		$html .= '<div id="th23-upload-mass-progress" class="hidden">';
		$html .= '<input type="button" id="th23-upload-mass-stop" class="button-secondary" value="' . esc_attr__('Stop', 'th23-upload') . '" /><input type="button" id="th23-upload-mass-close" class="button-secondary hidden" value="' . esc_attr__('Close', 'th23-upload') . '" />';
		$html .= '<p><div id="th23-upload-mass-bar"><div></div></div></p>';
		$html .= '<p id="th23-upload-mass-last"></p>';
		$html .= '</div>';

		return $html;
	}

	// Install
	function install() {

		// Prefill values in an option template, keeping them user editable (and therefore not specified in the default value itself)
		// need to check, if items exist(ed) before and can be reused - so we dont' overwrite them (see uninstall with delete_option inactive)
		if(isset($this->plugin['presets'])) {
			if(!isset($this->options) || !is_array($this->options)) {
				$this->options = array();
			}
			$this->options = array_merge($this->plugin['presets'], $this->options);
		}
		// Set option values, including current plugin version (invisibly) to be able to detect updates
		$this->options['version'] = $this->plugin['version'];
		update_option($this->plugin['slug'], $this->admin->get_options($this->options));
		$this->options = (array) get_option($this->plugin['slug']);

	}

	// Uninstall
	function uninstall() {

		// NOTICE: To keep all settings etc in case the plugin is reactivated, return right away - if you want to remove previous settings and data, comment out the following line!
		return;

		// Delete option values
		delete_option($this->plugin['slug']);

	}

	// Requirements - checks
	function requirements() {
		// check requirements only on relevant admin pages
		global $pagenow;
		if(empty($pagenow)) {
			return;
		}
		if('index.php' == $pagenow) {
			// admin dashboard
			$context = 'admin_index';
		}
		elseif('plugins.php' == $pagenow) {
			// plugins overview page
			$context = 'plugins_overview';
		}
		elseif($this->plugin['settings']['base'] == $pagenow && !empty($_GET['page']) && $this->plugin['slug'] == $_GET['page']) {
			// plugin settings page
			$context = 'plugin_settings';
		}
		else {
			return;
		}

		// customization: Check - plugin not designed for multisite setup
		if(is_multisite()) {
			$this->plugin['requirement_notices']['multisite'] = '<strong>' . __('Warning', 'th23-upload') . '</strong>: ' . __('Your are running a multisite installation - the plugin is not designed for this setup and therefore might not work properly', 'th23-upload');
		}

		// get watermark dir, url, file - force update ie no cache
		$watermark = $this->get_watermark(null, true);

		// customization: Check - missing watermark folder (and failed to create it)
		if(empty($watermark['dir']) && 'admin_index' != $context) {
			/* translators: parses in the expected folder */
			$this->plugin['requirement_notices']['watermark_folder'] = '<strong>' . __('Warning', 'th23-upload') . '</strong>: ' . sprintf(__('Watermark upload folder is missing and could not be created. Please use a FTP program to create the folder %s on your server and make sure its writable.', 'th23-upload'), '<code>' . esc_html($watermark['dir_expected']) . '</code>');
		}

		// customization: Check - enabled, but no watermark image
		if(!empty($this->options['watermarks']) && empty($watermark['file']) && 'admin_index' != $context) {
			$this->plugin['requirement_notices']['watermark_image'] = '<strong>' . __('Warning', 'th23-upload') . '</strong>: ' . __('No watermark image available. Please upload / select one in the plugin settings.', 'th23-upload');
		}

		// customization: Check - enabled and watermark image defined, existence and loading of image-editors.php
		if(!empty($this->options['watermarks']) && !empty($watermark['file']) && 'admin_index' != $context) {
			// try loading the watermark image which for sure is existing into the image editor as check
			$image_editor = wp_get_image_editor($watermark['dir_file']);
			if(is_wp_error($image_editor) || !is_callable(array($image_editor, 'th23_upload_add_watermark'))) {
				$this->plugin['requirement_notices']['image_editor'] = '<strong>' . __('Warning', 'th23-upload') . '</strong>: ' . __('Image editor does not support watermarking images. Please ensure your server uses Imagick or GD for image processing.', 'th23-upload');
			}
		}

		// customization: Check - enabled, but missing .htaccess entries in upload folder, to prevent access to non-watermarked images
		if(!empty($this->options['watermarks']) && 'admin_index' != $context) {
			// rule to prevent access to any watermarked image file ending with _no-watermark.jpg/.jpeg in upload folder (and its children) - F = forbidden, triggers 403 error / L = last rule / NC = case-insensitive
			$rule = array(
				'# th23 Upload - begin',
				'<IfModule mod_rewrite.c>',
				'RewriteEngine On',
				'RewriteBase /',
				'RewriteRule _(org-upload|no-watermark)\.(' . implode('|', array_keys($this->plugin['images_types'])) . ')$ - [F,L,NC]',
				'</IfModule>',
				'# th23 Upload - end',
			);
			// check for existing .htaccess file in upload folder
			$htaccess = $watermark['dir_base'] . '.htaccess';
			$content = (is_file($htaccess)) ? @file_get_contents($htaccess) : '';
			// check if .htaccess file has plugin specific rule included
			if(false === strpos($content, $rule[0])) {
				@file_put_contents($htaccess, implode(PHP_EOL, $rule) . PHP_EOL . $content);
			}
			// re-check
			if(!is_file($htaccess) || empty($content = @file_get_contents($htaccess)) || false === strpos($content, $rule[0])) {
				/* translators: parses in required file name and target folder */
				$this->plugin['requirement_notices']['htaccess'] = sprintf(__('<strong>Warning</strong>: Non-watermarked original images are accessible to users. Required server rules could not be added automatically. Please ensure %1$s file exists in the folder %2$s and contains the following lines:', 'th23-upload'), '<code>.htaccess</code>', '<code>/' . esc_html(str_replace(ABSPATH, '', $watermark['dir_base'])) . '</code>') . '<pre>' . esc_html(implode(PHP_EOL, $rule)) . '</pre>';
			}
		}

	}

	// == customization: from here on plugin specific ==

	// Load admin related JS and CSS - on plugin settings page (settings_page_th23-upload), media library (upload.php) and (attachment) edit page (post.php)
	function load_admin_js_css($page) {
		if('settings_page_th23-upload' == $page || 'upload.php' == $page || 'post.php' == $page) {
			wp_enqueue_script('th23-upload-admin-js');
			wp_enqueue_style('th23-upload-admin-css');
		}
	}

	// Add link to additional size and upload settings on Settings / Media page
	function add_media_settings(){
		add_settings_field('th23-upload-sizes', __('Maximum size', 'th23-upload'), array(&$this, 'media_sizes_link'), 'media', 'default');
		add_settings_field('th23-upload-watermarks', __('Watermarks', 'th23-upload'), array(&$this, 'media_watermarks_link'), 'media', 'uploads');
	}
	function media_sizes_link($args){
		/* translators: parses in link to "th23 Upload Settings" */
		printf(esc_html__('For further image dimension settings and resizing options upon upload see %s', 'th23-upload'), '<a href="' . esc_url($this->plugin['settings']['base'] . '?page=' . $this->plugin['slug']) . '">' . esc_html($this->plugin['data']['Name'] . ' ' . __('Settings')) . '</a>');
	}
	function media_watermarks_link($args){
		/* translators: parses in link to "th23 Upload Settings" */
		printf(esc_html__('For watermark setting upon image upload see %s', 'th23-upload'), '<a href="' . esc_url($this->plugin['settings']['base'] . '?page=' . $this->plugin['slug']) . '">' . esc_html($this->plugin['data']['Name'] . ' ' . __('Settings')) . '</a>');
	}

	// Add restore button for (editable) images on attachment edit screen
	function restore_attachment($form_fields, $post) {
		// default restore disabled, handling file attachment
		if(!empty($this->options['watermarks_restore']) && !empty($attached_file = get_attached_file($post->ID))) {
			// own backup file exists
			$source_backup = $this->str_lreplace('.', '_org-upload.', $attached_file);
			if(is_file($source_backup)) {
				$form_fields['th23_upload_restore'] = array(
					'label' => __('Previous version', 'th23-upload'),
					'input' => 'html',
					'html' => '<a href="" id="th23-upload-restore" data-img-src="' . esc_attr(add_query_arg(array('action' => 'th23-upload-restore', 'id' => $post->ID, 'nonce' => wp_create_nonce('th23-upload-nonce')), get_admin_url())) . '">' . esc_html__('Restore un-edited original', 'th23-upload') . '</a><div id="th23-upload-restore-select" class="hidden"><img src="" /><input type="button" class="button" id="th23-upload-restore-do" data-nonce="' . esc_attr(wp_create_nonce('th23-upload-nonce')) . '" data-attachment="' . esc_attr($post->ID) . '" value="' . esc_attr__('Restore image', 'th23-upload') . '"></div>',
				);
			}
		}
		return $form_fields;
	}

	// Pass through available restore image (aside .htaccess restrictions)
	function restore_attachment_image() {
		// restore image requested
		if(empty($_GET['action']) || 'th23-upload-restore' !== sanitize_text_field(wp_unslash($_GET['action']))) {
			return;
		}
		// check nonce
		if(empty($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'th23-upload-nonce')) {
			die(esc_html__('Invalid request!', 'th23-upload'));
		}
		// check user permission
		if(!current_user_can('edit_posts')) {
			die(esc_html__('No permission!', 'th23-upload'));
		}
		// check attachment id
		if(empty($_GET['id']) || empty($id = (int) $_GET['id']) || empty($attachment = get_post($id)) || empty($attachment->post_type) || 'attachment' != $attachment->post_type) {
			die(esc_html__('No valid attachment!', 'th23-upload'));
		}
		// get attachment files
		if(empty($this->options['watermarks_restore']) || empty($attached_file = get_attached_file($attachment->ID)) || !is_file($source_backup = $this->str_lreplace('.', '_org-upload.', $attached_file))) {
			die(esc_html__('No backup to restore!', 'th23-upload'));
		}
		header('Content-type: image/jpeg');
		readfile($source_backup);
		exit;
	}

	// Restore original image (AJAX)
	function ajax_restore_attachment() {
		// check nonce
		if(empty($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'th23-upload-nonce')) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Invalid request!', 'th23-upload')));
		}
		// check user permission
		if(!current_user_can('edit_posts')) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No permission!', 'th23-upload')));
		}
		// check attachment id
		if(empty($_POST['id']) || empty($id = (int) $_POST['id']) || empty($attachment = get_post($id)) || empty($attachment->post_type) || 'attachment' != $attachment->post_type) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No valid attachment!', 'th23-upload')));
		}
		// get attachment files
		if(empty($this->options['watermarks_restore']) || empty($attached_file = get_attached_file($attachment->ID)) || !is_file($source_backup = $this->str_lreplace('.', '_org-upload.', $attached_file))) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No backup to restore!', 'th23-upload')));
		}
		// copy backup to main image file
		if(empty($filesystem = $this->filesystem()) || !$filesystem->copy($source_backup, $attached_file, true)) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Failed to restore original!', 'th23-upload')));
		}
		// watermarked?
		$watermarked = is_file($no_watermark = $this->str_lreplace('.', '_no-watermark.', $attached_file));
		if($watermarked) {
			wp_delete_file($no_watermark);
			delete_post_meta($attachment->ID, 'th23-upload-watermarks');
		}
		// re-generate subsizes
		$attachment_meta = wp_get_attachment_metadata($attachment->ID);
		$attachment_meta['sizes'] = array();
		wp_update_attachment_metadata($attachment->ID, $attachment_meta);
		$attachment_meta = wp_update_image_subsizes($attachment->ID);
		$this->cleanup($attachment->ID);
		$response = array('result' => 'success');
		// re-apply watermark
		if($watermarked) {
			// note: requires re-update of returned meta data (not yet done by add_watermark function)
			$attachment_meta = $this->add_watermark($attachment_meta, $attachment->ID, 'th23-upload');
			// remove response element from the meta array (it shouldn't be saved)
			if(!empty($attachment_meta['th23-upload'])) {
				$response = $attachment_meta['th23-upload'];
				unset($attachment_meta['th23-upload']);
			}
			wp_update_attachment_metadata($attachment->ID, $attachment_meta);
		}
		$this->ajax_send_response($response);
	}

	// Remove unmarked and original copy of image upon attachment deletion
	function delete_backups($attachment_id) {
		$attached_file = get_attached_file($attachment_id);
		if(!empty($attached_file) && is_file($no_watermark = $this->str_lreplace('.', '_no-watermark.', $attached_file)) && !wp_delete_file($no_watermark)) {
			$this->log('Failed to delete unmarked copy upon deletion of image "/' . esc_attr(str_replace(ABSPATH, '', $no_watermark)) . '"', true);
		}
		if(!empty($attached_file) && is_file($org_upload = $this->str_lreplace('.', '_org-upload.', $attached_file)) && !wp_delete_file($org_upload)) {
			$this->log('Failed to delete original copy upon deletion of image "/' . esc_attr(str_replace(ABSPATH, '', $org_upload)) . '"', true);
		}
	}

	// Handle watermarking (AJAX)
	function ajax_watermark_upload() {
		$this->ajax_watermark('upload');
	}
	function ajax_watermark_delete() {
		$this->ajax_watermark('delete');
	}
	function ajax_watermark_add() {
		$this->ajax_watermark('add');
	}
	function ajax_watermark_remove() {
		$this->ajax_watermark('remove');
	}
	function ajax_watermark($action = '') {

		// check nonce
		if(empty($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'th23-upload-nonce')) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Invalid request!', 'th23-upload')));
		}
		// allowed actions / required permissons
		$perms = array(
			'upload' => 'manage_options',
			'delete' => 'manage_options',
			'add' => 'edit_posts',
			'remove' => 'edit_posts',
		);
		// check for valid action
		if(empty($action) || empty($perms[$action])) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No valid action!', 'th23-upload')));
		}
		// check user permission
		if(!current_user_can($perms[$action])) {
			$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No permission!', 'th23-upload')));
		}

		// get watermark dir, url, file
		$watermark = $this->get_watermark();

		// additional checks and data before adding / removing watermarks (see below)
		if('add' == $action || 'remove' == $action) {
			// get attachment, validate and get its meta data
			if(empty($_POST['id']) || empty($attachment_id = (int) $_POST['id']) || empty($attachment = get_post($attachment_id)) || empty($attachment->post_type) || 'attachment' != $attachment->post_type || empty($attachment->post_mime_type) || !in_array($attachment->post_mime_type, $this->plugin['images_types'])) {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No valid image!', 'th23-upload')));
			}
			$attachment_meta = wp_get_attachment_metadata($attachment_id);
			$attachment_ident = $attachment->post_title . ' (/' . str_replace(ABSPATH, '', $watermark['dir_base']) . $attachment_meta['file'] . ')';
		}

		// upload watermark image
		if('upload' == $action) {

			// check file specifics - and prevent uploading to outside plugin upload directory "/", prevent absolute windows drive path ":", prevent anything not an image
			if(empty($_FILES['file']) || empty($_FILES['file']['name']) || empty($filename = sanitize_text_field(wp_unslash($_FILES['file']['name']))) || false !== strpos($filename, '/') || ':' == substr($filename, 1, 1) || !isset($this->plugin['watermark_types'][pathinfo($filename, PATHINFO_EXTENSION)]) || empty($_FILES['file']['tmp_name']) || empty($tmpname = sanitize_text_field(wp_unslash($_FILES['file']['tmp_name']))) || !is_uploaded_file($tmpname) || empty($mime_type = mime_content_type($tmpname)) || !in_array($mime_type, $this->plugin['watermark_types'])) {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No valid watermark!', 'th23-upload')));
			}

			// move watermark to plugin upload folder
			if(empty($watermark['dir'])) {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Failed to upload watermark!', 'th23-upload')));
			}
			$target = $watermark['dir'] . $filename;
			$replace = is_file($target);
			// note: uses PHP function as no alternative WP core function offers option to handle upload without creating a media item
			if(call_user_func('move_uploaded_file', $tmpname, $target)) {
				$this->ajax_send_response(array(
					'result' => 'success',
					'replace' => $replace,
					'item' => esc_attr($filename),
					'item_url' => esc_url($watermark['url'] . $filename),
					'html' => $this->watermark_item_html($watermark['url'], $filename),
				));
			}
			else {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Failed to upload watermark!', 'th23-upload')));
			}

		}
		// delete watermark image
		elseif('delete' == $action) {

			// check allowed image string - prevent deleting outside plugin upload directory "/", prevent absolute windows drive path ":", prevent anything not an image
			if(empty($_POST['file']) || empty($filename = sanitize_text_field(wp_unslash($_POST['file']))) || false !== strpos($filename, '/') || ':' == substr($filename, 1, 1) || !isset($this->plugin['watermark_types'][pathinfo($filename, PATHINFO_EXTENSION)])) {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('No valid watermark!', 'th23-upload')));
			}

			// delete watermark
			if(!empty($watermark['dir']) && wp_delete_file($watermark['dir'] . $filename)) {
				$this->ajax_send_response(array('result' => 'success'));
			}
			else {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Failed to delete watermark!', 'th23-upload')));
			}

		}
		// add watermark to attachment image
		elseif('add' == $action) {

			// add watermark and update returned meta data (not yet done by add_watermark function)
			$attachment_meta = $this->add_watermark($attachment_meta, $attachment_id, 'th23-upload');
			if(!empty($attachment_meta['th23-upload'])) {
				$response = $attachment_meta['th23-upload'];
				// remove this element from the meta array (it shouldn't be saved)
				unset($attachment_meta['th23-upload']);
			}
			else {
				$response = array('result' => 'success', 'msg' => esc_html__('Watermarked', 'th23-upload'), 'item' => esc_html($attachment_ident), 'action' => 'th23_upload_watermark_remove', 'wait' => esc_attr__('Unmarking...', 'th23-upload'), 'html' => esc_html__('Remove watermark', 'th23-upload'));
			}
			wp_update_attachment_metadata($attachment_id, $attachment_meta);

			$this->ajax_send_response($response);

		}
		// remove watermark from attachment image
		elseif('remove' == $action) {

			$watermarks_meta = get_post_meta($attachment_id, 'th23-upload-watermarks', true);

			// check file is watermarked
			if(empty($watermarks_meta['marked'])) {
				$this->ajax_send_response(array('result' => 'success', 'msg' => esc_html__('Not watermarked!', 'th23-upload'), 'item' => esc_html($attachment_ident)));
			}

			// check unmarked copy exists
			if(empty($watermarks_meta['unmarked']) || !is_file($watermark['dir_base'] . $watermarks_meta['unmarked'])) {
				$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Missing unmarked file!', 'th23-upload'), 'item' => esc_html($attachment_ident)));
			}

			// restore full size
			if(!empty($watermarks_meta['marked']['full'])) {
				if(!empty($filesystem = $this->filesystem()) && $filesystem->move($watermark['dir_base'] . $watermarks_meta['unmarked'], $watermark['dir_base'] . $attachment_meta['file'], true)) {
					unset($watermarks_meta['marked']['full']);
				}
				else {
					$this->ajax_send_response(array('result' => 'error', 'msg' => esc_html__('Failed to restore unmarked!', 'th23-upload'), 'item' => esc_html($attachment_ident)));
				}
			}

			// remove other marked sizes - store to limit image subsize creation to those removed (see below filter around call to "wp_update_image_subsizes" and function "recreate_image_subsizes")
			$this->data['subsizes_removed'] = $watermarks_meta['marked'];
			$msg = __('Watermark removed', 'th23-upload');
			// get upload path of subsize images (only saved with full filename in attachment meta array)
			$upload_path = dirname($attachment_meta['file']) . '/';
			foreach($watermarks_meta['marked'] as $size => $marked) {
				$file = $watermark['dir_base'] . $upload_path . $attachment_meta['sizes'][$size]['file'];
				if(!is_file($file) || wp_delete_file($file)) {
					unset($attachment_meta['sizes'][$size]);
					unset($watermarks_meta['marked'][$size]);
				}
				else {
					$msg = __('Watermark partially removed, see log', 'th23-upload');
					$this->log('Failed to remove watermark from sub-size of attachment ID "' . esc_attr($attachment_id) . '", image "/' . esc_attr(str_replace(ABSPATH, '', $file)) . '"', true);
				}
			}

			// update attachment meta data (including watermarks meta)
			wp_update_attachment_metadata($attachment_id, $attachment_meta);
			update_post_meta($attachment_id, 'th23-upload-watermarks', $watermarks_meta);

			// recreate missing image sizes - limited to those we removed to get rid of the watermark (see filter)
			add_filter('wp_get_missing_image_subsizes', array(&$this, 'recreate_image_subsizes'));
			wp_update_image_subsizes($attachment_id);
			remove_filter('wp_get_missing_image_subsizes', array(&$this, 'recreate_image_subsizes'));

			$this->ajax_send_response(array('result' => 'success', 'msg' => esc_html($msg), 'item' => esc_html($attachment_ident), 'action' => 'th23_upload_watermark_add', 'wait' => esc_attr__('Watermarking...', 'th23-upload'), 'html' => esc_html__('Add watermark', 'th23-upload')));

		}

	}
	function ajax_send_response($response) {
		header('Content-Type: application/json');
		echo wp_json_encode($response);
		wp_die();
	}

	// Helper to limit re-creation of image sub-sizes when removing watermarks (see function "ajax_watermark" above)
	function recreate_image_subsizes($missing_sizes) {
		return array_intersect_key($missing_sizes, $this->data['subsizes_removed']);
	}

	// Add remove / add watermark option to attachments on Media Library overview page
	// todo: add filter "All marked/unmarked"
	// todo: allow for selection of multiple attachments at once and offer mass action (drop-down) for watermarks
	function add_watermark_actions($actions, $attachment) {

		// check user permission
		if(!current_user_can('edit_posts')) {
			return $actions;
		}

		// watermark only for image types the editor can handle
		if(empty($attachment->post_mime_type) || !in_array($attachment->post_mime_type, $this->plugin['images_types'])) {
			return $actions;
		}

		$nonce = wp_create_nonce('th23-upload-nonce');
		$attachment_meta = wp_get_attachment_metadata($attachment->ID);
		$watermarks_meta = get_post_meta($attachment->ID, 'th23-upload-watermarks', true);

		// unmarked original existing, marked image sizes existing
		if(!empty($watermarks_meta['unmarked']) && !empty($watermarks_meta['marked'])) {
			$actions['th23_upload_watermark_remove'] = '<a class="th23-upload-watermark" href="" data-attachment="' . esc_attr($attachment->ID) . '" data-action="th23_upload_watermark_remove" data-nonce="' . esc_attr($nonce) . '" data-wait="' . esc_attr__('Unmarking...', 'th23-upload') . '">' . esc_html__('Remove watermark', 'th23-upload') . '</a>';
		}

		// watermarks enabled, existing unmarked sizes of the image, where according to settings watermark could be applied
		// note: adding "full" size placeholder as its in the attachment meta not part of the sizes array
		$attachment_meta['sizes']['full'] = 'placeholder';
		if(!empty($this->options['watermarks']) && !empty($this->options['watermarks_image']) && !empty($this->options['watermarks_sizes']) && is_array($this->options['watermarks_sizes']) && (empty($watermarks_meta['marked']) || (is_array($watermarks_meta['marked']) && !empty(array_intersect_key(array_flip($this->options['watermarks_sizes']), array_diff_key($attachment_meta['sizes'], $watermarks_meta['marked'])))))) {
			$actions['th23_upload_watermark_add'] = '<a class="th23-upload-watermark" href="" data-attachment="' . esc_attr($attachment->ID) . '" data-action="th23_upload_watermark_add" data-nonce="' . esc_attr($nonce) . '" data-wait="' . esc_attr__('Watermarking...', 'th23-upload') . '">' . esc_html__('Add watermark', 'th23-upload') . '</a>';
		}

		return $actions;

	}

	// Add link to filtered attachments in row actions on posts / pages overview
	function add_media_link($actions, $post) {
		$actions[] = '<a href="upload.php?parent=' . esc_attr($post->ID) . '">' . esc_html__('Show Media', 'th23-upload') . '</a>';
		return $actions;
	}

	// Filter attachments items to display on specified parent post / page
	function filter_apply($where) {
		global $pagenow;
		if(!empty($pagenow) && 'upload.php' === $pagenow && isset($_REQUEST['parent']) && intval($_REQUEST['parent']) >= 0) {
			global $wpdb;
			$where .= ' AND ' . $wpdb->posts . '.post_parent = ' . intval($_REQUEST['parent']);
		}
		return $where;
	}

	// Add selection dropdown for parent filter in media library
	function filter_show() {
		global $pagenow;
		if(empty($pagenow) || $pagenow !== 'upload.php') {
			return;
		}

		// get all posts / pages with attachments
		if(empty($options = wp_cache_get('th23_upload_media_options'))) {
			global $wpdb;
			$parents = $wpdb->get_results('SELECT a.ID, a.post_title, (SELECT COUNT(ID) FROM ' . $wpdb->posts . ' WHERE post_type = "attachment" AND post_parent = a.ID) AS attachment_count FROM ' . $wpdb->posts . ' a WHERE (post_type = "post" OR post_type = "page") AND (post_status = "publish" OR post_status = "draft" OR post_status = "pending") HAVING attachment_count > 0 ORDER BY a.post_title ASC');
			$options = array(
				-1 => __('All parents', 'th23-upload'),
				0 => __('(Unattached)', 'th23-upload'),
			);
			foreach($parents as $parent) {
				$options[$parent->ID] = $parent->post_title;
			}
			wp_cache_set('th23_upload_media_options', $options);
		}

		// build dropdown
		echo '<select id="filter-by-parent" name="parent">';
		$selected_id = (isset($_REQUEST['parent'])) ? intval($_REQUEST['parent']) : -1;
		foreach($options as $id => $title) {
			echo '<option value="' . esc_attr($id) . '"' . (($selected_id == $id) ? ' selected="selected"' : '') . '>' . esc_attr($title) . '</option>';
		}
		echo '</select>';

	}

	// Reset cache for selection dropdown for parent filter in media library
	function filter_cache($post_id, $post_after, $post_before) {
		// no attachment
		if(empty($post_after->post_type) || 'attachment' !== $post_after->post_type) {
			return;
		}
		// undefined = unattached (0)
		$parent_after = (!empty($post_after->post_parent)) ? $post_after->post_parent : 0;
		$parent_before = (!empty($post_before->post_parent)) ? $post_before->post_parent : 0;
		if($parent_after != $parent_before) {
			wp_cache_delete('th23_upload_media_options');
		}
	}

	// Adjust standard "Uploaded to" column in media library
	// note: requires full rebuilding of the column as default columns content can not be altered by filtering (these columns are not passed through the "manage_media_custom_column" filter)
	function media_columns($default) {
		$columns = array();
		// note: loop through and re-build array to change key and value without changing order of columns
		foreach($default as $key => $value) {
			if('parent' === $key) {
				$key = 'attached_to';
				$value = __('Attached to', 'th23-upload');
			}
			$columns[$key] = $value;
		}
		$columns['filesize'] = __('Filesize', 'th23-upload');
		return $columns;
	}
	function media_columns_sortable($columns) {
		$columns['attached_to'] = 'attached_to';
		return $columns;
	}
	function media_columns_orderby($vars) {
		if(isset($vars['orderby']) && 'attached_to' === $vars['orderby']) {
			$vars['orderby'] = 'parent';
		}
		return $vars;
	}
	function media_columns_content($column, $id) {
		if(empty($column)) {
			return;
		}
		if('attached_to' === $column) {

			$parent_id = (int) get_post_field('post_parent', $id, 'raw');
			// already attached
			if(!empty($parent_id)) {
				if(!empty($url = get_edit_post_link($parent_id))) {
					echo '<strong><a href="' . esc_url($url) . '">' . esc_html(_draft_or_post_title($parent_id)) . '</a></strong>';
				}
				else {
					echo '<strong>' . esc_html(_draft_or_post_title($parent_id)) . '</strong>';
				}
				echo '<p>' . esc_html(get_the_time(__('Y/m/d'), $parent_id)) . '</p>';
				echo '<div class="row-actions">';
				if(current_user_can('edit_post', $id)) {
					echo '<a class="hide-if-no-js" onclick="findPosts.open(\'media[]\',\'' . intval($id) . '\'); return false;" href="#the-list">' . esc_html__('Re-attach', 'th23-upload') . '</a>';
					echo ' | ';
					echo '<a href="' . esc_url('upload.php?parent_post_id=' . $parent_id . '&media[]=' . $id . '&_wpnonce=' . wp_create_nonce('bulk-media')) . '">' . esc_html__('Detach', 'th23-upload') . '</a>';
					echo ' | ';
				}
				echo '<a href="' . esc_url('upload.php?parent=' . $parent_id) . '">' . esc_html__('Show all attached', 'th23-upload') . '</a>';
				echo '</div>';
			}
			// currently un-attached
			else {
				echo esc_html__('(Unattached)', 'th23-upload');
				echo '<p>&nbsp;</p>';
				echo '<div class="row-actions">';
				if(current_user_can('edit_post', $id)) {
					echo '<a class="hide-if-no-js" onclick="findPosts.open(\'media[]\',\'' . intval($id) . '\'); return false;" href="#the-list">' . esc_html__('Attach', 'th23-upload') . '</a>';
					echo ' | ';
				}
				echo '<a href="upload.php?parent=0">' . esc_html__('Show all unattached', 'th23-upload') . '</a>';
				echo '</div>';
			}

		}
		elseif('filesize' === $column) {

			// get total filesize from cache - or check filesizes on disk
			if(empty($filesize_sum = get_post_meta($id, 'th23-upload-filesize', true))) {
				$filesize_sum = $this->filesize_disk($id);
			}
			echo '<span class="th23-upload-filesize-field">' . esc_html($filesize_sum) . '</span>';
			if(current_user_can('edit_posts')) {
				echo '<p>&nbsp;</p>';
				echo '<div class="row-actions">';
				echo '<a class="th23-upload-filesize" href="" data-action="th23_upload_filesize" data-attachment="' . esc_attr($id) . '" data-nonce="' . esc_attr(wp_create_nonce('th23-upload-nonce')) . '" data-wait="' . esc_attr__('Calculating...', 'th23-upload') . '">' . esc_html__('Refresh', 'th23-upload') . '</a>';
				if(!empty($this->options['watermarks_restore'])) {
					echo ' | ';
					echo '<a class="th23-upload-cleanup" href="" data-action="th23_upload_cleanup" data-attachment="' . esc_attr($id) . '" data-nonce="' . esc_attr(wp_create_nonce('th23-upload-nonce')) . '" data-wait="' . esc_attr__('Checking...', 'th23-upload') . '">' . esc_html__('Clean up', 'th23-upload') . '</a>';
				}
				echo '</div>';
			}

		}

	}

	// Invalidate filesize cache upon change of attachment meta data
	function filesize_cache($meta_id, $object_id, $meta_key) {
		if(in_array($meta_key, array('_wp_attachment_metadata', '_wp_attachment_backup_sizes', 'th23-upload-watermarks'))) {
			delete_post_meta($object_id, 'th23-upload-filesize');
		}
	}

	// Handle refresh filesize, cleanup images (AJAX)
	function ajax_filesize_cleanup($action = 'filesize') {
		// check nonce
		if(empty($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'th23-upload-nonce')) {
			$this->ajax_send_response(array('item' => esc_html__('n/a', 'th23-upload'), 'result' => 'error', 'msg' => esc_html__('Invalid request!', 'th23-upload')));
		}
		// check user permission
		if(!current_user_can('edit_posts')) {
			$this->ajax_send_response(array('item' => esc_html__('n/a', 'th23-upload'), 'result' => 'error', 'msg' => esc_html__('No permission!', 'th23-upload')));
		}
		// check attachment id
		if(empty($_POST['id']) || empty($id = (int) $_POST['id']) || empty($attachment = get_post($id)) || empty($attachment->post_type) || 'attachment' != $attachment->post_type) {
			$this->ajax_send_response(array('item' => esc_html__('n/a', 'th23-upload'), 'result' => 'error', 'msg' => esc_html__('No valid attachment!', 'th23-upload')));
		}
		$attachment_ident = $attachment->post_title . ' (' . $id . ')';
		// clean up
		if('cleanup' == $action) {
			if(empty($this->options['watermarks_restore'])) {
				$this->ajax_send_response(array('item' => esc_html($attachment_ident), 'result' => 'error', 'msg' => esc_html__('Not possible!', 'th23-upload')));
			}
			$this->cleanup($id);
			$msg = __('Done', 'th23-upload');
			$wait = __('Checking...', 'th23-upload');
			$html = __('Clean up', 'th23-upload');
		}
		else {
			$msg = __('Updated', 'th23-upload');
			$wait = __('Calculating...', 'th23-upload');
			$html = __('Refresh', 'th23-upload');
		}
		// re-assess filesize on disk
		$filesize_sum = $this->filesize_disk($id);
		// update filesizes shown in media gallery
		$this->ajax_send_response(array(
			'item' => esc_html($attachment_ident),
			'result' => 'success',
			'msg' => esc_html($msg),
			'size' => esc_html($filesize_sum),
			'wait' => esc_attr($wait),
			'html' => esc_html($html),
		));
	}

	// Check attachment filesizes on disk
	function filesize_disk($attachment_id) {
		$filesize_sum = 0;
		$attachment_meta = wp_get_attachment_metadata($attachment_id, true);
		if(!empty($filesystem = $this->filesystem()) && !empty($attachment_meta['file'])) {
			$watermark = $this->get_watermark();
			// get attachment file path and basename (excl ".EXT")
			$path = $watermark['dir_base'] . dirname($attachment_meta['file']) . '/';
			$file_name = basename($attachment_meta['file']);
			// create filename => image size array
			$sizes = array();
			if(isset($attachment_meta['sizes']) && is_array($attachment_meta['sizes'])) {
				foreach($attachment_meta['sizes'] as $size => $data) {
					if(!empty($data['file'])) {
						$sizes[$path . $data['file']] = $size;
					}
				}
			}
			// get all files with same basename in folder
			// note: this will give an accurate number for overall filesize, including "dormant" image files and backups
			if(!empty($files = glob($path . substr($file_name, 0, strrpos($file_name, '.')) . '*'))) {
				foreach($files as $file) {
					$filesize = (int) $filesystem->size($file);
					if($path . $file_name == $file) {
						$attachment_meta['filesize'] = $filesize;
					}
					elseif(!empty($sizes[$file])) {
						$attachment_meta['sizes'][$sizes[$file]]['filesize'] = $filesize;
					}
					$filesize_sum += $filesize;
				}
			}
			// update attachment meta filesizes
			wp_update_attachment_metadata($attachment_id, $attachment_meta);
		}
		// format and cache
		$filesize_sum = size_format($filesize_sum);
		update_post_meta($attachment_id, 'th23-upload-filesize', $filesize_sum);
		return $filesize_sum;
	}

	// Pass-through cleanup (AJAX)
	function ajax_cleanup() {
		$this->ajax_filesize_cleanup('cleanup');
	}

}

?>
