<?php

if (!defined('ABSPATH')) {
    exit;
}


const COPELLA_TOPICS_META_LINK      = '_copella_topic_link';
const COPELLA_TOPICS_META_VIDEOS    = '_copella_topic_videos';
const COPELLA_PLAYLIST_META_ITEMS   = '_copella_playlist_items';
const COPELLA_AUTHOR_META_PLAYLISTS = '_copella_author_playlists';
const COPELLA_AUTHOR_META_SOCIAL    = '_copella_author_social';
const COPELLA_AUTHOR_META_DESC      = '_copella_author_desc';
const COPELLA_AUTHOR_META_TYPE      = '_copella_author_type'; // 'video' | 'stream'
const COPELLA_AUTHOR_META_STREAM_URL = '_copella_author_stream_url';
const COPELLA_AUTHOR_META_IS_LIVE   = '_copella_author_is_live'; // '1' | '0'
const COPELLA_AUTHOR_META_PROGRAM_ID = '_copella_author_program_id';
const COPELLA_AUTHOR_META_STREAM_INFO = '_copella_author_stream_info';
// New links
const COPELLA_AUTHOR_META_PAGE_ID = '_copella_author_page_id';
const COPELLA_AUTHOR_META_WP_USER_ID = '_copella_author_wp_user_id';

