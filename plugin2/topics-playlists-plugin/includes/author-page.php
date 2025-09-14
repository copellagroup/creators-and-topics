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
    .copella-author-page .ap-header{display:flex;gap:16px;align-items:center;margin-bottom:16px}
    .copella-author-page .ap-avatar{width:96px;height:96px;border-radius:50%;object-fit:cover;border:2px solid rgba(0,0,0,.1)}
    .copella-author-page .ap-name{margin:0 0 6px 0}
    .copella-author-page .ap-desc{opacity:.9}
    .copella-author-page .ap-section{margin:24px 0}
    .copella-author-page .ap-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
    .copella-author-page .ap-card{display:flex;gap:10px;align-items:center;padding:8px 10px;border:1px solid #e5e5e5;border-radius:10px;background:#fff;text-decoration:none;color:inherit}
    .copella-author-page .ap-card-thumb{width:42px;height:42px;border-radius:8px;object-fit:cover}
    .copella-author-page .ap-card-title{font-weight:600}
    .copella-author-page .ap-card-cat{font-size:.9em;opacity:.75}
    .copella-author-page .ap-social-list{list-style:none;padding:0;margin:0;display:flex;gap:12px;flex-wrap:wrap}
    .copella-author-page .ap-social-list a{text-decoration:none}
    </style>
    <?php
    return ob_get_clean();
});


