<?php
if (!defined('ABSPATH')) {
    exit;
}

// Подключаем вспомогательные функции
require_once __DIR__ . '/helpers.php';

// Подключаем шорткоды для авторов
require_once __DIR__ . '/shortcodes-authors.php';

// Подключаем шорткоды для тем
require_once __DIR__ . '/shortcodes-topics.php';

// Подключаем шорткоды для плейлистов
require_once __DIR__ . '/shortcodes-playlists.php';

/**
 * Bridge: [cpplayer_stream] - Render CP Player stream using author data from topics plugin
 */
function copella_topics_render_cpplayer_stream(array $atts = []): string
{
    $a = shortcode_atts([
        'author_id' => '0',
        'src' => '',
        'title' => '',
    ], $atts, 'cpplayer_stream');

    $aid = (int) $a['author_id'];
    $src = trim((string)$a['src']);
    $title = (string)$a['title'];
    if ($aid <= 0 || $src === '') {
        return '';
    }

    $name = get_the_title($aid) ?: '';
    $avatar = get_the_post_thumbnail_url($aid, 'thumbnail') ?: '';
    $link = get_permalink($aid);

    $cpplayer_atts = [
        'src' => $src,
        'type' => 'stream',
        'title' => $title ?: $name,
        'stream_author_name' => $name,
        'stream_author_avatar' => $avatar,
        'stream_author_link' => $link,
    ];

    $parts = [];
    foreach ($cpplayer_atts as $k => $v) {
        if ($v === '' || $v === null) continue;
        $parts[] = $k . '="' . esc_attr($v) . '"';
    }
    $shortcode = '[cpplayer ' . implode(' ', $parts) . ']';
    // Also ping live status endpoint optimistically (cpplayer will also report)
    add_action('wp_footer', function() use ($aid) {
        ?>
        <script>
        (function(){
            try {
                if (!window.copellaTopicsPinged) {
                    window.copellaTopicsPinged = true;
                    var fd = new FormData();
                    fd.append('action','copella_topics_report_live');
                    fd.append('author_id','<?php echo (int)$aid; ?>');
                    fd.append('status','on');
                    fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', { method:'POST', body: fd, credentials:'same-origin' });
                }
            } catch(_){}
        })();
        </script>
        <?php
    });
    return do_shortcode($shortcode);
}

/**
 * Регистрируем все шорткоды
 */
add_action('init', function (): void {
    add_shortcode('hot_topics', 'copella_topics_render_hot_topics');
    add_shortcode('topic_playlist', 'copella_topics_render_playlist');
    add_shortcode('playlist_authors', 'copella_topics_render_authors');
    add_shortcode('playlist_author', 'copella_topics_render_author');
    add_shortcode('video_authors', 'copella_topics_render_video_authors');
    add_shortcode('stream_authors', 'copella_topics_render_stream_authors');
    // Bridge shortcode to render CP Player stream with author data from this plugin
    add_shortcode('cpplayer_stream', 'copella_topics_render_cpplayer_stream');
    // Attach author page to author card rendering (filter output by adding link if exists)
});

