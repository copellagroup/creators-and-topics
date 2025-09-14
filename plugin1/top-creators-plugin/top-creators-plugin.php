<?php
/**
 * Plugin Name: Copella Top Creators
 * Description: Компонент «Лучшие креаторы» в едином стиле сайта.
 * Version: 1.0.0 (Stable)
 * Author: Copella
 * Text Domain: copella-creators
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('COPELLA_CREATORS_FILE', __FILE__);
define('COPELLA_CREATORS_DIR', plugin_dir_path(__FILE__));
define('COPELLA_CREATORS_URL', plugin_dir_url(__FILE__));

const COPELLA_CREATORS_OPTION = 'copella_top_creators';

function copella_creators_default_options(): array {
    return [
        'title' => __('Лучшие креаторы за месяц', 'copella-creators'),
        'items' => [
            ['name' => __('Персона 1', 'copella-creators'), 'link' => '', 'image_id' => 0],
            ['name' => __('Персона 2', 'copella-creators'), 'link' => '', 'image_id' => 0],
            ['name' => __('Персона 3', 'copella-creators'), 'link' => '', 'image_id' => 0],
            ['name' => __('Персона 4', 'copella-creators'), 'link' => '', 'image_id' => 0],
        ]
    ];
}

// Activation/Deactivation: flush rewrite to register CPT permalinks
register_activation_hook(__FILE__, function (): void {
    require_once COPELLA_CREATORS_DIR . 'includes/cpt.php';
    copella_creators_register_cpt();
    flush_rewrite_rules();
    
    if (!get_option(COPELLA_CREATORS_OPTION)) {
        add_option(COPELLA_CREATORS_OPTION, copella_creators_default_options());
    }
});

register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});

// Includes
require_once COPELLA_CREATORS_DIR . 'includes/cpt.php';
require_once COPELLA_CREATORS_DIR . 'includes/meta.php';
require_once COPELLA_CREATORS_DIR . 'includes/shortcodes.php';
require_once COPELLA_CREATORS_DIR . 'includes/admin.php';

// Assets loader (frontend) – loaded only when shortcode is present
function copella_creators_enqueue_assets(): void
{
    // Skip in admin, AJAX, REST API, and cron
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || wp_doing_cron()) {
        return;
    }

    // Detect shortcode usage in content
    $should_enqueue = false;
    $post = get_post();
    
    if ($post && isset($post->post_content)) {
        $shortcodes = ['top_creators', 'creator_page'];
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $should_enqueue = true;
                break;
            }
        }
    }
    
    // Check for creator pages
    if (!$should_enqueue && (is_singular(['creator']) || is_post_type_archive(['creator']))) {
        $should_enqueue = true;
    }
    
    if (!$should_enqueue) {
        return;
    }
    
    // Basic styles for creators block
    $css_file = COPELLA_CREATORS_DIR . 'assets/css/top-creators.css';
    wp_enqueue_style(
        'copella-creators-styles',
        COPELLA_CREATORS_URL . 'assets/css/top-creators.css',
        array(),
        file_exists($css_file) ? (string) filemtime($css_file) : '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'copella_creators_enqueue_assets');

// Load text domain for translations
add_action('plugins_loaded', function (): void {
    load_plugin_textdomain('copella-creators', false, dirname(plugin_basename(__FILE__)) . '/languages');
});