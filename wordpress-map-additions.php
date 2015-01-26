<?php
//
// Plugin Name: WordPress Map Additions
// Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
// Description: Collection of mapping extensions for WordPress.
// Version: 1.0.0
// Author: Jason Barrie Morley
// Author URI: http://jbmorley.co.uk
// License: GPL2
//
// Copyright 2015 Jason Barrie Morley (jbmorley@mac.com)
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License, version 2, as
// published by the Free Software Foundation.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
//

defined('ABSPATH') or die("No script kiddies please!");

class wordpress_map_additions {

    const SETTINGS_PAGE = "general";
    const SETTINGS_GROUP = "wordpress_map_additions";
    const SETTING_GOOGLE_MAPS_API_KEY = "wordpress_map_additions_google_maps_api_key";

    static $maps = array();
    static $galleryId = 0;

    static function init() {

        $api_key = get_option(self::SETTING_GOOGLE_MAPS_API_KEY);
        wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $asitepi_key);
        wp_enqueue_script('wordpress-map-additions', plugins_url('js/wordpress-map-additions.js', __FILE__));

    }

    static function admin_init() {

        add_settings_field(
            self::SETTING_GOOGLE_MAPS_API_KEY,
            'Google Maps API Key',
            array('wordpress_map_additions', 'settings_google_maps_api_key_callback'),
            self::SETTINGS_PAGE,
            'default');

        register_setting(self::SETTINGS_GROUP, self::SETTING_GOOGLE_MAPS_API_KEY);

    }

    static function settings_google_maps_api_key_callback() {
        self::input(self::SETTING_GOOGLE_MAPS_API_KEY);
    }

    static function input($setting, $default = None) {
        settings_fields(self::SETTINGS_GROUP);
        $value = get_option($setting, $default);
        echo '<input type="text" id="' . $setting . '" name="' . $setting . '" value="' . $value . '" />';
    }

    static function filter_content($content) {
        $pattern = get_shortcode_regex();
        $result = preg_replace_callback( "/$pattern/s", array('wordpress_map_additions', 'handle_gallery_shortcode'), $content);
        return $result;
    }

    static function get_map($id, $options = None) {

        $pins = array();
        if (array_key_exists($id, static::$maps)) {
            $pins = static::$maps[$id];
        }

        $details = array("pins" => $pins, "zoom" => $options["zoom"]);

        $result = "";
        $result .= "<script>";
        $result .= "locations['" . $id . "'] = " . json_encode($details) . ";";
        $result .= "</script>";
        $result .= "<div id='locations-map-canvas-" . $id . "' style='width: 100%; height: 400px'></div>";

        return $result;

    }

    static function shortcode_map($atts) {
        $a = shortcode_atts(
            array(
                "id" => "default",
                "zoom" => 4
                ),
            $atts);

        $id = $a["id"];
        return self::get_map($id, $a);
    }

    static function copy_array_keys($keys, $source_array) {
        $result = array();
        foreach ($keys as &$key) {
            $result[$key] = $source_array[$key];
        }
        unset($key);
        return $result;
    }

    static function add_pin($mapId, $pin) {

        if (!array_key_exists($mapId, static::$maps)) {
            static::$maps[$mapId] = array();
        }

        array_push(static::$maps[$mapId], $pin);

        $index = count(static::$maps[$mapId]) - 1;

        return $index;

    }

    static function shortcode_pin($atts) {
        $a = shortcode_atts(
            array(
                "map" => "default",
                "name" => "Dropped Pin",
                "lat" => "0.000",
                "lng" => "0.000"
                ),
            $atts);

        $mapId = $a["map"];

        $sanitised_pin = self::copy_array_keys(["name", "lat", "lng"], $a);
        $index = self::add_pin($mapId, $sanitised_pin);

        return '<a href="javascript:setLocation(\'' . $mapId . '\', ' . $index . ');">' . $sanitised_pin["name"] . '</a>';
    }

    function convert_gps($exifCoord, $hemi) {

        $degrees = count($exifCoord) > 0 ? self::gps_to_number($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? self::gps_to_number($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? self::gps_to_number($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

    }

    function gps_to_number($coordPart) {

        $parts = explode('/', $coordPart);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);

    }

    static function get_gps_coordinate($key, $exif) {

        $result = "0";

        $value_key = $key;
        $direction_key = $value_key . "Ref";

        if (!array_key_exists($value_key, $exif) || !array_key_exists($direction_key, $exif)) {
            return None;
        }

        $value = $exif[$value_key];
        $direction = $exif[$direction_key];

        if ($value == None || $direction == None) {
            return None;
        }

        return self::convert_gps($value, $direction);
    }

    static function get_gps($path) {

        $exif = exif_read_data($path);

        $latitude = self::get_gps_coordinate("GPSLatitude", $exif);
        $longitude = self::get_gps_coordinate("GPSLongitude", $exif);

        if ($longitude === None || $latitude === None) {
            return None;
        }

        return array($latitude, $longitude);
    }

    static function handle_gallery_shortcode($m) {

        $tag = $m[2];

        $result = "";

        if (strcmp($tag, "gallery") !== 0) {
            return $m[0];
        }

        self::$galleryId++;
        $mapId = "post-" . get_the_ID() . "-gallery-" . self::$galleryId;

        $attr = shortcode_parse_atts($m[3]);
        $ids = explode(',', $attr["ids"]);
        foreach ($ids as &$id) {
            if (wp_attachment_is_image($id)) {
                $path = get_attached_file($id);
                $gps = self::get_gps($path);
                if ($gps !== None) {
                    $result .= var_export($gps, true);
                    self::add_pin($mapId, array("name" => "Pin", "lat" => $gps[0], "lng" => $gps[1]));
                }
            }
        }
        unset($id);

        $gallery = self::get_map($mapId, array("zoom" => 12));

        return $m[0] . $gallery;

    }

}

// Actions
add_action('init', array('wordpress_map_additions', 'init'));
add_action('admin_init', array('wordpress_map_additions', 'admin_init'));

// Shortcodes.
add_shortcode('map', array('wordpress_map_additions', 'shortcode_map'));
add_shortcode('pin', array('wordpress_map_additions', 'shortcode_pin'));

// Filters
add_filter('the_content', array('wordpress_map_additions', 'filter_content'));

?>