function copella_topics_add_meta_boxes(): void
{
    add_meta_box(
        'copella_topic_details',
        __('Настройки темы', 'copella-topics'),
        'copella_topics_render_meta_box',
        'topic',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'copella_topics_add_meta_boxes');

function copella_topics_render_meta_box(WP_Post $post): void
{
    wp_nonce_field('copella_topics_meta', 'copella_topics_meta_nonce');
    $link   = (string) get_post_meta($post->ID, COPELLA_TOPICS_META_LINK, true);
    ?>
    <p>
        <label for="copella_topic_link"><strong><?php _e('Ссылка для темы (необязательно)', 'copella-topics'); ?></strong></label><br/>
        <input type="url" id="copella_topic_link" name="copella_topic_link" value="<?php echo esc_attr($link); ?>" class="widefat" placeholder="https://example.com/страница-темы"/>
    </p>
    <p><em><?php _e('Плейлисты создаются отдельно в разделе «Плейлисты». Вставляйте их на страницу темы через шорткод из плейлиста.', 'copella-topics'); ?></em></p>
    <?php
}

function copella_topics_save_meta_boxes(int $post_id): void
{
    if (!isset($_POST['copella_topics_meta_nonce']) || !wp_verify_nonce((string) $_POST['copella_topics_meta_nonce'], 'copella_topics_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $link = isset($_POST['copella_topic_link']) ? esc_url_raw((string) $_POST['copella_topic_link']) : '';
    update_post_meta($post_id, COPELLA_TOPICS_META_LINK, $link);
}
add_action('save_post_topic', 'copella_topics_save_meta_boxes');

function copella_playlist_add_meta_boxes(): void
{
    add_meta_box(
        'copella_playlist_items',
        __('Содержимое плейлиста', 'copella-topics'),
        'copella_playlist_render_meta_box',
        'topic_playlist',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'copella_playlist_add_meta_boxes');

function copella_playlist_render_meta_box(WP_Post $post): void
{
    wp_nonce_field('copella_playlist_meta', 'copella_playlist_meta_nonce');
    $items = (string) get_post_meta($post->ID, COPELLA_PLAYLIST_META_ITEMS, true);
    ?>
    <div class="copella-pl-finder" style="padding:10px 12px;border:1px solid rgba(0,0,0,.1);border-radius:8px;background:#fff;margin:0 0 14px 0">
        <p style="margin:0 0 8px 0"><strong><?php _e('Поиск и добавление записей в список', 'copella-topics'); ?></strong></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
            <input type="text" id="cp-pl-search-q" class="regular-text" placeholder="<?php esc_attr_e('Поиск записей…', 'copella-topics'); ?>"/>
            <input type="text" id="cp-pl-search-pt" class="regular-text" placeholder="post, topic" value="post"/>
            <input type="text" id="cp-pl-search-tax" class="regular-text" placeholder="category"/>
            <input type="text" id="cp-pl-search-terms" class="regular-text" placeholder="slugs: sezon-1,sezon-2"/>
            <button type="button" class="button" id="cp-pl-search-btn"><?php _e('Найти', 'copella-topics'); ?></button>
        </div>
        <div id="cp-pl-search-results" style="max-height:260px;overflow:auto;border:1px solid #ddd;border-radius:6px;padding:8px;display:none"></div>
        <div style="display:flex;gap:8px;margin-top:8px;align-items:center">
            <button type="button" class="button button-primary" id="cp-pl-copy-all"><?php _e('Скопировать ссылки', 'copella-topics'); ?></button>
            <span style="opacity:.75"><?php _e('Скопирует все найденные ссылки (по одной в строке) в буфер обмена.', 'copella-topics'); ?></span>
        </div>
    </div>
    
    <p>
        <label for="copella_playlist_items"><strong><?php _e('Ссылки или ID записей (по одной в строке). Для категорий используйте заголовки с #', 'copella-topics'); ?></strong></label>
        <textarea id="copella_playlist_items" name="copella_playlist_items" class="widefat" rows="10" placeholder="# 1 сезон\n123 | tag=Премьера | color=#FF653A\nhttps://site.com/post... | tag=Live | color=#00D084\n\n# 2 сезон\n456"><?php echo esc_textarea($items); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Можно добавлять метки для видео: допишите после ID/ссылки через вертикальную черту, например', 'copella-topics'); ?>
        <code>123 | tag=Премьера | color=#FF653A</code>.
    </p>
    <p>
        <label><strong><?php _e('Шорткод плейлиста', 'copella-topics'); ?></strong></label><br/>
        <input type="text" readonly class="widefat" value="[topic_playlist playlist_id=&quot;<?php echo (int) $post->ID; ?>&quot;]" onclick="this.select();document.execCommand('copy');"/>
    </p>
    <?php
}

function copella_playlist_save_meta_boxes(int $post_id): void
{
    if (!isset($_POST['copella_playlist_meta_nonce']) || !wp_verify_nonce((string) $_POST['copella_playlist_meta_nonce'], 'copella_playlist_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $raw = isset($_POST['copella_playlist_items']) ? (string) $_POST['copella_playlist_items'] : '';
    $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $raw)));
    $store = implode("\n", $lines);
    update_post_meta($post_id, COPELLA_PLAYLIST_META_ITEMS, $store);

    // В упрощённом режиме сохраняем только текстовый список
}
add_action('save_post_topic_playlist', 'copella_playlist_save_meta_boxes');

/**
 * Author meta: select playlists for the author
 */
function copella_author_add_meta_boxes(): void
{
    add_meta_box(
        'copella_author_type',
        __('Тип автора', 'copella-topics'),
        'copella_author_render_type_meta_box',
        'topic_author',
        'side',
        'high'
    );
    add_meta_box(
        'copella_author_playlists',
        __('Плейлисты автора', 'copella-topics'),
        'copella_author_render_meta_box',
        'topic_author',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_author_desc',
        __('Описание автора', 'copella-topics'),
        'copella_author_render_desc_meta_box',
        'topic_author',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_author_links',
        __('Связи', 'copella-topics'),
        'copella_author_render_links_meta_box',
        'topic_author',
        'side',
        'default'
    );
    add_meta_box(
        'copella_author_stream',
        __('Настройки стрима', 'copella-topics'),
        'copella_author_render_stream_meta_box',
        'topic_author',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'copella_author_add_meta_boxes');

function copella_author_render_type_meta_box(WP_Post $post): void
{
    $type = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_TYPE, true);
    if ($type !== 'stream' && $type !== 'video') { $type = 'video'; }
    ?>
    <p style="margin:0 0 8px 0"><strong><?php _e('Выберите тип автора', 'copella-topics'); ?></strong></p>
    <p style="margin:0 0 8px 0">
        <label><input type="radio" name="copella_author_type" value="video" <?php checked($type === 'video'); ?>/> <?php _e('Автор видео', 'copella-topics'); ?></label><br/>
        <label><input type="radio" name="copella_author_type" value="stream" <?php checked($type === 'stream'); ?>/> <?php _e('Автор стрима', 'copella-topics'); ?></label>
    </p>
    
    <?php
}

function copella_author_render_meta_box(WP_Post $post): void
{
    wp_nonce_field('copella_author_meta', 'copella_author_meta_nonce');
    // Backup current excerpt to defend against accidental clearing during save
    $current_excerpt = get_post_field('post_excerpt', $post->ID);
    $ids_raw = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_PLAYLISTS, true);
    $selected = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $ids_raw)));
    $selected_map = array_fill_keys($selected, true);
    
    $social_raw = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_SOCIAL, true);
    $social_networks = array();
    if (!empty($social_raw)) {
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $social_raw)));
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 2) {
                $social_networks[] = array(
                    'name' => $parts[0],
                    'url' => $parts[1],
                    'icon' => isset($parts[2]) ? $parts[2] : ''
                );
            }
        }
    }

    $q = new WP_Query(array(
        'post_type' => 'topic_playlist',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 500,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ));
    $items = array();
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $items[] = array(
                'id' => $pid,
                'title' => get_the_title($pid),
                'thumb' => get_the_post_thumbnail_url($pid, 'thumbnail'),
                'status' => get_post_status($pid),
            );
        }
        wp_reset_postdata();
    }
    ?>
    <div class="cp-au-picker" id="cp-author-video-settings" style="padding:10px 12px;border:1px solid rgba(0,0,0,.1);border-radius:8px;background:#fff;margin:0 0 14px 0">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
            <input type="text" id="cp-au-search" class="regular-text" placeholder="<?php esc_attr_e('Поиск плейлистов…', 'copella-topics'); ?>"/>
            <span style="opacity:.75"><?php printf(__('Всего: %d', 'copella-topics'), (int) count($items)); ?></span>
            <span id="cp-au-picked-count" style="margin-left:auto;opacity:.75"><?php printf(__('Выбрано: %d', 'copella-topics'), (int) count($selected)); ?></span>
        </div>
        <div id="cp-au-list" style="max-height:320px;overflow:auto;border:1px solid #ddd;border-radius:6px;padding:8px;background:#fff">
            <?php if (empty($items)): ?>
                <div style="opacity:.7;padding:6px 0"><?php _e('Плейлистов не найдено.', 'copella-topics'); ?></div>
            <?php else: foreach ($items as $pl): ?>
                <label class="cp-au-row" data-title="<?php echo esc_attr(function_exists('mb_strtolower') ? mb_strtolower($pl['title'], 'UTF-8') : strtolower($pl['title'])); ?>" style="display:flex;align-items:center;gap:10px;margin:6px 0">
                    <input type="checkbox" class="cp-au-pick" name="copella_author_picks[]" value="<?php echo (int) $pl['id']; ?>" <?php checked(isset($selected_map[$pl['id']])); ?>/>
                    <?php if ($pl['thumb']): ?><img src="<?php echo esc_url($pl['thumb']); ?>" width="40" height="40" style="object-fit:cover;border-radius:6px" alt=""/><?php endif; ?>
                    <span><?php echo esc_html($pl['title']); ?> <span style="opacity:.6">#<?php echo (int) $pl['id']; ?></span></span>
                    <?php if ($pl['status'] !== 'publish'): ?><span style="margin-left:auto;opacity:.65;"><em><?php echo esc_html($pl['status']); ?></em></span><?php endif; ?>
                </label>
            <?php endforeach; endif; ?>
        </div>
        <input type="hidden" id="copella_author_playlists" name="copella_author_playlists" value="<?php echo esc_attr(implode(',', $selected)); ?>"/>
    </div>
    <p style="opacity:.8;margin:6px 0 0 0"><?php _e('Выберите плейлисты из списка выше. Поиск работает по заголовку.', 'copella-topics'); ?></p>
    <input type="hidden" name="copella_author_excerpt_backup" value="<?php echo esc_attr($current_excerpt); ?>"/>
    
    <p>
        <label for="copella_author_social"><strong><?php _e('Социальные сети (по одной в строке)', 'copella-topics'); ?></strong></label><br/>
        <textarea id="copella_author_social" name="copella_author_social" class="widefat" rows="4" placeholder="YouTube | https://youtube.com/@username | 🎥&#10;Telegram | https://t.me/username | 📱&#10;Instagram | https://instagram.com/username | 📸"><?php 
            if (!empty($social_networks)) {
                foreach ($social_networks as $network) {
                    echo esc_textarea($network['name'] . ' | ' . $network['url'] . ' | ' . $network['icon']) . "\n";
                }
            }
        ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Формат: Название | Ссылка | Иконка (эмодзи или текст). Иконка необязательна.', 'copella-topics'); ?>
    </p>
    
    <p>
        <label><strong><?php _e('Шорткод автора', 'copella-topics'); ?></strong></label><br/>
        <input type="text" readonly class="widefat" value="[playlist_author author_id=&quot;<?php echo (int) $post->ID; ?>&quot;]" onclick="this.select();document.execCommand('copy');"/>
    </p>
    <?php
}

