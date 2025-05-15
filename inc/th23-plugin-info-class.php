<?php
/*
th23 Plugin Info
Create and update readme.txt (WordPress.org), README.md (Github) and update.json (online updater) from one single source, simplifying your plugin development
Version: 1.0.0

Coded 2025 by Thorsten Hartmann (th23)
https://th23.net/
*/

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

class th23_plugin_info {

	public $version = '1.0.0';

	// class wide information
	private $elements = array();
	private $modes = array();
	private $fields = array();
	private $plugin = array();
	private $plugin_header = '';
	private $plugin_header_fields = array();

	public function __construct() {

		// reusable elements (simply styled)
		$this->elements = array(
			'pre' => '<pre id="{ID}" style="border: 1px solid lightgray; white-space: pre-wrap; padding: 1em; background-color: whitesmoke;">{CONTENT}</pre>',
			'notice' => '<div style="margin: 1em 0; border: 1px solid lightgrey; border-left: 5px solid {COLOR}; padding: 1em;"><h3 style="margin-top: 0;">{FIELD}</h3>{CONTENT}</div>',
		);

		// output options
		$this->modes = array(
			'json' => array('file' => 'update.json', 'title' => 'update.json (own upgrader eg th23 Admin)', 'function' => 'update_json'),
			'git' => array('file' => 'README.md', 'title' => 'README.md (github.com)', 'function' => 'readme_md'),
			'wp' => array('file' => 'readme.txt', 'title' => 'readme.txt (wordpress.org)', 'function' => 'readme_txt'),
		);

		// plugin info fields
		$this->fields = array(
			// plugin basics
			'assets_base' => array(
				'type' => 'info',
				'example' => '$plugin[\'assets_base\'] = \'https://raw.githubusercontent.com/th23x/th23-specials/refs/heads/main/\';',
				'need' => 'recommended',
				'note' => '(external) assets base for banners, icons and screenshots on Github (readme.md) and own updater (update.json)',
			),
			'slug' => array(
				'type' => 'info',
				'example' => '$plugin[\'slug\'] = \'th23-specials\';',
				'need' => 'mandatory',
				'note' => '',
			),
			'name' => array(
				'type' => 'header-00',
				'example' => 'Plugin Name: th23 Specials',
				'need' => 'mandatory',
				'note' => '',
			),
			'icons' => array(
				'type' => 'info',
				'example' => '$plugin[\'icons\'] = array(
	\'square\' => \'assets/icon-square.png\',
	\'horizontal\' => \'assets/icon-horizontal.png\'
);',
				'need' => 'optional',
				'note' => 'relative url, recommended to be combined with "assets_base"',
			),
			'tags' => array(
				'type' => 'info',
				'example' => '$plugin[\'tags\'] = array(\'essentials\', \'filter\', \'smtp\', \'header\');',
				'need' => 'optional',
				'note' => '',
			),
			// contact
			'contributors' => array(
				'type' => 'header-10',
				'example' => 'Author: Thorsten (th23)
Author URI: https://thorstenhartmann.de
Author IMG: https://thorstenhartmann.de/avatar.png',
				'need' => 'mandatory',
				'note' => 'one (main) as author is required, at least USER ("th23" in example) is required and has to be a valid username on https://profiles.wordpress.org/username which is auto-linked to the WP profile - further contributors can be added via the plugin info file',
				'add_info' => '$plugin[\'contributors\'] = array(
	\'USER\' => array(
		\'name\' => \'NAME\',
		\'url\' => \'URL\',
		\'avatar\' => \'AVATAR\',
		\'author\' => true/false
	),
	...
);',
			),
			'homepage' => array(
				'type' => 'header-30',
				'example' => 'Plugin URI: https://github.com/th23x/th23-specials',
				'need' => 'recommended',
				'note' => '',
			),
			'donate_link' => array(
				'type' => 'info',
				'example' => '$plugin[\'donate_link\'] = \'\';',
				'need' => 'optional',
				'note' => 'if empty, homepage will be used instead for own updater (update.json) and WP.org (readme.txt)',
			),
			'support_url' => array(
				'type' => 'info',
				'example' => '$plugin[\'support_url\'] = \'\';',
				'need' => 'optional',
				'note' => 'if empty, homepage will be used instead for own updater (update.json)',
			),
			// license
			'license_short' => array(
				'type' => 'header-40',
				'example' => 'License: GPL-3.0',
				'need' => 'mandatory',
				'note' => '',
			),
			'license_uri' => array(
				'type' => 'header-40',
				'example' => 'License URI: https://github.com/th23x/th23-specials/blob/main/LICENSE',
				'need' => 'mandatory',
				'note' => '',
			),
			'license_description' => array(
				'type' => 'info',
				'example' => '$plugin[\'license_description\'] = \'You are free to use this code in your projects as per the `GNU General Public License v3.0`. References to this repository are of course very welcome in return for my work 😉\';',
				'need' => 'optional',
				'note' => 'if specified, used for Github (README.md) instead of short license',
			),
			// latest version
			'version' => array(
				'type' => 'header-50',
				'example' => 'Version: 6.0.1',
				'need' => 'mandatory',
				'note' => '',
			),
			'last_updated' => array(
				'type' => 'info',
				'example' => '$plugin[\'last_updated\'] = \'\';',
				'need' => 'optional',
				'note' => 'if left empty (recommended), will be filled with current date/time automatically - otherwise expects timestamp in the format "2025-04-25 20:21:15"',
			),
			'download_link' => array(
				'type' => 'info',
				'example' => '$plugin[\'download_link\'] = \'https://github.com/th23x/th23-specials/releases/latest/download/th23-specials-v{VERSION}.zip\';',
				'need' => 'optional',
				'note' => 'mandatory for plugins not hosted on WP.org via own updater (update.json) - note: using {VERSION} in the link will be replaced with latest version upon plugin info creation',
			),
			// requirements
			'requires' => array(
				'type' => 'header-60',
				'example' => 'Requires at least: 4.2',
				'need' => 'mandatory',
				'note' => 'min WP version',
			),
			'tested' => array(
				'type' => 'header-60',
				'example' => 'Tested up to: 6.8',
				'need' => 'mandatory',
				'note' => 'max tested WP version',
			),
			'requires_php' => array(
				'type' => 'header-60',
				'example' => 'Requires PHP: 8.0',
				'need' => 'mandatory',
				'note' => '',
			),
			// header banner
			'banners' => array(
				'type' => 'info',
				'example' => '$plugin[\'banners\'] = array(
	\'low\' => \'assets/banner-772x250.jpg\',
	\'high\' => \'assets/banner-1544x500.jpg\'
);',
				'need' => 'recommended',
				'note' => 'sizes are "low" 772px x 250px and "high" 1544 px x 500px - relative url, recommended to be combined with "assets_base"',
			),
			// description
			// - description [json] = summary (mandatory) + intro (optional) + usage (optional)
			// - introduction [git] = intro (optional) + screenshots (mandatory)
			// - description [wp] = intro (optional) + usage (optional)
			'summary' => array(
				'type' => 'header-00',
				'example' => 'Description: Essentials to customize Wordpress via simple settings, SMTP, title highlight, category selection, more separator, sticky posts, remove clutter, ...',
				'need' => 'mandatory',
				'note' => 'max 150 characters (WP.org restriction)'
			),
			'intro' => array(
				'type' => 'info',
				'example' => '$plugin[\'intro\'] = \'Customize your Wordpress website even more to your needs via **simple admin settings** instead of code modifications... [from here on only excerpt of original]

**New paragraphs** are simply added with inserting double new lines into the source code. Using them make sure that the new line does NOT start indeted as some editors do, otherwise the result will also show these (unwanted) indents.

Also lists can easily be created:

* Mail sending via **SMTP server** instead of default PHP mail function - and with custom subject prefix
* **Revision and edit restrictions** for posts and pages
* **Highlighting in titles** of posts and pages - for custom styling via theme
* ...

Even code style is in between `matching apostrophy`, which will work independet of the code language, eg also for CSS selectors like `.entry-title span`.

A favourite [search engine](https://duckduckgo.com) can serve as a link example.\';',
				'need' => 'recommended',
				'note' => 'key information about the plugin, option to use markdown for structuring, highlighting, links, etc'
			),
			'screenshots' => array(
				'type' => 'info',
				'example' => '$plugin[\'screenshots\'] = array(
	1 => array(\'src\' => \'assets/screenshot-1.jpg\', \'caption\' => \'Settings section in the admin dashboard with easy to reach options\'),
	2 => array(\'src\' => \'assets/screenshot-2.jpg\', \'caption\' => \'Category selection (when limited to one per post) via radion buttons in the quick edit view\'),
	3 => array(\'src\' => \'assets/screenshot-3.jpg\', \'caption\' => \'Enforced "read more" block in the Gutenberg / block editor\')
);',
				'need' => 'optional',
				'note' => 'relative urls, recommended to be combined with "assets_base"',
			),
			'usage' => array(
				'type' => 'info',
				'example' => '$plugin[\'usage\'] = \'Simply install plugin and choose customizations required from the plugin settings page. Few options involve further actions to achieve required result - **see below and FAQ section** for more details.

For **highlighting in post / page titles**, put part to highlight in between `*matching stars*` in the editor. This part will be enclosed by `<span></span>` tags in the HTML on the frontend, allowing styling by theme via the CSS selector `.entry-title span`.

> [!NOTE]
> Some options change important core functionality of Wordpress - make sure you **properly test your website** before usage in production environment!\';',
				'need' => 'optional',
				'note' => ''
			),
			'setup' => array(
				'type' => 'info',
				'example' => '$plugin[\'setup\'] = \'For a manual installation upload extracted `th23-specials` folder to your `wp-content/plugins` directory.

The plugin is **configured via its settings page in the admin area**. Find all options under `Settings` -> `th23 Specials`. The options come with a description of the setting and its behavior directly next to the respective settings field.\';',
				'need' => 'optional',
				'note' => ''
			),

			// frequently asked questions
			'faq' => array(
				'type' => 'info',
				'example' => '$plugin[\'faq\'] = array(
	\'non_compliance\' => array(
		\'q\' => \'Is there a way to identify **existing posts / pages that do not comply** with the one category only requirement or that are missing the "read more" block / tag?\',
		\'a\' => \'Yes, there are links in the descriptions on the th23 Specials settings page, **next to the respective option** to search for "non-compliant" posts / pages.

Upon a click on this link you will see all currently non-compliant posts / pages. You can modify these by clicking on their titles, which loads them into your default editor.\',
	),
	\'no_effect\' => array(
		\'q\' => \'Some **settings seem to have no effect** - eg oEmbed features are still active depsite deactivated?\',
		\'a\' => \'This might be happening as **some options can be "overruled"** by settings by your theme. For settings that might be affected, please see the description on the settings page.

To change such settings, please **check your active theme** and adjust them there, if required.\',
	),
);',
				'need' => 'mandatory',
				'note' => '',
			),
			// changelog
			'changelog' => array(
				'type' => 'info',
				'example' => '$plugin[\'changelog\'] = array(
	\'v6.0.1\' => array(
		\'fix: update th23 Admin class to v1.6.1\',
		\'fix: typos and wording adjustments\',
	),
	\'v6.0.0\' => array(
		\'n/a: first public release\',
	),
);',
				'need' => 'mandatory',
				'note' => 'sorted by version, content can be a string or an array for a list, at least info for current version must be present',
			),
			// upgrade_notice
			'upgrade_notice' => array(
				'type' => 'info',
				'example' => '$plugin[\'upgrade_notice\'] = array(
	\'v6.0.1\' => \'n/a\',
);',
				'need' => 'mandatory',
				'note' => 'sorted by version, content can be a string or an array for a list, at least info for current version must be present',
			),
		);

	}

	// generate output
	public function generate($plugin = array()) {

		// lets do it
		$mode = (!empty($_GET['mode'])) ? (string) $_GET['mode'] : 'info';
		$file = (!empty($_GET['file'])) ? (string) $_GET['file'] : 'show';

		// require plugin information, except for info mode
		if('info' != $mode) {

			if(empty($plugin)) {
				die('Missing plugin information');
			}

			// load plugin information from header of main plugin file
			if(!empty($plugin['slug'])) {

				// load first 8k of main plugin file
				$plugin_file = dirname(__DIR__, 1) . '/' . $plugin['slug'] . '.php';
				if(!file_exists($plugin_file)) {
					die('Failed to load main plugin file');
				}
				$this->plugin_header = file_get_contents($plugin_file, false, null, 0, 8192);

				// if not specifically set in plugin-info.php, try to populate from main plugin file
				$headers = array(
					'name' => '/Plugin Name:(.*)/',
					'summary' => '/Description:(.*)/',
					'version' => '/Version:(.*)/',
					'requires' => '/Requires at least:(.*)/', // min WP version
					'tested' => '/Tested up to:(.*)/', // max tested WP version
					'requires_php' => '/Requires PHP:(.*)/',
					'license_short' => '/License:(.*)/',
					'license_uri' => '/License URI:(.*)/',
					'homepage' => '/Plugin URI:(.*)/',
				);
				foreach($headers as $header => $pattern) {
					if(empty($plugin[$header]) && 1 === preg_match($pattern, $this->plugin_header, $matches)) {
						$this->plugin_header_fields[] = $header;
						$plugin[$header] = trim($matches[1]);
					}
				}

				// merge main author from main plugin file
				if(empty($plugin['contributors']) || !is_array($plugin['contributors'])) {
					$plugin['contributors'] = array();
				}
				$headers = array(
					'name' => '/Author:(.*?)(?:\(|$)/m',
					'user' => '/Author:.*?\((.*)\)/',
					'url' => '/Author URI:(.*)/',
					'avatar' => '/Author IMG:(.*)/',
				);
				$main_author = array();
				foreach($headers as $header => $pattern) {
					if(1 === preg_match($pattern, $this->plugin_header, $matches)) {
						$main_author[$header] = trim($matches[1]);
					}
				}
				if(!empty($main_author['name'])) {
					$user = (!empty($main_author['user'])) ? $main_author['user'] : $main_author['name'];
					unset($main_author['user']);
					$main_author['author'] = true;
					if(empty($plugin['contributors'][$user])) {
						$this->plugin_header_fields[] = 'contributors';
						$plugin['contributors'][$user] = $main_author;
					}
				}

			}

			// fill dynamically
			if(empty($plugin['last_updated'])) {
				$plugin['last_updated'] = date('Y-m-d H:i:s');
			}
			if(!empty($plugin['download_link'])) {
				$plugin['download_link'] = str_replace('{VERSION}', $plugin['version'], $plugin['download_link']);
			}

		}

		// make class-wide available
		$this->plugin = $plugin;

		$html = '';

		// validate, missing, recommended
		if('check' == $mode) {
			$title = ' &bull; Validation';
			$notices = $this->validate();
			// errors
			$html .= '<h2>Errors</h2>' . "\n";
			$html .= (!empty($notices['errors']) ? implode($notices['errors']) : 'n/a') . "\n";
			// recommendations
			$html .= '<h2 style="margin-top: 2em;">Recommendations</h2>' . "\n";
			$html .= (!empty($notices['recommendations']) ? implode($notices['recommendations']) : 'n/a') . "\n";
			// options
			$html .= '<h2 style="margin-top: 2em;">Optional additions</h2>' . "\n";
			$html .= (!empty($notices['options']) ? implode($notices['options']) : 'n/a') . "\n";
		}
		// invalid combination
		elseif('all' == $mode && 'download' == $file) {
			die('Files can only be downloaded one by one');
		}
		// create readme.txt (wp), README.md (git) and/or update.json (json) if no error / not ignore
		elseif(in_array($mode, array('wp', 'git', 'json', 'all'))) {
			// check for errors, if not ignored
			if(!isset($_GET['ignore'])) {
				$notices = $this->validate();
				if(!empty($notices['errors'])) {
					$html .= '<h2>Errors</h2>' . "\n";
					$html .= (!empty($notices['errors']) ? implode($notices['errors']) : 'n/a') . "\n";
					$html .= '<a href="' . $_SERVER['REQUEST_URI'] . '&ignore">I know, what I do! Lets <strong>ignore errors</strong>...</a>';
				}
			}
			// no previous output ie no errors
			if(empty($html)) {
				$i = 0;
				foreach($this->modes as $id => $details) {
					if($id != $mode && 'all' != $mode) {
						continue;
					}
					if('download' == $file) {
						$content = call_user_func(array($this, $details['function']));
						header('Content-Description: File Transfer');
						header('Content-Type: text/plain');
						header('Content-Disposition: attachment; filename=' . $details['file']);
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: ' . strlen($content));
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Expires: 0');
						header('Pragma: public');
						echo $content;
					}
					elseif('server' == $file) {
						if('all' != $mode) {
							$title = ' &bull; ' . $details['title'];
						}
						// note: writing to server is safe as it only rewrites what is distributed anyhow with the plugin (except during development) and only uses content of this file ie does not write any external input onto the server
						if(false !== file_put_contents($details['file'], call_user_func(array($this, $details['function'])))) {
							$html .= '<p><strong style="color: green;">Done</strong>: <strong>' . $details['file'] . '</strong> created</p>' . "\n";
						}
						else {
							$html .= '<p><strong style="color: red;">Error</strong>: Failed to create <strong>' . $details['file'] . '</strong></p>' . "\n";
						}
					}
					else {
						$i++;
						$html .= '<h2 style="' . (($i > 1) ? ' margin-top: 2em;' : '') . '">' . $details['title'] . '</h2>' . "\n";
						$html .= str_replace(array('{ID}', '{CONTENT}'), array($id, htmlspecialchars(call_user_func(array($this, $details['function'])))), $this->elements['pre']) . "\n";
						$html .= '<button type="button" onClick="navigator.clipboard.writeText(document.getElementById(\'' . $id . '\').textContent);">Copy to clipboard</button>';
					}
				}
			}
		}
		// help, modes, full example
		else {
			$title = ' &bull; Example';
			$html .= $this->help();
		}

		if(!empty($html)) {
			echo '<!doctype html>' . "\n";
			echo '<html>' . "\n";
			echo '<head>' . "\n";
			echo '<meta charset="UTF-8">' . "\n";
			echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
			echo '<title>th23 Plugin Info' . (!empty($title) ? $title : '') . '</title>' . "\n";
			echo '</head>' . "\n";
			echo '<body style="margin: 2em; font-family: sans-serif;">' . "\n";
			echo $html;
			echo '</body>' . "\n";
			echo '</html>' . "\n";
		}

	}

	// validate plugin info vs fields
	private function validate() {

		// collect all
		$notices = array();

		// loop through fields
		foreach($this->fields as $field => $details) {
			if(empty($this->plugin[$field])) {
				$where = ('info' != $details['type']) ? 'header of main plugin file' : 'plugin info file';
				// missing mandatory
				if('mandatory' == $details['need']) {
					$notices[] = array('type' => 'error', 'field' => $field, 'message' => 'Missing: Add to ' . $where);
				}
				// missing recommended
				elseif('recommended' == $details['need']) {
					$notices[] = array('type' => 'warning', 'field' => $field, 'message' => 'Recommendation: Define in ' . $where);
				}
				// missing optional
				elseif('optional' == $details['need']) {
					$notices[] = array('type' => 'info', 'field' => $field, 'message' => 'Consider adding in ' . $where);
				}
			}
			// info is defined in plugin info file, but ideally should be in main plugin file header
			elseif('info' != $details['type'] && !in_array($field, $this->plugin_header_fields)) {
				$notices[] = array('type' => 'info', 'field' => $field, 'message' => 'Recommendation: Define in header of main plugin file instead');
			}
		}

		// check matching version in plugin header and $this->plugin['version'] in main plugin file
		if(!empty($this->plugin_header) && 1 === preg_match('/\$this->plugin\[ *[\'"]version[\'"] *\] *= *[\'"](.*?)[\'"];/', $this->plugin_header, $matches) && trim($matches[1]) !== $this->plugin['version']) {
			$notices[] = array('type' => 'error', 'field' => 'version', 'message' => 'Version missmatch between plugin header and $this->plugin[\'version\']', 'issue' => 'Version: ' . $this->plugin['version'] . "\n\n" . $matches[0]);
		}

		// check change log includes current version
		if(empty($this->plugin['changelog']['v' . $this->plugin['version']])) {
			$notices[] = array('type' => 'error', 'field' => 'changelog', 'message' => 'Changelog for current version missing', 'issue' => 'Version: ' . $this->plugin['version'] . "\n\n" . var_export(array_keys($this->plugin['changelog']), true));
		}

		// check upgrade notice includes current version
		if(empty($this->plugin['upgrade_notice']['v' . $this->plugin['version']])) {
			$notices[] = array('type' => 'error', 'field' => 'upgrade_notice', 'message' => 'Upgrade notice for current version missing', 'issue' => 'Version: ' . $this->plugin['version'] . "\n\n" . var_export(array_keys($this->plugin['upgrade_notice']), true));
		}

		// sort by category and prepare for output
		$notices_sorted = array('errors' => array(), 'recommendations' => array(), 'options' => array());
		foreach($notices as $notice) {
			$note = (!empty($this->fields[$notice['field']]['note'])) ? 'note: ' . $this->fields[$notice['field']]['note'] : '';
			$example = (!empty($this->fields[$notice['field']]['example'])) ? htmlspecialchars($this->fields[$notice['field']]['example']) : '';
			// show issue instead of example, if given
			$example = (!empty($notice['issue'])) ? '<span style="color:red;">' . htmlspecialchars($notice['issue']) . '</span>' : $example;
			if(!empty($example)) {
				$example = str_replace(array('{ID}', '{CONTENT}'), array('', $example), $this->elements['pre']);
			}
			if('error' == $notice['type']) {
				$notices_sorted['errors'][] = str_replace(array('{COLOR}', '{FIELD}', '{CONTENT}'), array('red', $notice['field'], $notice['message'] . $example . $note), $this->elements['notice']) . "\n";
			}
			if('warning' == $notice['type']) {
				$notices_sorted['recommendations'][] = str_replace(array('{COLOR}', '{FIELD}', '{CONTENT}'), array('orange', $notice['field'], $notice['message'] . $example . $note), $this->elements['notice']) . "\n";
			}
			if('info' == $notice['type']) {
				$notices_sorted['options'][] = str_replace(array('{COLOR}', '{FIELD}', '{CONTENT}'), array('blue', $notice['field'], $notice['message'] . $example . $note), $this->elements['notice']) . "\n";
			}
		}

		return $notices_sorted;
	}

	// add plugin version to images to prevent extensive browser caching
	private function add_v($link) {
		$link .= (str_contains($link, '?')) ? '&' : '?';
		return $link .= 'v=' . $this->plugin['version'];
	}

	// WordPress.org online updater via "plugins_api" hook, expects to receive information as pre-formatted html, thus allowing more "fancy" styling than WP.org repository - enhance markdown / convert to html
	// note: fields supporting html are: summary, intro, usage, setup, faq - answers, changelog - change, screenshots - caption, upgrade_notice - notice, license - description
	private function md2html($content, $blocks = true) {

		if($blocks) {

			// blockquotes: incl Github special boxes
			/*
			> [!NOTE/TIP/IMPORTANT/WARNING/CAUTION]
			> Highlights information that users should be aware
			*/
			$content = preg_replace_callback("/^> (\[!([A-Z]*?)\]\n|.*?)?(.*?)(\n\n|\z)/ms", function ($matches) {
				$box_type = (!empty($matches[2])) ? $matches[2] : 'DEFAULT';
				$box_header = ('DEFAULT' != $box_type) ? '<div style="color: _color_' . $box_type . '_;">_icon_' . $box_type . '_ <strong>' . $box_type . '</strong></div><p>' : '<p>' . $matches[1];
				// blockquotes: remove leading "> " for lines within box
				$box_content = preg_replace("/(^> *)/m", "", $matches[3]);
				return '<blockquote style="padding: 0 0.5em; border-left: 5px solid _color_' . $box_type . '_;">' . $box_header . $box_content . '</p></blockquote>' . $matches[4];
			}, $content);
			// blockquotes: colors and icons for Github special boxes
			$replacements = array(
				'_color_DEFAULT_' => 'rgb(150, 150, 150)',
				'_color_NOTE_' => 'rgb(9, 105, 218)',
				'_color_TIP_' => 'rgb(26, 127, 55)',
				'_color_IMPORTANT_' => 'rgb(130, 80, 223)',
				'_color_WARNING_' => 'rgb(154, 103, 0)',
				'_color_CAUTION_' => 'rgb(207, 34, 46)',
				'_icon_NOTE_' => '&#128488;&#65039;',
				'_icon_TIP_' => '&#128161;',
				'_icon_IMPORTANT_' => '&#10071;',
				'_icon_WARNING_' => '&#9888;&#65039;',
				'_icon_CAUTION_' => '&#128721;',
			);
			$content = str_replace(array_keys($replacements), $replacements, $content);

			// code blocks: replace "```language\n...```" with "<pre><code>...</code></pre>" - "s" modifier to capture "..." across lines
			$content = preg_replace_callback("/```.*?\n(.*?)(\n)?```/s", function ($matches) {
				// code blocks: replace line breaks in code block with "<br>"
				return '<pre><code>' . str_replace("\n", '<br>', $matches[1]) . '</code></pre>';
			}, $content);

			$replacements = array(

				// list elements: wrap lines starting with "* " in "li" tags - "m" modifier to capture start ("^") and end ("$") of lines
				"/^\* (.*?)$/m" => "<li>$1</li>",
				// list elements: remove remaining "\n" (new lines) between list elements
				"/<\/li>\n<li>/" => "</li><li>",
				// list elements: wrap set of "<li>...</li>" (list elements) in "ul" tags - "s" modifier to capture "..." across single (!) linebreaks
				"/(<li>.*?<\/li>)\n\n/s" => "<ul>$1</ul>\n\n",

				// linebreaks: remove "\n" at beginning, end or in between block elements
				"/(\A\n+|\n+\z)/" => "",
				"/(<\/blockquote>|<\/ul>|<\/pre>|<\/h\d>|<\/p>)\n+(<blockquote|<ul|<pre|<h\d|<p)/" => "$1$2",
				// linebreaks: start with "<p>" if beginning is not opening of a block element
				"/\A(?!<blockquote|<ul|<pre|<h\d|<p>)/" => "<p>",
				// linebreaks: end with "</p>" if ending is not closure of a block element
				"/(?<!<\/blockquote>|<\/ul>|<\/pre>|<\/h\d>|<\/p>)\z/" => "</p>",
				// linebreaks: remove new lines after closure of a block element and start new "<p>" if not directly another block element follows
				"/(<\/blockquote>|<\/ul>|<\/pre>|<\/h\d>|<\/p>)\n+(?!<blockquote|<ul|<pre|<h\d|<p)/" => "$1<p>",
				// linebreaks: remove new lines before start of a block element and close "</p>" if not directly after another block element
				"/(?!<\/blockquote>|<\/ul>|<\/pre>|<\/h\d>|<\/p>)\n+(<blockquote|<ul|<pre|<h\d|<p)/" => "</p>$1",

			);
			$content = preg_replace(array_keys($replacements), $replacements, $content);

			$replacements = array(

				// linebreaks: convert remaining "\n" to paragraph breaks and "<br>" tags
				"\n\n" => "</p><p>",
				"\n" => "<br>",

			);
			$content = str_replace(array_keys($replacements), $replacements, $content);

		}
		$replacements = array(

			// code inline: replace "`...`" with "<code>...</code>"
			"/`(.*?)`/" => "<code>$1</code>",

			// bold: replace "**...**" with "<strong>...</strong>"
			"/\*\*(.*?)\*\*/" => "<strong>$1</strong>",

			// images: ![alt text](/images/icon48.png "Logo Title Text 1")
			"/!\[(.*?)\]\((.*?)(?: \"(.*?)\")?\)/" => "<img alt=\"$1\" src=\"$2\" title=\"$3\" />",

			// links: [I'm an inline-style link with title](https://www.google.com "Google's Homepage")
			"/\[(.*?)\]\((.*?)(?: \"(.*?)\")?\)/" => "<a href=\"$2\" title=\"$3\">$1</a>",

		);
		$content = preg_replace(array_keys($replacements), $replacements, $content);

		$replacements = array(

			// other: corerct bad practice of character arrows ("->"), change to html arrow symbol
			"->" => "&#8594;",

		);
		$content = str_replace(array_keys($replacements), $replacements, $content);

		return $content;

	}

	// update.json (th23 Admin Upgrader)
	private function update_json() {

		// basis can be used with some modifications to match json expected by WP upgrader
		$json = $this->plugin;
		unset($json['assets_base']);
		$assets_base = (!empty($this->plugin['assets_base'])) ? $this->plugin['assets_base'] : '';

		// icons
		unset($json['icons']);
		if(!empty($this->plugin['icons']['square'])) {
			$json['icons'] = array('1x' => $this->add_v($assets_base . $this->plugin['icons']['square']));
		}

		// tags
		foreach($json['tags'] as $id => $tag) {
			unset($json['tags'][$id]);
			$json['tags'][$tag] = $tag;
		}

		// author / contributors
		$json['contributors'] = array();
		foreach($this->plugin['contributors'] as $user => $details) {
			$display_name = (!empty($details['name'])) ? $details['name'] : $user;
			$profile_url = 'https://profiles.wordpress.org/' . $user . '/';
			if(empty($json['author']) && !empty($details['author'])) {
				$json['author'] = '<a href="' . $profile_url . '">' . $display_name . '</a>';
				$json['author_profile'] = $profile_url;
			}
			$json['contributors'][$user] = array(
				'profile' => 'https://profiles.wordpress.org/' . $user . '/',
				'avatar' => (!empty($details['avatar'])) ? $this->add_v($details['avatar']) : '',
				'display_name' => $display_name,
			);
		}

		// support
		if(empty($json['donate_link'])) {
			$json['donate_link'] = $this->plugin['homepage'];
		}
		if(empty($json['support_url'])) {
			$json['support_url'] = $this->plugin['homepage'];
		}

		// remove copied source that requires re-formatting
		unset($json['license_short'], $json['license_uri'], $json['license_description'], $json['summary'], $json['intro'], $json['usage'], $json['setup'], $json['faq'], $json['changelog']);

		// banners
		foreach($json['banners'] as $id => $banner) {
			$json['banners'][$id] = $this->add_v($assets_base . $banner);
		}
		if(!empty($json['banners']['high'])) {
			$json['banners']['2x'] = $json['banners']['high'];
		}
		if(!empty($json['banners']['low'])) {
			$json['banners']['1x'] = $json['banners']['low'];
		}

		// description - summary, intro, usage
		$json['sections'] = array();
		$description = $this->plugin['summary'];
		if(!empty($this->plugin['intro'])) {
			$description .= "\n\n" . $this->plugin['intro'];
		}
		if(!empty($this->plugin['usage'])) {
			$description .= "\n\n" . '<h3>Usage</h3>' . "\n\n" . $this->plugin['usage'];
		}
		$json['sections']['description'] = $this->md2html($description);

		// installation - setup
		if(!empty($this->plugin['setup'])) {
			$json['sections']['installation'] = $this->md2html($this->plugin['setup']);
		}

		// faq
		$faq = '';
		foreach($this->plugin['faq'] as $id => $qa) {
			$faq .= '<h4>' . $this->md2html($qa['q'], false) . '</h4>';
			$faq .= '<div>' . $this->md2html($qa['a']) . '</div>';
		}
		$json['sections']['faq'] = $faq;

		// changelog
		$changelog = '';
		foreach($this->plugin['changelog'] as $version => $changes) {
			$changelog .= '<h4>' . $version . '</h4><ul>';
			$changes = (is_array($changes)) ? $changes : array($changes);
			foreach($changes as $change) {
				$changelog .= '<li>' . $this->md2html($change) . '</li>';
			}
			$changelog .= '</ul>';
		}
		$json['sections']['changelog'] = $changelog;

		// screenshots
		$screenshots = '<ol>';
		foreach($this->plugin['screenshots'] as $num => $screenshot) {
			if(!empty($screenshot['src'])) {
				$screenshots .= '<li>';
				$src = $this->add_v($assets_base . $screenshot['src']);
				$caption = (!empty($screenshot['caption'])) ? $this->md2html($screenshot['caption'], false) : '';
				$screenshots .= '<a href="' . $src . '"><img src="' . $src . '" alt="' . $caption . '"></a>';
				if(!empty($caption)) {
					$screenshots .= '<p>' . $caption . '</p>';
				}
				$screenshots .= '</li>';
				// note: update assets base and html format caption main json array
				$json['screenshots'][$num] = array(
					'src' => $src,
					'caption' => $caption,
				);
			}
		}
		$json['sections']['screenshots'] = $screenshots . '</ol>';

		// upgrade notice
		foreach($json['upgrade_notice'] as $version => $notices) {
			$notices = (is_array($notices)) ? $notices : array($notices);
			$notices_html = '';
			foreach($notices as $notice) {
				$notices_html = '<li>' . $this->md2html($notice) . '</li>';
			}
			if(!empty($notices_html)) {
				$notices_html = '<ul>' . $notices_html . '</ul>';
			}
			$json['upgrade_notice'][$version] = $notices_html;
		}

		// dev: in case of errors, consider "JSON_UNESCAPED_UNICODE" and "JSON_UNESCAPED_LINE_TERMINATORS" constants as well
		return json_encode($json, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

	}

	// README.md (github.com)
	private function readme_md() {
		$assets_base = (!empty($this->plugin['assets_base'])) ? $this->plugin['assets_base'] : '';
		$git = '';
		// headline
		$git .= '# ' . ((!empty($this->plugin['icons']['horizontal'])) ? '<img alt="' . $this->plugin['name'] . '" src="' . $this->add_v($assets_base . $this->plugin['icons']['horizontal']) . '" width="200">' : $this->plugin['name']) . "\n\n";
		$git .= $this->plugin['summary'] . "\n\n\n";

		// intro
		if(!empty($this->plugin['intro'])) {
			$git .= '## 🚀 Introduction' . "\n\n";
			$git .= $this->plugin['intro'] . "\n\n";
		}
		foreach($this->plugin['screenshots'] as $screenshot) {
			if(!empty($screenshot['src'])) {
				$caption = (!empty($screenshot['caption'])) ? $screenshot['caption'] : '';
				$git .= '> <img alt="" title="' . $caption . '" src="' . $this->add_v($assets_base . $screenshot['src']) . '" width="400">' . "\n";
			}
		}
		$git .= "\n\n";
		// setup
		if(!empty($this->plugin['setup'])) {
			$git .= '## ⚙️ Setup' . "\n\n";
			$git .= $this->plugin['setup'] . "\n\n\n";
		}
		// usage
		if(!empty($this->plugin['usage'])) {
			$git .= '## 🖐️ Usage' . "\n\n";
			$git .= $this->plugin['usage'] . "\n\n\n";
			}
		// faq
		$git .= '## ❓ FAQ' . "\n\n";
		foreach($this->plugin['faq'] as $id => $qa) {
			$git .= '### Q: ' . $qa['q'] . ' ###' . "\n\n";
			$git .= 'A: ' . $qa['a'] . "\n\n";
		}
		$git .= "\n";
		// support
		$git .= '## 🤝 Contributors' . "\n\n";
		if(0 === strpos($this->plugin['homepage'], 'https://github.com/')) {
			$git .= 'Feel free to [raise issues](' . $this->plugin['homepage'] . '/issues) or [contribute code](' . $this->plugin['homepage'] . '/pulls) for improvements via GitHub.' . "\n\n\n";
		}
		else {
			$git .= 'Feel free to raise issues or contribute code for improvements via [the homepage](' . $this->plugin['homepage'] . ').' . "\n\n\n";
		}
		// license
		$git .= '## ©️ License' . "\n\n";
		$git .= ((!empty($this->plugin['license_description'])) ? $this->plugin['license_description'] : $this->plugin['license_short']) . "\n";
		return $git;
	}

	// remove additional markdown supported by Github, see https://docs.github.com/de/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax#alerts
	private function simple_md($content) {
		$replacements = array(
			// replace notice boxes "> [!{NOTE/TIP/IMPORTANT/WARNING/CAUTION}]" by respective headlines "= {...} ="
			"/^> \[!([A-Z]*?)\]/m" => "= $1 =\n",
			// remove blockquotes (also used as bottom part of notice boxes) ie remove "> " when on line start, replace lines only containing ">" by an empty line
			"/^> /m" => "",
			"/^>$/m" => "",
		);
		return preg_replace(array_keys($replacements), $replacements, $content);
	}

	// readme.txt (wordpress.org)
	private function readme_txt() {
		$wp = '';
		// basics (mandatory, except "donate_link")
		$wp .= '=== ' . $this->plugin['name'] . ' ===' . "\n";
		$wp .= 'Contributors: ' . implode(', ', array_keys($this->plugin['contributors'])) . "\n";
		$wp .= 'Donate link: ' . ((!empty($this->plugin['donate_link'])) ? $this->plugin['donate_link'] : $this->plugin['homepage']) . "\n";
		$wp .= 'Tags: ' . implode(', ', $this->plugin['tags']) . "\n";
		$wp .= 'Requires at least: ' . $this->plugin['requires'] . "\n";
		$wp .= 'Tested up to: ' . $this->plugin['tested'] . "\n";
		$wp .= 'Stable tag: ' . $this->plugin['version'] . "\n";
		$wp .= 'Requires PHP: ' . $this->plugin['requires_php'] . "\n";
		$wp .= 'License: ' . $this->plugin['license_short'] . "\n";
		$wp .= 'License URI: ' . $this->plugin['license_uri'] . "\n\n\n";
		// short description (mandatory)
		$wp .= $this->plugin['summary'] . "\n\n\n";
		// intro (optional) / usage (optional)
		if(!empty($this->plugin['intro']) || !empty($this->plugin['usage'])) {
			$wp .= '== Description ==' . "\n\n";
		}
		if(!empty($this->plugin['intro'])) {
			$wp .= $this->simple_md($this->plugin['intro']) . "\n\n";
		}
		if(!empty($this->plugin['intro']) && !empty($this->plugin['usage'])) {
			$wp .= '= Usage =' . "\n\n";
		}
		if(!empty($this->plugin['usage'])) {
			$wp .= $this->simple_md($this->plugin['usage']) . "\n\n";
		}
		if(!empty($this->plugin['intro']) || !empty($this->plugin['usage'])) {
			$wp .= "\n";
		}
		// setup / config (optional)
		if(!empty($this->plugin['setup'])) {
			$wp .= '== Installation ==' . "\n\n";
			$wp .= $this->simple_md($this->plugin['setup']) . "\n\n\n";
		}
		// faq (mandatory)
		$wp .= '== Frequently Asked Questions ==' . "\n\n";
		foreach($this->plugin['faq'] as $id => $qa) {
			$wp .= '= ' . preg_replace("/\*\*(.*?)\*\*/", "$1", $qa['q']) . ' =' . "\n\n";
			$wp .= $this->simple_md($qa['a']) . "\n\n";
		}
		$wp .= "\n";
		// screenshots (mandatory)
		$wp .= '== Screenshots ==' . "\n\n";
		$i = 0;
		foreach($this->plugin['screenshots'] as $screenshot) {
			if(!empty($screenshot['src'])) {
				$i++;
				$wp .= $i . '. ' . ((!empty($screenshot['caption'])) ? $screenshot['caption'] : 'n/a') . "\n";
			}
		}
		$wp .= "\n\n";
		// changelog (mandatory)
		$wp .= '== Changelog ==' . "\n";
		foreach($this->plugin['changelog'] as $version => $changes) {
			$wp .= "\n" . '= ' . $version . ' =' . "\n\n";
			$changes = (is_array($changes)) ? $changes : array($changes);
			foreach($changes as $change) {
				$wp .= '* ' . $this->simple_md($change) . "\n";
			}
		}
		$wp .= "\n\n";
		// upgrade notice (mandatory)
		$wp .= '== Upgrade Notice ==' . "\n";
		foreach($this->plugin['upgrade_notice'] as $version => $notices) {
			$wp .= "\n" . '= ' . $version . ' =' . "\n\n";
			$notices = (is_array($notices)) ? $notices : array($notices);
			foreach($notices as $notice) {
				$wp .= '* ' . $this->simple_md($notice) . "\n";
			}
		}
		return $wp;
	}

	// help / example
	private function help() {

		// intro / modes
		$html = '<h2>Usage</h2>' . "\n";
		$content = 'mode:
	[empty] / info (help, modes, full example)
	check (validate, missing, recommended)
	wp (create readme.txt, if no error / not ignore)
	git (create README.md, if no error / not ignore)
	json (create update.json, if no error / not ignore)
	all (create all, if no error / not ignore)
ignore:
	[defined] (ignore any errors, just create)
file:
	[empty] / show (create by showing in browser)
	server (create by writing to files)
	download (create by downloading as files)';
		$html .= str_replace(array('{ID}', '{CONTENT}'), array('usage', htmlspecialchars($content)), $this->elements['pre']) . "\n";
		$html .= '<div style="margin: 1em 0;">note: ensure to "re-lock" file again before production release, see "// safety" line in plugin-info.php</div>' . "\n";
		$html .= '<a href="' . $_SERVER['SCRIPT_URI'] . '?mode=check">Ok, lets <strong>check existing plugin info</strong>...</a>';

		// main plugin header
		$html .= '<h2 style="margin-top: 2em;">th23-specials.php - Main Plugin File Header</h2>' . "\n";
		$content = '/*' . "\n";
		// sort fields by order "type" column
		$fields_sorted = $this->fields;
		uasort($fields_sorted, function($a, $b, $f = 'type') { return strcmp($a[$f], $b[$f]); });
		foreach($fields_sorted as $field => $details) {
			if('info' != $details['type']) {
				if(!empty($type) && $details['type'] != $type) {
					$content .= "\n";
				}
				$content .= $details['example'] . "\n";
				$type = $details['type'];
			}
		}
		$content .= "\n";
		$content .= 'Text Domain: th23-specials' . "\n";
		$content .= 'Domain Path: /lang' . "\n";
		$content .= '*/';
		$html .= str_replace(array('{ID}', '{CONTENT}'), array('header', htmlspecialchars($content)), $this->elements['pre']) . "\n";
		$html .= '<button type="button" onClick="navigator.clipboard.writeText(document.getElementById(\'header\').textContent);">Copy to clipboard</button>';
		$html .= '<div style="margin: 1em 0;">note: "Text Domain" and "Domain Path" are independed from plugin info, but your main plugin file header might contain these and other info as well - see core WP documentation</div>' . "\n";

		// plugin info file
		$html .= '<h2 style="margin-top: 2em;">plugin-info.php - Additional Plugin Information</h2>' . "\n";
		$content = '';
		$last_field = array_key_last($this->fields);
		foreach($this->fields as $field => $details) {
			$content .= '// ' . $field . ' [' . $details['need'] . ']' . "\n";
			if(!empty($details['note'])) {
				$content .= '// note: ' . $details['note'] . "\n";
			}
			if('info' == $details['type']) {
				$content .= $details['example'] . "\n";
			}
			else {
				// fields recommended in main plugin header are included empty, but with a reference note
				$content .= '// note: recommended as header "' . ((false === strpos($details['example'], "\n")) ? $details['example'] : strstr($details['example'], "\n", true) . ' ...') . '"' . "\n";
				if(empty($details['add_info'])) {
					$content .= '$plugin[\'' . $field . '\'] = \'\';' . "\n";
				}
				else {
					$content .= $details['add_info'] . "\n";
				}
			}
			if($field != $last_field) {
				$content .= "\n";
			}
		}
		$html .= str_replace(array('{ID}', '{CONTENT}'), array('info', htmlspecialchars($content)), $this->elements['pre']) . "\n";
		$html .= '<button type="button" onClick="navigator.clipboard.writeText(document.getElementById(\'info\').textContent);">Copy to clipboard</button>';
		$html .= '<div style="margin: 1em 0;">note: plugin-info.php file needs to be placed in main plugin folder - see plugin-info-example.php for full code and where to insert $plugin array lines</div>' . "\n";

		return $html;
	}

}

?>
