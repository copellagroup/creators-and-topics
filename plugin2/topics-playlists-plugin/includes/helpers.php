<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get author description with fallback to excerpt
 */
function copella_topics_get_author_desc(int $author_id, bool $fallback_to_excerpt = true): string
{
    $desc = (string) get_post_meta($author_id, COPELLA_AUTHOR_META_DESC, true);
    if ($desc === '' && $fallback_to_excerpt) {
        $desc = (string) get_the_excerpt($author_id);
    }
    return $desc;
}

/**
 * Get author playlist IDs as an array of integers
 */
function copella_topics_get_author_playlist_ids(int $author_id): array
{
    $ids_raw = (string) get_post_meta($author_id, COPELLA_AUTHOR_META_PLAYLISTS, true);
    return array_filter(array_map('absint', preg_split("/\r\n|\r|\n|,|\s+/", $ids_raw)));
}

/**
 * Render author's playlists area: optional tabsbar + playlist embeds
 */
function copella_topics_render_author_playlists(array $playlist_ids, bool $with_tabsbar = true, bool $is_individual_tab = false): string
{
    if (empty($playlist_ids)) {
        return '<div class="au-empty">' . esc_html__('Плейлисты не назначены.', 'copella-topics') . '</div>';
    }
    
    $single_playlist = count($playlist_ids) === 1;
    
    ob_start();
    ?>
    <?php if (count($playlist_ids) > 1 && $with_tabsbar): ?>
        <div class="au-pl-tabsbar" aria-label="<?php echo esc_attr__('Плейлисты автора', 'copella-topics'); ?>">
            <button class="au-pl-tabs-nav au-pl-prev" type="button" aria-label="<?php echo esc_attr__('Назад', 'copella-topics'); ?>" title="<?php echo esc_attr__('Назад', 'copella-topics'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="au-pl-tabs-viewport">
                <div class="au-pl-tabs" role="tablist">
                    <button type="button" class="au-pl-tab is-active" data-pl="all"><?php echo esc_html__('Все', 'copella-topics'); ?></button>
                    <?php foreach ($playlist_ids as $pid): ?>
                        <button type="button" class="au-pl-tab" data-pl="<?php echo (int) $pid; ?>"><?php echo esc_html(wp_trim_words(get_the_title($pid), 3, '...')); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="au-pl-tabs-nav au-pl-next" type="button" aria-label="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>" title="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if ($single_playlist && $with_tabsbar): ?>
        <!-- Single playlist: show content directly without tabs -->
        <div class="au-playlists au-playlists--single">
            <div class="au-pl-wrap--full au-pl-wrap--single" data-pl-id="<?php echo (int) $playlist_ids[0]; ?>">
                <?php echo do_shortcode('[topic_playlist playlist_id="' . (int) $playlist_ids[0] . '" collapsed="false" cover="false" aspect_ratio="1/1"]'); ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Multiple playlists: use tab system -->
        <div class="au-playlists au-playlists--mixed">
            <!-- Title-only version for "All" tab - single container with grid -->
            <div class="au-pl-wrap--title-only">
                <?php foreach ($playlist_ids as $pid): ?>
                    <?php echo copella_topics_render_playlist_title_only($pid); ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Full playlist versions for individual project tabs -->
            <?php foreach ($playlist_ids as $pid): ?>
                <div class="au-pl-wrap--full" data-pl-id="<?php echo (int) $pid; ?>" style="display: none;">
                    <?php echo do_shortcode('[topic_playlist playlist_id="' . (int) $pid . '" collapsed="false" cover="false" aspect_ratio="1/1"]'); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    return (string) ob_get_clean();
}

/**
 * Render social networks for an author
 */
function copella_topics_render_author_social_networks(int $author_id): string
{
    $social_raw = (string) get_post_meta($author_id, COPELLA_AUTHOR_META_SOCIAL, true);
    if (empty($social_raw)) {
        return '';
    }
    
    $lines = array_filter(array_map('trim', preg_split("/\r\n|\r|\n/", $social_raw)));
    if (empty($lines)) {
        return '';
    }
    
    $output = '<div class="au-social">';
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 2) {
            continue;
        }
        
        $name = $parts[0];
        $url = $parts[1];
        $icon = isset($parts[2]) ? $parts[2] : '';
        
        if (empty($name) || empty($url)) {
            continue;
        }
        $isTelegram = (bool) preg_match('~(t\.me|telegram\.(me|org))~i', $url);
        $output .= '<a href="' . esc_url($url) . '" class="au-social-link" target="_blank" rel="noopener noreferrer" title="' . esc_attr($name) . '">';
        $isYouTube = (bool) preg_match('~(youtube\.com|youtu\.be)~i', $url);
        if ($isYouTube) {
            $ytLogo = COPELLA_TOPICS_PLUGIN_URL . 'assets/icons/youtube.svg';
            $output .= '<span class="au-social-logo" aria-hidden="true"><img src="' . esc_url($ytLogo) . '" alt="" loading="lazy"/></span>';
        }
        if ($isTelegram) {
            $tgLogo = 'https://copella.live/wp-content/uploads/2025/08/image_2025-08-16_19-21-48.png';
            $output .= '<span class="au-social-logo" aria-hidden="true"><img src="' . esc_url($tgLogo) . '" alt="" loading="lazy"/></span>';
        }
        if (!empty($icon)) {
            $output .= '<span class="au-social-icon">' . esc_html($icon) . '</span>';
        }
        $output .= '<span class="au-social-name">' . esc_html($name) . '</span>';
        $output .= '</a>';
    }
    $output .= '</div>';
    
    return $output;
}

