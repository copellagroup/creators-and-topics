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