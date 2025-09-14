<?php
/**
 * Расширенный интерфейс админки для креаторов
 * Улучшенное управление креаторами с дополнительными возможностями
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Добавление дополнительных пунктов меню
 */
add_action('admin_menu', function(): void {
    // Подменю для управления креаторами
    add_submenu_page(
        'copella-creators',
        __('Все креаторы', 'copella-creators'),
        __('Все креаторы', 'copella-creators'),
        'manage_options',
        'edit.php?post_type=creator'
    );
    
    add_submenu_page(
        'copella-creators',
        __('Категории креаторов', 'copella-creators'),
        __('Категории креаторов', 'copella-creators'),
        'manage_options',
        'edit-tags.php?taxonomy=creator_category&post_type=creator'
    );
    
    add_submenu_page(
        'copella-creators',
        __('Настройки интеграции', 'copella-creators'),
        __('Интеграция', 'copella-creators'),
        'manage_options',
        'copella-creators-integration',
        'copella_creators_integration_page'
    );
});

/**
 * Страница настроек интеграции
 */
function copella_creators_integration_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Обработка сохранения настроек
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'copella_creators_integration')) {
        $settings = array(
            'auto_link_authors' => isset($_POST['auto_link_authors']) ? 1 : 0,
            'show_creator_in_topics' => isset($_POST['show_creator_in_topics']) ? 1 : 0,
            'default_creator_category' => sanitize_text_field($_POST['default_creator_category']),
        );
        update_option('copella_creators_integration_settings', $settings);
        echo '<div class="notice notice-success"><p>' . __('Настройки сохранены', 'copella-creators') . '</p></div>';
    }
    
    $settings = get_option('copella_creators_integration_settings', array());
    $auto_link_authors = $settings['auto_link_authors'] ?? 0;
    $show_creator_in_topics = $settings['show_creator_in_topics'] ?? 0;
    $default_category = $settings['default_creator_category'] ?? '';
    
    // Получаем категории креаторов
    $categories = get_terms(array(
        'taxonomy' => 'creator_category',
        'hide_empty' => false,
    ));
    ?>
    <div class="wrap copella-creators-integration">
        <h1><?php _e('Настройки интеграции', 'copella-creators'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('copella_creators_integration'); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="auto_link_authors"><?php _e('Автоматическая связь', 'copella-creators'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="auto_link_authors">
                                    <input type="checkbox" id="auto_link_authors" name="auto_link_authors" value="1" <?php checked($auto_link_authors); ?> />
                                    <?php _e('Автоматически связывать новых авторов Topics Plugin с креаторами по имени', 'copella-creators'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Если включено, система будет автоматически искать креаторов с похожими именами при создании авторов в Topics Plugin.', 'copella-creators'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="show_creator_in_topics"><?php _e('Отображение в Topics', 'copella-creators'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="show_creator_in_topics">
                                    <input type="checkbox" id="show_creator_in_topics" name="show_creator_in_topics" value="1" <?php checked($show_creator_in_topics); ?> />
                                    <?php _e('Показывать информацию о креаторе в админке Topics Plugin', 'copella-creators'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Добавляет колонку с информацией о связанном креаторе в списке авторов Topics Plugin.', 'copella-creators'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_creator_category"><?php _e('Категория по умолчанию', 'copella-creators'); ?></label>
                        </th>
                        <td>
                            <select id="default_creator_category" name="default_creator_category" class="regular-text">
                                <option value=""><?php _e('Не выбрана', 'copella-creators'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($default_category, $category->slug); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Категория, которая будет автоматически назначаться новым креаторам.', 'copella-creators'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2><?php _e('Статистика', 'copella-creators'); ?></h2>
        <div class="copella-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <?php
            $creators_count = wp_count_posts('creator');
            $topics_authors_count = wp_count_posts('topic_author');
            $playlists_count = wp_count_posts('topic_playlist');
            $categories_count = wp_count_terms('creator_category');
            ?>
            
            <div class="stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo $creators_count->publish; ?></h3>
                <p style="margin: 0; color: #666;"><?php _e('Креаторов', 'copella-creators'); ?></p>
            </div>
            
            <div class="stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo $topics_authors_count->publish; ?></h3>
                <p style="margin: 0; color: #666;"><?php _e('Авторов Topics', 'copella-creators'); ?></p>
            </div>
            
            <div class="stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo $playlists_count->publish; ?></h3>
                <p style="margin: 0; color: #666;"><?php _e('Плейлистов', 'copella-creators'); ?></p>
            </div>
            
            <div class="stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo $categories_count; ?></h3>
                <p style="margin: 0; color: #666;"><?php _e('Категорий', 'copella-creators'); ?></p>
            </div>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <h2><?php _e('Полезные шорткоды', 'copella-creators'); ?></h2>
        <div class="shortcode-help" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
            <h3><?php _e('Полноценные страницы', 'copella-creators'); ?></h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <strong><?php _e('Страница креатора:', 'copella-creators'); ?></strong>
                    <span style="margin-left: 10px; color: #666;"><?php _e('Автоматически создается при публикации креатора', 'copella-creators'); ?></span>
                </li>
                <li style="margin-bottom: 10px;">
                    <strong><?php _e('Архив креаторов:', 'copella-creators'); ?></strong>
                    <span style="margin-left: 10px; color: #666;"><?php _e('Список всех креаторов по адресу /creators/', 'copella-creators'); ?></span>
                </li>
            </ul>
            
            <h3><?php _e('Шорткоды', 'copella-creators'); ?></h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-family: monospace;">[creator_card id="123"]</code>
                    <span style="margin-left: 10px; color: #666;"><?php _e('Карточка креатора с кнопкой копирования', 'copella-creators'); ?></span>
                </li>
                <li style="margin-bottom: 10px;">
                    <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-family: monospace;">[creators_list category="gaming" limit="6"]</code>
                    <span style="margin-left: 10px; color: #666;"><?php _e('Список креаторов', 'copella-creators'); ?></span>
                </li>
                <li style="margin-bottom: 10px;">
                    <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-family: monospace;">[creator_projects id="123" type="video" limit="4"]</code>
                    <span style="margin-left: 10px; color: #666;"><?php _e('Проекты креатора', 'copella-creators'); ?></span>
                </li>
            </ul>
            
            <h3><?php _e('Параметры шорткодов', 'copella-creators'); ?></h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 8px;">
                    <strong>id</strong> - <?php _e('ID креатора', 'copella-creators'); ?>
                </li>
                <li style="margin-bottom: 8px;">
                    <strong>slug</strong> - <?php _e('Слаг креатора', 'copella-creators'); ?>
                </li>
                <li style="margin-bottom: 8px;">
                    <strong>category</strong> - <?php _e('Категория креаторов', 'copella-creators'); ?>
                </li>
                <li style="margin-bottom: 8px;">
                    <strong>limit</strong> - <?php _e('Количество элементов', 'copella-creators'); ?>
                </li>
                <li style="margin-bottom: 8px;">
                    <strong>style</strong> - <?php _e('Стиль отображения (default, compact, minimal)', 'copella-creators'); ?>
                </li>
                <li style="margin-bottom: 8px;">
                    <strong>type</strong> - <?php _e('Тип проектов (all, video, stream)', 'copella-creators'); ?>
                </li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Добавление быстрых действий в админ-список креаторов
 */
add_filter('post_row_actions', function($actions, $post) {
    if ($post->post_type === 'creator') {
        $actions['view_page'] = '<a href="' . esc_url(get_permalink($post->ID)) . '" target="_blank">' . __('Просмотреть страницу', 'copella-creators') . '</a>';
        $actions['copy_shortcode'] = '<a href="#" onclick="copyCreatorShortcode(' . $post->ID . '); return false;">' . __('Скопировать шорткод', 'copella-creators') . '</a>';
    }
    return $actions;
}, 10, 2);

/**
 * Добавление скрипта для копирования шорткодов в админке
 */
add_action('admin_footer', function() {
    if (get_current_screen()->post_type === 'creator') {
        ?>
        <script>
        function copyCreatorShortcode(creatorId) {
            var shortcode = '[creator_card id="' + creatorId + '"]';
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    alert('<?php _e('Шорткод скопирован в буфер обмена!', 'copella-creators'); ?>');
                });
            } else {
                var textArea = document.createElement('textarea');
                textArea.value = shortcode;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('<?php _e('Шорткод скопирован в буфер обмена!', 'copella-creators'); ?>');
            }
        }
        </script>
        <?php
    }
});

/**
 * Добавление мета-бокса с информацией о шорткодах
 */
add_action('add_meta_boxes', function(): void {
    add_meta_box(
        'copella_creator_shortcodes',
        __('Шорткоды', 'copella-creators'),
        'copella_creator_shortcodes_meta_box',
        'creator',
        'side',
        'default'
    );
});

/**
 * Мета-бокс с информацией о шорткодах
 */
function copella_creator_shortcodes_meta_box(WP_Post $post): void {
    $creator_id = $post->ID;
    ?>
    <div class="copella-shortcodes-info">
        <p><strong><?php _e('Основные шорткоды:', 'copella-creators'); ?></strong></p>
        
        <div style="margin-bottom: 15px;">
            <label><?php _e('Полноценная страница:', 'copella-creators'); ?></label>
            <input type="text" readonly value="<?php echo esc_url(get_permalink($creator_id)); ?>" 
                   style="width: 100%; margin-top: 5px; font-family: monospace; font-size: 12px;" 
                   onclick="this.select();" />
            <small style="color: #666;"><?php _e('Автоматически создается при публикации', 'copella-creators'); ?></small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label><?php _e('Карточка креатора:', 'copella-creators'); ?></label>
            <input type="text" readonly value='[creator_card id="<?php echo $creator_id; ?>"]' 
                   style="width: 100%; margin-top: 5px; font-family: monospace; font-size: 12px;" 
                   onclick="this.select();" />
        </div>
        
        <div style="margin-bottom: 15px;">
            <label><?php _e('Проекты креатора:', 'copella-creators'); ?></label>
            <input type="text" readonly value='[creator_projects id="<?php echo $creator_id; ?>"]' 
                   style="width: 100%; margin-top: 5px; font-family: monospace; font-size: 12px;" 
                   onclick="this.select();" />
        </div>
        
        <p style="font-size: 12px; color: #666; margin-top: 15px;">
            <?php _e('Кликните на поле, чтобы выделить шорткод для копирования.', 'copella-creators'); ?>
        </p>
        
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
            <strong><?php _e('Дополнительные параметры:', 'copella-creators'); ?></strong><br>
            <small>
                • <code>show_social="true/false"</code><br>
                • <code>show_achievements="true/false"</code><br>
                • <code>show_playlists="true/false"</code><br>
                • <code>style="default/compact/minimal"</code><br>
                • <code>limit="6"</code>
            </small>
        </div>
    </div>
    <?php
}

/**
 * Добавление уведомлений в админку
 */
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'creator') {
        $creators_count = wp_count_posts('creator');
        if ($creators_count->publish === 0) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Добро пожаловать в систему креаторов!', 'copella-creators'); ?></strong><br>
                    <?php _e('Создайте своего первого креатора, чтобы начать использовать все возможности плагина.', 'copella-creators'); ?>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=creator')); ?>" class="button button-primary" style="margin-left: 10px;">
                        <?php _e('Создать креатора', 'copella-creators'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
});

/**
 * Автоматическое создание категорий по умолчанию
 */
add_action('init', function() {
    $default_categories = array(
        'gaming' => __('Гейминг', 'copella-creators'),
        'education' => __('Образование', 'copella-creators'),
        'entertainment' => __('Развлечения', 'copella-creators'),
        'tech' => __('Технологии', 'copella-creators'),
        'lifestyle' => __('Образ жизни', 'copella-creators'),
    );
    
    foreach ($default_categories as $slug => $name) {
        if (!term_exists($slug, 'creator_category')) {
            wp_insert_term($name, 'creator_category', array('slug' => $slug));
        }
    }
}, 20);