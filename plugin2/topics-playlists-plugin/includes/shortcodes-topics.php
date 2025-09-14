<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [hot_topics] - Render hot topics carousel
 */
function copella_topics_render_hot_topics(array $atts = []): string
{
    $defaults = array(
        'title'            => __('Горячие темы', 'copella-topics'),
        'posts_per_page'   => '10',
        'aspect_ratio'     => '1/1',
        'card_radius'      => '28',
        'gap'              => '28',
        'container_radius' => '32',
        'container_bg'     => '#171717',
        'card_placeholder' => '#FF653A',
        'paged'            => '1',
        'stagger'          => 'false',
        'stagger_offset'   => '56',
    );

    $a = shortcode_atts($defaults, $atts, 'hot_topics');

    $title           = trim((string) $a['title']);
    $postsPerPage    = max(1, (int) $a['posts_per_page']);
    $aspectRatio     = preg_replace('~[^0-9/\.]+~', '', (string) $a['aspect_ratio']);
    $cardRadius      = (int) $a['card_radius'];
    $gap             = (int) $a['gap'];
    $containerRadius = (int) $a['container_radius'];
    $containerBg     = sanitize_hex_color((string) $a['container_bg']) ?: '#171717';
    $cardPlaceholder = sanitize_hex_color((string) $a['card_placeholder']) ?: '#FF653A';
    $stagger         = filter_var($a['stagger'], FILTER_VALIDATE_BOOLEAN);
    $staggerOffset   = (int) $a['stagger_offset'];

    $paged = isset($_GET['topics_page']) ? max(1, (int) $_GET['topics_page']) : max(1, (int) $a['paged']);

    $query = new WP_Query(array(
        'post_type'      => 'topic',
        'post_status'    => ['publish', 'future'],  // Include scheduled posts
        'posts_per_page' => $postsPerPage,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    $total_topics = (int) $query->found_posts;
    $max_pages    = max(1, (int) $query->max_num_pages);

    $uid = 'hot_topics_' . wp_generate_password(8, false, false);

    $styleVars = sprintf(
        '--ht-gap:%dpx;--ht-card-radius:%dpx;--ht-container-radius:%dpx;--ht-aspect-ratio:%s;--ht-container-bg:%s;--ht-card-placeholder:%s;--ht-stagger:%dpx;',
        $gap,
        $cardRadius,
        $containerRadius,
        $aspectRatio,
        $containerBg,
        $cardPlaceholder,
        $staggerOffset
    );

    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-ht<?php echo $stagger ? ' is-staggered' : ''; ?>" style="<?php echo esc_attr($styleVars); ?>" aria-label="<?php echo esc_attr($title ?: __('Горячие темы', 'copella-topics')); ?>">
        <div class="ht-header">
            <div class="ht-titlebar">
                <button class="ht-nav ht-prev" type="button" aria-label="<?php echo esc_attr__('Назад', 'copella-topics'); ?>" title="<?php echo esc_attr__('Назад', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <h3 class="ht-title"><?php echo esc_html($title ?: __('Горячие темы', 'copella-topics')); ?></h3>
                <button class="ht-nav ht-next" type="button" aria-label="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>" title="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div class="ht-count"><?php echo esc_html(sprintf(__('всего %d тем', 'copella-topics'), $total_topics)); ?></div>
        </div>
        <div class="ht-viewport">
            <div class="ht-track" role="list">
                <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                    $permalink = get_permalink();
                    $customLink = (string) get_post_meta(get_the_ID(), COPELLA_TOPICS_META_LINK, true);
                    $targetUrl = $customLink ? $customLink : $permalink;
                    $postTitle = get_the_title();
                    $thumbUrl  = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                    $postStatus = get_post_status();
                    $isScheduled = ($postStatus === 'future');
                    $publishTime = $isScheduled ? get_post_time('U', true) : null;
                    $currentTime = time();
                    $timeLeft = $isScheduled ? max(0, $publishTime - $currentTime) : 0;
                ?>
                <?php if ($isScheduled): ?>
                    <div class="ht-card ht-scheduled" role="listitem" title="<?php echo esc_attr($postTitle); ?>" data-publish-time="<?php echo esc_attr($publishTime); ?>">
                <?php else: ?>
                    <a class="ht-card" role="listitem" href="<?php echo esc_url($targetUrl); ?>" title="<?php echo esc_attr($postTitle); ?>">
                <?php endif; ?>
                    <span class="ht-thumb" aria-hidden="true" style="<?php echo $thumbUrl ? '' : 'background:' . esc_attr($cardPlaceholder) . ';'; ?>">
                        <?php if ($thumbUrl): ?>
                            <img src="<?php echo esc_url($thumbUrl); ?>" alt="<?php echo esc_attr($postTitle); ?>" loading="lazy"/>
                        <?php else: ?>
                            <span class="ht-fallback"></span>
                        <?php endif; ?>
                        <?php if ($isScheduled): ?>
                            <div class="ht-premiere-banner">
                                <div class="ht-premiere-timer" data-target="<?php echo esc_attr($publishTime); ?>">
                                    <span class="ht-premiere-label">ДО ПРЕМЬЕРЫ ОСТАЛОСЬ</span>
                                    <span class="ht-premiere-time"><?php echo gmdate('H:i:s', $timeLeft); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </span>
                    <span class="ht-card-title"><?php echo esc_html($postTitle); ?></span>
                <?php if ($isScheduled): ?>
                    </div>
                <?php else: ?>
                    </a>
                <?php endif; ?>
                <?php endwhile; wp_reset_postdata(); else: ?>
                    <div class="ht-empty" role="status"><?php _e('Тем пока нет.', 'copella-topics'); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($max_pages > 1): ?>
        <nav class="ht-pagination" aria-label="<?php echo esc_attr__('Пагинация тем', 'copella-topics'); ?>">
            <?php
            $base_url = remove_query_arg('topics_page');
            for ($i = 1; $i <= $max_pages; $i++) {
                $url = esc_url(add_query_arg('topics_page', (string) $i, $base_url));
                $class = $i === $paged ? 'class="is-active"' : '';
                echo '<a ' . $class . ' href="' . $url . '">' . (int) $i . '</a>';
            }
            ?>
        </nav>
        <?php endif; ?>
    </section>
    <script>
      (function(){
        var root = document.getElementById('<?php echo esc_js($uid); ?>');
        if(!root) return;
        var track = root.querySelector('.ht-track');
        var btnPrev = root.querySelector('.ht-prev');
        var btnNext = root.querySelector('.ht-next');
        if(!track || !btnPrev || !btnNext) return;

        function getScrollAmount(){
          return Math.max(120, root.clientWidth/3 + 20);
        }
        function updateButtons(){
          var max = track.scrollWidth - track.clientWidth - 1;
          btnPrev.disabled = track.scrollLeft <= 1;
          btnNext.disabled = track.scrollLeft >= max;
        }
        function scrollByDir(dir){ track.scrollBy({ left: dir * getScrollAmount(), behavior: 'smooth'}); }

        btnPrev.addEventListener('click', function(){ scrollByDir(-1); });
        btnNext.addEventListener('click', function(){ scrollByDir(1); });
        track.addEventListener('scroll', updateButtons, { passive:true });
        window.addEventListener('resize', updateButtons);
        updateButtons();
        
        // Premiere countdown functionality
        function initPremiereCountdowns() {
            var premiereTimers = root.querySelectorAll('.ht-premiere-timer[data-target]');
            if (premiereTimers.length === 0) return;
            
            function updateCountdown(timer) {
                var targetTime = parseInt(timer.getAttribute('data-target'), 10);
                var currentTime = Math.floor(Date.now() / 1000);
                var timeLeft = Math.max(0, targetTime - currentTime);
                
                if (timeLeft <= 0) {
                    // Reload page when countdown reaches zero to show published post
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                    return;
                }
                
                var hours = Math.floor(timeLeft / 3600);
                var minutes = Math.floor((timeLeft % 3600) / 60);
                var seconds = timeLeft % 60;
                
                var timeDisplay = '';
                if (hours > 0) {
                    // Show H:MM:SS format when hours > 0
                    timeDisplay = hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                } else {
                    // Show MM:SS format when less than 1 hour
                    timeDisplay = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }
                
                var timeElement = timer.querySelector('.ht-premiere-time');
                if (timeElement) {
                    timeElement.textContent = timeDisplay;
                }
            }
            
            function updateAllCountdowns() {
                premiereTimers.forEach(updateCountdown);
            }
            
            // Update immediately and then every second
            updateAllCountdowns();
            setInterval(updateAllCountdowns, 1000);
        }
        
        initPremiereCountdowns();
      })();
    </script>
    <?php
    return ob_get_clean();
}
