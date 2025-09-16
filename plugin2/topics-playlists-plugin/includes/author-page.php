<?php
if (!defined('ABSPATH')) { exit; }

// Page meta for author page: social networks
const COPELLA_AUTHORPAGE_META_SOCIAL = '_copella_authorpage_social';

add_action('add_meta_boxes', function(): void{
    add_meta_box(
        'copella_authorpage_social',
        __('Социальные сети автора', 'copella-topics'),
        function(WP_Post $post): void {
            $social_raw = (string) get_post_meta($post->ID, COPELLA_AUTHORPAGE_META_SOCIAL, true);
            ?>
            <p>
                <label for="copella_authorpage_social"><strong><?php _e('Социальные сети (по одной в строке)', 'copella-topics'); ?></strong></label><br/>
                <textarea id="copella_authorpage_social" name="copella_authorpage_social" class="widefat" rows="4" placeholder="YouTube | https://youtube.com/@username | 🎥&#10;Telegram | https://t.me/username | 📱&#10;Instagram | https://instagram.com/username | 📸"><?php echo esc_textarea($social_raw); ?></textarea>
            </p>
            <p style="opacity:.8;margin-top:-4px"><?php _e('Формат: Название | Ссылка | Иконка (эмодзи или текст). Иконка необязательна.', 'copella-topics'); ?></p>
            <?php
        },
        'page',
        'normal',
        'default'
    );
});

add_action('save_post_page', function(int $post_id): void{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['copella_authorpage_social'])) {
        $raw = (string) $_POST['copella_authorpage_social'];
        // Save as-is; validation happens on render
        update_post_meta($post_id, COPELLA_AUTHORPAGE_META_SOCIAL, $raw);
    }
});

