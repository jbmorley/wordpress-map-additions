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

	static function init() {
		$api_key = 'AIzaSyBxqKnZdnWREeJePXTLJZmIXulbQwCx_hk';
		$path = plugins_url('js/locations.js', __FILE__);
		wp_enqueue_script('locations', $path);
		wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key);
	}

	static function filter_something($content) {
		return $content;
	}

}

add_action('init', array('locations', 'init'));
add_filter('the_content', array('locations', 'filter_something'));

?>