/**
 * Fetch compact schedule summary for a CP Schedule channel.
 * Returns associative array with keys: offline(bool), current(?array), upcoming(array of arrays)
 */
function copella_topics_fetch_tvschedule_summary(int $channelId, int $limit = 3): array
{
    $summary = array('offline' => false, 'current' => null, 'upcoming' => array());
    if ($channelId <= 0) {
        return $summary;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'tv_schedule';
    // Ensure table exists
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if ($exists !== $table) {
        return $summary;
    }
    // Offline channels option
    $offlineChannels = get_option('tvschedule_offline_channels', array());
    $summary['offline'] = in_array($channelId, (array) $offlineChannels, true);
    $currentDate = current_time('Y-m-d');
    $currentTime = current_time('H:i:s');
    // Current program <= now
    $current = $wpdb->get_row($wpdb->prepare(
        "SELECT program_time, program_name FROM {$table} WHERE channel_id = %d AND program_date = %s AND program_time <= %s ORDER BY program_time DESC LIMIT 1",
        $channelId, $currentDate, $currentTime
    ));
    if ($current) {
        $summary['current'] = array(
            'time' => substr((string) $current->program_time, 0, 5),
            'name' => (string) $current->program_name,
        );
    }
    // Upcoming next N today > now
    $limit = max(1, min(10, $limit));
    $upcoming = $wpdb->get_results($wpdb->prepare(
        "SELECT program_time, program_name FROM {$table} WHERE channel_id = %d AND program_date = %s AND program_time > %s ORDER BY program_time ASC LIMIT %d",
        $channelId, $currentDate, $currentTime, $limit
    ));
    if (!empty($upcoming)) {
        foreach ($upcoming as $row) {
            $summary['upcoming'][] = array(
                'time' => substr((string) $row->program_time, 0, 5),
                'name' => (string) $row->program_name,
            );
        }
    }
    return $summary;
}

/**
 * Render compact schedule summary markup for authors stream card
 */
function copella_topics_render_schedule_compact(array $data): string
{
    $offline = !empty($data['offline']);
    $current = isset($data['current']) ? $data['current'] : null;
    $upcoming = isset($data['upcoming']) && is_array($data['upcoming']) ? $data['upcoming'] : array();
    ob_start();
    ?>
    <div class="au-program-mini">
        <?php if ($offline): ?>
            <div class="ap-offline"><?php echo esc_html__('Канал временно не вещает', 'copella-topics'); ?></div>
        <?php else: ?>
            <?php if (empty($current) && empty($upcoming)): ?>
                <div class="ap-nodata">
                    <span class="ap-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="ap-text"><?php echo esc_html__('Не нашли подготовленной программы', 'copella-topics'); ?></span>
                </div>
            <?php else: ?>
            <div class="ap-now">
                <span class="ap-label"><?php echo esc_html__('Сейчас', 'copella-topics'); ?>:</span>
                <?php if ($current): ?>
                    <span class="ap-time"><?php echo esc_html((string) $current['time']); ?></span>
                    <span class="ap-title"><?php echo esc_html((string) $current['name']); ?></span>
                <?php else: ?>
                    <span class="ap-empty"><?php echo esc_html__('Нет текущей программы', 'copella-topics'); ?></span>
                <?php endif; ?>
            </div>
            <div class="ap-next">
                <span class="ap-label"><?php echo esc_html__('Далее', 'copella-topics'); ?>:</span>
                <?php if (!empty($upcoming)): ?>
                    <ul class="ap-list">
                        <?php foreach ($upcoming as $row): ?>
                            <li><span class="ap-time"><?php echo esc_html((string) $row['time']); ?></span> <span class="ap-title"><?php echo esc_html((string) $row['name']); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <span class="ap-empty"><?php echo esc_html__('Нет данных', 'copella-topics'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Render playlist title only (for "All" view - no episodes, no expansion)
 */
function copella_topics_render_playlist_title_only(int $playlist_id): string
{
    if ($playlist_id <= 0) {
        return '';
    }
    
    $title = get_the_title($playlist_id);
    $uid = 'pl_title_' . wp_generate_password(8, false, false);
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($uid); ?>" class="copella-playlist copella-playlist--title-only" data-pl-init="1" style="--pl-container-bg:#171717;--pl-container-radius:28px;">
        <div class="pl-header pl-header--no-expand">
            <h3 class="pl-title"><?php echo esc_html($title); ?></h3>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
?>
