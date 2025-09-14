<?php
/**
 * Интеграция с плагином Topics & Playlists
 * Связывание креаторов с плейлистами и проектами
 */

if (!defined('ABSPATH')) {
    exit;
}

// Константы для связи с topics plugin
const COPELLA_CREATOR_META_TOPICS_AUTHORS = '_copella_creator_topics_authors';

/**
 * Добавление мета-бокса для связи с авторами из topics plugin
 */
add_action('add_meta_boxes', function(): void {
    add_meta_box(
        'copella_creator_topics_integration',
        __('Интеграция с Topics Plugin', 'copella-creators'),
        'copella_creator_topics_integration_meta_box',
        'creator',
        'side',
        'default'
    );
});

/**
 * Мета-бокс для интеграции с topics plugin
 */
function copella_creator_topics_integration_meta_box(WP_Post $post): void {
    $topics_authors_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_TOPICS_AUTHORS, true);
    $topics_authors = $topics_authors_raw ? explode(',', $topics_authors_raw) : array();
    
    // Получаем всех авторов из topics plugin
    $available_authors = get_posts(array(
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <p>
        <label for="copella_creator_topics_authors"><strong><?php _e('Связать с авторами Topics Plugin', 'copella-creators'); ?></strong></label>
    </p>
    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
        <?php foreach ($available_authors as $author): ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" name="copella_creator_topics_authors[]" value="<?php echo esc_attr($author->ID); ?>" 
                       <?php checked(in_array($author->ID, $topics_authors)); ?> />
                <?php echo esc_html($author->post_title); ?>
                <?php 
                $author_type = (string) get_post_meta($author->ID, COPELLA_AUTHOR_META_TYPE, true);
                if ($author_type): 
                ?>
                    <span style="color: #666; font-size: 12px;">
                        (<?php echo $author_type === 'stream' ? __('Стрим', 'copella-creators') : __('Видео', 'copella-creators'); ?>)
                    </span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <p class="description">
        <?php _e('Выберите авторов из Topics Plugin, которые будут связаны с этим креатором. Это позволит отображать их проекты на странице креатора.', 'copella-creators'); ?>
    </p>
    <?php
}

/**
 * Сохранение связи с topics authors
 */
add_action('save_post_creator', function(int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Сохранение связи с topics authors
    if (isset($_POST['copella_creator_topics_authors'])) {
        $topics_authors = array_map('intval', $_POST['copella_creator_topics_authors']);
        $topics_authors_raw = implode(',', $topics_authors);
        update_post_meta($post_id, COPELLA_CREATOR_META_TOPICS_AUTHORS, $topics_authors_raw);
        
        // Обновляем обратную связь - добавляем creator_id к каждому связанному author
        foreach ($topics_authors as $author_id) {
            update_post_meta($author_id, '_copella_author_creator_id', $post_id);
        }
    } else {
        update_post_meta($post_id, COPELLA_CREATOR_META_TOPICS_AUTHORS, '');
    }
});

/**
 * Функция для получения связанных авторов из topics plugin
 */
function copella_get_creator_topics_authors($creator_id): array {
    $topics_authors_raw = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_TOPICS_AUTHORS, true);
    $topics_authors_ids = $topics_authors_raw ? array_map('intval', explode(',', $topics_authors_raw)) : array();
    
    if (empty($topics_authors_ids)) {
        return array();
    }
    
    return get_posts(array(
        'post_type' => 'topic_author',
        'post__in' => $topics_authors_ids,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post__in',
    ));
}

/**
 * Функция для получения всех проектов креатора (включая связанные из topics)
 */
