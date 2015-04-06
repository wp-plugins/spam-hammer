<?php

/**
* Plugin Name: Spam Hammer
* Author: wpspamhammer
* Author URI: http://www.wpspamhammer.com
* Description: No moderation, no captchas, no puzzles, no false positives.  Simple.
* Version: 4.1.1
**/

require_once ABSPATH . "wp-admin/includes/plugin.php";
require_once ABSPATH . "wp-includes/pluggable.php";

define("SPAM_HAMMER_DIR", WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "spam-hammer");

require_once SPAM_HAMMER_DIR . "/includes/functions.php";

SpamHammer::default_options();
SpamHammer::default_filters();