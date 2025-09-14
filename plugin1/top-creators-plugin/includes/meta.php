<?php

if (!defined('ABSPATH')) {
    exit;
}

// Creator meta constants
const COPELLA_CREATOR_META_AGE = '_copella_creator_age';
const COPELLA_CREATOR_META_EXPERIENCE = '_copella_creator_experience';
const COPELLA_CREATOR_META_SPECIALIZATION = '_copella_creator_specialization';
const COPELLA_CREATOR_META_ACHIEVEMENTS = '_copella_creator_achievements';
const COPELLA_CREATOR_META_SOCIAL = '_copella_creator_social';
const COPELLA_CREATOR_META_PLAYLISTS = '_copella_creator_playlists';
const COPELLA_CREATOR_META_BACKGROUND_IMAGE = '_copella_creator_background_image';
const COPELLA_CREATOR_META_TOPICS_AUTHOR = '_copella_creator_topics_author';

function copella_creator_add_meta_boxes(): void
{
    add_meta_box(
        'copella_creator_topics_author',
        __('Автор из Topics', 'copella-creators'),
        'copella_creator_render_topics_author_meta_box',
        'creator',
        'side',
        'high'
    );
    add_meta_box(
        'copella_creator_details',
        __('Детали креатора', 'copella-creators'),
        'copella_creator_render_meta_box',
        'creator',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_creator_social',
        __('Социальные сети', 'copella-creators'),
        'copella_creator_render_social_meta_box',
        'creator',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_creator_achievements',
        __('Достижения и информация', 'copella-creators'),
        'copella_creator_render_achievements_meta_box',
        'creator',
        'normal',
        'default'
    );
    add_meta_box(
        'copella_creator_playlists',
        __('Плейлисты', 'copella-creators'),
        'copella_creator_render_playlists_meta_box',
        'creator',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'copella_creator_add_meta_boxes');

function copella_creator_render_topics_author_meta_box(WP_Post $post): void {
    $topics_author_id = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_TOPICS_AUTHOR, true);
    
    // Get available authors from topics plugin
    $authors = get_posts(array(
        'post_type' => 'topic_author',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 500,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    ?>
    <p>
        <label for="copella_creator_topics_author"><strong><?php _e('Выберите автора из Topics', 'copella-creators'); ?></strong></label><br/>
        <select id="copella_creator_topics_author" name="copella_creator_topics_author" class="widefat">
            <option value="0"><?php _e('— Не выбрано —', 'copella-creators'); ?></option>
            <?php foreach ($authors as $author): ?>
                <option value="<?php echo (int) $author->ID; ?>" <?php selected($topics_author_id, $author->ID); ?>>
                    <?php echo esc_html($author->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="description">
        <?php _e('Выберите автора из плагина Topics & Playlists. Это автоматически подтянет его данные, картинку и плейлисты.', 'copella-creators'); ?>
    </p>
    
    <?php if ($topics_author_id > 0): ?>
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
            <strong><?php _e('Предварительный просмотр:', 'copella-creators'); ?></strong><br/>
            <?php 
            $author_name = get_the_title($topics_author_id);
            $author_thumb = get_the_post_thumbnail_url($topics_author_id, 'thumbnail');
            $author_desc = get_post_field('post_excerpt', $topics_author_id);
            ?>
            <?php if ($author_thumb): ?>
                <img src="<?php echo esc_url($author_thumb); ?>" width="40" height="40" style="border-radius: 50%; object-fit: cover; margin: 5px 0;" alt=""/>
            <?php endif; ?>
            <div style="margin-top: 5px;">
                <strong><?php echo esc_html($author_name); ?></strong><br/>
                <?php if ($author_desc): ?>
                    <small><?php echo esc_html($author_desc); ?></small>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php
}

function copella_creator_render_meta_box(WP_Post $post): void {
    wp_nonce_field('copella_creator_meta', 'copella_creator_meta_nonce');
    $age = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_AGE, true);
    $experience = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_EXPERIENCE, true);
    $specialization = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_SPECIALIZATION, true);
    $background_image = (int) get_post_meta($post->ID, COPELLA_CREATOR_META_BACKGROUND_IMAGE, true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="copella_creator_age"><?php _e('Возраст', 'copella-creators'); ?></label></th>
            <td>
                <input type="number" id="copella_creator_age" name="copella_creator_age" value="<?php echo esc_attr($age); ?>" class="regular-text" min="0" max="120"/>
                <p class="description"><?php _e('Возраст креатора в годах', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="copella_creator_experience"><?php _e('Опыт работы', 'copella-creators'); ?></label></th>
            <td>
                <input type="number" id="copella_creator_experience" name="copella_creator_experience" value="<?php echo esc_attr($experience); ?>" class="regular-text" min="0" max="50"/>
                <p class="description"><?php _e('Количество лет опыта в сфере', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="copella_creator_specialization"><?php _e('Специализация', 'copella-creators'); ?></label></th>
            <td>
                <input type="text" id="copella_creator_specialization" name="copella_creator_specialization" value="<?php echo esc_attr($specialization); ?>" class="regular-text" placeholder="<?php esc_attr_e('Например: веб-разработка, дизайн, маркетинг', 'copella-creators'); ?>"/>
                <p class="description"><?php _e('В чем специализируется креатор', 'copella-creators'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="copella_creator_background_image"><?php _e('Фоновое изображение', 'copella-creators'); ?></label></th>
            <td>
                <div class="cp-creator-bg-preview" style="margin-bottom: 10px;">
                    <?php if ($background_image): ?>
                        <?php echo wp_get_attachment_image($background_image, 'medium', false, array('style' => 'max-width: 200px; height: auto;')); ?>
                    <?php else: ?>
                        <div style="width: 200px; height: 100px; background: #f0f0f0; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; color: #666;"><?php _e('Изображение не выбрано', 'copella-creators'); ?></div>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="copella_creator_background_image" name="copella_creator_background_image" value="<?php echo esc_attr($background_image); ?>"/>
                <button type="button" class="button cp-select-bg-image"><?php _e('Выбрать изображение', 'copella-creators'); ?></button>
                <button type="button" class="button cp-remove-bg-image"><?php _e('Убрать', 'copella-creators'); ?></button>
                <p class="description"><?php _e('Изображение для размытого фона на странице креатора', 'copella-creators'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

function copella_creator_render_social_meta_box(WP_Post $post): void {
    $social_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_SOCIAL, true);
    ?>
    <p>
        <label for="copella_creator_social"><strong><?php _e('Социальные сети (по одной в строке)', 'copella-creators'); ?></strong></label><br/>
        <textarea id="copella_creator_social" name="copella_creator_social" class="widefat" rows="6" placeholder="YouTube | https://youtube.com/@username | 🎥&#10;Telegram | https://t.me/username | 📱&#10;Instagram | https://instagram.com/username | 📸&#10;Twitter | https://twitter.com/username | 🐦"><?php echo esc_textarea($social_raw); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Формат: Название | Ссылка | Иконка (эмодзи или текст). Иконка необязательна.', 'copella-creators'); ?>
    </p>
    <?php
}

function copella_creator_render_achievements_meta_box(WP_Post $post): void {
    $achievements = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_ACHIEVEMENTS, true);
    ?>
    <p>
        <label for="copella_creator_achievements"><strong><?php _e('Достижения и заслуги (по одному в строке)', 'copella-creators'); ?></strong></label><br/>
        <textarea id="copella_creator_achievements" name="copella_creator_achievements" class="widefat" rows="6" placeholder="🏆 Победитель конкурса дизайна 2023&#10;📈 100k+ подписчиков на YouTube&#10;💼 Работал с топ-брендами&#10;🎓 Сертифицированный специалист"><?php echo esc_textarea($achievements); ?></textarea>
    </p>
    <p style="opacity:.8;margin-top:-4px">
        <?php _e('Перечислите основные достижения и заслуги креатора. Можно использовать эмодзи для визуального оформления.', 'copella-creators'); ?>
    </p>
    <?php
}

function copella_creator_render_playlists_meta_box(WP_Post $post): void {
    $playlists_raw = (string) get_post_meta($post->ID, COPELLA_CREATOR_META_PLAYLISTS, true);
    $selected = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $playlists_raw)));
    
    // Get available playlists from topics plugin
    $playlists = get_posts(array(
        'post_type' => 'topic_playlist',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 500,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    ?>
    <div class="cp-creator-playlists-picker">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
            <input type="text" id="cp-creator-playlist-search" class="regular-text" placeholder="<?php esc_attr_e('Поиск плейлистов…', 'copella-creators'); ?>"/>
            <span style="opacity:.75"><?php printf(__('Всего: %d', 'copella-creators'), count($playlists)); ?></span>
            <span id="cp-creator-playlist-count" style="margin-left:auto;opacity:.75"><?php printf(__('Выбрано: %d', 'copella-creators'), count($selected)); ?></span>
        </div>
        <div id="cp-creator-playlist-list" style="max-height:320px;overflow:auto;border:1px solid #ddd;border-radius:6px;padding:8px;background:#fff">
            <?php if (empty($playlists)): ?>
                <div style="opacity:.7;padding:6px 0"><?php _e('Плейлистов не найдено. Убедитесь, что плагин Topics & Playlists активен.', 'copella-creators'); ?></div>
            <?php else: foreach ($playlists as $playlist): ?>
                <label class="cp-creator-playlist-row" data-title="<?php echo esc_attr(strtolower($playlist->post_title)); ?>" style="display:flex;align-items:center;gap:10px;margin:6px 0">
                    <input type="checkbox" class="cp-creator-playlist-pick" name="copella_creator_playlist_picks[]" value="<?php echo (int) $playlist->ID; ?>" <?php checked(in_array($playlist->ID, $selected)); ?>/>
                    <?php 
                    $thumb = get_the_post_thumbnail_url($playlist->ID, 'thumbnail');
                    if ($thumb): 
                    ?>
                        <img src="<?php echo esc_url($thumb); ?>" width="40" height="40" style="object-fit:cover;border-radius:6px" alt=""/>
                    <?php endif; ?>
                    <span><?php echo esc_html($playlist->post_title); ?> <span style="opacity:.6">#<?php echo (int) $playlist->ID; ?></span></span>
                    <?php if ($playlist->post_status !== 'publish'): ?><span style="margin-left:auto;opacity:.65;"><em><?php echo esc_html($playlist->post_status); ?></em></span><?php endif; ?>
                </label>
            <?php endforeach; endif; ?>
        </div>
        <input type="hidden" id="copella_creator_playlists" name="copella_creator_playlists" value="<?php echo esc_attr(implode(',', $selected)); ?>"/>
    </div>
    <p style="opacity:.8;margin:6px 0 0 0"><?php _e('Выберите плейлисты, которые будут отображаться на странице креатора.', 'copella-creators'); ?></p>
    <?php
}

function copella_creator_save_meta_boxes(int $post_id): void {
    if (!isset($_POST['copella_creator_meta_nonce']) || !wp_verify_nonce((string) $_POST['copella_creator_meta_nonce'], 'copella_creator_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save basic info
    if (isset($_POST['copella_creator_age'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_AGE, absint((string) $_POST['copella_creator_age']));
    }
    
    if (isset($_POST['copella_creator_experience'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_EXPERIENCE, absint((string) $_POST['copella_creator_experience']));
    }
    
    if (isset($_POST['copella_creator_specialization'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_SPECIALIZATION, sanitize_text_field((string) $_POST['copella_creator_specialization']));
    }
    
    if (isset($_POST['copella_creator_background_image'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_BACKGROUND_IMAGE, absint((string) $_POST['copella_creator_background_image']));
    }
    
    // Save social networks
    if (isset($_POST['copella_creator_social'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_SOCIAL, sanitize_textarea_field((string) $_POST['copella_creator_social']));
    }
    
    // Save achievements
    if (isset($_POST['copella_creator_achievements'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_ACHIEVEMENTS, sanitize_textarea_field((string) $_POST['copella_creator_achievements']));
    }
    
    // Save playlists
    $playlist_ids = array();
    if (isset($_POST['copella_creator_playlist_picks']) && is_array($_POST['copella_creator_playlist_picks'])) {
        $playlist_ids = array_map('absint', (array) $_POST['copella_creator_playlist_picks']);
    } else {
        $raw = isset($_POST['copella_creator_playlists']) ? (string) $_POST['copella_creator_playlists'] : '';
        $playlist_ids = array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $raw)));
    }
    $playlist_ids = array_values(array_unique(array_filter($playlist_ids)));
    update_post_meta($post_id, COPELLA_CREATOR_META_PLAYLISTS, implode(',', $playlist_ids));
    
    // Save topics author
    if (isset($_POST['copella_creator_topics_author'])) {
        update_post_meta($post_id, COPELLA_CREATOR_META_TOPICS_AUTHOR, absint((string) $_POST['copella_creator_topics_author']));
    }
}
add_action('save_post_creator', 'copella_creator_save_meta_boxes');