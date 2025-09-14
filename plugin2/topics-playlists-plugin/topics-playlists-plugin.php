<?php
/**
 * Plugin Name: Copella Topics & Playlists
 * Description: Создаёт тип записей «Темы/Плейлисты» и шорткоды для вывода блока «Горячие темы» с пагинацией.
 * Version: 0.1.0
 * Author: Copella
 * Text Domain: copella-topics
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('COPELLA_TOPICS_PLUGIN_FILE', __FILE__);
define('COPELLA_TOPICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COPELLA_TOPICS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation/Deactivation: flush rewrite to register CPT permalinks
register_activation_hook(__FILE__, function (): void {
    require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/cpt.php';
    copella_topics_register_cpt();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});

// Includes
require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/cpt.php';
require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/meta.php';
require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/shortcodes.php';
require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/author-page.php';
require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/admin.php';
require_once COPELLA_TOPICS_PLUGIN_DIR . 'includes/search.php';

// Assets loader (frontend) – loaded only when shortcode is present
function copella_topics_enqueue_assets(): void
{
    // Skip in admin, AJAX, REST API, and cron
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || wp_doing_cron()) {
        return;
    }

    // Detect shortcode usage in content
    $should_enqueue = false;
    $post = get_post();
    
    if ($post && isset($post->post_content)) {
        $shortcodes = ['hot_topics', 'topic_playlist', 'playlist_authors', 'playlist_author', 'video_authors', 'stream_authors', 'cpplayer_stream', 'tp_search'];
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $should_enqueue = true;
                break;
            }
        }
    }
    
    // Check for author/topic pages
    if (!$should_enqueue && (is_singular(['topic', 'topic_playlist', 'topic_author']) || is_post_type_archive(['topic', 'topic_playlist', 'topic_author']))) {
        $should_enqueue = true;
    }
    
    // Check for widgets containing shortcodes
    if (!$should_enqueue && is_active_widget(false, false, 'text')) {
        $sidebars = wp_get_sidebars_widgets();
        foreach ($sidebars as $widgets) {
            foreach ($widgets as $widget) {
                if (strpos($widget, 'text') === 0) {
                    $widget_content = get_option('widget_text');
                    if ($widget_content) {
                        foreach ($widget_content as $instance) {
                            if (isset($instance['text'])) {
                                $shortcodes = ['hot_topics', 'topic_playlist', 'playlist_authors'];
                                foreach ($shortcodes as $shortcode) {
                                    if (has_shortcode($instance['text'], $shortcode)) {
                                        $should_enqueue = true;
                                        break 3;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    if (!$should_enqueue) {
        return;
    }
    // Basic styles for topics block
    $css_file = COPELLA_TOPICS_PLUGIN_DIR . 'assets/css/topics.css';
    wp_enqueue_style(
        'copella-topics-styles',
        COPELLA_TOPICS_PLUGIN_URL . 'assets/css/topics.css',
        array(),
        file_exists($css_file) ? (string) filemtime($css_file) : '0.1.0'
    );

    // Optional progressive enhancement script
    $js_file = COPELLA_TOPICS_PLUGIN_DIR . 'assets/js/topics.js';
    wp_enqueue_script(
        'copella-topics-script',
        COPELLA_TOPICS_PLUGIN_URL . 'assets/js/topics.js',
        array(),
        file_exists($js_file) ? (string) filemtime($js_file) : '0.1.0',
        array(
            'in_footer' => true,
            'strategy'  => 'defer',
        )
    );
    
    // Playlist script
    $playlist_js_file = COPELLA_TOPICS_PLUGIN_DIR . 'assets/js/playlist.js';
    wp_enqueue_script(
        'copella-playlist-script',
        COPELLA_TOPICS_PLUGIN_URL . 'assets/js/playlist.js',
        array(),
        file_exists($playlist_js_file) ? (string) filemtime($playlist_js_file) : '0.1.0',
        array(
            'in_footer' => true,
            'strategy'  => 'defer',
        )
    );
    
    // Authors interactive script
    $authors_js_file = COPELLA_TOPICS_PLUGIN_DIR . 'assets/js/authors.js';
    wp_enqueue_script(
        'copella-authors-script',
        COPELLA_TOPICS_PLUGIN_URL . 'assets/js/authors.js',
        array(),
        file_exists($authors_js_file) ? (string) filemtime($authors_js_file) : '0.1.0',
        array(
            'in_footer' => true,
            'strategy'  => 'defer',
        )
    );
}
add_action('wp_enqueue_scripts', 'copella_topics_enqueue_assets');

// Load text domain for translations
add_action('plugins_loaded', function (): void {
    load_plugin_textdomain('copella-topics', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

