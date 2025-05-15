<?php

// safety
die();

// === Config: plugin information (plugin-info.php) ===

// note: key plugin information are collected from main file plugin header (see above) and thus, these fields linke "name" are empty below - however if not empty, the below specified data "overrule" any other settings

$plugin = array();

// assets_base [recommended]
// note: (external) assets base for banners, icons and screenshots on Github (readme.md) and own updater (update.json)
$plugin['assets_base'] = 'https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/';

// slug [mandatory]
$plugin['slug'] = 'th23-upload';

// name [mandatory]
// note: recommended as header "Plugin Name: th23 Specials"
$plugin['name'] = '';

// icons [optional]
// note: relative url, recommended to be combined with "assets_base"
$plugin['icons'] = array(
	'square' => 'assets/icon-128x128.png',
	'horizontal' => 'assets/icon-horizontal.png'
);

// tags [optional]
$plugin['tags'] = array('upload', 'watermark', 'image', 'resize');

// contributors [mandatory]
// note: one (main) as author is required, at least USER ("th23" in example) is required and has to be a valid username on https://profiles.wordpress.org/username which is auto-linked to the WP profile - further contributors can be added via the plugin info file
// note: recommended as header "Author: Thorsten (th23) ..."
$plugin['contributors'] = array();

// homepage [recommended]
// note: recommended as header "Plugin URI: https://github.com/th23x/th23-specials"
$plugin['homepage'] = '';

// donate_link [optional]
// note: if empty, homepage will be used instead for own updater (update.json) and WP.org (readme.txt)
$plugin['donate_link'] = '';

// support_url [optional]
// note: if empty, homepage will be used instead for own updater (update.json)
$plugin['support_url'] = '';

// license_short [mandatory]
// note: recommended as header "License: GPL-3.0"
$plugin['license_short'] = '';

// license_uri [mandatory]
// note: recommended as header "License URI: https://github.com/th23x/th23-specials/blob/main/LICENSE"
$plugin['license_uri'] = '';

// license_description [optional]
// note: if specified, used for Github (README.md) instead of short license
$plugin['license_description'] = 'You are free to use this code in your projects as per the `GNU General Public License v3.0`. References to this repository are of course very welcome in return for my work 😉';

// version [mandatory]
// note: recommended as header "Version: 6.0.1"
$plugin['version'] = '';

// last_updated [optional]
// note: if left empty (recommended), will be filled with current date/time automatically - otherwise expects timestamp in the format "2025-04-25 20:21:15"
$plugin['last_updated'] = '';

// download_link [optional]
// note: mandatory for plugins not hosted on WP.org via own updater (update.json) - note: using {VERSION} in the link will be replaced with latest version upon plugin info creation
$plugin['download_link'] = 'https://github.com/th23x/th23-upload/releases/latest/download/th23-upload-v{VERSION}.zip';

// requires [mandatory]
// note: min WP version
// note: recommended as header "Requires at least: 4.2"
$plugin['requires'] = '';

// tested [mandatory]
// note: max tested WP version
// note: recommended as header "Tested up to: 6.8"
$plugin['tested'] = '';

// requires_php [mandatory]
// note: recommended as header "Requires PHP: 8.0"
$plugin['requires_php'] = '';

// banners [recommended]
// note: sizes are "low" 772px x 250px and "high" 1544 px x 500px - relative url, recommended to be combined with "assets_base"
$plugin['banners'] = array(
	'low' => 'assets/banner-772x250.jpg',
	'high' => 'assets/banner-1544x500.jpg'
);

// summary [mandatory]
// note: max 150 characters (WP.org restriction)
// note: recommended as header "Description: Essentials to customize Wordpress via simple settings, SMTP, title highlight, category selection, more separator, sticky posts, remove clutter, ..."
$plugin['summary'] = '';

// intro [recommended]
// note: key information about the plugin, option to use markdown for structuring, highlighting, links, etc
$plugin['intro'] = 'Provides easy admin access to **define maximum width and height** of uploaded images. Uploads exceeding these dimensions will be resized accordingly. Only the defined maximum size will be stored on the server. This way you will be able to **save space and bandwidth** and serve pages faster to your visitors due to the reduced image size.

**Watermark your precious images**, keeping track of them in the internet. New uploads can automatically be marked or you can **add/remove watermarks** via the Media Library. Unmarked copies of images are kept inaccessible to the public in case you want to restore it.

