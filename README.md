# <img alt="th23 Upload" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/icon-horizontal.png?v=2.1.0" width="200">

Resize images on upload to maximum dimensions, saving space and bandwidth. Watermark images upon upload or manually via Media Library


## 🚀 Introduction

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

> <img alt="" title="Plugin settings (maximum image upload size)" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/screenshot-1.jpg?v=2.1.0" width="400">
> <img alt="" title="Media uploader" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/screenshot-2.jpg?v=2.1.0" width="400">
> <img alt="" title="Media library (add/remove watermarks)" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/screenshot-3.jpg?v=2.1.0" width="400">
> <img alt="" title="Watermarked image (bottom right corner)" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/screenshot-4.jpg?v=2.1.0" width="400">
> <img alt="" title="Watermark settings (part 1)" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/screenshot-5.jpg?v=2.1.0" width="400">
> <img alt="" title="Watermark settings (part 2)" src="https://raw.githubusercontent.com/th23x/th23-upload/refs/heads/main/assets/screenshot-6.jpg?v=2.1.0" width="400">


## ⚙️ Setup

For a manual installation upload extracted `th23-upload` folder to your `wp-content/plugins` directory.

The plugin is **configured via its settings page in the admin area**. Find all options under `Settings` -> `th23 Upload`. The options come with a description of the setting and its behavior directly next to the respective settings field.


## 🖐️ Usage

This plugin **works in the background**, even for Admins and Editors of your website.

Once configured all work upon upload of new images happens during the normal process - images are resized and watermarked as per your configuration before they are added in the post / page editor.

See intro videos about functionality and configuration for an overview: [Visit the th23 Upload video series on Youtube](https://www.youtube.com/playlist?list=PLnpYL-vo05g3JRAJiL0xG6Dqc0HxZ1DJL).

> [!NOTE]
> **Only handles JPG / JPEG images**, as PNG (transparency) and GIF (animation) could loose their features upon resizing.!

For manually adding / removing of watermarks visit the Media Library, where you find this function as part of an images row actions, when hovering.


## ❓ FAQ

### Q: **Video tutorial**: Explaining all plugin settings in the admin area ###

A: [![th23 Upload - Resize options video](https://img.youtube.com/vi/Cll7btE7udE/0.jpg)](https://www.youtube.com/watch?v=Cll7btE7udE)

[![th23 Upload - Watermark options video](https://img.youtube.com/vi/dHO0qUTx1QE/0.jpg)](https://www.youtube.com/watch?v=dHO0qUTx1QE)

### Q: How does this plugin help compared to the **default scaling of uploads**? ###

A: By default WordPress limits the upload of images to **max 2560 px width / height, without providing any admin options to adjust** this setting. This plugin allows you to disable this default and replace it with a setting accessible via the admin options.

This plugin additionally allows a **customized suffix for resized images**, compared to the unchangeable default `-scaled` suffix.

Also the plugin can prevent creation of additional intermediate sizes of all uploaded images, which due to their dimensions of 1536px and 2048px would be taking up much additional space on your webserver.

### Q: What is the ideal **image to use as a watermark**? ###

A: You should use a `PNG` image as watermark with transparent background. Try it out locally over various backgrounds/ images - light ones as well as darker ones.

The size depends largely on the size of your images to be marked. You can save a larger copy of the watermark PNG and limit the maximum space to cover via the plugin options. Make sure your watermark is still readable/ visible also when scaled down.

### Q: Why can **only JPG / JPEG images** be watermarked? ###

A: Simple answer: Most images used in the internet are in the `JPG` format. Watermarking such images should meet most users needs.

Technical answer: Other image formats can contain information and have special characteristics making their handling very tricky and error-prone. For example: `PNG` files can contain transparency levels, additionally they can be animated. Similar for `GIF` images.

Honest answer: To limit complexity of the plugin ;-)


## 🤝 Contributors

Feel free to [raise issues](https://github.com/th23x/th23-upload/issues) or [contribute code](https://github.com/th23x/th23-upload/pulls) for improvements via GitHub.


## ©️ License

You are free to use this code in your projects as per the `GNU General Public License v3.0`. References to this repository are of course very welcome in return for my work 😉
