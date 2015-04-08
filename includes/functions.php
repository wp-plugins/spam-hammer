<?php

class SpamHammer {
	const VERSION = "4.1.1";

	static $servers = array(
		'production' => array(
			'label' => "Live (Production)",
			'value' => "services.wpspamhammer.com"
		),
		'testing' => array(
			'label' => "Beta (Testing)",
			'value' => "test-services.wpspamhammer.com"
		)
	);

	static $options = array(
		"server",
		"auth_token",
		"version",
		"nuke_comments",
		"retroactive_ping_status",
		"default_policy",
		"selectors"
	);

	static $process_form = null;

	static function default_filters() {
		load_plugin_textdomain('spam-hammer', false, 'spam-hammer/languages');

		if (!current_user_can("administrator")) {
			add_filter("init", array(__CLASS__, "init"));
			add_filter("wp_head", array(__CLASS__, "wp_head"));
			add_filter('pre_comment_approved', array(__CLASS__, 'pre_comment_approved'));
			add_filter("wp_footer", array(__CLASS__, "wp_footer"));
		} else {
			add_filter('plugin_action_links', array(__CLASS__, "plugin_action_links"), 10, 2);

			add_action('admin_init', array(__CLASS__, 'admin_init'));
			add_action('admin_head', array(__CLASS__, 'admin_head'));
			add_action('admin_menu', array(__CLASS__, 'admin_menu'));

			if (pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) == "options.php" && $_POST['action'] == "update" && $_POST['option_page'] == "spam_hammer") {
				self::update_remote_settings();
			}
		}
	}

	static function default_options() {
		add_filter("default_option_spam_hammer_server", array(__CLASS__, "default_server"));
		add_filter("default_option_spam_hammer_auth_token", array(__CLASS__, "default_auth_token"));
		add_filter("default_option_spam_hammer_version", array(__CLASS__, "default_version"));
		add_filter("default_option_spam_hammer_selectors", array(__CLASS__, "default_selectors"));
		add_filter("default_option_spam_hammer_nuke_comments", array(__CLASS__, "default_option_true"));
		add_filter("default_option_spam_hammer_default_policy", array(__CLASS__, "default_option_false"));
		add_filter("default_option_spam_hammer_retroactive_ping_status", array(__CLASS__, "default_option_false"));

		if (current_user_can("administrator")):
			foreach (self::$options as $option):
				$key = "spam_hammer_{$option}";

				if (get_option($key, "@unset") === "@unset"):
					add_option($key, get_option($key));
				endif;

				register_setting("spam_hammer", $key);
			endforeach;
		endif;
	}

	static function default_server($default = null) {
		if ($default):
			return $default;
		endif;

		return self::$servers['production']['value'];
	}

	static function default_auth_token($default = null) {
		if ($default):
			return $default;
		endif;

		return ''; // __AUTH_TOKEN__
	}

	static function default_version($default = null) {
		if ($default):
			return $default;
		endif;

		return self::VERSION;
	}

	static function default_selectors($default = null) {
		if ($default):
			return $default;
		endif;

		return null;
	}

	static function default_option_true($default = null) {
		if ($default):
			return $default;
		endif;

		return true;
	}

	static function default_option_false($default = null) {
		if ($default):
			return $default;
		endif;

		return false;
	}

	static function plugin_action_links($actions, $plugin_file) {
		if (plugin_basename(SPAM_HAMMER_DIR . DIRECTORY_SEPARATOR . "index.php") == plugin_basename($plugin_file)):
			foreach (array("http://www.wpspamhammer.com" => __('Website', 'spam-hammer'), admin_url("admin.php?page=" . str_replace("-", "_", basename(SPAM_HAMMER_DIR))) => __('Settings', 'spam-hammer')) as $key => $value):
				$actions[] = sprintf('<a href="%1$s">%2$s</a>', $key, $value);
			endforeach;

			return $actions;
		endif;

		return $actions;
	}

	static function init() {
		if (strcasecmp($_SERVER['REQUEST_METHOD'], "POST") !== 0):
			return true;
		endif;

		$selectors = get_option("spam_hammer_selectors");

		if (!($definitions = preg_split("/\s*,\s*/", $selectors['scripts']))):
			return true;
		endif;

		$sources = array(
			"request" => $_REQUEST,
			"post" => $_POST,
			"get" => $_GET
		);

		foreach ($definitions as $definition):
			@list($script, $params) = explode(" ", $definition);

			if ($script == pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) || ($script == "*" && $params)):
				if ($params):
					parse_str($params, $variables);

					foreach ($variables as $key => $value):
						reset($sources);
						$source = key($sources);
						$name = $key;

						if (strpos($key, ":") !== false):
							@list($source, $name) = explode(":", $key);

							if (!in_array($source, array_keys($sources))):
								$source = key($sources);
							endif;
						endif;

						if (!isset($sources[$source][$name]) || $sources[$source][$name] != $value):
							continue 2;
						endif;
					endforeach;
				endif;

				return self::$process_form = self::process_form();
			endif;
		endforeach;

		return true;
	}

	static function wp_head() {
		$phrases = array(
			'userAgent' => __('Your User Agent Is Missing Or Invalid', 'spam-hammer'),
			'domain' => __('Your Domain Is Missing Or Invalid', 'spam-hammer'),
			'authToken' => __('Your Authentication Token Is Missing Or Invalid', 'spam-hammer'),
			'network' => __("There is suspicious activity on your network and you can only continue with the webmaster's assistance -- contact him or her immediately.", 'spam-hammer'),
			'ip' => __("There is suspicious activity on your ip and you can only continue with the webmaster's assistance -- contact him or her immediately.", 'spam-hammer'),
			'country' => __("There is suspicious activity from your country and you can only continue with the webmaster's assistance -- contact him or her immediately.", 'spam-hammer')
		);

		$selectors = get_option("spam_hammer_selectors");

		printf('<script type="text/javascript">window.$pam_hammer = %1$s;</script>', json_encode(array("Options" => array("Server" => get_option("spam_hammer_server"), "Domain" => parse_url(get_bloginfo("url"), PHP_URL_HOST), "Forms" => $selectors['forms']), "Statuses" => array("New" => 0, "Request" => 1, "Response" => 2), 'Phrases' => $phrases)));
		printf('<script type="text/javascript" async defer src="//%1$s/js/client/request.min.js"></script>', get_option("spam_hammer_server"));
	}

	static function wp_footer() {
		printf('<noscript style="position: fixed; bottom: 0; right: 0; font-size: x-small; color: red;">%1$s</noscript>', __('You must enable JavaScript to submit forms on this website.', 'spam-hammer'));
	}

	static function admin_init() {
		global $wpdb;

		if (($plugins = self::get_plugins()) != false):
			foreach (array("spammers-suck") as $plugin):
				if (in_array($plugin, array_keys($plugins)) && is_plugin_active("{$plugin}/{$plugins[$plugin]['Script']}")):
					deactivate_plugins("{$plugin}/{$plugins[$plugin]['Script']}");
				endif;
			endforeach;
		endif;

		if (get_option("spam_hammer_version") != self::VERSION):
			update_option("spam_hammer_version", self::VERSION);
		endif;

		if (!get_option("spam_hammer_retroactive_ping_status") && get_option("default_ping_status") == "open"):
			update_option("default_ping_status", "closed");
			update_option("spam_hammer_retroactive_ping_status", true);
		endif;

		if (!get_option("spam_hammer_auth_token")):
			SpamHammer_Network::get("subscriptions", "settings");
			$wpdb->query("UPDATE {$wpdb->posts} SET ping_status = 'closed' WHERE post_type IN ('post', 'page')");
		endif;

		if (!($selectors = get_option("spam_hammer_selectors")) || !$selectors['pull'] || $selectors['pull'] <= strtotime("-1 hour")):
			self::selectors();
		endif;

		return true;
	}

	static function selectors() {
		global $wpdb;

		$wpdb->query("UPDATE {$wpdb->posts} SET ping_status = 'closed' WHERE post_type IN ('post', 'page')");

		if (!($selectors = SpamHammer_Network::get("subscriptions", "selectors"))):
			return false;
		endif;

		return update_option("spam_hammer_selectors", $selectors);
	}

	static function admin_head() {
		$settings = pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_BASENAME) == "admin.php" && $_GET['page'] == "spam_hammer";

		$data = array(
			get_option("spam_hammer_server"),
			get_option("spam_hammer_auth_token"),
			get_option("timezone_string"),
			"wordpress",
			get_option("admin_email"),
			str_replace("_", "-", get_locale())
		);

		wp_enqueue_script("jquery");

		$tags = array(
			'<script async defer src="//%1$s/js/admin/head.min.js" id="spam-hammer-wp-admin-head" data-spam-hammer-server="%1$s" data-spam-hammer-auth-token="%2$s" data-spam-hammer-timezone-string="%3$s" data-spam-hammer-platform="%4$s" data-spam-hammer-admin="%5$s" data-spam-hammer-locale="%6$s"></script>',
			$settings ? '<script async defer src="//%1$s/js/admin/settings.min.js"></script>' : ""
		);

		foreach (array_filter($tags) as $tag):
			vprintf($tag, $data);
		endforeach;
	}

	static function update_remote_settings() {
		SpamHammer_Network::get("subscriptions", "settings", array('remote-settings' => $_POST['remote-settings']));
	}

	static function pre_comment_approved($approved, &$comment_data = null) {
		if (self::$process_form === null):
			return $approved;
		endif;

		if (!self::$process_form) {
			if (!get_option("spam_hammer_nuke_comments")):
				$approved = 'spam';
			else:
				wp_die(__("There is suspicious activity on your network and commenting can only complete with the webmaster's assistance -- contact him or her immediately.", 'spam-hammer'));
			endif;
		}

		return $approved;
	}

	static function process_form($params = array()) {
		$defaults = array(
			'client_token' => isset($_POST['spam_hammer']['client_token']) ? $_POST['spam_hammer']['client_token'] : ""
		);

		$params += $defaults;

		if (!$params['client_token'] || !($process = SpamHammer_Network::get("commands", "process_form", $params)) || is_array($process)):
			wp_die(__('You must enable JavaScript to submit forms on this website.', 'spam-hammer'));
		endif;

		return true;
	}

	static function admin_menu() {
		global $wp_version;

		add_menu_page(__('Spam Hammer', 'spam-hammer'), __('Spam Hammer', 'spam-hammer'), 'manage_options', 'spam_hammer', array(__CLASS__, 'admin_options_page'), "http://" . get_option("spam_hammer_server", "services.wpspamhammer.com") . "/img/dashicon.png", 3);
	}

	static function admin_options_page() {
		if (!($settings = SpamHammer_Network::get("subscriptions", "settings")) || !is_string($settings)) {
			$settings = print_r($settings, true);
		}

		$servers = array();

		foreach (self::$servers as $type => $server):
			if ($type == "testing" && (!defined("WP_DEBUG") || !WP_DEBUG)):
				continue;
			endif;

			$servers[] = $server;
		endforeach;

		$input_fields = array(
			self::template('wp-admin/settings_form/raw', array(
				'key' => 'spam_hammer_settings',
				'name' => __('Cloud Settings', 'spam-hammer'),
				'markup' => $settings
			)),
			self::template('wp-admin/settings_form/radio', array(
				'options' => $servers,
				'key' => 'spam_hammer_server',
				'name' => __('Software Version', 'spam-hammer'),
				'value' => get_option('spam_hammer_server')
			)),
			self::template('wp-admin/settings_form/text', array(
				'key' => 'spam_hammer_auth_token',
				'name' => __('Authentication Token', 'spam-hammer'),
				'value' => esc_attr(get_option('spam_hammer_auth_token')),
				'description' => implode('<br />', array(
					implode('&nbsp;&nbsp;', array(
						__('Authentication token for your account.', 'spam-hammer'),
					))
				))
			)),
			self::template('wp-admin/settings_form/radio', array(
				'options' => array(
					array('label' => __('Yes', 'spam-hammer'), 'value' => true),
					array('label' => __('No', 'spam-hammer'), 'value' => false)
				),

				'key' => 'spam_hammer_nuke_comments',
				'name' => __('Nuke Comments', 'spam-hammer'),
				'value' => get_option('spam_hammer_nuke_comments'),
				'description' => __('Whether or not to delete comments instead of sending them to the Spam folder.', 'spam-hammer')
			)),
			self::template('wp-admin/settings_form/radio', array(
				'options' => array(
					array('label' => __('MODERATE', 'spam-hammer'), 'value' => true),
					array('label' => __('DROP', 'spam-hammer'), 'value' => false)
				),

				'key' => 'spam_hammer_default_policy',
				'name' => __('Default Policy', 'spam-hammer'),
				'value' => get_option('spam_hammer_default_policy'),
				'description' => __('How to treat comments if the Spam Hammer network becomes unreachable.', 'spam-hammer')
			))
		);

		$hidden_fields = array(
			'<input type="hidden" name="option_page" value="spam_hammer" />',
			'<input type="hidden" name="action" value="update" />',
			wp_nonce_field('spam_hammer-options', '_wpnonce', true, false)
		);

		echo self::template('wp-admin/settings_form/form', array(	
			'input_fields' => implode("\n", $input_fields),
			'hidden_fields' => implode("\n", $hidden_fields),

			'title' => __('Spam Hammer Settings', 'spam-hammer'),
			'icon' => 'icon-users',
			'submit' => __('Save Changes', 'spam-hammer')
		));
	}

	static function template($name, $data = array()) {
		if (!$name || !file_exists(($template__ = SPAM_HAMMER_DIR . "/templates/{$name}.html.php"))) {
			return false;
		}

		if (!empty($data)) {
			extract($data, EXTR_OVERWRITE);
		}

		ob_start();
		include $template__;
		return ob_get_clean();
	}

	static function get_plugins() {
		if (!($defaults = get_plugins())):
			return false;
		endif;

		foreach ($defaults as $key => $value):
			if (strpos($key, DIRECTORY_SEPARATOR) !== false):
				list($plugin, $Script) = explode(DIRECTORY_SEPARATOR, $key, 2);
				$plugins[$plugin] = $value + compact("Script");
			else:
				$plugins[$key] = $value;
			endif;
		endforeach;

		return $plugins;
	}
}

