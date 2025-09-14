<?php
/**
 * Шорткоды для креаторов
 * Интеграция с Topics Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Шорткод для отображения карточки креатора
 * [creator_card id="123"] или [creator_card slug="creator-slug"]
 */
add_shortcode('creator_card', function($atts): string {
    $a = shortcode_atts([
        'id' => '0',
        'slug' => '',
        'show_social' => 'true',
        'show_achievements' => 'true',
        'show_playlists' => 'false',
        'show_projects' => 'false',
        'style' => 'default', // default, compact, minimal
        'copy_shortcode' => 'true',
    ], $atts, 'creator_card');

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
        return '<p>' . __('Креатор не найден', 'copella-topics') . '</p>';
    }

    return copella_render_creator_card($creator_id, $a);
});

/**
 * Основная функция рендеринга карточки креатора
 */
function copella_render_creator_card($creator_id, $options = array()): string {
    $creator = get_post($creator_id);
    if (!$creator || $creator->post_type !== 'creator') {
        return '<p>' . __('Креатор не найден', 'copella-topics') . '</p>';
    }

    $name = get_the_title($creator_id);
    $avatar_url = get_the_post_thumbnail_url($creator_id, 'medium');
    $excerpt = get_post_field('post_excerpt', $creator_id);
    
    // Получаем мета-данные
    $age = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_AGE, true);
    $experience = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_EXPERIENCE, true);
    $specialization = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SPECIALIZATION, true);
    $social_networks = copella_get_creator_social_networks($creator_id);
    $achievements = copella_get_creator_achievements($creator_id);
    $playlists = copella_get_creator_playlists($creator_id);
    $projects = copella_get_creator_projects($creator_id);
    
    $uid = 'creator_card_' . wp_generate_password(8, false, false);
    $style_class = 'cp-card-' . $options['style'];
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($uid); ?>" class="copella-creator-card <?php echo esc_attr($style_class); ?>">
        <!-- Кнопка копирования шорткода -->
        <?php if ($options['copy_shortcode'] !== 'false'): ?>
            <div class="cp-copy-shortcode">
                <button type="button" class="cp-copy-btn" data-creator-id="<?php echo esc_attr($creator_id); ?>" title="<?php _e('Скопировать шорткод', 'copella-topics'); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <!-- Заголовок карточки -->
        <header class="cp-card-header">
            <div class="cp-card-avatar">
                <?php if ($avatar_url): ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" />
                <?php else: ?>
                    <div class="cp-card-avatar-placeholder">
                        <?php echo esc_html(mb_substr($name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="cp-card-info">
                <h3 class="cp-card-name"><?php echo esc_html($name); ?></h3>
                <?php if ($specialization): ?>
                    <div class="cp-card-specialization"><?php echo esc_html($specialization); ?></div>
                <?php endif; ?>
                <?php if ($age || $experience): ?>
                    <div class="cp-card-stats">
                        <?php if ($age): ?>
                            <span class="cp-card-stat"><?php echo esc_html($age); ?> <?php _e('лет', 'copella-topics'); ?></span>
                        <?php endif; ?>
                        <?php if ($experience): ?>
                            <span class="cp-card-stat"><?php echo esc_html($experience); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Описание -->
        <?php if (!empty($excerpt)): ?>
            <div class="cp-card-description">
                <?php echo esc_html($excerpt); ?>
            </div>
        <?php endif; ?>

        <!-- Достижения -->
        <?php if (!empty($achievements) && $options['show_achievements'] !== 'false'): ?>
            <div class="cp-card-achievements">
                <h4 class="cp-card-section-title"><?php _e('Достижения', 'copella-topics'); ?></h4>
                <div class="cp-card-achievements-list">
                    <?php foreach (array_slice($achievements, 0, 3) as $achievement): ?>
                        <div class="cp-card-achievement">
                            <?php echo esc_html($achievement); ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($achievements) > 3): ?>
                        <div class="cp-card-more">
                            +<?php echo count($achievements) - 3; ?> <?php _e('еще', 'copella-topics'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Социальные сети -->
        <?php if (!empty($social_networks) && $options['show_social'] !== 'false'): ?>
            <div class="cp-card-social">
                <h4 class="cp-card-section-title"><?php _e('Социальные сети', 'copella-topics'); ?></h4>
                <div class="cp-card-social-list">
                    <?php foreach (array_slice($social_networks, 0, 4) as $social): ?>
                        <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener noreferrer" class="cp-card-social-link">
                            <?php if ($social['icon']): ?>
                                <span class="cp-card-social-icon"><?php echo esc_html($social['icon']); ?></span>
                            <?php endif; ?>
                            <span class="cp-card-social-name"><?php echo esc_html($social['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($social_networks) > 4): ?>
                        <div class="cp-card-more">
                            +<?php echo count($social_networks) - 4; ?> <?php _e('еще', 'copella-topics'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Плейлисты -->
        <?php if (!empty($playlists) && $options['show_playlists'] !== 'false'): ?>
            <div class="cp-card-playlists">
                <h4 class="cp-card-section-title"><?php _e('Плейлисты', 'copella-topics'); ?></h4>
                <div class="cp-card-playlists-list">
                    <?php foreach (array_slice($playlists, 0, 3) as $playlist): ?>
                        <a href="<?php echo esc_url(get_permalink($playlist->ID)); ?>" class="cp-card-playlist-item">
                            <?php 
                            $playlist_thumb = get_the_post_thumbnail_url($playlist->ID, 'thumbnail');
                            if ($playlist_thumb): 
                            ?>
                                <img src="<?php echo esc_url($playlist_thumb); ?>" alt="<?php echo esc_attr($playlist->post_title); ?>" loading="lazy" />
                            <?php endif; ?>
                            <span><?php echo esc_html($playlist->post_title); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Проекты -->
        <?php if (!empty($projects) && $options['show_projects'] !== 'false'): ?>
            <div class="cp-card-projects">
                <h4 class="cp-card-section-title"><?php _e('Проекты', 'copella-topics'); ?></h4>
                <div class="cp-card-projects-list">
                    <?php foreach (array_slice($projects, 0, 3) as $project): ?>
                        <a href="<?php echo esc_url(get_permalink($project->ID)); ?>" class="cp-card-project-item">
                            <?php 
                            $project_thumb = get_the_post_thumbnail_url($project->ID, 'thumbnail');
                            if ($project_thumb): 
                            ?>
                                <img src="<?php echo esc_url($project_thumb); ?>" alt="<?php echo esc_attr($project->post_title); ?>" loading="lazy" />
                            <?php endif; ?>
                            <span><?php echo esc_html($project->post_title); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ссылка на полную страницу -->
        <div class="cp-card-footer">
            <a href="<?php echo esc_url(get_permalink($creator_id)); ?>" class="cp-card-link">
                <?php _e('Подробнее о креаторе', 'copella-topics'); ?>
                <svg viewBox="0 0 24 24" width="16" height="16">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" fill="currentColor"/>
                </svg>
            </a>
        </div>
    </div>

    <style>
    /* Стили для карточки креатора в стиле Topics Plugin */
    .copella-creator-card {
        background: #171717;
        border-radius: 24px;
        padding: 24px;
        color: #fff;
        font-family: 'Gilroy-SemiBold', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        max-width: 100%;
        box-sizing: border-box;
    }

    .copella-creator-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
    }

    /* Кнопка копирования шорткода */
    .copella-creator-card .cp-copy-shortcode {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 10;
    }

    .copella-creator-card .cp-copy-btn {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 8px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .copella-creator-card .cp-copy-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .copella-creator-card .cp-copy-btn.copied {
        background: #4CAF50;
    }

    /* Заголовок карточки */
    .copella-creator-card .cp-card-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
        padding-right: 40px; /* Место для кнопки копирования */
    }

    .copella-creator-card .cp-card-avatar {
        flex-shrink: 0;
    }

    .copella-creator-card .cp-card-avatar img {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .copella-creator-card .cp-card-avatar-placeholder {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #FF653A;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .copella-creator-card .cp-card-info {
        flex: 1;
        min-width: 0;
    }

    .copella-creator-card .cp-card-name {
        margin: 0 0 4px 0;
        font-size: 20px;
        font-weight: 700;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
        color: #fff;
        line-height: 1.2;
    }

    .copella-creator-card .cp-card-specialization {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .copella-creator-card .cp-card-stats {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .copella-creator-card .cp-card-stat {
        background: rgba(255, 255, 255, 0.08);
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }

    /* Описание */
    .copella-creator-card .cp-card-description {
        font-size: 14px;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 20px;
    }

    /* Секции */
    .copella-creator-card .cp-card-section-title {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 700;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
        color: #fff;
    }

    /* Достижения */
    .copella-creator-card .cp-card-achievements {
        margin-bottom: 20px;
    }

    .copella-creator-card .cp-card-achievements-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .copella-creator-card .cp-card-achievement {
        background: rgba(255, 255, 255, 0.06);
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border-left: 3px solid #FF653A;
    }

    /* Социальные сети */
    .copella-creator-card .cp-card-social {
        margin-bottom: 20px;
    }

    .copella-creator-card .cp-card-social-list {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .copella-creator-card .cp-card-social-link {
        display: flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        text-decoration: none;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .copella-creator-card .cp-card-social-link:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .copella-creator-card .cp-card-social-icon {
        font-size: 14px;
    }

    /* Плейлисты и проекты */
    .copella-creator-card .cp-card-playlists,
    .copella-creator-card .cp-card-projects {
        margin-bottom: 20px;
    }

    .copella-creator-card .cp-card-playlists-list,
    .copella-creator-card .cp-card-projects-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .copella-creator-card .cp-card-playlist-item,
    .copella-creator-card .cp-card-project-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background 0.2s ease;
    }

    .copella-creator-card .cp-card-playlist-item:hover,
    .copella-creator-card .cp-card-project-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .copella-creator-card .cp-card-playlist-item img,
    .copella-creator-card .cp-card-project-item img {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .copella-creator-card .cp-card-playlist-item span,
    .copella-creator-card .cp-card-project-item span {
        font-size: 13px;
        font-weight: 600;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Индикатор "еще" */
    .copella-creator-card .cp-card-more {
        background: rgba(255, 255, 255, 0.08);
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        text-align: center;
    }

    /* Футер */
    .copella-creator-card .cp-card-footer {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .copella-creator-card .cp-card-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #FF653A;
        color: #fff;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s ease;
    }

    .copella-creator-card .cp-card-link:hover {
        background: #e55a34;
        color: #fff;
    }

    /* Компактный стиль */
    .copella-creator-card.cp-card-compact {
        padding: 16px;
    }

    .copella-creator-card.cp-card-compact .cp-card-header {
        margin-bottom: 12px;
    }

    .copella-creator-card.cp-card-compact .cp-card-avatar img,
    .copella-creator-card.cp-card-compact .cp-card-avatar-placeholder {
        width: 48px;
        height: 48px;
    }

    .copella-creator-card.cp-card-compact .cp-card-avatar-placeholder {
        font-size: 18px;
    }

    .copella-creator-card.cp-card-compact .cp-card-name {
        font-size: 16px;
    }

    .copella-creator-card.cp-card-compact .cp-card-description {
        font-size: 13px;
        margin-bottom: 12px;
    }

    .copella-creator-card.cp-card-compact .cp-card-section-title {
        font-size: 14px;
        margin-bottom: 8px;
    }

    /* Минимальный стиль */
    .copella-creator-card.cp-card-minimal {
        padding: 12px;
        border-radius: 16px;
    }

    .copella-creator-card.cp-card-minimal .cp-card-header {
        margin-bottom: 8px;
    }

    .copella-creator-card.cp-card-minimal .cp-card-avatar img,
    .copella-creator-card.cp-card-minimal .cp-card-avatar-placeholder {
        width: 40px;
        height: 40px;
    }

    .copella-creator-card.cp-card-minimal .cp-card-avatar-placeholder {
        font-size: 16px;
    }

    .copella-creator-card.cp-card-minimal .cp-card-name {
        font-size: 14px;
    }

    .copella-creator-card.cp-card-minimal .cp-card-description {
        font-size: 12px;
        margin-bottom: 8px;
    }

    .copella-creator-card.cp-card-minimal .cp-card-section-title {
        font-size: 12px;
        margin-bottom: 6px;
    }

    /* Адаптивность */
    @media (max-width: 768px) {
        .copella-creator-card {
            padding: 20px;
            border-radius: 20px;
        }

        .copella-creator-card .cp-card-header {
            gap: 12px;
        }

        .copella-creator-card .cp-card-avatar img,
        .copella-creator-card .cp-card-avatar-placeholder {
            width: 56px;
            height: 56px;
        }

        .copella-creator-card .cp-card-name {
            font-size: 18px;
        }

        .copella-creator-card .cp-card-social-list {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .copella-creator-card {
            padding: 16px;
        }

        .copella-creator-card .cp-card-header {
            flex-direction: column;
            text-align: center;
            gap: 8px;
        }

        .copella-creator-card .cp-card-stats {
            justify-content: center;
        }

        .copella-creator-card .cp-card-social-list {
            flex-direction: column;
            align-items: center;
        }

        .copella-creator-card .cp-card-social-link {
            width: 100%;
            max-width: 200px;
            justify-content: center;
        }
    }
    </style>

    <script>
    (function() {
        var card = document.getElementById('<?php echo esc_js($uid); ?>');
        if (!card) return;
        
        var copyBtn = card.querySelector('.cp-copy-btn');
        if (!copyBtn) return;
        
        copyBtn.addEventListener('click', function() {
            var creatorId = this.getAttribute('data-creator-id');
            var shortcode = '[creator_card id="' + creatorId + '"]';
            
            // Копируем в буфер обмена
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    showCopySuccess(copyBtn);
                });
            } else {
                // Fallback для старых браузеров
                var textArea = document.createElement('textarea');
                textArea.value = shortcode;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopySuccess(copyBtn);
            }
        });
        
        function showCopySuccess(btn) {
            var originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/></svg>';
            btn.classList.add('copied');
            
            setTimeout(function() {
                btn.innerHTML = originalHTML;
                btn.classList.remove('copied');
            }, 2000);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Шорткод для отображения списка креаторов
 * [creators_list category="gaming" limit="6" style="grid"]
 */
add_shortcode('creators_list', function($atts): string {
    $a = shortcode_atts([
        'category' => '',
        'limit' => '6',
        'style' => 'grid', // grid, list, carousel
        'show_social' => 'true',
        'show_achievements' => 'true',
        'orderby' => 'date',
        'order' => 'DESC',
    ], $atts, 'creators_list');

    $args = array(
        'post_type' => 'creator',
        'post_status' => 'publish',
        'posts_per_page' => (int) $a['limit'],
        'orderby' => $a['orderby'],
        'order' => $a['order'],
    );

    if (!empty($a['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'creator_category',
                'field' => 'slug',
                'terms' => $a['category'],
            ),
        );
    }

    $creators = get_posts($args);
    
    if (empty($creators)) {
        return '<p>' . __('Креаторы не найдены', 'copella-topics') . '</p>';
    }

    $uid = 'creators_list_' . wp_generate_password(8, false, false);
    $style_class = 'cp-list-' . $a['style'];
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($uid); ?>" class="copella-creators-list <?php echo esc_attr($style_class); ?>">
        <?php foreach ($creators as $creator): ?>
            <?php echo copella_render_creator_card($creator->ID, array(
                'show_social' => $a['show_social'],
                'show_achievements' => $a['show_achievements'],
                'style' => 'default',
                'copy_shortcode' => 'true',
            )); ?>
        <?php endforeach; ?>
    </div>

    <style>
    .copella-creators-list {
        display: grid;
        gap: 24px;
        max-width: 100%;
    }

    .copella-creators-list.cp-list-grid {
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }

    .copella-creators-list.cp-list-list {
        grid-template-columns: 1fr;
    }

    .copella-creators-list.cp-list-carousel {
        display: flex;
        overflow-x: auto;
        gap: 20px;
        padding: 20px 0;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
    }

    .copella-creators-list.cp-list-carousel::-webkit-scrollbar {
        display: none;
    }

    .copella-creators-list.cp-list-carousel .copella-creator-card {
        flex: 0 0 320px;
        scroll-snap-align: start;
    }

    @media (max-width: 768px) {
        .copella-creators-list.cp-list-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .copella-creators-list.cp-list-carousel .copella-creator-card {
            flex: 0 0 280px;
        }
    }
    </style>
    <?php
    return ob_get_clean();
});

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
        $custom_template = COPELLA_TOPICS_PLUGIN_DIR . 'templates/single-creator.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    if (is_post_type_archive('creator')) {
        $custom_template = COPELLA_TOPICS_PLUGIN_DIR . 'templates/archive-creator.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
});