// Shortcode: [author_page] — full author page built from a WP Page
add_shortcode('author_page', function($atts): string {
    $a = shortcode_atts([
        'id' => '0',
        'page_id' => '0',
    ], $atts, 'author_page');

    $pid = (int) ($a['id'] ?: $a['page_id']);
    if ($pid <= 0) {
        $pid = get_the_ID();
    }
    if ($pid <= 0) return '';

    $name = get_the_title($pid);
    $avatar = get_the_post_thumbnail_url($pid, 'thumbnail');
    $desc = get_post_field('post_excerpt', $pid);
    if ($desc === '') {
        $raw = get_post_field('post_content', $pid);
        $desc = wp_trim_words(wp_strip_all_tags($raw), 40, '…');
    }
    $social_raw = (string) get_post_meta($pid, COPELLA_AUTHORPAGE_META_SOCIAL, true);
    $social_networks = [];
    if ($social_raw !== '') {
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $social_raw)));
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 2) {
                $social_networks[] = [
                    'name' => sanitize_text_field($parts[0]),
                    'url'  => esc_url($parts[1]),
                    'icon' => isset($parts[2]) ? sanitize_text_field($parts[2]) : ''
                ];
            }
        }
    }

    // Collect projects linked to this author page (topic_author with meta page_id = current page)
    $projects = get_posts([
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'meta_key' => COPELLA_AUTHOR_META_PAGE_ID,
        'meta_value' => $pid,
        'posts_per_page' => 200,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    $videos = [];
    $streams = [];
    foreach ($projects as $prj) {
        $type = (string) get_post_meta($prj->ID, COPELLA_AUTHOR_META_TYPE, true);
        if ($type !== 'stream') $videos[] = $prj; else $streams[] = $prj;
    }

    ob_start();
    ?>
    <section class="copella-author-page" id="copella-authorpage-<?php echo (int)$pid; ?>">
        <header class="ap-header">
            <?php if ($avatar): ?><img class="ap-avatar" src="<?php echo esc_url($avatar); ?>" alt="" loading="lazy"/><?php endif; ?>
            <div class="ap-head">
                <h1 class="ap-name"><?php echo esc_html($name); ?></h1>
                <div class="ap-subscribe-wrapper">
                    <button class="ap-subscribe-btn" data-author-id="<?php echo (int)$pid; ?>">
                        <?php echo esc_html__('Подписаться', 'copella-topics'); ?>
                    </button>
                </div>
                <?php if (!empty($desc)): ?><div class="ap-desc"><?php echo esc_html($desc); ?></div><?php endif; ?>
            </div>
        </header>

        <?php if (!empty($videos)): ?>
        <section class="ap-section ap-projects-video">
            <h2 class="ap-section-title"><?php echo esc_html__('Видео', 'copella-topics'); ?></h2>
            <div class="ap-cards">
                <?php foreach ($videos as $prj): ?>
                    <a class="ap-card" href="<?php echo esc_url(get_permalink($prj->ID)); ?>">
                        <?php $pth = get_the_post_thumbnail_url($prj->ID, 'thumbnail'); if ($pth): ?><img class="ap-card-thumb" src="<?php echo esc_url($pth); ?>" alt=""/><?php endif; ?>
                        <div class="ap-card-info">
                            <div class="ap-card-title"><?php echo esc_html(get_the_title($prj->ID)); ?></div>
                            <div class="ap-card-cat"><?php echo esc_html__('Видео', 'copella-topics'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($streams)): ?>
        <section class="ap-section ap-projects-stream">
            <h2 class="ap-section-title"><?php echo esc_html__('Стримы', 'copella-topics'); ?></h2>
            <div class="ap-cards">
                <?php foreach ($streams as $prj): ?>
                    <a class="ap-card" href="<?php echo esc_url(get_permalink($prj->ID)); ?>">
                        <?php $pth = get_the_post_thumbnail_url($prj->ID, 'thumbnail'); if ($pth): ?><img class="ap-card-thumb" src="<?php echo esc_url($pth); ?>" alt=""/><?php endif; ?>
                        <div class="ap-card-info">
                            <div class="ap-card-title"><?php echo esc_html(get_the_title($prj->ID)); ?></div>
                            <div class="ap-card-cat"><?php echo esc_html__('Стримы', 'copella-topics'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php 
        // Get playlists for this author
        $playlist_ids_raw = (string) get_post_meta($pid, COPELLA_AUTHOR_META_PLAYLISTS, true);
        $playlist_ids = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $playlist_ids_raw)));
        if (!empty($playlist_ids)): 
        ?>
        <section class="ap-section ap-playlists">
            <h2 class="ap-section-title"><?php echo esc_html__('Плейлисты', 'copella-topics'); ?></h2>
            <div class="ap-playlists-carousel">
                <?php foreach ($playlist_ids as $playlist_id): 
                    $playlist_title = get_the_title($playlist_id);
                    $playlist_thumb = get_the_post_thumbnail_url($playlist_id, 'medium');
                    $playlist_link = get_permalink($playlist_id);
                    if ($playlist_title && $playlist_link):
                ?>
                    <div class="ap-playlist-card">
                        <a href="<?php echo esc_url($playlist_link); ?>" class="ap-playlist-link">
                            <?php if ($playlist_thumb): ?>
                                <img class="ap-playlist-thumb" src="<?php echo esc_url($playlist_thumb); ?>" alt="<?php echo esc_attr($playlist_title); ?>" loading="lazy"/>
                            <?php endif; ?>
                            <div class="ap-playlist-info">
                                <h3 class="ap-playlist-title"><?php echo esc_html($playlist_title); ?></h3>
                                <div class="ap-playlist-meta"><?php echo esc_html__('Плейлист', 'copella-topics'); ?></div>
                            </div>
                        </a>
                    </div>
                <?php 
                    endif;
                endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($social_networks)): ?>
        <section class="ap-section ap-socials">
            <h2 class="ap-section-title"><?php echo esc_html__('Социальные сети', 'copella-topics'); ?></h2>
            <ul class="ap-social-list">
                <?php foreach ($social_networks as $sn): ?>
                <li><a href="<?php echo esc_url($sn['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo $sn['icon'] ? esc_html($sn['icon']) . ' ' : ''; ?><?php echo esc_html($sn['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </section>
    <style>
    .copella-author-page {
        background: #1a1a1a;
        color: #ffffff;
        padding: 24px;
        border-radius: 16px;
        margin: 20px 0;
    }
    .copella-author-page .ap-header {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        margin-bottom: 32px;
    }
    .copella-author-page .ap-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255,255,255,0.1);
        flex-shrink: 0;
    }
    .copella-author-page .ap-head {
        flex: 1;
    }
    .copella-author-page .ap-name {
        margin: 0 0 16px 0;
        font-size: 2.2em;
        font-weight: 700;
        color: #ffffff;
    }
    .copella-author-page .ap-subscribe-wrapper {
        margin-bottom: 16px;
    }
    .copella-author-page .ap-subscribe-btn {
        background: #ffffff;
        color: #000000;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1em;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .copella-author-page .ap-subscribe-btn:hover {
        background: #f0f0f0;
        transform: translateY(-1px);
    }
    .copella-author-page .ap-desc {
        opacity: 0.9;
        font-size: 1.1em;
        line-height: 1.6;
        color: #cccccc;
    }
    .copella-author-page .ap-section {
        margin: 32px 0;
    }
    .copella-author-page .ap-section-title {
        font-size: 1.5em;
        font-weight: 600;
        margin-bottom: 20px;
        color: #ffffff;
    }
    .copella-author-page .ap-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    .copella-author-page .ap-card {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 16px;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        background: rgba(255,255,255,0.05);
        text-decoration: none;
        color: inherit;
        transition: all 0.2s ease;
    }
    .copella-author-page .ap-card:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-2px);
    }
    .copella-author-page .ap-card-thumb {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
    }
    .copella-author-page .ap-card-title {
        font-weight: 600;
        color: #ffffff;
    }
    .copella-author-page .ap-card-cat {
        font-size: 0.9em;
        opacity: 0.7;
        color: #cccccc;
    }
    .copella-author-page .ap-playlists-carousel {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .copella-author-page .ap-playlist-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    .copella-author-page .ap-playlist-card:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-2px);
    }
    .copella-author-page .ap-playlist-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .copella-author-page .ap-playlist-thumb {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    .copella-author-page .ap-playlist-info {
        padding: 16px;
    }
    .copella-author-page .ap-playlist-title {
        font-size: 1.2em;
        font-weight: 600;
        margin: 0 0 8px 0;
        color: #ffffff;
    }
    .copella-author-page .ap-playlist-meta {
        font-size: 0.9em;
        opacity: 0.7;
        color: #cccccc;
    }
    .copella-author-page .ap-social-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .copella-author-page .ap-social-list a {
        text-decoration: none;
        color: #ffffff;
        padding: 8px 16px;
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .copella-author-page .ap-social-list a:hover {
        background: rgba(255,255,255,0.2);
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Subscribe button functionality
        const subscribeBtns = document.querySelectorAll('.ap-subscribe-btn');
        subscribeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const authorId = this.getAttribute('data-author-id');
                const originalText = this.textContent;
                
                // Visual feedback
                this.textContent = 'Подписываемся...';
                this.disabled = true;
                
                // Simulate subscription (you can replace this with actual AJAX call)
                setTimeout(() => {
                    this.textContent = 'Подписан ✓';
                    this.style.background = '#4CAF50';
                    this.style.color = '#ffffff';
                    
                    // Store subscription state in localStorage
                    localStorage.setItem('copella_subscribed_' + authorId, 'true');
                }, 1000);
            });
            
            // Check if already subscribed
            const authorId = btn.getAttribute('data-author-id');
            if (localStorage.getItem('copella_subscribed_' + authorId) === 'true') {
                btn.textContent = 'Подписан ✓';
                btn.style.background = '#4CAF50';
                btn.style.color = '#ffffff';
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
});


