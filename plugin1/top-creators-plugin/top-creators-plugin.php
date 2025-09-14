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

register_activation_hook(__FILE__, function (): void {
    if (!get_option(COPELLA_CREATORS_OPTION)) {
        add_option(COPELLA_CREATORS_OPTION, copella_creators_default_options());
    }
});

add_action('admin_menu', function (): void {
    add_menu_page(
        __('Лучшие креаторы', 'copella-creators'),
        __('Креаторы', 'copella-creators'),
        'manage_options',
        'copella-creators',
        'copella_creators_render_admin_page',
        'dashicons-groups',
        28
    );
});

add_action('admin_init', function (): void {
    register_setting('copella_creators_group', COPELLA_CREATORS_OPTION, 'copella_creators_sanitize_options');
});

function copella_creators_sanitize_options($raw) {
    $defaults = copella_creators_default_options();
    $opts = is_array($raw) ? $raw : array();
    $clean = array();
    $clean['title'] = isset($opts['title']) ? sanitize_text_field((string) $opts['title']) : $defaults['title'];
    $clean['items'] = array();
    for ($i = 0; $i < 4; $i++) {
        $item = $opts['items'][$i] ?? array();
        $clean['items'][$i] = array(
            'name' => isset($item['name']) ? sanitize_text_field((string) $item['name']) : $defaults['items'][$i]['name'],
            'link' => isset($item['link']) ? esc_url_raw((string) $item['link']) : '',
            'image_id' => isset($item['image_id']) ? max(0, (int) $item['image_id']) : 0,
        );
    }
    return $clean;
}

