=== th23 Upload ===
Contributors: th23
Donate link: https://github.com/th23x/th23-upload
Tags: upload, watermark, image, resize
Requires at least: 4.2
Tested up to: 6.8
Stable tag: 2.1.0
Requires PHP: 8.0
License: GPL-3.0
License URI: https://github.com/th23x/th23-upload/blob/main/LICENSE


Resize images on upload to maximum dimensions, saving space and bandwidth. Watermark images upon upload or manually via Media Library


== Description ==

Provides easy admin access to **define maximum width and height** of uploaded images. Uploads exceeding these dimensions will be resized accordingly. Only the defined maximum size will be stored on the server. This way you will be able to **save space and bandwidth** and serve pages faster to your visitors due to the reduced image size.

**Watermark your precious images**, keeping track of them in the internet. New uploads can automatically be marked or you can **add/remove watermarks** via the Media Library. Unmarked copies of images are kept inaccessible to the public in case you want to restore it.

[![th23 Upload - Intro video](https://img.youtube.com/vi/umfS6tGseqI/0.jpg)](https://www.youtube.com/watch?v=umfS6tGseqI)

Additionally th23 Upload offers some further options:

* Keep **original aspect ration** of uploaded images, preventing automatic cropping
* Specify a **suffix for resized images** to be added to names, eg upload of too large image `test.jpg` will be stored as `test_resized.jpg`
* Set **quality for resized images**, allowing to save further space and bandwidth
* Select image sizes to watermark, eg to excluding thumbnails
* **Mass-add/-remove watermarks** for already uploaded image attachments
* Select **location of watermark** on the image and maximum width/height to cover
* Supports **GD Library** and **ImageMagick (Imagick)**

= Usage =

This plugin **works in the background**, even for Admins and Editors of your website.

Once configured all work upon upload of new images happens during the normal process - images are resized and watermarked as per your configuration before they are added in the post / page editor.

See intro videos about functionality and configuration for an overview: [Visit the th23 Upload video series on Youtube](https://www.youtube.com/playlist?list=PLnpYL-vo05g3JRAJiL0xG6Dqc0HxZ1DJL).

= NOTE =

**Only handles JPG / JPEG images**, as PNG (transparency) and GIF (animation) could loose their features upon resizing.!

For manually adding / removing of watermarks visit the Media Library, where you find this function as part of an images row actions, when hovering.


== Installation ==

For a manual installation upload extracted `th23-upload` folder to your `wp-content/plugins` directory.

The plugin is **configured via its settings page in the admin area**. Find all options under `Settings` -> `th23 Upload`. The options come with a description of the setting and its behavior directly next to the respective settings field.


== Frequently Asked Questions ==

= Video tutorial: Explaining all plugin settings in the admin area =

[![th23 Upload - Resize options video](https://img.youtube.com/vi/Cll7btE7udE/0.jpg)](https://www.youtube.com/watch?v=Cll7btE7udE)

[![th23 Upload - Watermark options video](https://img.youtube.com/vi/dHO0qUTx1QE/0.jpg)](https://www.youtube.com/watch?v=dHO0qUTx1QE)

= How does this plugin help compared to the default scaling of uploads? =

By default WordPress limits the upload of images to **max 2560 px width / height, without providing any admin options to adjust** this setting. This plugin allows you to disable this default and replace it with a setting accessible via the admin options.

This plugin additionally allows a **customized suffix for resized images**, compared to the unchangeable default `-scaled` suffix.

Also the plugin can prevent creation of additional intermediate sizes of all uploaded images, which due to their dimensions of 1536px and 2048px would be taking up much additional space on your webserver.

= What is the ideal image to use as a watermark? =

You should use a `PNG` image as watermark with transparent background. Try it out locally over various backgrounds/ images - light ones as well as darker ones.

The size depends largely on the size of your images to be marked. You can save a larger copy of the watermark PNG and limit the maximum space to cover via the plugin options. Make sure your watermark is still readable/ visible also when scaled down.

= Why can only JPG / JPEG images be watermarked? =

Simple answer: Most images used in the internet are in the `JPG` format. Watermarking such images should meet most users needs.

Technical answer: Other image formats can contain information and have special characteristics making their handling very tricky and error-prone. For example: `PNG` files can contain transparency levels, additionally they can be animated. Similar for `GIF` images.

Honest answer: To limit complexity of the plugin ;-)


== Screenshots ==

1. Plugin settings (maximum image upload size)
2. Media uploader
3. Media library (add/remove watermarks)
4. Watermarked image (bottom right corner)
5. Watermark settings (part 1)
6. Watermark settings (part 2)


== Changelog ==

= v2.1.0 =

* enhancement: add columns to media gallery with option to re-attach/detach and to show/cleanup filesizes (previously separate th23 Media Gallery Extension plugin, but "close" to upload functionality)
* enhancement: option to keep own backup upon upload / as soon as possible, prevent "dormant" copies of images upon usage of built-in image editor, properly use unmarked copy as input for image editor where required, restore original image
* fix: improved css styling of some admin items not separated properly on smaller screens
* fix: harmonize file paths in error messages, consistently based on WP root directory
* fix: parent filter in media gallery
* code: simplify image editor extension to maintain watermark size and position code only once
* code: simplify various data handovers to/from JS

= v2.0.0 =

* enhancement: major review and rewrite of code, based on th23 Admin class
* enhancement: switch to full Open Source licence, including previously separate add-on for watermarking
* fix: various changes fixing minor issues and meeting latest WP coding standards

= v1.6.0 =

* enhancement: manage watermark images via plugin settings page
* enhancement: improved plugin settings page, add screen options and help, add units to settings fields
* fix: prevent installation of older Professional extension on newer Basic plugin
* fix: prevent requirement messages from showing multiple times
* fix: various small bug fixes
* fix: check for non-empty nonce upon AJAX requests

= v1.4.2 =

* fix: reminder about update/re-installation of Professional extension after plugin update

= v1.4.1 =

* fix: ensure all options are always captured upon setting updates, including disabled ones

= v1.4.0 =

* enhancement: add option to watermark images
* enhancement: allow upload of Professional extension through the admin panel
* enhancement: allow for hidden input fields in settings area
* fix: avoid empty default values upon fresh install / upgrade to Professional
* fix: small visual / CSS fixes

= v1.2.0 =

* enhancement: add option to watermark images

= v1.0.0 =

* first public release


== Upgrade Notice ==

= v2.1.0 =

* introduces new setting, properly use updater

= v2.0.0 =

* n/a

= v1.6.0 =

* n/a

= v1.4.0 =

* n/a

= v1.2.0 =

* n/a

= v1.0.0 =

* n/a