class SpamHammer_Network {
	static $functions = array(
		'SpamHammer_Proxy' => array(
			"set_auth_token",
			"error"
		),

		"wp_mail"
	);

	static function getRemoteAddr($src_ip = null) {
		if (!$src_ip):
			$src_ip = $_SERVER["REMOTE_ADDR"];
		endif;

		if (filter_var($src_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)):
			return $src_ip;
		endif;

		$dst_ip = "";

		foreach (array("HTTP_X_FORWARDED_FOR", "HTTP_X_REAL_IP", "HTTP_CLIENT_IP", "HTTP_FROM") as $header):
			if (!$dst_ip && isset($_SERVER[$header]) && !empty($_SERVER[$header]) && preg_match_all('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $_SERVER[$header], $matches)):
				foreach ($matches[0] as $match):
					if (filter_var($match, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)):
						$dst_ip = $match;
						break;
					endif;
				endforeach;
			endif;
		endforeach;

		return $dst_ip ? $dst_ip : $src_ip;
	}

	static function functions($key = null, $value = null) {
		if (!isset(self::$functions)) {
			self::$functions = array();
		}

		if ($key === null) {
			return self::$functions;
		}

		if ($value !== null) {
			return self::$functions[$key] = $value;
		}

		return isset(self::$functions[$key]) ? self::$functions[$key] : false;
	}

	static function get($controller, $action, $params = array()) {
		$server = get_option("spam_hammer_server");

		$headers = array(
			sprintf('Date: %1$s', date("D, M d Y H:i:s T")),
			sprintf('Accept: %1$s', "application/json"),
			sprintf('Accept-Language: %1$s', str_replace("_", "-", get_locale())),
			sprintf('Accept-Charset: %1$s', get_bloginfo("charset")),
			sprintf('X-Forwarded-For: %1$s', self::getRemoteAddr()),

			sprintf('X-WordPress-Version: %1$s', get_bloginfo("version")),
			sprintf('X-Spam-Hammer-Version: %1$s', SpamHammer::VERSION),
			sprintf('X-Spam-Hammer-Auth-Token: %1$s', get_option("spam_hammer_auth_token")),
			sprintf('X-Spam-Hammer-Url: %1$s', get_bloginfo("url")),

			sprintf('X-Spam-Hammer-Referer-Id: %1$s', "")
		);

		$exec = false;

		if (function_exists("curl_init") && ($ch = curl_init()) != false) {
			curl_setopt_array($ch, array(
				CURLOPT_URL => "http://{$server}/{$controller}/{$action}",
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => http_build_query($params),
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_RETURNTRANSFER => true
			));

			if (($exec = @curl_exec($ch)) !== false):
				$response = @json_decode($exec, true);
			endif;

			curl_close($ch);
		}

		if (!$exec && ini_get("allow_url_fopen")) {
			$opts = array('http' => array(
				'method' => "POST",
				'timeout' => 30,
				'header' => implode("\r\n", $headers),
				'content' => http_build_query($params)
			));

			if (($exec = @file_get_contents("http://{$server}/{$controller}/{$action}", false, stream_context_create($opts))) != false):
				$response = @json_decode($exec, true);
			endif;
		}

		if (!$exec):
			return false;
		endif;

		if (is_array($response)) {
			if (isset($response['executions']) && !empty($response['executions'])) {
				foreach ($response['executions'] as $execution) {
					$execution += array('class' => null, 'function' => null);

					if ((!$execution['class'] && !$execution['function']) || !$execution['function']) {
						continue;
					}

					if ($execution['class'] && $execution['function'] && ($function = array($execution['class'], $execution['function'])) != false) {
						if (!in_array($execution['function'], self::functions($execution['class']))) {
							continue;
						}
					}

					if (!$execution['class'] && ($function = strtolower($execution['function'])) != false) {
						if (!in_array($execution['function'], self::functions())) {
							continue;
						}

						if ($function == "wp_mail") {
							$to = array(strtolower(get_option("admin_email")));
							$query = new WP_User_Query(array('role' => "administrator"));

							if (($admins = $query->get_results()) != false):
								foreach ($admins as $admin):
									$email = strtolower($admin->user_email);

									if (in_array($email, $to)):
										continue;
									endif;

									$to[] = $email;
								endforeach;
							endif;

							$execution['params'][0] = $to;
						}
					}

					if (isset($execution['params'])) {
						call_user_func_array($function, $execution['params']);
					} else {
						call_user_func($function);
					}
				}
			}

			if (isset($response['response']) && !empty($response['response'])) {
				return $response['code'] == 200 ? $response['response'] : false;
			}
		}

		return false;
	}
}

class SpamHammer_Proxy {
	static function set_auth_token($set_auth_token) {
		$auth_token = trim(get_option("spam_hammer_auth_token"));

		if ($auth_token && strlen($auth_token) > 1) {
			return false;
		}

		return update_option("spam_hammer_auth_token", $set_auth_token);
	}

	static function error($message = "") {
		if (!$message) {
			$message = __('You must enable JavaScript to submit forms on this website.', 'spam-hammer');
		}

		wp_die($message);
	}
}