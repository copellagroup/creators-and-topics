<?php
/**
 * Custom Post Type для креаторов
 * Расширенная система страниц креаторов с интеграцией с topics plugin
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
const COPELLA_CREATOR_META_PROJECTS = '_copella_creator_projects';

/**
 * Регистрация Custom Post Type для креаторов
 */
function copella_creators_register_cpt(): void
{
    $labels = array(
        'name'               => __('Креаторы', 'copella-creators'),
        'singular_name'      => __('Креатор', 'copella-creators'),
        'menu_name'          => __('Креаторы', 'copella-creators'),
        'name_admin_bar'     => __('Креатор', 'copella-creators'),
        'add_new'            => __('Добавить креатора', 'copella-creators'),
        'add_new_item'       => __('Добавить нового креатора', 'copella-creators'),
        'new_item'           => __('Новый креатор', 'copella-creators'),
        'edit_item'          => __('Редактировать креатора', 'copella-creators'),
        'view_item'          => __('Просмотреть креатора', 'copella-creators'),
        'all_items'          => __('Все креаторы', 'copella-creators'),
        'search_items'       => __('Искать креаторов', 'copella-creators'),
        'parent_item_colon'  => __('Родительские креаторы:', 'copella-creators'),
        'not_found'          => __('Креаторов не найдено.', 'copella-creators'),
        'not_found_in_trash' => __('В корзине креаторов не найдено.', 'copella-creators'),
    );

    // Поддержка заголовка, миниатюры, редактора и excerpt
    $supports = array('title', 'thumbnail', 'editor', 'excerpt');

    register_post_type('creator', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'creators'),
        'menu_icon' => 'dashicons-groups',
        'supports' => $supports,
        'show_in_rest' => true,
        'show_in_menu' => true,
        'menu_position' => 29, // После существующего меню креаторов
        'capability_type' => 'post',
        'hierarchical' => false,
        'publicly_queryable' => true,
        'query_var' => true,
        'can_export' => true,
    ));

    // Регистрация таксономии для категорий креаторов
    $cat_labels = array(
        'name'              => __('Категории креаторов', 'copella-creators'),
        'singular_name'     => __('Категория креатора', 'copella-creators'),
        'menu_name'         => __('Категории креаторов', 'copella-creators'),
        'search_items'      => __('Поиск категорий', 'copella-creators'),
        'all_items'         => __('Все категории', 'copella-creators'),
        'parent_item'       => __('Родительская категория', 'copella-creators'),
        'parent_item_colon' => __('Родительская категория:', 'copella-creators'),
        'edit_item'         => __('Редактировать категорию', 'copella-creators'),
        'update_item'       => __('Обновить категорию', 'copella-creators'),
        'add_new_item'      => __('Добавить новую категорию', 'copella-creators'),
        'new_item_name'     => __('Название новой категории', 'copella-creators'),
    );
    
    register_taxonomy('creator_category', 'creator', array(
        'hierarchical'      => true,
        'labels'            => $cat_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'show_in_rest'      => true,
        'rewrite'           => array('slug' => 'creator-category'),
    ));
}

add_action('init', 'copella_creators_register_cpt');

/**
 * Добавление мета-боксов для креаторов
 */
add_action('add_meta_boxes', function(): void {
    add_meta_box(
        'copella_creator_social',
        __('Социальные сети', 'copella-creators'),
        'copella_creator_social_meta_box',
        'creator',
        'normal',
        'default'
    );

    add_meta_box(
        'copella_creator_details',
        __('Детали креатора', 'copella-creators'),
        'copella_creator_details_meta_box',
        'creator',
        'normal',
        'default'
    );

    add_meta_box(
        'copella_creator_playlists',
        __('Привязанные плейлисты', 'copella-creators'),
        'copella_creator_playlists_meta_box',
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
        <label for="copella_creator_social"><strong><?php _e('Социальные сети (по одной в строке)', 'copella-creators'); ?></strong></label><br/>
        <textarea id="copella_creator_social" name="copella_creator_social" class="widefat" rows="6" placeholder="YouTube | https://youtube.com/@username | 🎥&#10;Telegram | https://t.me/username | 📱&#10;Instagram | https://instagram.com/username | 📸&#10;Twitter | https://twitter.com/username | 🐦&#10;TikTok | https://tiktok.com/@username | 🎵&#10;Twitch | https://twitch.tv/username | 🎮"><?php echo esc_textarea($social_raw); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Формат: Название | Ссылка | Иконка (эмодзи или текст). Иконка необязательна.', 'copella-creators'); ?>
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
                <label for="copella_creator_age"><?php _e('Возраст', 'copella-creators'); ?></label>
            </th>
            <td>
                <input type="number" id="copella_creator_age" name="copella_creator_age" value="<?php echo esc_attr($age); ?>" class="regular-text" min="1" max="120" />
                <p class="description"><?php _e('Возраст креатора в годах', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="copella_creator_experience"><?php _e('Опыт работы', 'copella-creators'); ?></label>
            </th>
            <td>
                <input type="text" id="copella_creator_experience" name="copella_creator_experience" value="<?php echo esc_attr($experience); ?>" class="regular-text" placeholder="5 лет в создании контента" />
                <p class="description"><?php _e('Опыт работы креатора', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="copella_creator_specialization"><?php _e('Специализация', 'copella-creators'); ?></label>
            </th>
            <td>
                <input type="text" id="copella_creator_specialization" name="copella_creator_specialization" value="<?php echo esc_attr($specialization); ?>" class="regular-text" placeholder="Гейминг, Обзоры, Образование" />
                <p class="description"><?php _e('В чем специализируется креатор', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="copella_creator_achievements"><?php _e('Достижения и заслуги', 'copella-creators'); ?></label>
            </th>
            <td>
                <textarea id="copella_creator_achievements" name="copella_creator_achievements" class="widefat" rows="4" placeholder="🏆 Победитель конкурса 'Лучший геймер года'&#10;📺 1M+ подписчиков на YouTube&#10;🎮 Профессиональный киберспортсмен"><?php echo esc_textarea($achievements); ?></textarea>
                <p class="description"><?php _e('Достижения и заслуги креатора (по одному в строке)', 'copella-creators'); ?></p>
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
    
    // Получаем все доступные плейлисты из topics plugin
    $available_playlists = get_posts(array(
        'post_type' => 'topic_playlist',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <p>
        <label for="copella_creator_playlists"><strong><?php _e('Выберите плейлисты', 'copella-creators'); ?></strong></label>
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
    <p class="description"><?php _e('Выберите плейлисты, которые будут отображаться на странице креатора', 'copella-creators'); ?></p>
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
    $new_columns['creator_avatar'] = __('Аватар', 'copella-creators');
    $new_columns['creator_age'] = __('Возраст', 'copella-creators');
    $new_columns['creator_specialization'] = __('Специализация', 'copella-creators');
    $new_columns['creator_category'] = __('Категория', 'copella-creators');
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
            echo $age ? esc_html($age) . ' ' . __('лет', 'copella-creators') : '—';
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
 * Сортировка колонок
 */
add_filter('manage_edit-creator_sortable_columns', function($columns) {
    $columns['creator_age'] = 'creator_age';
    $columns['creator_specialization'] = 'creator_specialization';
    return $columns;
});

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