function copella_author_render_desc_meta_box(WP_Post $post): void
{
    $desc = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_DESC, true);
    ?>
    <p>
        <label for="copella_author_desc"><strong><?php _e('Краткое описание (показывается в карточке автора)', 'copella-topics'); ?></strong></label><br/>
        <textarea id="copella_author_desc" name="copella_author_desc" class="widefat" rows="4" placeholder="<?php echo esc_attr__('Краткое описание автора…', 'copella-topics'); ?>"><?php echo esc_textarea($desc); ?></textarea>
    </p>
    <?php
}

function copella_author_render_links_meta_box(WP_Post $post): void
{
    $page_id = (int) get_post_meta($post->ID, COPELLA_AUTHOR_META_PAGE_ID, true);
    ?>
    <p>
        <label for="copella_author_page_id"><strong><?php _e('Страница автора', 'copella-topics'); ?></strong></label><br/>
        <?php
        wp_dropdown_pages([
            'name' => 'copella_author_page_id',
            'id' => 'copella_author_page_id',
            'selected' => $page_id,
            'show_option_none' => __('— Не выбрано —', 'copella-topics'),
        ]);
        ?>
    </p>
    <?php
}

function copella_author_render_stream_meta_box(WP_Post $post): void
{
    $program_id = (int) get_post_meta($post->ID, COPELLA_AUTHOR_META_PROGRAM_ID, true);
    $stream_url = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_STREAM_URL, true);
    $stream_info = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_STREAM_INFO, true);
    $is_live = (string) get_post_meta($post->ID, COPELLA_AUTHOR_META_IS_LIVE, true) === '1';
    ?>
    <p>
        <label for="copella_author_program_id"><strong><?php _e('ID программы передач', 'copella-topics'); ?></strong></label><br/>
        <input type="number" id="copella_author_program_id" name="copella_author_program_id" value="<?php echo (int) $program_id; ?>" class="regular-text" min="0" placeholder="Например: 123"/>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Укажите ID канала для отображения программы передач.', 'copella-topics'); ?>
    </p>
    
    <p>
        <label for="copella_author_stream_url"><strong><?php _e('Ссылка на стрим', 'copella-topics'); ?></strong></label><br/>
        <input type="url" id="copella_author_stream_url" name="copella_author_stream_url" value="<?php echo esc_attr($stream_url); ?>" class="widefat" placeholder="https://example.com/stream"/>
    </p>
    
    <p>
        <label>
            <input type="checkbox" id="copella_author_is_live" name="copella_author_is_live" value="1" <?php checked($is_live); ?>/>
            <strong><?php _e('Стрим в эфире', 'copella-topics'); ?></strong>
        </label>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Отметьте, если стрим в данный момент вещает в прямом эфире.', 'copella-topics'); ?>
    </p>
    
    <p>
        <label for="copella_author_stream_info"><strong><?php _e('Дополнительная информация о стриме', 'copella-topics'); ?></strong></label><br/>
        <textarea id="copella_author_stream_info" name="copella_author_stream_info" class="widefat" rows="3" placeholder="Описание стрима..."><?php echo esc_textarea($stream_info); ?></textarea>
    </p>
    <?php
}

