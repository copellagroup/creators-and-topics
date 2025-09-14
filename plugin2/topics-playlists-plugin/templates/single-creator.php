<?php
/**
 * Шаблон для отображения отдельной страницы креатора
 * Полноценная страница в стиле Topics Plugin
 */

get_header(); ?>

<div class="copella-creator-single-page">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $creator_id = get_the_ID();
        $name = get_the_title();
        $avatar_url = get_the_post_thumbnail_url($creator_id, 'large');
        $description = get_post_field('post_content', $creator_id);
        $excerpt = get_post_field('post_excerpt', $creator_id);
        
        // Получаем мета-данные
        $age = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_AGE, true);
        $experience = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_EXPERIENCE, true);
        $specialization = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SPECIALIZATION, true);
        $social_networks = copella_get_creator_social_networks($creator_id);
        $achievements = copella_get_creator_achievements($creator_id);
        $playlists = copella_get_creator_playlists($creator_id);
        $projects = copella_get_creator_projects($creator_id);
        ?>
        
        <!-- Заголовок с размытым фоном аватарки -->
        <header class="cp-single-header">
            <div class="cp-single-header-bg" <?php if ($avatar_url): ?>style="background-image: url('<?php echo esc_url($avatar_url); ?>')"<?php endif; ?>></div>
            <div class="cp-single-header-content">
                <div class="cp-single-avatar">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" />
                    <?php else: ?>
                        <div class="cp-single-avatar-placeholder">
                            <?php echo esc_html(mb_substr($name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="cp-single-info">
                    <h1 class="cp-single-name"><?php echo esc_html($name); ?></h1>
                    <?php if ($specialization): ?>
                        <div class="cp-single-specialization"><?php echo esc_html($specialization); ?></div>
                    <?php endif; ?>
                    <?php if ($age || $experience): ?>
                        <div class="cp-single-stats">
                            <?php if ($age): ?>
                                <span class="cp-single-stat">
                                    <span class="cp-single-stat-icon">🎂</span>
                                    <?php echo esc_html($age); ?> <?php _e('лет', 'copella-topics'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($experience): ?>
                                <span class="cp-single-stat">
                                    <span class="cp-single-stat-icon">💼</span>
                                    <?php echo esc_html($experience); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Основной контент -->
        <div class="cp-single-content">
            <!-- Описание -->
            <?php if (!empty($description)): ?>
                <section class="cp-single-section cp-single-description">
                    <h2 class="cp-single-section-title"><?php _e('О креаторе', 'copella-topics'); ?></h2>
                    <div class="cp-single-description-content">
                        <?php echo wp_kses_post(wpautop($description)); ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Достижения -->
            <?php if (!empty($achievements)): ?>
                <section class="cp-single-section cp-single-achievements">
                    <h2 class="cp-single-section-title"><?php _e('Достижения и заслуги', 'copella-topics'); ?></h2>
                    <div class="cp-single-achievements-list">
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="cp-single-achievement">
                                <?php echo esc_html($achievement); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Социальные сети -->
            <?php if (!empty($social_networks)): ?>
                <section class="cp-single-section cp-single-social">
                    <h2 class="cp-single-section-title"><?php _e('Социальные сети', 'copella-topics'); ?></h2>
                    <div class="cp-single-social-list">
                        <?php foreach ($social_networks as $social): ?>
                            <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener noreferrer" class="cp-single-social-link">
                                <?php if ($social['icon']): ?>
                                    <span class="cp-single-social-icon"><?php echo esc_html($social['icon']); ?></span>
                                <?php endif; ?>
                                <span class="cp-single-social-name"><?php echo esc_html($social['name']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Плейлисты -->
            <?php if (!empty($playlists)): ?>
                <section class="cp-single-section cp-single-playlists">
                    <h2 class="cp-single-section-title"><?php _e('Плейлисты', 'copella-topics'); ?></h2>
                    <div class="cp-single-playlists-grid">
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="cp-single-playlist-card">
                                <a href="<?php echo esc_url(get_permalink($playlist->ID)); ?>" class="cp-single-playlist-link">
                                    <?php 
                                    $playlist_thumb = get_the_post_thumbnail_url($playlist->ID, 'medium');
                                    if ($playlist_thumb): 
                                    ?>
                                        <div class="cp-single-playlist-thumb">
                                            <img src="<?php echo esc_url($playlist_thumb); ?>" alt="<?php echo esc_attr($playlist->post_title); ?>" loading="lazy" />
                                        </div>
                                    <?php endif; ?>
                                    <div class="cp-single-playlist-info">
                                        <h3 class="cp-single-playlist-title"><?php echo esc_html($playlist->post_title); ?></h3>
                                        <div class="cp-single-playlist-meta"><?php _e('Плейлист', 'copella-topics'); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Проекты (видео и стримы) -->
            <?php if (!empty($projects)): ?>
                <section class="cp-single-section cp-single-projects">
                    <h2 class="cp-single-section-title"><?php _e('Проекты', 'copella-topics'); ?></h2>
                    <div class="cp-single-projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="cp-single-project-card">
                                <a href="<?php echo esc_url(get_permalink($project->ID)); ?>" class="cp-single-project-link">
                                    <?php 
                                    $project_thumb = get_the_post_thumbnail_url($project->ID, 'medium');
                                    if ($project_thumb): 
                                    ?>
                                        <div class="cp-single-project-thumb">
                                            <img src="<?php echo esc_url($project_thumb); ?>" alt="<?php echo esc_attr($project->post_title); ?>" loading="lazy" />
                                        </div>
                                    <?php endif; ?>
                                    <div class="cp-single-project-info">
                                        <h3 class="cp-single-project-title"><?php echo esc_html($project->post_title); ?></h3>
                                        <div class="cp-single-project-meta">
                                            <?php 
                                            $project_type = (string) get_post_meta($project->ID, COPELLA_AUTHOR_META_TYPE, true);
                                            echo $project_type === 'stream' ? __('Стрим', 'copella-topics') : __('Видео', 'copella-topics');
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
    <?php endwhile; ?>
</div>

<style>
/* Стили для страницы креатора в стиле Topics Plugin */
.copella-creator-single-page {
    color: #fff;
    font-family: 'Gilroy-SemiBold', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    max-width: 100%;
    margin: 0 auto;
    background: #171717;
    min-height: 100vh;
}

/* Заголовок с размытым фоном */
.copella-creator-single-page .cp-single-header {
    position: relative;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 40px;
}

.copella-creator-single-page .cp-single-header-bg {
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

.copella-creator-single-page .cp-single-header-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 32px;
    padding: 60px 40px;
    text-align: center;
    max-width: 1200px;
    width: 100%;
}

.copella-creator-single-page .cp-single-avatar {
    flex-shrink: 0;
}

.copella-creator-single-page .cp-single-avatar img {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
}

.copella-creator-single-page .cp-single-avatar-placeholder {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: #FF653A;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    font-weight: 700;
    color: #fff;
    border: 6px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
}

.copella-creator-single-page .cp-single-info {
    flex: 1;
    text-align: left;
}

.copella-creator-single-page .cp-single-name {
    margin: 0 0 12px 0;
    font-size: 48px;
    font-weight: 700;
    font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.7);
    line-height: 1.1;
}

.copella-creator-single-page .cp-single-specialization {
    font-size: 24px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 20px;
    font-weight: 600;
}

.copella-creator-single-page .cp-single-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.copella-creator-single-page .cp-single-stat {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.15);
    padding: 12px 20px;
    border-radius: 24px;
    font-size: 16px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.copella-creator-single-page .cp-single-stat-icon {
    font-size: 20px;
}

/* Основной контент */
.copella-creator-single-page .cp-single-content {
    padding: 0 40px 60px 40px;
    max-width: 1200px;
    margin: 0 auto;
}

.copella-creator-single-page .cp-single-section {
    margin-bottom: 60px;
}

.copella-creator-single-page .cp-single-section:last-child {
    margin-bottom: 0;
}

.copella-creator-single-page .cp-single-section-title {
    margin: 0 0 30px 0;
    font-size: 32px;
    font-weight: 700;
    font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
    color: #fff;
    border-bottom: 2px solid #FF653A;
    padding-bottom: 10px;
}

/* Описание */
.copella-creator-single-page .cp-single-description-content {
    font-size: 18px;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.9);
}

/* Достижения */
.copella-creator-single-page .cp-single-achievements-list {
    display: grid;
    gap: 16px;
}

.copella-creator-single-page .cp-single-achievement {
    background: rgba(255, 255, 255, 0.08);
    padding: 20px 24px;
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    border-left: 4px solid #FF653A;
    transition: background 0.2s ease;
}

.copella-creator-single-page .cp-single-achievement:hover {
    background: rgba(255, 255, 255, 0.12);
}

/* Социальные сети */
.copella-creator-single-page .cp-single-social-list {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.copella-creator-single-page .cp-single-social-link {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    text-decoration: none;
    padding: 16px 20px;
    border-radius: 16px;
    font-weight: 600;
    transition: background 0.2s ease, transform 0.2s ease;
    font-size: 16px;
}

.copella-creator-single-page .cp-single-social-link:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    transform: translateY(-2px);
}

.copella-creator-single-page .cp-single-social-icon {
    font-size: 20px;
}

/* Плейлисты и проекты */
.copella-creator-single-page .cp-single-playlists-grid,
.copella-creator-single-page .cp-single-projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.copella-creator-single-page .cp-single-playlist-card,
.copella-creator-single-page .cp-single-project-card {
    background: rgba(255, 255, 255, 0.06);
    border-radius: 20px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.copella-creator-single-page .cp-single-playlist-card:hover,
.copella-creator-single-page .cp-single-project-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
}

.copella-creator-single-page .cp-single-playlist-link,
.copella-creator-single-page .cp-single-project-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

.copella-creator-single-page .cp-single-playlist-thumb,
.copella-creator-single-page .cp-single-project-thumb {
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.copella-creator-single-page .cp-single-playlist-thumb img,
.copella-creator-single-page .cp-single-project-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.copella-creator-single-page .cp-single-playlist-card:hover .cp-single-playlist-thumb img,
.copella-creator-single-page .cp-single-project-card:hover .cp-single-project-thumb img {
    transform: scale(1.05);
}

.copella-creator-single-page .cp-single-playlist-info,
.copella-creator-single-page .cp-single-project-info {
    padding: 20px 24px;
}

.copella-creator-single-page .cp-single-playlist-title,
.copella-creator-single-page .cp-single-project-title {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    line-height: 1.3;
    font-family: 'Gilroy-SemiBold', system-ui, sans-serif;
}

.copella-creator-single-page .cp-single-playlist-meta,
.copella-creator-single-page .cp-single-project-meta {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .copella-creator-single-page .cp-single-header-content {
        flex-direction: column;
        gap: 24px;
        padding: 40px 24px;
        text-align: center;
    }

    .copella-creator-single-page .cp-single-info {
        text-align: center;
    }

    .copella-creator-single-page .cp-single-name {
        font-size: 36px;
    }

    .copella-creator-single-page .cp-single-specialization {
        font-size: 20px;
    }

    .copella-creator-single-page .cp-single-content {
        padding: 0 24px 40px 24px;
    }

    .copella-creator-single-page .cp-single-section-title {
        font-size: 28px;
    }

    .copella-creator-single-page .cp-single-playlists-grid,
    .copella-creator-single-page .cp-single-projects-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .copella-creator-single-page .cp-single-header {
        min-height: 300px;
        margin-bottom: 30px;
    }

    .copella-creator-single-page .cp-single-header-content {
        padding: 30px 20px;
        gap: 20px;
    }

    .copella-creator-single-page .cp-single-avatar img,
    .copella-creator-single-page .cp-single-avatar-placeholder {
        width: 120px;
        height: 120px;
    }

    .copella-creator-single-page .cp-single-avatar-placeholder {
        font-size: 48px;
    }

    .copella-creator-single-page .cp-single-name {
        font-size: 28px;
    }

    .copella-creator-single-page .cp-single-specialization {
        font-size: 18px;
    }

    .copella-creator-single-page .cp-single-stats {
        justify-content: center;
        gap: 16px;
    }

    .copella-creator-single-page .cp-single-stat {
        padding: 10px 16px;
        font-size: 14px;
    }

    .copella-creator-single-page .cp-single-content {
        padding: 0 20px 30px 20px;
    }

    .copella-creator-single-page .cp-single-section {
        margin-bottom: 40px;
    }

    .copella-creator-single-page .cp-single-section-title {
        font-size: 24px;
    }

    .copella-creator-single-page .cp-single-description-content {
        font-size: 16px;
    }

    .copella-creator-single-page .cp-single-playlists-grid,
    .copella-creator-single-page .cp-single-projects-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .copella-creator-single-page .cp-single-social-list {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .copella-creator-single-page .cp-single-header-content {
        padding: 20px 16px;
    }

    .copella-creator-single-page .cp-single-avatar img,
    .copella-creator-single-page .cp-single-avatar-placeholder {
        width: 100px;
        height: 100px;
    }

    .copella-creator-single-page .cp-single-avatar-placeholder {
        font-size: 40px;
    }

    .copella-creator-single-page .cp-single-name {
        font-size: 24px;
    }

    .copella-creator-single-page .cp-single-specialization {
        font-size: 16px;
    }

    .copella-creator-single-page .cp-single-content {
        padding: 0 16px 20px 16px;
    }

    .copella-creator-single-page .cp-single-section-title {
        font-size: 20px;
    }

    .copella-creator-single-page .cp-single-social-list {
        flex-direction: column;
        align-items: center;
    }

    .copella-creator-single-page .cp-single-social-link {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
}
</style>

<?php get_footer(); ?>