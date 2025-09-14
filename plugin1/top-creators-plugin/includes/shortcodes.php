<?php
if (!defined('ABSPATH')) {
    exit;
}

// Подключаем вспомогательные функции
require_once __DIR__ . '/helpers.php';

/**
 * Регистрируем все шорткоды
 */
add_action('init', function (): void {
    add_shortcode('top_creators', 'copella_creators_render_top_creators');
    add_shortcode('creator_page', 'copella_creators_render_creator_page');
});

// --- TOP CREATORS SHORTCODE ---

function copella_creators_render_top_creators($atts = array()): string {
    $opts = wp_parse_args(get_option(COPELLA_CREATORS_OPTION, array()), copella_creators_default_options());

    $a = shortcode_atts(array(
        'title' => (string) ($opts['title'] ?? ''),
        'card_radius' => '16',
        'gap' => '20',
    ), $atts, 'top_creators');

    $title = trim((string) $a['title']);
    $gap = (int) $a['gap'];
    $radius = (int) $a['card_radius'];

    wp_enqueue_style('copella-creators-styles');

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
}

// --- CREATOR PAGE SHORTCODE ---

function copella_creators_render_creator_page($atts = array()): string {
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
    $topics_author_id = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_TOPICS_AUTHOR, true);
    
    // If topics author is selected, use their data
    if ($topics_author_id > 0) {
        $topics_author = get_post($topics_author_id);
        if ($topics_author && $topics_author->post_type === 'topic_author') {
            // Use topics author data if creator fields are empty
            if (empty($name)) $name = get_the_title($topics_author_id);
            if (empty($avatar)) $avatar = get_the_post_thumbnail_url($topics_author_id, 'large');
            if (empty($excerpt)) $excerpt = get_post_field('post_excerpt', $topics_author_id);
            if (empty($description)) $description = get_post_field('post_content', $topics_author_id);
            
            // Get topics author social networks
            $topics_social_raw = (string) get_post_meta($topics_author_id, '_copella_author_social', true);
            if (empty($social_raw) && !empty($topics_social_raw)) {
                $social_raw = $topics_social_raw;
            }
            
            // Get topics author playlists
            $topics_playlists_raw = (string) get_post_meta($topics_author_id, '_copella_author_playlists', true);
            if (empty($playlists_raw) && !empty($topics_playlists_raw)) {
                $playlists_raw = $topics_playlists_raw;
            }
            
            // Get author type and additional info
            $author_type = (string) get_post_meta($topics_author_id, '_copella_author_type', true);
            $author_desc = (string) get_post_meta($topics_author_id, '_copella_author_desc', true);
            
            // Add type info to specialization if not set
            if (empty($specialization)) {
                if ($author_type === 'stream') {
                    $specialization = __('Автор стримов', 'copella-creators');
                } else {
                    $specialization = __('Автор видео', 'copella-creators');
                }
            }
            
            // Use author description if excerpt is empty
            if (empty($excerpt) && !empty($author_desc)) {
                $excerpt = $author_desc;
            }
        }
    }

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
    wp_enqueue_style('copella-creators-styles');

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
                    <?php if (count($achievements) === 1): ?>
                        <!-- Single achievement - display as highlighted card -->
                        <div class="cp-single-achievement">
                            <div class="cp-achievement-icon">🏆</div>
                            <div class="cp-achievement-content">
                                <h3 class="cp-achievement-title"><?php _e('Достижение', 'copella-creators'); ?></h3>
                                <p class="cp-achievement-text"><?php echo esc_html($achievements[0]); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Multiple achievements - display as list -->
                        <ul class="cp-achievements-list">
                            <?php foreach ($achievements as $achievement): ?>
                                <li class="cp-achievement-item"><?php echo esc_html($achievement); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
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
                            <?php 
                            // Use Topics plugin shortcode for consistent design
                            echo do_shortcode('[topic_playlist playlist_id="' . (int) $playlist->ID . '"]');
                            ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php if ($topics_author_id > 0): ?>
                <!-- Use Topics author page shortcode for consistent design -->
                <section class="cp-creator-section cp-creator-topics-author">
                    <h2 class="cp-section-title"><?php _e('Автор из Topics', 'copella-creators'); ?></h2>
                    <?php echo do_shortcode('[topic_author author_id="' . (int) $topics_author_id . '"]'); ?>
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
    
    .cp-single-achievement {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 25px 30px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.08));
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
    }
    
    .cp-achievement-icon {
        font-size: 48px;
        flex: 0 0 auto;
        text-align: center;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .cp-achievement-content {
        flex: 1;
        min-width: 0;
    }
    
    .cp-achievement-title {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
    }
    
    .cp-achievement-text {
        margin: 0;
        font-size: 18px;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.9);
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
        
        .cp-single-achievement {
            flex-direction: column;
            text-align: center;
            gap: 15px;
            padding: 20px;
        }
        
        .cp-achievement-icon {
            width: 60px;
            height: 60px;
            font-size: 36px;
        }
        
        .cp-achievement-title {
            font-size: 18px;
        }
        
        .cp-achievement-text {
            font-size: 16px;
        }
    }
    </style>
    
    <?php
    return ob_get_clean();
}