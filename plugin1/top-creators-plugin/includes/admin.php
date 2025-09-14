<?php
if (!defined('ABSPATH')) {
    exit;
}

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