function copella_creators_admin_enqueue($hook): void {
    if ($hook !== 'toplevel_page_copella-creators') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('copella-creators-admin', COPELLA_CREATORS_URL . 'assets/css/admin.css', array(), '1.0.0');
    wp_enqueue_script('copella-creators-admin', COPELLA_CREATORS_URL . 'assets/js/top-creators-admin.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'copella_creators_admin_enqueue');

function copella_creators_render_admin_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    $opts = wp_parse_args(get_option(COPELLA_CREATORS_OPTION, array()), copella_creators_default_options());
    ?>
    <div class="wrap copella-creators-admin">
        <h1><?php esc_html_e('Лучшие креаторы', 'copella-creators'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('copella_creators_group'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label for="cp-title"><?php esc_html_e('Заголовок', 'copella-creators'); ?></label></th>
                    <td>
                        <input type="text" id="cp-title" class="regular-text" name="<?php echo esc_attr(COPELLA_CREATORS_OPTION); ?>[title]" value="<?php echo esc_attr($opts['title']); ?>"/>
                        <p class="description"><?php esc_html_e('Текст заголовка блока', 'copella-creators'); ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <h2 style="margin-top:18px;"><?php esc_html_e('Креаторы', 'copella-creators'); ?></h2>
            <div class="cp-creators-grid">
                <?php for ($i = 0; $i < 4; $i++): $item = $opts['items'][$i]; ?>
                    <div class="cp-creator-item">
                        <div class="cp-creator-thumb">
                            <?php if (!empty($item['image_id'])): ?>
                                <?php echo wp_get_attachment_image((int) $item['image_id'], 'medium', false, array('class' => 'cp-preview')); ?>
                            <?php else: ?>
                                <div class="cp-placeholder"><?php echo esc_html(sprintf(__('Персона %d', 'copella-creators'), $i + 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <p>
                            <label><?php esc_html_e('Имя', 'copella-creators'); ?><br/>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(COPELLA_CREATORS_OPTION); ?>[items][<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr($item['name']); ?>"/>
                            </label>
                        </p>
                        <p>
                            <label><?php esc_html_e('Ссылка (опционально)', 'copella-creators'); ?><br/>
                                <input type="url" class="regular-text" name="<?php echo esc_attr(COPELLA_CREATORS_OPTION); ?>[items][<?php echo (int) $i; ?>][link]" value="<?php echo esc_attr($item['link']); ?>"/>
                            </label>
                        </p>
                        <input type="hidden" class="cp-image-id" name="<?php echo esc_attr(COPELLA_CREATORS_OPTION); ?>[items][<?php echo (int) $i; ?>][image_id]" value="<?php echo (int) $item['image_id']; ?>"/>
                        <p>
                            <button type="button" class="button cp-select-image" data-index="<?php echo (int) $i; ?>"><?php esc_html_e('Выбрать изображение', 'copella-creators'); ?></button>
                            <button type="button" class="button cp-remove-image" data-index="<?php echo (int) $i; ?>"><?php esc_html_e('Убрать', 'copella-creators'); ?></button>
                        </p>
                    </div>
                <?php endfor; ?>
            </div>
            <?php submit_button(); ?>
        </form>
        <p style="margin-top:14px">
            <strong><?php esc_html_e('Шорткод', 'copella-creators'); ?>:</strong>
            <code>[top_creators]</code>
        </p>
    </div>
    <?php
}

function copella_creators_register_assets(): void {
    $css_file = COPELLA_CREATORS_DIR . 'assets/css/top-creators.css';
    wp_register_style('copella-top-creators', COPELLA_CREATORS_URL . 'assets/css/top-creators.css', array(), file_exists($css_file) ? (string) filemtime($css_file) : '1.0.0');
}
add_action('wp_enqueue_scripts', 'copella_creators_register_assets');

// --- CREATOR PAGES FUNCTIONALITY ---

// Register Creator CPT
add_action('init', function(): void {
    $labels = array(
        'name'               => __('Креаторы', 'copella-creators'),
        'singular_name'      => __('Креатор', 'copella-creators'),
        'menu_name'          => __('Креаторы', 'copella-creators'),
        'add_new'            => __('Добавить креатора', 'copella-creators'),
        'add_new_item'       => __('Добавить нового креатора', 'copella-creators'),
        'new_item'           => __('Новый креатор', 'copella-creators'),
        'edit_item'          => __('Редактировать креатора', 'copella-creators'),
        'view_item'          => __('Просмотреть креатора', 'copella-creators'),
        'all_items'          => __('Все креаторы', 'copella-creators'),
        'search_items'       => __('Искать креаторов', 'copella-creators'),
        'not_found'          => __('Креаторов не найдено.', 'copella-creators'),
        'not_found_in_trash' => __('В корзине креаторов не найдено.', 'copella-creators'),
    );

    register_post_type('creator', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'creators'),
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'thumbnail', 'editor', 'excerpt'),
        'show_in_rest' => true,
        'show_in_menu' => false, // We'll add it to our custom menu
    ));
});

// Add Creator meta boxes
add_action('add_meta_boxes', function(): void {
    add_meta_box(
        'copella_creator_details',
        __('Детали креатора', 'copella-creators'),
        'copella_creator_render_meta_box',
        'creator',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_creator_social',
        __('Социальные сети', 'copella-creators'),
        'copella_creator_render_social_meta_box',
        'creator',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_creator_achievements',
        __('Достижения и информация', 'copella-creators'),
        'copella_creator_render_achievements_meta_box',
        'creator',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_creator_playlists',
        __('Плейлисты', 'copella-creators'),
        'copella_creator_render_playlists_meta_box',
        'creator',
        'normal',
        'default'
    );
});

// Creator meta constants
const COPELLA_CREATOR_META_AGE = '_copella_creator_age';
const COPELLA_CREATOR_META_EXPERIENCE = '_copella_creator_experience';
const COPELLA_CREATOR_META_SPECIALIZATION = '_copella_creator_specialization';
const COPELLA_CREATOR_META_ACHIEVEMENTS = '_copella_creator_achievements';
const COPELLA_CREATOR_META_SOCIAL = '_copella_creator_social';
const COPELLA_CREATOR_META_PLAYLISTS = '_copella_creator_playlists';
const COPELLA_CREATOR_META_BACKGROUND_IMAGE = '_copella_creator_background_image';

function copella_creator_render_meta_box(WP_Post $post): void {
    wp_nonce_field('copella_creator_meta', 'copella_creator_meta_nonce');
    $age = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_AGE, true);
    $experience = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_EXPERIENCE, true);
    $specialization = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_SPECIALIZATION, true);
    $background_image = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_BACKGROUND_IMAGE, true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="copella_creator_age"><?php _e('Возраст', 'copella-creators'); ?></label></th>
            <td>
                <input type="number" id="copella_creator_age" name="copella_creator_age" value="<?php echo esc_attr($age); ?>" class="regular-text" min="0" max="120"/>
                <p class="description"><?php _e('Возраст креатора в годах', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="copella_creator_experience"><?php _e('Опыт работы', 'copella-creators'); ?></label></th>
            <td>
                <input type="number" id="copella_creator_experience" name="copella_creator_experience" value="<?php echo esc_attr($experience); ?>" class="regular-text" min="0" max="50"/>
                <p class="description"><?php _e('Количество лет опыта в сфере', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="copella_creator_specialization"><?php _e('Специализация', 'copella-creators'); ?></label></th>
            <td>
                <input type="text" id="copella_creator_specialization" name="copella_creator_specialization" value="<?php echo esc_attr($specialization); ?>" class="regular-text" placeholder="<?php esc_attr_e('Например: веб-разработка, дизайн, маркетинг', 'copella-creators'); ?>"/>
                <p class="description"><?php _e('В чем специализируется креатор', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="copella_creator_background_image"><?php _e('Фоновое изображение', 'copella-creators'); ?></label></th>
            <td>
                <div class="cp-creator-bg-preview" style="margin-bottom: 10px;">
                    <?php if ($background_image): ?>
                        <?php echo wp_get_attachment_image($background_image, 'medium', false, array('style' => 'max-width: 200px; height: auto;')); ?>
                    <?php else: ?>
                        <div style="width: 200px; height: 100px; background: #f0f0f0; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; color: #666;"><?php _e('Изображение не выбрано', 'copella-creators'); ?></div>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="copella_creator_background_image" name="copella_creator_background_image" value="<?php echo esc_attr($background_image); ?>"/>
                <button type="button" class="button cp-select-bg-image"><?php _e('Выбрать изображение', 'copella-creators'); ?></button>
                <button type="button" class="button cp-remove-bg-image"><?php _e('Убрать', 'copella-creators'); ?></button>
                <p class="description"><?php _e('Изображение для размытого фона на странице креатора', 'copella-creators'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

function copella_creator_render_social_meta_box(WP_Post $post): void {
    $social_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_SOCIAL, true);
    ?>
    <p>
        <label for="copella_creator_social"><strong><?php _e('Социальные сети (по одной в строке)', 'copella-creators'); ?></strong></label><br/>
        <textarea id="copella_creator_social" name="copella_creator_social" class="widefat" rows="6" placeholder="YouTube | https://youtube.com/@username | 🎥&#10;Telegram | https://t.me/username | 📱&#10;Instagram | https://instagram.com/username | 📸&#10;Twitter | https://twitter.com/username | 🐦"><?php echo esc_textarea($social_raw); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Формат: Название | Ссылка | Иконка (эмодзи или текст). Иконка необязательна.', 'copella-creators'); ?>
    </p>
    <?php
}

function copella_creator_render_achievements_meta_box(WP_Post $post): void {
    $achievements = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_ACHIEVEMENTS, true);
    ?>
    <p>
        <label for="copella_creator_achievements"><strong><?php _e('Достижения и заслуги (по одному в строке)', 'copella-creators'); ?></strong></label><br/>
        <textarea id="copella_creator_achievements" name="copella_creator_achievements" class="widefat" rows="6" placeholder="🏆 Победитель конкурса дизайна 2023&#10;📈 100k+ подписчиков на YouTube&#10;💼 Работал с топ-брендами&#10;🎓 Сертифицированный специалист"><?php echo esc_textarea($achievements); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Перечислите основные достижения и заслуги креатора. Можно использовать эмодзи для визуального оформления.', 'copella-creators'); ?>
    </p>
    <?php
}

function copella_creator_render_playlists_meta_box(WP_Post $post): void {
    $playlists_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_PLAYLISTS, true);
    $selected = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $playlists_raw)));
    
    // Get available playlists from topics plugin
    $playlists = get_posts(array(
        'post_type' => 'topic_playlist',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 500,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    ?>
    <div class="cp-creator-playlists-picker">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
            <input type="text" id="cp-creator-playlist-search" class="regular-text" placeholder="<?php esc_attr_e('Поиск плейлистов…', 'copella-creators'); ?>"/>
            <span style="opacity:.75"><?php printf(__('Всего: %d', 'copella-creators'), count($playlists)); ?></span>
            <span id="cp-creator-playlist-count" style="margin-left:auto;opacity:.75"><?php printf(__('Выбрано: %d', 'copella-creators'), count($selected)); ?></span>
        </div>
        <div id="cp-creator-playlist-list" style="max-height:320px;overflow:auto;border:1px solid #ddd;border-radius:6px;padding:8px;background:#fff">
            <?php if (empty($playlists)): ?>
                <div style="opacity:.7;padding:6px 0"><?php _e('Плейлистов не найдено. Убедитесь, что плагин Topics & Playlists активен.', 'copella-creators'); ?></div>
            <?php else: foreach ($playlists as $playlist): ?>
                <label class="cp-creator-playlist-row" data-title="<?php echo esc_attr(strtolower($playlist->post_title)); ?>" style="display:flex;align-items:center;gap:10px;margin:6px 0">
                    <input type="checkbox" class="cp-creator-playlist-pick" name="copella_creator_playlist_picks[]" value="<?php echo (int) $playlist->ID; ?>" <?php checked(in_array($playlist->ID, $selected)); ?>/>
                    <?php 
                    $thumb = get_the_post_thumbnail_url($playlist->ID, 'thumbnail');
                    if ($thumb): 
                    ?>
                        <img src="<?php echo esc_url($thumb); ?>" width="40" height="40" style="object-fit:cover;border-radius:6px" alt=""/>
                    <?php endif; ?>
                    <span><?php echo esc_html($playlist->post_title); ?> <span style="opacity:.6">#<?php echo (int) $playlist->ID; ?></span></span>
                    <?php if ($playlist->post_status !== 'publish'): ?><span style="margin-left:auto;opacity:.65;"><em><?php echo esc_html($playlist->post_status); ?></em></span><?php endif; ?>
                </label>
            <?php endforeach; endif; ?>
        </div>
        <input type="hidden" id="copella_creator_playlists" name="copella_creator_playlists" value="<?php echo esc_attr(implode(',', $selected)); ?>"/>
    </div>
    <p style="opacity:.8;margin:6px 0 0 0"><?php _e('Выберите плейлисты, которые будут отображаться на странице креатора.', 'copella-creators'); ?></p>
    
    <p style="margin-top: 20px;">
        <label><strong><?php _e('Шорткод страницы креатора', 'copella-creators'); ?></strong></label><br/>
        <input type="text" readonly class="widefat" value="[creator_page creator_id=&quot;<?php echo (int) $post->ID; ?>&quot;]" onclick="this.select();document.execCommand('copy');"/>
        <p class="description"><?php _e('Скопируйте этот шорткод для вставки на любую страницу', 'copella-creators'); ?></p>
    </p>
    <?php
}

function copella_creator_save_meta_boxes(int $post_id): void {
    if (!isset($_POST['copella_creator_meta_nonce']) || !wp_verify_nonce((string) $_POST['copella_creator_meta_nonce'], 'copella_creator_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save basic info
    if (isset($_POST['copella_creator_age'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_AGE, absint((string) $_POST['copella_creator_age']));
    }
    
    if (isset($_POST['copella_creator_experience'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_EXPERIENCE, absint((string) $_POST['copella_creator_experience']));
    }
    
    if (isset($_POST['copella_creator_specialization'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_SPECIALIZATION, sanitize_text_field((string) $_POST['copella_creator_specialization']));
    }
    
    if (isset($_POST['copella_creator_background_image'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_BACKGROUND_IMAGE, absint((string) $_POST['copella_creator_background_image']));
    }
    
    // Save social networks
    if (isset($_POST['copella_creator_social'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_SOCIAL, sanitize_textarea_field((string) $_POST['copella_creator_social']));
    }
    
    // Save achievements
    if (isset($_POST['copella_creator_achievements'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_ACHIEVEMENTS, sanitize_textarea_field((string) $_POST['copella_creator_achievements']));
    }
    
    // Save playlists
    $playlist_ids = array();
    if (isset($_POST['copella_creator_playlist_picks']) && is_array($_POST['copella_creator_playlist_picks'])) {
        $playlist_ids = array_map('absint', (array) $_POST['copella_creator_playlist_picks']);
    } else {
        $raw = isset($_POST['copella_creator_playlists']) ? (string) $_POST['copella_creator_playlists'] : '';
        $playlist_ids = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $raw)));
    }
    $playlist_ids = array_values(array_unique(array_filter($playlist_ids)));
    update_post_meta($post_id, COPELLA_CREATOR_META_PLAYLISTS, implode(',', $playlist_ids));
}
add_action('save_post_creator', 'copella_creator_save_meta_boxes');

// Add Creators submenu to our admin menu
add_action('admin_menu', function(): void {
    add_submenu_page(
        'copella-creators',
        __('Все креаторы', 'copella-creators'),
        __('Все креаторы', 'copella-creators'),
        'manage_options',
        'edit.php?post_type=creator'
    );
});

// Add "Add New Creator" submenu
add_action('admin_menu', function(): void {
    add_submenu_page(
        'copella-creators',
        __('Добавить креатора', 'copella-creators'),
        __('Добавить креатора', 'copella-creators'),
        'manage_options',
        'post-new.php?post_type=creator'
    );
});

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function(): void {
    flush_rewrite_rules();
});

// Add custom template for creator pages
add_filter('template_include', function($template) {
    if (is_singular('creator')) {
        $custom_template = COPELLA_CREATORS_DIR . 'single-creator.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    if (is_post_type_archive('creator')) {
        $custom_template = COPELLA_CREATORS_DIR . 'archive-creator.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
});

// Add body class for creator pages
add_filter('body_class', function($classes) {
    if (is_singular('creator')) {
        $classes[] = 'single-creator';
    }
    if (is_post_type_archive('creator')) {
        $classes[] = 'archive-creator';
    }
    return $classes;
});

// Create custom template for creator pages
add_action('wp_head', function() {
    if (is_singular('creator')) {
        $creator_id = get_the_ID();
        $background_image = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_BACKGROUND_IMAGE, true);
        if ($background_image) {
            $background_url = wp_get_attachment_image_url($background_image, 'full');
            if ($background_url) {
                echo '<style>
                body.single-creator .cp-creator-page .cp-creator-background {
                    background-image: url("' . esc_url($background_url) . '") !important;
                }
                </style>';
            }
        }
    }
});

// --- ФИНАЛЬНАЯ ФУНКЦИЯ ШОРТКОДА ---

add_shortcode('top_creators', function ($atts = array()): string {
    $opts = wp_parse_args(get_option(COPELLA_CREATORS_OPTION, array()), copella_creators_default_options());

    $a = shortcode_atts(array(
        'title' => (string) ($opts['title'] ?? ''),
        'card_radius' => '16',
        'gap' => '20',
    ), $atts, 'top_creators');

    $title = trim((string) $a['title']);
    $gap = (int) $a['gap'];
    $radius = (int) $a['card_radius'];

    wp_enqueue_style('copella-top-creators');

    $items = is_array($opts['items'] ?? null) ? $opts['items'] : array();
    for ($i = 0; $i < 4; $i++) {
        if (!isset($items[$i])) { $items[$i] = array('name' => sprintf(__('Персона %d', 'copella-creators'), $i + 1), 'link' => '', 'image_id' => 0); }
    }

    $styleVars = sprintf('--tc-gap:%dpx;--tc-radius:%dpx;', $gap, $radius);
    $uid = 'top_creators_' . wp_generate_password(8, false, false);

    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="cp-top-creators" style="<?php echo esc_attr($styleVars); ?>">
        <div class="tc-header">
            <?php if ($title !== ''): ?>
                <h3 class="tc-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <div class="tc-titlebar">
                <button class="tc-nav tc-prev" type="button" aria-label="<?php esc_attr_e('Назад', 'copella-creators'); ?>">
                    <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" fill="currentColor"></path></svg>
                </button>
                <button class="tc-nav tc-next" type="button" aria-label="<?php esc_attr_e('Вперед', 'copella-creators'); ?>">
                    <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" fill="currentColor"></path></svg>
                </button>
            </div>
        </div>
        
        <div class="tc-viewport">
            <div class="tc-track" role="list">
                <?php foreach ($items as $idx => $it):
                    $name = trim((string) ($it['name'] ?? ''));
                    if ($name === '') { $name = sprintf(__('Персона %d', 'copella-creators'), $idx + 1); }
                    $link = esc_url($it['link'] ?? '');
                    $imageId = (int) ($it['image_id'] ?? 0);
                    $thumb = $imageId ? wp_get_attachment_image_url($imageId, 'medium_large') : '';
                    ?>
                    <div class="tc-card" role="listitem">
                        <?php if ($link): ?><a class="tc-link" href="<?php echo $link; ?>" title="<?php echo esc_attr($name); ?>"></a><?php endif; ?>
                        <div class="tc-thumb" aria-hidden="true">
                            <?php if ($thumb): ?>
                                <img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy"/>
                            <?php else: ?>
                                <div class="tc-fallback"></div>
                            <?php endif; ?>
                        </div>
                        <div class="tc-name"><?php echo esc_html($name); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <script>
      (function(){
        var root=document.getElementById('<?php echo esc_js($uid); ?>');if(!root)return;
        var track=root.querySelector('.tc-track');var btnPrev=root.querySelector('.tc-prev');var btnNext=root.querySelector('.tc-next');
        if(!track||!btnPrev||!btnNext)return;
        function isDesktop(){return window.matchMedia('(min-width:1024px)').matches}
        function updateButtons(){if(isDesktop()){btnPrev.disabled=!0;btnNext.disabled=!0;return}var max=track.scrollWidth-track.clientWidth-1;btnPrev.disabled=track.scrollLeft<=1;btnNext.disabled=track.scrollLeft>=max}
        function scrollByDir(dir){var card=track.querySelector('.tc-card');var scrollAmount=card?card.clientWidth*1.5:track.clientWidth*.7;track.scrollBy({left:dir*scrollAmount,behavior:'smooth'})}
        btnPrev.addEventListener('click',function(){scrollByDir(-1)});btnNext.addEventListener('click',function(){scrollByDir(1)});
        track.addEventListener('scroll',updateButtons,{passive:!0});window.addEventListener('resize',updateButtons);updateButtons();
      })();
    </script>
    <?php
    return (string) ob_get_clean();
});

// --- CREATOR PAGE SHORTCODE ---

add_shortcode('creator_page', function ($atts = array()): string {
    $a = shortcode_atts(array(
        'creator_id' => '0',
        'show_playlists' => 'true',
        'show_social' => 'true',
        'show_achievements' => 'true',
    ), $atts, 'creator_page');

    $creator_id = (int) $a['creator_id'];
    if ($creator_id <= 0) {
        return '<p>' . __('Ошибка: не указан ID креатора', 'copella-creators') . '</p>';
    }

    $creator = get_post($creator_id);
    if (!$creator || $creator->post_type !== 'creator') {
        return '<p>' . __('Креатор не найден', 'copella-creators') . '</p>';
    }

    // Get creator data
    $name = get_the_title($creator_id);
    $avatar = get_the_post_thumbnail_url($creator_id, 'large');
    $description = get_post_field('post_content', $creator_id);
    $excerpt = get_post_field('post_excerpt', $creator_id);
    
    // Get meta data
    $age = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_AGE, true);
    $experience = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_EXPERIENCE, true);
    $specialization = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SPECIALIZATION, true);
    $achievements_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_ACHIEVEMENTS, true);
    $social_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SOCIAL, true);
    $background_image = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_BACKGROUND_IMAGE, true);
    $playlists_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_PLAYLISTS, true);

    // Parse social networks
    $social_networks = array();
    if ($social_raw !== '' && $a['show_social'] === 'true') {
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $social_raw)));
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 2) {
                $social_networks[] = array(
                    'name' => sanitize_text_field($parts[0]),
                    'url'  => esc_url($parts[1]),
                    'icon' => isset($parts[2]) ? sanitize_text_field($parts[2]) : ''
                );
            }
        }
    }

    // Parse achievements
    $achievements = array();
    if ($achievements_raw !== '' && $a['show_achievements'] === 'true') {
        $achievements = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $achievements_raw)));
    }

    // Get playlists
    $playlists = array();
    if ($playlists_raw !== '' && $a['show_playlists'] === 'true') {
        $playlist_ids = array_filter(array_map('absint', explode(',', $playlists_raw)));
        if (!empty($playlist_ids)) {
            $playlists = get_posts(array(
                'post_type' => 'topic_playlist',
                'post__in' => $playlist_ids,
                'post_status' => 'publish',
                'posts_per_page' => 50,
                'orderby' => 'post__in',
                'no_found_rows' => true,
            ));
        }
    }

    // Get background image URL
    $background_url = '';
    if ($background_image) {
        $background_url = wp_get_attachment_image_url($background_image, 'full');
    }

    // Enqueue styles
    wp_enqueue_style('copella-top-creators');

    $uid = 'creator_page_' . wp_generate_password(8, false, false);

    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="cp-creator-page">
        <?php if ($background_url): ?>
            <div class="cp-creator-background" style="background-image: url('<?php echo esc_url($background_url); ?>');"></div>
        <?php endif; ?>
        
        <div class="cp-creator-content">
            <header class="cp-creator-header">
                <?php if ($avatar): ?>
                    <div class="cp-creator-avatar">
                        <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy"/>
                    </div>
                <?php endif; ?>
                
                <div class="cp-creator-info">
                    <h1 class="cp-creator-name"><?php echo esc_html($name); ?></h1>
                    
                    <?php if ($excerpt || $description): ?>
                        <div class="cp-creator-description">
                            <?php if ($excerpt): ?>
                                <p><?php echo esc_html($excerpt); ?></p>
                            <?php elseif ($description): ?>
                                <div><?php echo wp_kses_post($description); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="cp-creator-stats">
                        <?php if ($age > 0): ?>
                            <div class="cp-creator-stat">
                                <span class="cp-stat-label"><?php _e('Возраст', 'copella-creators'); ?>:</span>
                                <span class="cp-stat-value"><?php echo esc_html($age); ?> <?php _e('лет', 'copella-creators'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($experience > 0): ?>
                            <div class="cp-creator-stat">
                                <span class="cp-stat-label"><?php _e('Опыт', 'copella-creators'); ?>:</span>
                                <span class="cp-stat-value"><?php echo esc_html($experience); ?> <?php _e('лет', 'copella-creators'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($specialization): ?>
                            <div class="cp-creator-stat">
                                <span class="cp-stat-label"><?php _e('Специализация', 'copella-creators'); ?>:</span>
                                <span class="cp-stat-value"><?php echo esc_html($specialization); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <?php if (!empty($achievements)): ?>
                <section class="cp-creator-section cp-creator-achievements">
                    <h2 class="cp-section-title"><?php _e('Достижения', 'copella-creators'); ?></h2>
                    <ul class="cp-achievements-list">
                        <?php foreach ($achievements as $achievement): ?>
                            <li class="cp-achievement-item"><?php echo esc_html($achievement); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!empty($social_networks)): ?>
                <section class="cp-creator-section cp-creator-social">
                    <h2 class="cp-section-title"><?php _e('Социальные сети', 'copella-creators'); ?></h2>
                    <div class="cp-social-links">
                        <?php foreach ($social_networks as $network): ?>
                            <a href="<?php echo esc_url($network['url']); ?>" target="_blank" rel="noopener noreferrer" class="cp-social-link">
                                <?php if ($network['icon']): ?>
                                    <span class="cp-social-icon"><?php echo esc_html($network['icon']); ?></span>
                                <?php endif; ?>
                                <span class="cp-social-name"><?php echo esc_html($network['name']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($playlists)): ?>
                <section class="cp-creator-section cp-creator-playlists">
                    <h2 class="cp-section-title"><?php _e('Плейлисты', 'copella-creators'); ?></h2>
                    <div class="cp-playlists-grid">
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="cp-playlist-item">
                                <a href="<?php echo esc_url(get_permalink($playlist->ID)); ?>" class="cp-playlist-link">
                                    <?php 
                                    $thumb = get_the_post_thumbnail_url($playlist->ID, 'medium');
                                    if ($thumb): 
                                    ?>
                                        <div class="cp-playlist-thumb">
                                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($playlist->post_title); ?>" loading="lazy"/>
                                        </div>
                                    <?php endif; ?>
                                    <div class="cp-playlist-info">
                                        <h3 class="cp-playlist-title"><?php echo esc_html($playlist->post_title); ?></h3>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </section>

    <style>
    .cp-creator-page {
        position: relative;
        background: #171717;
        border-radius: 32px;
        overflow: hidden;
        color: #fff;
        font-family: 'Gilroy-SemiBold', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        margin: 20px 0;
    }
    
    .cp-creator-background {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-size: cover;
        background-position: center;
        filter: blur(20px);
        opacity: 0.3;
        z-index: 1;
    }
    
    .cp-creator-content {
        position: relative;
        z-index: 2;
        padding: 40px;
    }
    
    .cp-creator-header {
        display: flex;
        gap: 30px;
        align-items: flex-start;
        margin-bottom: 40px;
    }
    
    .cp-creator-avatar {
        flex: 0 0 200px;
    }
    
    .cp-creator-avatar img {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    .cp-creator-info {
        flex: 1;
        min-width: 0;
    }
    
    .cp-creator-name {
        margin: 0 0 20px 0;
        font-size: 36px;
        font-weight: 800;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
        line-height: 1.2;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }
    
    .cp-creator-description {
        margin-bottom: 25px;
        font-size: 18px;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .cp-creator-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .cp-creator-stat {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .cp-stat-label {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
    }
    
    .cp-stat-value {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }
    
    .cp-creator-section {
        margin-bottom: 40px;
    }
    
    .cp-section-title {
        margin: 0 0 20px 0;
        font-size: 24px;
        font-weight: 700;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
        color: #fff;
    }
    
    .cp-achievements-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 12px;
    }
    
    .cp-achievement-item {
        padding: 15px 20px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 16px;
        line-height: 1.5;
    }
    
    .cp-social-links {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .cp-social-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #fff;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 16px;
        font-weight: 600;
    }
    
    .cp-social-link:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
        color: #fff;
    }
    
    .cp-social-icon {
        font-size: 20px;
    }
    
    .cp-playlists-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .cp-playlist-item {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    
    .cp-playlist-item:hover {
        background: rgba(255, 255, 255, 0.12);
        transform: translateY(-4px);
    }
    
    .cp-playlist-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }
    
    .cp-playlist-thumb {
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
    }
    
    .cp-playlist-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .cp-playlist-item:hover .cp-playlist-thumb img {
        transform: scale(1.05);
    }
    
    .cp-playlist-info {
        padding: 20px;
    }
    
    .cp-playlist-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
        color: #fff;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .cp-creator-content {
            padding: 20px;
        }
        
        .cp-creator-header {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        
        .cp-creator-avatar {
            flex: none;
            align-self: center;
        }
        
        .cp-creator-avatar img {
            width: 150px;
            height: 150px;
        }
        
        .cp-creator-name {
            font-size: 28px;
        }
        
        .cp-creator-description {
            font-size: 16px;
        }
        
        .cp-creator-stats {
            justify-content: center;
        }
        
        .cp-playlists-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .cp-creator-content {
            padding: 15px;
        }
        
        .cp-creator-avatar img {
            width: 120px;
            height: 120px;
        }
        
        .cp-creator-name {
            font-size: 24px;
        }
        
        .cp-social-links {
            flex-direction: column;
        }
        
        .cp-social-link {
            justify-content: center;
        }
    }
    </style>
    
    <?php
    return ob_get_clean();
});