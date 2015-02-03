<?php

/**
* Plugin Name: Spam Hammer
* Author: wpspamhammer
* Author URI: http://www.wpspamhammer.com
* Description: No moderation, no captchas, no puzzles, no false positives.  Simple.
* Version: 3.9.8.7
**/

require_once ABSPATH . "wp-admin/includes/plugin.php";
require_once ABSPATH . "wp-includes/pluggable.php";

define("SPAM_HAMMER_DIR", WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "spam-hammer");

require_once SPAM_HAMMER_DIR . "/includes/functions.php";

SpamHammer::defaultOptions();
SpamHammer::defaultFilters();

if (current_user_can("administrator")) {
	if (basename($_SERVER['SCRIPT_FILENAME']) == "options.php" && $_POST['action'] == "update" && $_POST['option_page'] == "spam_hammer") {
		SpamHammer::updateOptions();
	}

	SpamHammer::adminInit();
}

if (isset($_GET['spam_hammer_script'])) {
	$headers = array(
		"X-Robots-Tag: none",
		"Content-Type: text/html",
		"Cache-Control: no-cache, no-store, must-revalidate",
		"Pragma: no-cache",
		"Expires: 0"
	);

	foreach ($headers as $header):
		header($header);
	endforeach;

	$markup = SpamHammer_Network::get("commands", "renderForm", array(
		'ip_address' => $_SERVER['REMOTE_ADDR'],
		'template' => "script"
	));

	exit(($markup && is_string($markup)) ? $markup : "");
}