[![th23 Upload - Intro video](https://img.youtube.com/vi/umfS6tGseqI/0.jpg)](https://www.youtube.com/watch?v=umfS6tGseqI)

Additionally th23 Upload offers some further options:

* Keep **original aspect ration** of uploaded images, preventing automatic cropping
* Specify a **suffix for resized images** to be added to names, eg upload of too large image `test.jpg` will be stored as `test_resized.jpg`
* Set **quality for resized images**, allowing to save further space and bandwidth
* Select image sizes to watermark, eg to excluding thumbnails
* **Mass-add/-remove watermarks** for already uploaded image attachments
* Select **location of watermark** on the image and maximum width/height to cover
* Supports **GD Library** and **ImageMagick (Imagick)**';

// screenshots [optional]
// note: relative urls, recommended to be combined with "assets_base"
$plugin['screenshots'] = array(
	1 => array('src' => 'assets/screenshot-1.jpg', 'caption' => 'Plugin settings (maximum image upload size)'),
	2 => array('src' => 'assets/screenshot-2.jpg', 'caption' => 'Media uploader'),
	3 => array('src' => 'assets/screenshot-3.jpg', 'caption' => 'Media library (add/remove watermarks)'),
	4 => array('src' => 'assets/screenshot-4.jpg', 'caption' => 'Watermarked image (bottom right corner)'),
	5 => array('src' => 'assets/screenshot-5.jpg', 'caption' => 'Watermark settings (part 1)'),
	6 => array('src' => 'assets/screenshot-6.jpg', 'caption' => 'Watermark settings (part 2)'),
);

// usage [optional]
$plugin['usage'] = 'This plugin **works in the background**, even for Admins and Editors of your website.

Once configured all work upon upload of new images happens during the normal process - images are resized and watermarked as per your configuration before they are added in the post / page editor.

See intro videos about functionality and configuration for an overview: [Visit the th23 Upload video series on Youtube](https://www.youtube.com/playlist?list=PLnpYL-vo05g3JRAJiL0xG6Dqc0HxZ1DJL).

> [!NOTE]
> **Only handles JPG / JPEG images**, as PNG (transparency) and GIF (animation) could loose their features upon resizing.!

For manually adding / removing of watermarks visit the Media Library, where you find this function as part of an images row actions, when hovering.';

// setup [optional]
$plugin['setup'] = 'For a manual installation upload extracted `th23-upload` folder to your `wp-content/plugins` directory.

The plugin is **configured via its settings page in the admin area**. Find all options under `Settings` -> `th23 Upload`. The options come with a description of the setting and its behavior directly next to the respective settings field.';

// faq [mandatory]
$plugin['faq'] = array(
	'video_settings' => array(
		'q' => '**Video tutorial**: Explaining all plugin settings in the admin area',
		'a' => '[![th23 Upload - Resize options video](https://img.youtube.com/vi/Cll7btE7udE/0.jpg)](https://www.youtube.com/watch?v=Cll7btE7udE)

[![th23 Upload - Watermark options video](https://img.youtube.com/vi/dHO0qUTx1QE/0.jpg)](https://www.youtube.com/watch?v=dHO0qUTx1QE)',
	),
	'advantage_default' => array(
		'q' => 'How does this plugin help compared to the **default scaling of uploads**?',
		'a' => 'By default WordPress limits the upload of images to **max 2560 px width / height, without providing any admin options to adjust** this setting. This plugin allows you to disable this default and replace it with a setting accessible via the admin options.

This plugin additionally allows a **customized suffix for resized images**, compared to the unchangeable default `-scaled` suffix.

Also the plugin can prevent creation of additional intermediate sizes of all uploaded images, which due to their dimensions of 1536px and 2048px would be taking up much additional space on your webserver.',
	),
	'watermark_image' => array(
		'q' => 'What is the ideal **image to use as a watermark**?',
		'a' => 'You should use a `PNG` image as watermark with transparent background. Try it out locally over various backgrounds/ images - light ones as well as darker ones.

The size depends largely on the size of your images to be marked. You can save a larger copy of the watermark PNG and limit the maximum space to cover via the plugin options. Make sure your watermark is still readable/ visible also when scaled down.',
	),
	'watermark_jpg' => array(
		'q' => 'Why can **only JPG / JPEG images** be watermarked?',
		'a' => 'Simple answer: Most images used in the internet are in the `JPG` format. Watermarking such images should meet most users needs.

Technical answer: Other image formats can contain information and have special characteristics making their handling very tricky and error-prone. For example: `PNG` files can contain transparency levels, additionally they can be animated. Similar for `GIF` images.

Honest answer: To limit complexity of the plugin ;-)',
	),
);

// changelog [mandatory]
// note: sorted by version, content can be a string or an array for a list, at least info for current version must be present
$plugin['changelog'] = array(
	'v2.0.0' => array(
		'enhancement: major review and rewrite of code, based on th23 Admin class',
		'enhancement: switch to full Open Source licence, including previously separate add-on for watermarking',
		'fix: various changes fixing minor issues and meeting latest WP coding standards',
	),
	'v1.6.0' => array(
		'enhancement: manage watermark images via plugin settings page',
		'enhancement: improved plugin settings page, add screen options and help, add units to settings fields',
		'fix: prevent installation of older Professional extension on newer Basic plugin',
		'fix: prevent requirement messages from showing multiple times',
		'fix: various small bug fixes',
		'fix: check for non-empty nonce upon AJAX requests',
	),
	'v1.4.2' => array(
		'fix: reminder about update/re-installation of Professional extension after plugin update',
	),
	'v1.4.1' => array(
		'fix: ensure all options are always captured upon setting updates, including disabled ones',
	),
	'v1.4.0' => array(
		'enhancement: add option to watermark images',
		'enhancement: allow upload of Professional extension through the admin panel',
		'enhancement: allow for hidden input fields in settings area',
		'fix: avoid empty default values upon fresh install / upgrade to Professional',
		'fix: small visual / CSS fixes',
	),
	'v1.2.0' => array(
		'enhancement: add option to watermark images',
	),
	'v1.0.0' => array(
		'first public release',
	),
);

// upgrade_notice [mandatory]
// note: sorted by version, content can be a string or an array for a list, at least info for current version must be present
$plugin['upgrade_notice'] = array(
	'v2.0.0' => 'n/a',
	'v1.6.0' => 'n/a',
	'v1.4.0' => 'n/a',
	'v1.2.0' => 'n/a',
	'v1.0.0' => 'n/a',
);

// === Do NOT edit below this line for config ===

// safety
define('ABSPATH', 'defined');

// load class, generate plugin info
require_once(__DIR__ . '/inc/th23-plugin-info-class.php');
$th23_plugin_info = new th23_plugin_info();
$th23_plugin_info->generate($plugin);

?>