function copella_get_creator_all_projects($creator_id): array {
    $projects = array();
    
    // Получаем проекты напрямую связанные с креатором
    $direct_projects = get_posts(array(
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'meta_key' => '_copella_author_creator_id',
        'meta_value' => $creator_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    
    $projects = array_merge($projects, $direct_projects);
    
    // Получаем проекты через связанных авторов
    $topics_authors = copella_get_creator_topics_authors($creator_id);
    foreach ($topics_authors as $author) {
        $author_projects = get_posts(array(
            'post_type' => 'topic_author',
            'post_status' => 'publish',
            'meta_key' => '_copella_author_creator_id',
            'meta_value' => $author->ID,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        $projects = array_merge($projects, $author_projects);
    }
    
    // Убираем дубликаты
    $unique_projects = array();
    $seen_ids = array();
    foreach ($projects as $project) {
        if (!in_array($project->ID, $seen_ids)) {
            $unique_projects[] = $project;
            $seen_ids[] = $project->ID;
        }
    }
    
    return $unique_projects;
}

/**
 * Добавление колонки "Связанные проекты" в админ-список креаторов
 */
add_filter('manage_creator_posts_columns', function($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'creator_category') {
            $new_columns['creator_projects'] = __('Проекты', 'copella-creators');
        }
    }
    return $new_columns;
});

add_action('manage_creator_posts_custom_column', function($column, $post_id) {
    if ($column === 'creator_projects') {
        $projects = copella_get_creator_all_projects($post_id);
        $count = count($projects);
        if ($count > 0) {
            echo '<a href="' . esc_url(admin_url('edit.php?post_type=topic_author&meta_key=_copella_author_creator_id&meta_value=' . $post_id)) . '">';
            echo esc_html($count) . ' ' . _n('проект', 'проекта', $count, 'copella-creators');
            echo '</a>';
        } else {
            echo '—';
        }
    }
}, 10, 2);

/**
 * Добавление фильтра по креаторам в админ-список авторов topics plugin
 */
add_action('restrict_manage_posts', function() {
    global $typenow;
    if ($typenow === 'topic_author') {
        $creators = get_posts(array(
            'post_type' => 'creator',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        if (!empty($creators)) {
            echo '<select name="creator_filter">';
            echo '<option value="">' . __('Все креаторы', 'copella-creators') . '</option>';
            foreach ($creators as $creator) {
                $selected = isset($_GET['creator_filter']) ? selected($_GET['creator_filter'], $creator->ID, false) : '';
                echo '<option value="' . esc_attr($creator->ID) . '"' . $selected . '>' . esc_html($creator->post_title) . '</option>';
            }
            echo '</select>';
        }
    }
});

/**
 * Фильтрация авторов по креаторам
 */
add_filter('parse_query', function($query) {
    global $pagenow, $typenow;
    
    if ($pagenow === 'edit.php' && $typenow === 'topic_author' && isset($_GET['creator_filter']) && !empty($_GET['creator_filter'])) {
        $creator_id = (int) $_GET['creator_filter'];
        $query->set('meta_key', '_copella_author_creator_id');
        $query->set('meta_value', $creator_id);
    }
});

/**
 * Добавление мета-поля creator_id к авторам topics plugin
 */
add_action('add_meta_boxes', function(): void {
    add_meta_box(
        'copella_author_creator_link',
        __('Связь с креатором', 'copella-creators'),
        'copella_author_creator_link_meta_box',
        'topic_author',
        'side',
        'default'
    );
});

/**
 * Мета-бокс для связи автора с креатором
 */
function copella_author_creator_link_meta_box(WP_Post $post): void {
    $creator_id = (int) get_post_meta($post->ID, '_copella_author_creator_id', true);
    
    // Получаем всех креаторов
    $creators = get_posts(array(
        'post_type' => 'creator',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <p>
        <label for="copella_author_creator_id"><strong><?php _e('Связать с креатором', 'copella-creators'); ?></strong></label>
    </p>
    <select id="copella_author_creator_id" name="copella_author_creator_id" class="widefat">
        <option value=""><?php _e('Не выбран', 'copella-creators'); ?></option>
        <?php foreach ($creators as $creator): ?>
            <option value="<?php echo esc_attr($creator->ID); ?>" <?php selected($creator_id, $creator->ID); ?>>
                <?php echo esc_html($creator->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php _e('Выберите креатора, с которым связан этот автор. Это позволит отображать проекты автора на странице креатора.', 'copella-creators'); ?>
    </p>
    <?php
}

/**
 * Сохранение связи автора с креатором
 */
add_action('save_post_topic_author', function(int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['copella_author_creator_id'])) {
        $creator_id = (int) $_POST['copella_author_creator_id'];
        update_post_meta($post_id, '_copella_author_creator_id', $creator_id);
    } else {
        delete_post_meta($post_id, '_copella_author_creator_id');
    }
});

/**
 * Добавление колонки "Креатор" в админ-список авторов topics plugin
 */
add_filter('manage_topic_author_posts_columns', function($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'date') {
            $new_columns['creator_link'] = __('Креатор', 'copella-creators');
        }
    }
    return $new_columns;
});

add_action('manage_topic_author_posts_custom_column', function($column, $post_id) {
    if ($column === 'creator_link') {
        $creator_id = (int) get_post_meta($post_id, '_copella_author_creator_id', true);
        if ($creator_id > 0) {
            $creator = get_post($creator_id);
            if ($creator) {
                echo '<a href="' . esc_url(get_edit_post_link($creator_id)) . '">' . esc_html($creator->post_title) . '</a>';
            }
        } else {
            echo '—';
        }
    }
}, 10, 2);

/**
 * Шорткод для отображения проектов креатора из topics plugin
 * [creator_projects id="123" type="all|video|stream" limit="6"]
 */
add_shortcode('creator_projects', function($atts): string {
    $a = shortcode_atts([
        'id' => '0',
        'slug' => '',
        'type' => 'all', // all, video, stream
        'limit' => '6',
        'style' => 'grid', // grid, list
    ], $atts, 'creator_projects');

    $creator_id = 0;
    
    if (!empty($a['slug'])) {
        $creator = get_page_by_path($a['slug'], OBJECT, 'creator');
        if ($creator) {
            $creator_id = $creator->ID;
        }
    } elseif (!empty($a['id'])) {
        $creator_id = (int) $a['id'];
    } else {
        $creator_id = get_the_ID();
    }
    
    if ($creator_id <= 0) {
        return '<p>' . __('Креатор не найден', 'copella-creators') . '</p>';
    }

    $projects = copella_get_creator_all_projects($creator_id);
    
    // Фильтруем по типу
    if ($a['type'] !== 'all') {
        $filtered_projects = array();
        foreach ($projects as $project) {
            $project_type = (string) get_post_meta($project->ID, COPELLA_AUTHOR_META_TYPE, true);
            if ($a['type'] === 'stream' && $project_type === 'stream') {
                $filtered_projects[] = $project;
            } elseif ($a['type'] === 'video' && $project_type !== 'stream') {
                $filtered_projects[] = $project;
            }
        }
        $projects = $filtered_projects;
    }
    
    // Ограничиваем количество
    $projects = array_slice($projects, 0, (int) $a['limit']);
    
    if (empty($projects)) {
        return '<p>' . __('Проекты не найдены', 'copella-creators') . '</p>';
    }

    $uid = 'creator_projects_' . wp_generate_password(8, false, false);
    $style_class = 'cp-projects-' . $a['style'];
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($uid); ?>" class="copella-creator-projects <?php echo esc_attr($style_class); ?>">
        <?php foreach ($projects as $project): ?>
            <div class="cp-project-item">
                <a href="<?php echo esc_url(get_permalink($project->ID)); ?>" class="cp-project-link">
                    <?php 
                    $project_thumb = get_the_post_thumbnail_url($project->ID, 'medium');
                    if ($project_thumb): 
                    ?>
                        <div class="cp-project-thumb">
                            <img src="<?php echo esc_url($project_thumb); ?>" alt="<?php echo esc_attr($project->post_title); ?>" loading="lazy" />
                        </div>
                    <?php endif; ?>
                    <div class="cp-project-info">
                        <h3 class="cp-project-title"><?php echo esc_html($project->post_title); ?></h3>
                        <div class="cp-project-meta">
                            <?php 
                            $project_type = (string) get_post_meta($project->ID, COPELLA_AUTHOR_META_TYPE, true);
                            echo $project_type === 'stream' ? __('Стрим', 'copella-creators') : __('Видео', 'copella-creators');
                            ?>
                        </div>
                        <?php if ($project->post_excerpt): ?>
                            <div class="cp-project-excerpt"><?php echo esc_html($project->post_excerpt); ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <style>
    .copella-creator-projects {
        display: grid;
        gap: 20px;
        max-width: 100%;
    }

    .copella-creator-projects.cp-projects-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }

    .copella-creator-projects.cp-projects-list {
        grid-template-columns: 1fr;
    }

    .copella-creator-projects .cp-project-item {
        background: rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .copella-creator-projects .cp-project-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .copella-creator-projects .cp-project-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .copella-creator-projects .cp-project-thumb {
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
    }

    .copella-creator-projects .cp-project-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .copella-creator-projects .cp-project-item:hover .cp-project-thumb img {
        transform: scale(1.05);
    }

    .copella-creator-projects .cp-project-info {
        padding: 16px 20px;
    }

    .copella-creator-projects .cp-project-title {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
        color: #fff;
        line-height: 1.3;
        font-family: 'Gilroy-SemiBold', system-ui, sans-serif;
    }

    .copella-creator-projects .cp-project-meta {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
        margin-bottom: 8px;
    }

    .copella-creator-projects .cp-project-excerpt {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.4;
    }

    @media (max-width: 768px) {
        .copella-creator-projects.cp-projects-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
    </style>
    <?php
    return ob_get_clean();
});