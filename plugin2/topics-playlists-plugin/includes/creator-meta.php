<?php
/**
 * Мета-поля для креаторов
 * Расширенная информация о креаторах
 */

if (!defined('ABSPATH')) {
    exit;
}

// Константы для мета-полей креаторов
const COPELLA_CREATOR_META_SOCIAL = '_copella_creator_social';
const COPELLA_CREATOR_META_AGE = '_copella_creator_age';
const COPELLA_CREATOR_META_EXPERIENCE = '_copella_creator_experience';
const COPELLA_CREATOR_META_ACHIEVEMENTS = '_copella_creator_achievements';
const COPELLA_CREATOR_META_SPECIALIZATION = '_copella_creator_specialization';
const COPELLA_CREATOR_META_PLAYLISTS = '_copella_creator_playlists';

/**
 * Добавление мета-боксов для креаторов
 */
add_action('add_meta_boxes', function(): void {
    add_meta_box(
        'copella_creator_social',
        __('Социальные сети', 'copella-topics'),
        'copella_creator_social_meta_box',
        'creator',
        'normal',
        'default'
    );

    add_meta_box(
        'copella_creator_details',
        __('Детали креатора', 'copella-topics'),
        'copella_creator_details_meta_box',
        'creator',
        'normal',
        'default'
    );

    add_meta_box(
        'copella_creator_playlists',
        __('Привязанные плейлисты', 'copella-topics'),
        'copella_creator_playlists_meta_box',
        'creator',
        'side',
        'default'
    );

    add_meta_box(
        'copella_creator_shortcodes',
        __('Шорткоды', 'copella-topics'),
        'copella_creator_shortcodes_meta_box',
        'creator',
        'side',
        'default'
    );
});

/**
 * Мета-бокс для социальных сетей
 */
function copella_creator_social_meta_box(WP_Post $post): void {
    $social_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_SOCIAL, true);
    ?>
    <p>
        <label for="copella_creator_social"><strong><?php _e('Социальные сети (по одной в строке)', 'copella-topics'); ?></strong></label><br/>
        <textarea id="copella_creator_social" name="copella_creator_social" class="widefat" rows="6" placeholder="YouTube | https://youtube.com/@username | 🎥&#10;Telegram | https://t.me/username | 📱&#10;Instagram | https://instagram.com/username | 📸&#10;Twitter | https://twitter.com/username | 🐦&#10;TikTok | https://tiktok.com/@username | 🎵&#10;Twitch | https://twitch.tv/username | 🎮"><?php echo esc_textarea($social_raw); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Формат: Название | Ссылка | Иконка (эмодзи или текст). Иконка необязательна.', 'copella-topics'); ?>
    </p>
    <?php
}

/**
 * Мета-бокс для деталей креатора
 */