function copella_author_save_meta_boxes(int $post_id): void
{
    if (!isset($_POST['copella_author_meta_nonce']) || !wp_verify_nonce((string) $_POST['copella_author_meta_nonce'], 'copella_author_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Сохраняем плейлисты
    $ids = array();
    if (isset($_POST['copella_author_picks']) && is_array($_POST['copella_author_picks'])) {
        $ids = array_map('absint', (array) $_POST['copella_author_picks']);
    } else {
        $raw = isset($_POST['copella_author_playlists']) ? (string) $_POST['copella_author_playlists'] : '';
        $ids = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $raw)));
    }
    $ids = array_values(array_unique(array_filter($ids)));
    $store = implode(",", $ids);
    update_post_meta($post_id, COPELLA_AUTHOR_META_PLAYLISTS, $store);
    
    // Сохраняем социальные сети
    $social_raw = isset($_POST['copella_author_social']) ? (string) $_POST['copella_author_social'] : '';
    $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $social_raw)));
    $social_networks = array();
    
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 2) {
            $name = sanitize_text_field($parts[0]);
            $url = esc_url_raw($parts[1]);
            $icon = isset($parts[2]) ? sanitize_text_field($parts[2]) : '';
            
            if (!empty($name) && !empty($url)) {
                $social_networks[] = $name . ' | ' . $url . ($icon ? ' | ' . $icon : '');
            }
        }
    }
    
    $social_store = implode("\n", $social_networks);
    update_post_meta($post_id, COPELLA_AUTHOR_META_SOCIAL, $social_store);

    // Save separate author description meta
    if (isset($_POST['copella_author_desc'])) {
        $desc = sanitize_textarea_field((string) $_POST['copella_author_desc']);
        update_post_meta($post_id, COPELLA_AUTHOR_META_DESC, $desc);
    }

    // Save author type and stream-specific settings
    if (isset($_POST['copella_author_type'])) {
        $type = strtolower(sanitize_text_field((string) $_POST['copella_author_type']));
        if ($type !== 'video' && $type !== 'stream') { $type = 'video'; }
        update_post_meta($post_id, COPELLA_AUTHOR_META_TYPE, $type);
    }
    
    // Save stream-specific fields
    if (isset($_POST['copella_author_program_id'])) {
        update_post_meta($post_id, COPELLA_AUTHOR_META_PROGRAM_ID, absint((string) $_POST['copella_author_program_id']));
    }
    
    if (isset($_POST['copella_author_stream_url'])) {
        $stream_url = sanitize_url((string) $_POST['copella_author_stream_url']);
        update_post_meta($post_id, COPELLA_AUTHOR_META_STREAM_URL, $stream_url);
    }
    
    if (isset($_POST['copella_author_stream_info'])) {
        $stream_info = sanitize_textarea_field((string) $_POST['copella_author_stream_info']);
        update_post_meta($post_id, COPELLA_AUTHOR_META_STREAM_INFO, $stream_info);
    }
    
    // Save live status
    $is_live = isset($_POST['copella_author_is_live']) && $_POST['copella_author_is_live'] === '1' ? '1' : '0';
    update_post_meta($post_id, COPELLA_AUTHOR_META_IS_LIVE, $is_live);

    // Save links
    if (isset($_POST['copella_author_page_id'])) {
        update_post_meta($post_id, COPELLA_AUTHOR_META_PAGE_ID, absint((string) $_POST['copella_author_page_id']));
    }
}
add_action('save_post_topic_author', 'copella_author_save_meta_boxes');

