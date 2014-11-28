<?php

class SpamHammer_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(__CLASS__, __('Spam Attack Statistics', 'spam-hammer'), array(
			'classname' => __CLASS__,
			'description' => __('Show your year-to-date spam attack statistics.', 'spam-hammer')
		));
	}

	function widget($args, $instance) {
		if (($statistics = get_option('spam_hammer_statistics')) != false):
			echo SpamHammer::template('public/widgets/stats', compact('args', 'statistics'));
		else:
			echo '';
		endif;
	}

	function form($instance) {}
	function update($new_instance, $old_instance) {}
}