function copella_creator_details_meta_box(WP_Post $post): void {
    $age = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_AGE, true);
    $experience = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_EXPERIENCE, true);
    $achievements = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_ACHIEVEMENTS, true);
    $specialization = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_SPECIALIZATION, true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="copella_creator_age"><?php _e('Возраст', 'copella-topics'); ?></label>
            </th>
            <td>
                <input type="number" id="copella_creator_age" name="copella_creator_age" value="<?php echo esc_attr($age); ?>" class="regular-text" min="1" max="120" />
                <p class="description"><?php _e('Возраст креатора в годах', 'copella-topics'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="copella_creator_experience"><?php _e('Опыт работы', 'copella-topics'); ?></label>
            </th>
            <td>
                <input type="text" id="copella_creator_experience" name="copella_creator_experience" value="<?php echo esc_attr($experience); ?>" class="regular-text" placeholder="5 лет в создании контента" />
                <p class="description"><?php _e('Опыт работы креатора', 'copella-topics'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="copella_creator_specialization"><?php _e('Специализация', 'copella-topics'); ?></label>
            </th>
            <td>
                <input type="text" id="copella_creator_specialization" name="copella_creator_specialization" value="<?php echo esc_attr($specialization); ?>" class="regular-text" placeholder="Гейминг, Обзоры, Образование" />
                <p class="description"><?php _e('В чем специализируется креатор', 'copella-topics'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="copella_creator_achievements"><?php _e('Достижения и заслуги', 'copella-topics'); ?></label>
            </th>
            <td>
                <textarea id="copella_creator_achievements" name="copella_creator_achievements" class="widefat" rows="4" placeholder="🏆 Победитель конкурса 'Лучший геймер года'&#10;📺 1M+ подписчиков на YouTube&#10;🎮 Профессиональный киберспортсмен"><?php echo esc_textarea($achievements); ?></textarea>
                <p class="description"><?php _e('Достижения и заслуги креатора (по одному в строке)', 'copella-topics'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Мета-бокс для привязанных плейлистов
 */
function copella_creator_playlists_meta_box(WP_Post $post): void {
    $playlists_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_PLAYLISTS, true);
    $playlists = $playlists_raw ? explode(',', $playlists_raw) : array();
    
    // Получаем все доступные плейлисты
    $available_playlists = get_posts(array(
        'post_type' => 'topic_playlist',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <p>
        <label for="copella_creator_playlists"><strong><?php _e('Выберите плейлисты', 'copella-topics'); ?></strong></label>
    </p>
    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
        <?php foreach ($available_playlists as $playlist): ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" name="copella_creator_playlists[]" value="<?php echo esc_attr($playlist->ID); ?>" 
                       <?php checked(in_array($playlist->ID, $playlists)); ?> />
                <?php echo esc_html($playlist->post_title); ?>
            </label>
        <?php endforeach; ?>
    </div>
    <p class="description"><?php _e('Выберите плейлисты, которые будут отображаться на странице креатора', 'copella-topics'); ?></p>
    <?php
}

/**
 * Мета-бокс с информацией о шорткодах
 */
function copella_creator_shortcodes_meta_box(WP_Post $post): void {
    $creator_id = $post->ID;
    ?>
    <div class="copella-shortcodes-info">
        <p><strong><?php _e('Основные шорткоды:', 'copella-topics'); ?></strong></p>
        
        <div style="margin-bottom: 15px;">
            <label><?php _e('Полноценная страница:', 'copella-topics'); ?></label>
            <input type="text" readonly value="<?php echo esc_url(get_permalink($creator_id)); ?>" 
                   style="width: 100%; margin-top: 5px; font-family: monospace; font-size: 12px;" 
                   onclick="this.select();" />
            <small style="color: #666;"><?php _e('Автоматически создается при публикации', 'copella-topics'); ?></small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label><?php _e('Карточка креатора:', 'copella-topics'); ?></label>
            <input type="text" readonly value='[creator_card id="<?php echo $creator_id; ?>"]' 
                   style="width: 100%; margin-top: 5px; font-family: monospace; font-size: 12px;" 
                   onclick="this.select();" />
        </div>
        
        <div style="margin-bottom: 15px;">
            <label><?php _e('Проекты креатора:', 'copella-topics'); ?></label>
            <input type="text" readonly value='[creator_projects id="<?php echo $creator_id; ?>"]' 
                   style="width: 100%; margin-top: 5px; font-family: monospace; font-size: 12px;" 
                   onclick="this.select();" />
        </div>
        
        <p style="font-size: 12px; color: #666; margin-top: 15px;">
            <?php _e('Кликните на поле, чтобы выделить шорткод для копирования.', 'copella-topics'); ?>
        </p>
        
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
            <strong><?php _e('Дополнительные параметры:', 'copella-topics'); ?></strong><br>
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
 * Сохранение мета-данных креатора
 */
add_action('save_post_creator', function(int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Сохранение социальных сетей
    if (isset($_POST['copella_creator_social'])) {
        $raw = (string) $_POST['copella_creator_social'];
        update_post_meta($post_id, COPELLA_CREATOR_META_SOCIAL, $raw);
    }

    // Сохранение деталей
    if (isset($_POST['copella_creator_age'])) {
        $age = (int) $_POST['copella_creator_age'];
        update_post_meta($post_id, COPELLA_CREATOR_META_AGE, $age);
    }

    if (isset($_POST['copella_creator_experience'])) {
        $experience = sanitize_text_field($_POST['copella_creator_experience']);
        update_post_meta($post_id, COPELLA_CREATOR_META_EXPERIENCE, $experience);
    }

    if (isset($_POST['copella_creator_achievements'])) {
        $achievements = sanitize_textarea_field($_POST['copella_creator_achievements']);
        update_post_meta($post_id, COPELLA_CREATOR_META_ACHIEVEMENTS, $achievements);
    }

    if (isset($_POST['copella_creator_specialization'])) {
        $specialization = sanitize_text_field($_POST['copella_creator_specialization']);
        update_post_meta($post_id, COPELLA_CREATOR_META_SPECIALIZATION, $specialization);
    }

    // Сохранение плейлистов
    if (isset($_POST['copella_creator_playlists'])) {
        $playlists = array_map('intval', $_POST['copella_creator_playlists']);
        $playlists_raw = implode(',', $playlists);
        update_post_meta($post_id, COPELLA_CREATOR_META_PLAYLISTS, $playlists_raw);
    } else {
        update_post_meta($post_id, COPELLA_CREATOR_META_PLAYLISTS, '');
    }
});

/**
 * Добавление колонок в админ-список креаторов
 */
add_filter('manage_creator_posts_columns', function($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['creator_avatar'] = __('Аватар', 'copella-topics');
    $new_columns['creator_age'] = __('Возраст', 'copella-topics');
    $new_columns['creator_specialization'] = __('Специализация', 'copella-topics');
    $new_columns['creator_category'] = __('Категория', 'copella-topics');
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
});

add_action('manage_creator_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case 'creator_avatar':
            $thumbnail = get_the_post_thumbnail($post_id, array(40, 40));
            echo $thumbnail ?: '<div style="width:40px;height:40px;background:#ddd;border-radius:50%;"></div>';
            break;
        case 'creator_age':
            $age = get_post_meta($post_id, COPELLA_CREATOR_META_AGE, true);
            echo $age ? esc_html($age) . ' ' . __('лет', 'copella-topics') : '—';
            break;
        case 'creator_specialization':
            $specialization = get_post_meta($post_id, COPELLA_CREATOR_META_SPECIALIZATION, true);
            echo $specialization ? esc_html($specialization) : '—';
            break;
        case 'creator_category':
            $terms = get_the_terms($post_id, 'creator_category');
            if ($terms && !is_wp_error($terms)) {
                $term_names = array_map(function($term) { return $term->name; }, $terms);
                echo esc_html(implode(', ', $term_names));
            } else {
                echo '—';
            }
            break;
    }
}, 10, 2);

/**
 * Функция для получения социальных сетей креатора
 */
function copella_get_creator_social_networks($creator_id): array {
    $social_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SOCIAL, true);
    $social_networks = array();
    
    if ($social_raw !== '') {
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
    
    return $social_networks;
}

/**
 * Функция для получения достижений креатора
 */
function copella_get_creator_achievements($creator_id): array {
    $achievements_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_ACHIEVEMENTS, true);
    $achievements = array();
    
    if ($achievements_raw !== '') {
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $achievements_raw)));
        foreach ($lines as $line) {
            if (!empty($line)) {
                $achievements[] = sanitize_text_field($line);
            }
        }
    }
    
    return $achievements;
}

/**
 * Функция для получения привязанных плейлистов
 */
function copella_get_creator_playlists($creator_id): array {
    $playlists_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_PLAYLISTS, true);
    $playlist_ids = $playlists_raw ? array_map('intval', explode(',', $playlists_raw)) : array();
    
    if (empty($playlist_ids)) {
        return array();
    }
    
    return get_posts(array(
        'post_type' => 'topic_playlist',
        'post__in' => $playlist_ids,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post__in',
    ));
}

/**
 * Автоматическое создание категорий по умолчанию
 */
add_action('init', function() {
    $default_categories = array(
        'gaming' => __('Гейминг', 'copella-topics'),
        'education' => __('Образование', 'copella-topics'),
        'entertainment' => __('Развлечения', 'copella-topics'),
        'tech' => __('Технологии', 'copella-topics'),
        'lifestyle' => __('Образ жизни', 'copella-topics'),
    );
    
    foreach ($default_categories as $slug => $name) {
        if (!term_exists($slug, 'creator_category')) {
            wp_insert_term($name, 'creator_category', array('slug' => $slug));
        }
    }
}, 20);