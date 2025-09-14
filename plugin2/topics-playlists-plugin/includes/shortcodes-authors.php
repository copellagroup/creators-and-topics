<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [video_authors] - Render video authors grid
 */
function copella_topics_render_video_authors(array $atts = []): string
{
    $a = shortcode_atts(array(
        'title' => __('Авторы видео', 'copella-topics'),
        'posts_per_page' => '50',
        'columns' => '3',
        'gap' => '24',
        'cover_ratio' => '16/9',
        'layout' => 'grid',
    ), $atts, 'video_authors');

    $perPage = max(1, (int)$a['posts_per_page']);
    $columns = max(1, min(4, (int)$a['columns']));
    $gap = max(8, (int)$a['gap']);
    $coverRatio = preg_replace('~[^0-9/\.]+~', '', (string) $a['cover_ratio']);
    $layout = strtolower(trim((string)$a['layout'])) === 'tabs' ? 'tabs' : 'grid';

    $q = new WP_Query(array(
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'posts_per_page' => $perPage,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => COPELLA_AUTHOR_META_TYPE,
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => COPELLA_AUTHOR_META_TYPE,
                'value' => 'stream',
                'compare' => '!=',
            )
        ),
        'no_found_rows' => true,
    ));

    $uid = 'pl_vauthors_' . wp_generate_password(8, false, false);
    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-authors<?php echo $layout === 'tabs' ? ' is-tabs' : ''; ?>" style="<?php echo esc_attr(sprintf('--au-cols:%d;--au-gap:%dpx;--au-cover-ratio:%s;', $columns, $gap, $coverRatio ?: '16/9')); ?>">
        <?php if (!empty($a['title'])): ?><h3 class="au-title"><?php echo esc_html((string)$a['title']); ?></h3><?php endif; ?>
        
        <?php if ($layout === 'tabs' && $q->have_posts()): ?>
            <div class="au-tabsbar" aria-label="<?php echo esc_attr__('Авторы', 'copella-topics'); ?>">
                <button class="au-tabs-nav au-prev" type="button" aria-label="<?php echo esc_attr__('Назад', 'copella-topics'); ?>" title="<?php echo esc_attr__('Назад', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="au-tabs-viewport">
                    <div class="au-tabs" role="tablist">
                        <button type="button" class="au-tab is-active" role="tab" aria-selected="true" aria-controls="<?php echo esc_attr($uid . '_panel_all'); ?>" id="<?php echo esc_attr($uid . '_tab_all'); ?>" data-au-index="all">
                            <?php echo esc_html__('Все', 'copella-topics'); ?>
                        </button>
                        <?php
                        $tabIndex = 0;
                        while ($q->have_posts()): $q->the_post();
                            $authorName = get_the_title();
                        ?>
                            <button type="button" class="au-tab" role="tab" aria-selected="false" aria-controls="<?php echo esc_attr($uid . '_panel_' . $tabIndex); ?>" id="<?php echo esc_attr($uid . '_tab_' . $tabIndex); ?>" data-au-index="<?php echo (int) $tabIndex; ?>">
                                <?php echo esc_html($authorName); ?>
                            </button>
                        <?php
                            $tabIndex++;
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <button class="au-tabs-nav au-next" type="button" aria-label="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>" title="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>

            <div class="au-panels">
                <div class="au-panel is-active" role="tabpanel" id="<?php echo esc_attr($uid . '_panel_all'); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_all'); ?>" data-au-index="all">
                    <div class="au-grid">
                        <?php
                        $q->rewind_posts();
                        while ($q->have_posts()): $q->the_post();
                            $aid = get_the_ID();
                            $name = get_the_title();
                            $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                            $desc = copella_topics_get_author_desc($aid);
                            $pl_ids = copella_topics_get_author_playlist_ids($aid);
                        ?>
                            <article class="au-card is-collapsed">
                                <div class="au-header" role="button" tabindex="0" aria-expanded="false">
                                    <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                                    <button class="au-arrow" type="button" aria-label="<?php echo esc_attr__('Развернуть', 'copella-topics'); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                </div>
                                <div class="au-cover" aria-hidden="true">
                                    <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                                </div>
                                <div class="au-body">
                                    <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                                    <?php echo copella_topics_render_author_social_networks($aid); ?>
                                    <?php echo copella_topics_render_author_playlists($pl_ids); ?>
                                </div>
                            </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php
                $panelIndex = 0;
                $q->rewind_posts();
                while ($q->have_posts()): $q->the_post();
                    $aid = get_the_ID();
                    $name = get_the_title();
                    $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                    $desc = copella_topics_get_author_desc($aid);
                    $pl_ids = copella_topics_get_author_playlist_ids($aid);
                ?>
                    <div class="au-panel" role="tabpanel" id="<?php echo esc_attr($uid . '_panel_' . $panelIndex); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_' . $panelIndex); ?>" data-au-index="<?php echo (int) $panelIndex; ?>">
                        <div class="au-grid au-grid--individual-tab">
                            <article class="au-card au-card--full-width au-card--no-cover">
                                <div class="au-header">
                                    <h4 class="au-name">
                                        <?php if ($cover): ?>
                                            <span class="au-author-icon">
                                                <img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/>
                                            </span>
                                        <?php else: ?>
                                            <span class="au-author-icon"><?php echo esc_html(mb_substr($name, 0, 1)); ?></span>
                                        <?php endif; ?>
                                        <?php echo esc_html($name); ?>
                                    </h4>
                                </div>
                                <div class="au-body">
                                    <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                                    <?php echo copella_topics_render_author_social_networks($aid); ?>
                                    <?php echo copella_topics_render_author_playlists($pl_ids, true, true); ?>
                                </div>
                            </article>
                        </div>
                    </div>
                <?php
                    $panelIndex++;
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        <?php else: ?>
            <div class="au-grid">
                <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
                    $aid = get_the_ID();
                    $name = get_the_title();
                    $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                    $desc = copella_topics_get_author_desc($aid);
                    $pl_ids = copella_topics_get_author_playlist_ids($aid);
                ?>
                <article class="au-card is-collapsed">
                    <div class="au-header" role="button" tabindex="0" aria-expanded="false">
                        <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                        <button class="au-arrow" type="button" aria-label="<?php echo esc_attr__('Развернуть', 'copella-topics'); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    </div>
                    <div class="au-cover" aria-hidden="true">
                        <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                    </div>
                    <div class="au-body">
                        <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                        <?php echo copella_topics_render_author_social_networks($aid); ?>
                        <?php echo copella_topics_render_author_playlists($pl_ids); ?>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); else: ?>
                    <div class="au-empty"><?php echo esc_html__('Авторы не найдены.', 'copella-topics'); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    
    <script>
    // Initialize this specific author container when DOM is ready
    (function() {
        if (typeof window.CopellaAuthors !== 'undefined') {
            var container = document.getElementById('<?php echo esc_js($uid); ?>');
            if (container) {
                window.CopellaAuthors.init(container);
            }
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [stream_authors] - Render stream authors grid
 */
function copella_topics_render_stream_authors(array $atts = []): string
{
    $a = shortcode_atts(array(
        'title' => __('Авторы стримов', 'copella-topics'),
        'posts_per_page' => '50',
        'columns' => '3',
        'gap' => '24',
        'cover_ratio' => '16/9',
        'layout' => 'grid',
    ), $atts, 'stream_authors');

    $perPage = max(1, (int)$a['posts_per_page']);
    $columns = max(1, min(4, (int)$a['columns']));
    $gap = max(8, (int)$a['gap']);
    $coverRatio = preg_replace('~[^0-9/\.]+~', '', (string) $a['cover_ratio']);
    $layout = strtolower(trim((string)$a['layout'])) === 'tabs' ? 'tabs' : 'grid';

    $q = new WP_Query(array(
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'posts_per_page' => $perPage,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'meta_key' => COPELLA_AUTHOR_META_TYPE,
        'meta_value' => 'stream',
        'no_found_rows' => true,
    ));

    $uid = 'pl_sauthors_' . wp_generate_password(8, false, false);
    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-authors<?php echo $layout === 'tabs' ? ' is-tabs' : ''; ?>" style="<?php echo esc_attr(sprintf('--au-cols:%d;--au-gap:%dpx;--au-cover-ratio:%s;', $columns, $gap, $coverRatio ?: '16/9')); ?>">
        <?php if (!empty($a['title'])): ?><h3 class="au-title"><?php echo esc_html((string)$a['title']); ?></h3><?php endif; ?>
        
        <?php if ($layout === 'tabs' && $q->have_posts()): ?>
            <div class="au-tabsbar" aria-label="<?php echo esc_attr__('Авторы', 'copella-topics'); ?>">
                <button class="au-tabs-nav au-prev" type="button" aria-label="<?php echo esc_attr__('Назад', 'copella-topics'); ?>" title="<?php echo esc_attr__('Назад', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="au-tabs-viewport">
                    <div class="au-tabs" role="tablist">
                        <button type="button" class="au-tab is-active" role="tab" aria-selected="true" aria-controls="<?php echo esc_attr($uid . '_panel_all'); ?>" id="<?php echo esc_attr($uid . '_tab_all'); ?>" data-au-index="all">
                            <?php echo esc_html__('Все', 'copella-topics'); ?>
                        </button>
                        <?php
                        $tabIndex = 0;
                        while ($q->have_posts()): $q->the_post();
                            $authorName = get_the_title();
                        ?>
                            <button type="button" class="au-tab" role="tab" aria-selected="false" aria-controls="<?php echo esc_attr($uid . '_panel_' . $tabIndex); ?>" id="<?php echo esc_attr($uid . '_tab_' . $tabIndex); ?>" data-au-index="<?php echo (int) $tabIndex; ?>">
                                <?php echo esc_html($authorName); ?>
                            </button>
                        <?php
                            $tabIndex++;
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <button class="au-tabs-nav au-next" type="button" aria-label="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>" title="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>

            <div class="au-panels">
                <div class="au-panel is-active" role="tabpanel" id="<?php echo esc_attr($uid . '_panel_all'); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_all'); ?>" data-au-index="all">
                    <div class="au-grid">
                        <?php
                        $q->rewind_posts();
                        while ($q->have_posts()): $q->the_post();
                            $aid = get_the_ID();
                            $name = get_the_title();
                            $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                            $streamUrl = (string) get_post_meta($aid, COPELLA_AUTHOR_META_STREAM_URL, true);
                            $isLive = (string) get_post_meta($aid, COPELLA_AUTHOR_META_IS_LIVE, true) === '1';
                            $desc = copella_topics_get_author_desc($aid);
                            $link = $streamUrl ?: get_permalink($aid);
                            $programId = (int) get_post_meta($aid, COPELLA_AUTHOR_META_PROGRAM_ID, true);
                        ?>
                        <article class="au-card is-stream is-collapsed">
                            <div class="au-header" role="button" tabindex="0" aria-expanded="false">
                                <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                                <?php $live = (string) get_post_meta($aid, COPELLA_AUTHOR_META_IS_LIVE, true) === '1'; ?>
                                <span class="au-live <?php echo $live ? 'is-on' : 'is-off'; ?>" aria-label="<?php echo esc_attr($live ? __('В эфире', 'copella-topics') : __('Нет эфира', 'copella-topics')); ?>"></span>
                                <span class="au-live-label"><?php echo $live ? esc_html__('В эфире', 'copella-topics') : esc_html__('Нет эфира', 'copella-topics'); ?></span>
                                <button class="au-arrow" type="button" aria-label="<?php echo esc_attr__('Развернуть', 'copella-topics'); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                            </div>
                            <div class="au-cover">
                                <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                                <?php if (!empty($link)): ?><a class="au-watch" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Смотреть', 'copella-topics'); ?></a><?php endif; ?>
                            </div>
                            <div class="au-body">
                                <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                                <?php if ($programId > 0): ?>
                                    <?php $summ = copella_topics_fetch_tvschedule_summary($programId, 3); echo '<div class="au-stream-program">' . copella_topics_render_schedule_compact($summ) . '</div>'; ?>
                                <?php endif; ?>
                                <?php echo copella_topics_render_author_social_networks($aid); ?>
                            </div>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php
                $panelIndex = 0;
                $q->rewind_posts();
                while ($q->have_posts()): $q->the_post();
                    $aid = get_the_ID();
                    $name = get_the_title();
                    $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                    $streamUrl = (string) get_post_meta($aid, COPELLA_AUTHOR_META_STREAM_URL, true);
                    $isLive = (string) get_post_meta($aid, COPELLA_AUTHOR_META_IS_LIVE, true) === '1';
                    $desc = copella_topics_get_author_desc($aid);
                    $link = $streamUrl ?: get_permalink($aid);
                    $programId = (int) get_post_meta($aid, COPELLA_AUTHOR_META_PROGRAM_ID, true);
                ?>
                    <div class="au-panel" role="tabpanel" id="<?php echo esc_attr($uid . '_panel_' . $panelIndex); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_' . $panelIndex); ?>" data-au-index="<?php echo (int) $panelIndex; ?>">
                        <div class="au-grid au-grid--individual-tab">
                            <article class="au-card is-stream au-card--full-width au-card--no-cover">
                                <div class="au-header" role="button" tabindex="0" aria-expanded="true">
                                    <h4 class="au-name">
                                        <?php if ($cover): ?>
                                            <span class="au-author-icon">
                                                <img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/>
                                            </span>
                                        <?php else: ?>
                                            <span class="au-author-icon"><?php echo esc_html(mb_substr($name, 0, 1)); ?></span>
                                        <?php endif; ?>
                                        <?php echo esc_html($name); ?>
                                    </h4>
                                    <?php $live = (string) get_post_meta($aid, COPELLA_AUTHOR_META_IS_LIVE, true) === '1'; ?>
                                    <span class="au-live <?php echo $live ? 'is-on' : 'is-off'; ?>" aria-label="<?php echo esc_attr($live ? __('В эфире', 'copella-topics') : __('Нет эфира', 'copella-topics')); ?>"></span>
                                    <span class="au-live-label"><?php echo $live ? esc_html__('В эфире', 'copella-topics') : esc_html__('Нет эфира', 'copella-topics'); ?></span>
                                </div>
                                <div class="au-body">
                                    <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                                    <?php if ($programId > 0): ?>
                                        <?php $summ = copella_topics_fetch_tvschedule_summary($programId, 3); echo '<div class="au-stream-program">' . copella_topics_render_schedule_compact($summ) . '</div>'; ?>
                                    <?php endif; ?>
                                    <?php echo copella_topics_render_author_social_networks($aid); ?>
                                    <?php if (!empty($link)): ?><p><a class="au-watch-link" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Смотреть стрим', 'copella-topics'); ?></a></p><?php endif; ?>
                                </div>
                            </article>
                        </div>
                    </div>
                <?php
                    $panelIndex++;
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        <?php else: ?>
            <div class="au-grid">
                <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
                    $aid = get_the_ID();
                    $name = get_the_title();
                    $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                    $streamUrl = (string) get_post_meta($aid, COPELLA_AUTHOR_META_STREAM_URL, true);
                    $isLive = (string) get_post_meta($aid, COPELLA_AUTHOR_META_IS_LIVE, true) === '1';
                    $desc = copella_topics_get_author_desc($aid);
                    $link = $streamUrl ?: get_permalink($aid);
                    $programId = (int) get_post_meta($aid, COPELLA_AUTHOR_META_PROGRAM_ID, true);
                ?>
                <article class="au-card is-stream is-collapsed">
                    <div class="au-header" role="button" tabindex="0" aria-expanded="false">
                        <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                        <?php $live = (string) get_post_meta($aid, COPELLA_AUTHOR_META_IS_LIVE, true) === '1'; ?>
                        <span class="au-live <?php echo $live ? 'is-on' : 'is-off'; ?>" aria-label="<?php echo esc_attr($live ? __('В эфире', 'copella-topics') : __('Нет эфира', 'copella-topics')); ?>"></span>
                        <span class="au-live-label"><?php echo $live ? esc_html__('В эфире', 'copella-topics') : esc_html__('Нет эфира', 'copella-topics'); ?></span>
                        <button class="au-arrow" type="button" aria-label="<?php echo esc_attr__('Развернуть', 'copella-topics'); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    </div>
                    <div class="au-cover">
                        <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                        <?php if (!empty($link)): ?><a class="au-watch" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Смотреть', 'copella-topics'); ?></a><?php endif; ?>
                    </div>
                    <div class="au-body">
                        <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                        <?php if ($programId > 0): ?>
                            <?php $summ = copella_topics_fetch_tvschedule_summary($programId, 3); echo '<div class="au-stream-program">' . copella_topics_render_schedule_compact($summ) . '</div>'; ?>
                        <?php endif; ?>
                        <?php echo copella_topics_render_author_social_networks($aid); ?>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); else: ?>
                    <div class="au-empty"><?php echo esc_html__('Авторы не найдены.', 'copella-topics'); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    
    <script>
    // Initialize this specific author container when DOM is ready
    (function() {
        if (typeof window.CopellaAuthors !== 'undefined') {
            var container = document.getElementById('<?php echo esc_js($uid); ?>');
            if (container) {
                window.CopellaAuthors.init(container);
            }
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [playlist_authors] - Render authors with playlists
 */
function copella_topics_render_authors(array $atts = []): string
{
    $a = shortcode_atts(array(
        'title' => __('Авторы', 'copella-topics'),
        'posts_per_page' => '50',
        'columns' => '3',
        'gap' => '24',
        'cover_ratio' => '16/9',
        'layout' => 'tabs',
    ), $atts, 'playlist_authors');

    $title = trim((string)$a['title']);
    $perPage = max(1, (int)$a['posts_per_page']);
    $columns = max(1, min(4, (int)$a['columns']));
    $gap = max(8, (int)$a['gap']);
    $coverRatio = preg_replace('~[^0-9/\.]+~', '', (string) $a['cover_ratio']);
    $layout = strtolower(trim((string)$a['layout'])) === 'grid' ? 'grid' : 'tabs';

    $q = new WP_Query(array(
        'post_type' => 'topic_author',
        'post_status' => 'publish',
        'posts_per_page' => $perPage,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));

    $uid = 'pl_authors_' . wp_generate_password(8, false, false);
    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-authors<?php echo $layout === 'tabs' ? ' is-tabs' : ''; ?>" style="<?php echo esc_attr(sprintf('--au-cols:%d;--au-gap:%dpx;--au-cover-ratio:%s;', $columns, $gap, $coverRatio ?: '16/9')); ?>">
        <?php if ($title !== ''): ?><h3 class="au-title"><?php echo esc_html($title); ?></h3><?php endif; ?>
        
        <?php if ($layout === 'tabs' && $q->have_posts()): ?>
            <div class="au-tabsbar" aria-label="<?php echo esc_attr__('Авторы', 'copella-topics'); ?>">
                <button class="au-tabs-nav au-prev" type="button" aria-label="<?php echo esc_attr__('Назад', 'copella-topics'); ?>" title="<?php echo esc_attr__('Назад', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="au-tabs-viewport">
                    <div class="au-tabs" role="tablist">
                        <button type="button" class="au-tab is-active" role="tab" aria-selected="true" aria-controls="<?php echo esc_attr($uid . '_panel_all'); ?>" id="<?php echo esc_attr($uid . '_tab_all'); ?>" data-au-index="all">
                            <?php echo esc_html__('Все', 'copella-topics'); ?>
                        </button>
                        <?php
                        $tabIndex = 0;
                        while ($q->have_posts()): $q->the_post();
                            $authorId = get_the_ID();
                            $authorName = get_the_title();
                        ?>
                            <button type="button" class="au-tab" role="tab" aria-selected="false" aria-controls="<?php echo esc_attr($uid . '_panel_' . $tabIndex); ?>" id="<?php echo esc_attr($uid . '_tab_' . $tabIndex); ?>" data-au-index="<?php echo (int) $tabIndex; ?>">
                                <?php echo esc_html($authorName); ?>
                            </button>
                        <?php
                            $tabIndex++;
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <button class="au-tabs-nav au-next" type="button" aria-label="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>" title="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            
            <div class="au-panels">
                <!-- Панель "Все" -->
                <div class="au-panel is-active" role="tabpanel" id="<?php echo esc_attr($uid . '_panel_all'); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_all'); ?>" data-au-index="all">
                    <div class="au-grid">
                        <?php
                        $q->rewind_posts();
                        while ($q->have_posts()): $q->the_post();
                            $aid = get_the_ID();
                            $name = get_the_title();
                            $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                            $desc = copella_topics_get_author_desc($aid);
                            $pl_ids = copella_topics_get_author_playlist_ids($aid);
                        ?>
                            <article class="au-card au-card--full-width">
                                <div class="au-header">
                                    <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                                </div>
                                <div class="au-cover" aria-hidden="true">
                                    <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                                </div>
                                <div class="au-body">
                                    <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                                    <?php echo copella_topics_render_author_social_networks($aid); ?>
                                    <?php echo copella_topics_render_author_playlists($pl_ids, true, true); ?>
                                </div>
                            </article>
                        <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                
                <!-- Панели отдельных авторов -->
                <?php
                $panelIndex = 0;
                $q->rewind_posts();
                while ($q->have_posts()): $q->the_post();
                    $aid = get_the_ID();
                    $name = get_the_title();
                    $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                    $desc = copella_topics_get_author_desc($aid);
                    $pl_ids = copella_topics_get_author_playlist_ids($aid);
                ?>
                    <div class="au-panel" role="tabpanel" id="<?php echo esc_attr($uid . '_panel_' . $panelIndex); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_' . $panelIndex); ?>" data-au-index="<?php echo (int) $panelIndex; ?>">
                        <div class="au-grid au-grid--individual-tab">
                            <article class="au-card au-card--full-width au-card--no-cover">
                                <div class="au-header">
                                    <h4 class="au-name">
                                        <?php if ($cover): ?>
                                            <span class="au-author-icon">
                                                <img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/>
                                            </span>
                                        <?php else: ?>
                                            <span class="au-author-icon"><?php echo esc_html(mb_substr($name, 0, 1)); ?></span>
                                        <?php endif; ?>
                                        <?php echo esc_html($name); ?>
                                    </h4>
                                </div>
                                <div class="au-body">
                                    <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                                    <?php echo copella_topics_render_author_social_networks($aid); ?>
                                    <?php echo copella_topics_render_author_playlists($pl_ids, true, true); ?>
                                </div>
                            </article>
                        </div>
                    </div>
                <?php
                    $panelIndex++;
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        <?php else: ?>
            <div class="au-grid">
                <?php if ($q->have_posts()): ?>
                    <?php while ($q->have_posts()): ?>
                        <?php $q->the_post(); ?>
                        <?php
                    $aid = get_the_ID();
                    $name = get_the_title();
                    $cover = get_the_post_thumbnail_url($aid, 'medium_large');
                    $desc = copella_topics_get_author_desc($aid);
                    $pl_ids = copella_topics_get_author_playlist_ids($aid);
                ?>
                <article class="au-card is-collapsed">
                    <div class="au-header" role="button" tabindex="0" aria-expanded="false">
                        <?php $authorPageId = (int) get_post_meta($aid, COPELLA_AUTHOR_META_PAGE_ID, true); $authorPageUrl = $authorPageId ? get_permalink($authorPageId) : ''; ?>
                        <?php if ($authorPageUrl): ?>
                        <h4 class="au-name"><a href="<?php echo esc_url($authorPageUrl); ?>"><?php echo esc_html($name); ?></a></h4>
                        <?php else: ?>
                        <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                        <?php endif; ?>
                        <button class="au-arrow" type="button" aria-label="<?php echo esc_attr__('Развернуть', 'copella-topics'); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    </div>
                    <div class="au-cover" aria-hidden="true">
                        <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                    </div>
                    <div class="au-body">
                        <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                        <?php echo copella_topics_render_author_social_networks($aid); ?>
                        <?php echo copella_topics_render_author_playlists($pl_ids); ?>
                    </div>
                </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="au-empty"><?php echo esc_html__('Авторы не найдены.', 'copella-topics'); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    
    <script>
    // Initialize this specific author container when DOM is ready
    (function() {
        if (typeof window.CopellaAuthors !== 'undefined') {
            var container = document.getElementById('<?php echo esc_js($uid); ?>');
            if (container) {
                window.CopellaAuthors.init(container);
            }
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [playlist_author] - Render single author
 */
function copella_topics_render_author(array $atts = []): string
{
    $a = shortcode_atts(array(
        'author_id' => '0',
        'id' => '0',
        'cover_ratio' => '16/9',
    ), $atts, 'playlist_author');

    $aid = (int) ($a['author_id'] ?: $a['id']);
    if ($aid <= 0 && get_post_type() === 'topic_author') {
        $aid = get_the_ID();
    }
    if ($aid <= 0) {
        return '';
    }

    $name = get_the_title($aid);
    $cover = get_the_post_thumbnail_url($aid, 'large');
    $desc = copella_topics_get_author_desc($aid);
    $pl_ids = copella_topics_get_author_playlist_ids($aid);
    $coverRatio = preg_replace('~[^0-9/\.]+~', '', (string) $a['cover_ratio']);

    $uid = 'pl_author_' . wp_generate_password(8, false, false);
    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-authors" style="<?php echo esc_attr(sprintf('--au-cols:%d;--au-cover-ratio:%s;', 1, $coverRatio)); ?>">
        <div class="au-grid">
            <article class="au-card is-collapsed">
                <div class="au-header" role="button" tabindex="0" aria-expanded="false">
                    <h4 class="au-name"><?php echo esc_html($name); ?></h4>
                    <button class="au-arrow" type="button" aria-label="<?php echo esc_attr__('Свернуть', 'copella-topics'); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </div>
                <div class="au-cover" aria-hidden="true">
                    <?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"/><?php endif; ?>
                </div>
                <div class="au-body">
                    <?php if (!empty($desc)): ?><p class="au-desc"><?php echo esc_html($desc); ?></p><?php endif; ?>
                    <?php echo copella_topics_render_author_social_networks($aid); ?>
                    <?php echo copella_topics_render_author_playlists($pl_ids); ?>
                </div>
            </article>
        </div>
    </section>
    
    <script>
    // Initialize this specific author container when DOM is ready
    (function() {
        if (typeof window.CopellaAuthors !== 'undefined') {
            var container = document.getElementById('<?php echo esc_js($uid); ?>');
            if (container) {
                window.CopellaAuthors.init(container);
            }
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
