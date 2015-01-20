<?php
/**
 * Plugin Name: Locations
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: A brief description of the plugin.
 * Version: 1.0.0
 * Author: Jason Barrie Morley
 * Author URI: http://jbmorley.co.uk
 * Text Domain: Optional. Plugin's text domain for localization. Example: mytextdomain
 * License: A short license name. Example: GPL2
 */

class locations {

	const SETTINGS_PAGE = "general";
	const SETTINGS_GROUP = "locations";
	const SETTING_GOOGLE_MAPS_API_KEY = "locations_google_maps_api_key";

	static function init() {

		$api_key = get_option(self::SETTING_GOOGLE_MAPS_API_KEY);
		$path = plugins_url('js/locations.js', __FILE__);
		wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $asitepi_key);
		wp_enqueue_script('locations', $path);

	}

	static function admin_init() {

		add_settings_field(
			self::SETTING_GOOGLE_MAPS_API_KEY,
			'Google Maps API Key',
			array('locations', 'settings_google_maps_api_key_callback'),
			self::SETTINGS_PAGE,
			'default');

		register_setting(self::SETTINGS_GROUP, self::SETTING_GOOGLE_MAPS_API_KEY);

	}

	static function settings_google_maps_api_key_callback() {
		self::input(self::SETTING_GOOGLE_MAPS_API_KEY, "Your API key here");
	}

	static function input($setting, $default = None) {
		settings_fields(self::SETTINGS_GROUP);
		$value = get_option($setting, $default);
		echo '<input type="text" id="' . $setting . '" name="' . $setting . '" value="' . $value . '" />';
	}

	static function filter_something($content) {
		return $content;
	}

	static function shortcode_map($atts) {
		return "<div id='map-canvas' style='width: 100%; height: 400px'></div>";
	}

}

// Actions
add_action('init', array('locations', 'init'));
add_action('admin_init', array('locations', 'admin_init'));

// Shortcodes.
add_shortcode('map', array('locations', 'shortcode_map'));

// Filters
add_filter('the_content', array('locations', 'filter_something'));

?>
