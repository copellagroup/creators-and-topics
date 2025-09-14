<?php
/**
 * Шаблон страницы креатора
 * Единый стиль с размытым фоном аватарки и красивым дизайном
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Шорткод для отображения полной страницы креатора (устарел)
 * Теперь используется полноценный шаблон single-creator.php
 * Оставляем для обратной совместимости
 */
add_shortcode('creator_page', function($atts): string {
    $a = shortcode_atts([
        'id' => '0',
        'slug' => '',
        'show_playlists' => 'true',
        'show_projects' => 'true',
        'show_social' => 'true',
    ], $atts, 'creator_page');

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

    // Перенаправляем на полноценную страницу креатора
    $creator_url = get_permalink($creator_id);
    if ($creator_url) {
        return '<p><a href="' . esc_url($creator_url) . '" class="button">' . __('Перейти к странице креатора', 'copella-creators') . '</a></p>';
    }

    return copella_render_creator_page($creator_id, $a);
});

/**
 * Основная функция рендеринга страницы креатора
 */
function copella_render_creator_page($creator_id, $options = array()): string {
    $creator = get_post($creator_id);
    if (!$creator || $creator->post_type !== 'creator') {
        return '<p>' . __('Креатор не найден', 'copella-creators') . '</p>';
    }

    $name = get_the_title($creator_id);
    $avatar_url = get_the_post_thumbnail_url($creator_id, 'large');
    $description = get_post_field('post_content', $creator_id);
    $excerpt = get_post_field('post_excerpt', $creator_id);
    
    // Если нет описания, используем excerpt
    if (empty($description) && !empty($excerpt)) {
        $description = $excerpt;
    }
    
    // Получаем мета-данные
    $age = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_AGE, true);
    $experience = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_EXPERIENCE, true);
    $specialization = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SPECIALIZATION, true);
    $social_networks = copella_get_creator_social_networks($creator_id);
    $achievements = copella_get_creator_achievements($creator_id);
    $playlists = copella_get_creator_playlists($creator_id);
    
    // Получаем связанные проекты из topics plugin
    $projects = copella_get_creator_projects($creator_id);
    
    $uid = 'creator_page_' . wp_generate_password(8, false, false);
    
    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-creator-page">
        <!-- Заголовок с размытым фоном аватарки -->
        <header class="cp-header">
            <div class="cp-header-bg" <?php if ($avatar_url): ?>style="background-image: url('<?php echo esc_url($avatar_url); ?>')"<?php endif; ?>></div>
            <div class="cp-header-content">
                <div class="cp-avatar">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" />
                    <?php else: ?>
                        <div class="cp-avatar-placeholder">
                            <?php echo esc_html(mb_substr($name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="cp-info">
                    <h1 class="cp-name"><?php echo esc_html($name); ?></h1>
                    <?php if ($specialization): ?>
                        <div class="cp-specialization"><?php echo esc_html($specialization); ?></div>
                    <?php endif; ?>
                    <?php if ($age || $experience): ?>
                        <div class="cp-stats">
                            <?php if ($age): ?>
                                <span class="cp-stat">
                                    <span class="cp-stat-icon">🎂</span>
                                    <?php echo esc_html($age); ?> <?php _e('лет', 'copella-creators'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($experience): ?>
                                <span class="cp-stat">
                                    <span class="cp-stat-icon">💼</span>
                                    <?php echo esc_html($experience); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Основной контент -->
        <div class="cp-content">
            <!-- Описание -->
            <?php if (!empty($description)): ?>
                <section class="cp-section cp-description">
                    <h2 class="cp-section-title"><?php _e('О креаторе', 'copella-creators'); ?></h2>
                    <div class="cp-description-content">
                        <?php echo wp_kses_post(wpautop($description)); ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Достижения -->
            <?php if (!empty($achievements)): ?>
                <section class="cp-section cp-achievements">
                    <h2 class="cp-section-title"><?php _e('Достижения и заслуги', 'copella-creators'); ?></h2>
                    <div class="cp-achievements-list">
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="cp-achievement">
                                <?php echo esc_html($achievement); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Социальные сети -->
            <?php if (!empty($social_networks) && $options['show_social'] !== 'false'): ?>
                <section class="cp-section cp-social">
                    <h2 class="cp-section-title"><?php _e('Социальные сети', 'copella-creators'); ?></h2>
                    <div class="cp-social-list">
                        <?php foreach ($social_networks as $social): ?>
                            <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener noreferrer" class="cp-social-link">
                                <?php if ($social['icon']): ?>
                                    <span class="cp-social-icon"><?php echo esc_html($social['icon']); ?></span>
                                <?php endif; ?>
                                <span class="cp-social-name"><?php echo esc_html($social['name']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Плейлисты -->
            <?php if (!empty($playlists) && $options['show_playlists'] !== 'false'): ?>
                <section class="cp-section cp-playlists">
                    <h2 class="cp-section-title"><?php _e('Плейлисты', 'copella-creators'); ?></h2>
                    <div class="cp-playlists-grid">
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="cp-playlist-card">
                                <a href="<?php echo esc_url(get_permalink($playlist->ID)); ?>" class="cp-playlist-link">
                                    <?php 
                                    $playlist_thumb = get_the_post_thumbnail_url($playlist->ID, 'medium');
                                    if ($playlist_thumb): 
                                    ?>
                                        <div class="cp-playlist-thumb">
                                            <img src="<?php echo esc_url($playlist_thumb); ?>" alt="<?php echo esc_attr($playlist->post_title); ?>" loading="lazy" />
                                        </div>
                                    <?php endif; ?>
                                    <div class="cp-playlist-info">
                                        <h3 class="cp-playlist-title"><?php echo esc_html($playlist->post_title); ?></h3>
                                        <div class="cp-playlist-meta"><?php _e('Плейлист', 'copella-creators'); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Проекты (видео и стримы) -->
            <?php if (!empty($projects) && $options['show_projects'] !== 'false'): ?>
                <section class="cp-section cp-projects">
                    <h2 class="cp-section-title"><?php _e('Проекты', 'copella-creators'); ?></h2>
                    <div class="cp-projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="cp-project-card">
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
    /* Стили для страницы креатора в едином стиле */
    .copella-creator-page {
        color: #fff;
        font-family: 'Gilroy-SemiBold', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        max-width: 100%;
        margin: 0 auto;
        background: #171717;
        border-radius: 32px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    /* Заголовок с размытым фоном */
    .copella-creator-page .cp-header {
        position: relative;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .copella-creator-page .cp-header-bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-size: cover;
        background-position: center;
        filter: blur(20px) brightness(0.4);
        transform: scale(1.1);
    }

    .copella-creator-page .cp-header-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 40px;
        text-align: center;
    }

    .copella-creator-page .cp-avatar {
        flex-shrink: 0;
    }

    .copella-creator-page .cp-avatar img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }

    .copella-creator-page .cp-avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #FF653A;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: 700;
        color: #fff;
        border: 4px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }

    .copella-creator-page .cp-info {
        flex: 1;
        text-align: left;
    }

    .copella-creator-page .cp-name {
        margin: 0 0 8px 0;
        font-size: 32px;
        font-weight: 700;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
    }

    .copella-creator-page .cp-specialization {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 16px;
        font-weight: 600;
    }

    .copella-creator-page .cp-stats {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .copella-creator-page .cp-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .copella-creator-page .cp-stat-icon {
        font-size: 16px;
    }

    /* Основной контент */
    .copella-creator-page .cp-content {
        padding: 40px;
    }

    .copella-creator-page .cp-section {
        margin-bottom: 40px;
    }

    .copella-creator-page .cp-section:last-child {
        margin-bottom: 0;
    }

    .copella-creator-page .cp-section-title {
        margin: 0 0 20px 0;
        font-size: 24px;
        font-weight: 700;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
        color: #fff;
    }

    /* Описание */
    .copella-creator-page .cp-description-content {
        font-size: 16px;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.9);
    }

    /* Достижения */
    .copella-creator-page .cp-achievements-list {
        display: grid;
        gap: 12px;
    }

    .copella-creator-page .cp-achievement {
        background: rgba(255, 255, 255, 0.08);
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        border-left: 4px solid #FF653A;
    }

    /* Социальные сети */
    .copella-creator-page .cp-social-list {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .copella-creator-page .cp-social-link {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        text-decoration: none;
        padding: 12px 16px;
        border-radius: 12px;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .copella-creator-page .cp-social-link:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .copella-creator-page .cp-social-icon {
        font-size: 18px;
    }

    /* Плейлисты и проекты */
    .copella-creator-page .cp-playlists-grid,
    .copella-creator-page .cp-projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .copella-creator-page .cp-playlist-card,
    .copella-creator-page .cp-project-card {
        background: rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .copella-creator-page .cp-playlist-card:hover,
    .copella-creator-page .cp-project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .copella-creator-page .cp-playlist-link,
    .copella-creator-page .cp-project-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .copella-creator-page .cp-playlist-thumb,
    .copella-creator-page .cp-project-thumb {
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
    }

    .copella-creator-page .cp-playlist-thumb img,
    .copella-creator-page .cp-project-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .copella-creator-page .cp-playlist-card:hover .cp-playlist-thumb img,
    .copella-creator-page .cp-project-card:hover .cp-project-thumb img {
        transform: scale(1.05);
    }

    .copella-creator-page .cp-playlist-info,
    .copella-creator-page .cp-project-info {
        padding: 16px 20px;
    }

    .copella-creator-page .cp-playlist-title,
    .copella-creator-page .cp-project-title {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
        color: #fff;
        line-height: 1.3;
    }

    .copella-creator-page .cp-playlist-meta,
    .copella-creator-page .cp-project-meta {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
    }

    /* Адаптивность */
    @media (max-width: 768px) {
        .copella-creator-page {
            border-radius: 20px;
            margin: 0 12px;
        }

        .copella-creator-page .cp-header {
            min-height: 250px;
        }

        .copella-creator-page .cp-header-content {
            flex-direction: column;
            gap: 16px;
            padding: 24px;
            text-align: center;
        }

        .copella-creator-page .cp-info {
            text-align: center;
        }

        .copella-creator-page .cp-name {
            font-size: 24px;
        }

        .copella-creator-page .cp-specialization {
            font-size: 16px;
        }

        .copella-creator-page .cp-stats {
            justify-content: center;
            gap: 12px;
        }

        .copella-creator-page .cp-content {
            padding: 24px;
        }

        .copella-creator-page .cp-section-title {
            font-size: 20px;
        }

        .copella-creator-page .cp-playlists-grid,
        .copella-creator-page .cp-projects-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .copella-creator-page .cp-social-list {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .copella-creator-page .cp-header-content {
            padding: 20px;
        }

        .copella-creator-page .cp-avatar img,
        .copella-creator-page .cp-avatar-placeholder {
            width: 80px;
            height: 80px;
        }

        .copella-creator-page .cp-avatar-placeholder {
            font-size: 32px;
        }

        .copella-creator-page .cp-name {
            font-size: 20px;
        }

        .copella-creator-page .cp-content {
            padding: 20px;
        }

        .copella-creator-page .cp-social-list {
            flex-direction: column;
            align-items: center;
        }

        .copella-creator-page .cp-social-link {
            width: 100%;
            max-width: 200px;
            justify-content: center;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Функция для получения связанных проектов креатора
 */
function copella_get_creator_projects($creator_id): array {
    // Ищем проекты, связанные с этим креатором через мета-поле
    $projects = get_posts(array(
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'meta_key' => '_copella_author_creator_id',
        'meta_value' => $creator_id,
        'posts_per_page' => 20,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    
    return $projects;
}

/**
 * Добавление поддержки шаблонов для Custom Post Type
 */
add_filter('template_include', function($template) {
    if (is_singular('creator')) {
        $custom_template = COPELLA_CREATORS_DIR . 'templates/single-creator.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    if (is_post_type_archive('creator')) {
        $custom_template = COPELLA_CREATORS_DIR . 'templates/archive-creator.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
});