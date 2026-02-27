<?php
/**
 * Plugin Name: Mind Map Studio
 * Description: قابلیت ساخت نقشه ذهنی حرفه‌ای با خروجی تصویر و مدیریت آسان.
 * Version: 1.0.1
 * Author: Dr. Khasteh
 * Text Domain: mind-map-studio
 */

defined( 'ABSPATH' ) || exit;

define( 'MIND_MAP_STUDIO_VERSION', '1.0.1' );
define( 'MIND_MAP_STUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MIND_MAP_STUDIO_URL', plugin_dir_url( __FILE__ ) );

require_once MIND_MAP_STUDIO_PATH . 'inc/class-mind-map-studio.php';

function mind_map_studio_init() {
	Mind_Map_Studio::init();
}
add_action( 'plugins_loaded', 'mind_map_studio_init' );
