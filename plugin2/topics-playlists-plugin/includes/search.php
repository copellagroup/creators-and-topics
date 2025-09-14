<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Advanced search shortcode [tp_search]
 *
 * Features:
 * - Tokenization with simple RU/EN stemming and stop-word removal
 * - Relevance scoring by fields (title/excerpt/content/taxonomies) + phrase bonus + recency bonus
 * - Manual sorting by score with pagination
 * - Highlighting query terms in titles and excerpts
 * - Lightweight UI: search box, filters (post types), sort switch, results grid
 */

add_action('init', function (): void {
    add_shortcode('tp_search', 'copella_topics_render_search');
});

function copella_topics_render_search(array $atts = []): string
{
    $a = shortcode_atts(array(
        'post_types' => 'post,topic,page',
        'per_page'   => '12',
        'max_query'  => '120',
        'sort'       => 'relevance',
        'placeholder'=> __('Поиск по сайту…', 'copella-topics'),
        'show_filters'=> 'true',
        'action'     => '',
    ), $atts, 'tp_search');

    $allowedPostTypes = array('post', 'page', 'topic', 'topic_playlist');
    $requestedTypes = array_filter(array_map('trim', explode(',', (string) $a['post_types'])));
    $postTypes = array_values(array_intersect($allowedPostTypes, $requestedTypes ?: $allowedPostTypes));
    if (empty($postTypes)) {
        $postTypes = array('post', 'topic', 'page');
    }

    $perPage   = max(1, min(40, (int) $a['per_page']));
    $maxQuery  = max(40, min(300, (int) $a['max_query']));
    $defaultSort = strtolower((string) $a['sort']) === 'date' ? 'date' : 'relevance';
    $showFilters = filter_var($a['show_filters'], FILTER_VALIDATE_BOOLEAN);

    $get_s       = isset($_GET['s']) ? (string) $_GET['s'] : '';
    $queryText   = copella_topics_sanitize_query_text($get_s, $maxQuery);
    if ($queryText === '' && isset($_GET['q']) && (string) $_GET['q'] !== '') {
        $queryText = copella_topics_sanitize_query_text((string) $_GET['q'], $maxQuery);
    }

    $selectedTypes = array();
    if (isset($_GET['tp_types'])) {
        if (is_array($_GET['tp_types'])) {
            $selectedTypes = array_map('sanitize_text_field', (array) $_GET['tp_types']);
        } else {
            $selectedTypes = array_filter(array_map('trim', explode(',', (string) $_GET['tp_types'])));
        }
    }
    $activeTypes = array_values(array_intersect($postTypes, $selectedTypes));
    if (empty($activeTypes)) {
        $activeTypes = $postTypes;
    }

    // Sorting is simplified: always by relevance, no UI controls
    $sort = 'relevance';

    $page = isset($_GET['tp_page']) ? max(1, (int) $_GET['tp_page']) : 1;

    $uid = 'tp_search_' . wp_generate_password(8, false, false);

    $results = array();
    $totalFound = 0;

    if ($queryText !== '') {
        $tokens = copella_topics_extract_tokens($queryText);

        $queryArgs = array(
            'post_type'        => count($activeTypes) === 1 ? $activeTypes[0] : $activeTypes,
            'post_status'      => 'publish',
            's'                => $queryText,
            'posts_per_page'   => 80,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => false,
            'ignore_sticky_posts' => true,
        );

        $q = new WP_Query($queryArgs);
        $candidates = array();
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                $pid = get_the_ID();
                $scoreInfo = copella_topics_score_post($pid, $tokens, $queryText);
                if ($scoreInfo['score'] > 0) {
                    $candidates[] = $scoreInfo;
                }
            }
            wp_reset_postdata();
        }

        usort($candidates, function ($a, $b) {
            // Relevance first; if equal, newer first
            if ($a['score'] === $b['score']) {
                return $b['date'] - $a['date'];
            }
            return $b['score'] <=> $a['score'];
        });

        $totalFound = count($candidates);
        $offset = ($page - 1) * $perPage;
        $pagedItems = array_slice($candidates, $offset, $perPage);

        foreach ($pagedItems as $it) {
            $results[] = $it;
        }
    }

    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="tp-search" aria-label="<?php echo esc_attr__('Расширенный поиск', 'copella-topics'); ?>">
        <form class="tps-form" role="search" method="get" action="<?php echo esc_url(copella_topics_search_form_action_url((string) $a['action'])); ?>">
            <div class="tps-bar">
                <button type="button" class="tps-params" aria-haspopup="dialog" aria-controls="<?php echo esc_attr($uid); ?>_filters" aria-expanded="false" title="<?php echo esc_attr__('Параметры поиска', 'copella-topics'); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 6h16M6 12h12M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="tps-params-text"><?php echo esc_html__('Фильтр', 'copella-topics'); ?></span>
                </button>
                <input type="hidden" name="s" value="<?php echo esc_attr($queryText); ?>" />
            </div>
            <input type="hidden" name="tp_page" value="<?php echo (int) $page; ?>" />
            <?php if ($showFilters): ?>
            <div class="tps-modal" id="<?php echo esc_attr($uid); ?>_filters" role="dialog" aria-modal="true" hidden>
                <div class="tps-sheet" role="document">
                    <div class="tps-sheet-h">
                        <span><?php echo esc_html__('Параметры', 'copella-topics'); ?></span>
                        <button type="button" class="tps-close" aria-label="<?php echo esc_attr__('Закрыть', 'copella-topics'); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div class="tps-sheet-b">
                        <div class="tps-filter-group">
                            <div class="tps-filter-label"><?php echo esc_html__('Типы', 'copella-topics'); ?></div>
                            <div class="tps-chips">
                                <?php foreach ($postTypes as $pt): $checked = in_array($pt, $activeTypes, true); ?>
                                    <label class="tps-chip">
                                        <input type="checkbox" name="tp_types[]" value="<?php echo esc_attr($pt); ?>" <?php checked($checked); ?> />
                                        <span><?php echo esc_html(copella_topics_human_pt($pt)); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                    </div>
                    <div class="tps-sheet-f">
                        <button type="button" class="tps-reset"><?php echo esc_html__('Сбросить', 'copella-topics'); ?></button>
                        <button type="button" class="tps-apply"><?php echo esc_html__('Применить', 'copella-topics'); ?></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </form>

        <?php if ($queryText === ''): ?>
            <div class="tps-empty">
                <div class="tps-search-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                        <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="tps-empty-title"><?php echo esc_html__('Введите свой запрос', 'copella-topics'); ?></h3>
                <p class="tps-empty-desc"><?php echo esc_html__('Ищите по заголовкам, описаниям, содержимому и тегам', 'copella-topics'); ?></p>
            </div>
        <?php else: ?>
            <div class="tps-meta">
                <span class="tps-count"><?php echo esc_html(sprintf(_n('%d результат', '%d результатов', $totalFound, 'copella-topics'), (int) $totalFound)); ?></span>
                <?php if ($totalFound > 0): ?>
                    <span class="tps-query">«<?php echo esc_html($queryText); ?>»</span>
                <?php endif; ?>
            </div>

            <?php if ($totalFound === 0): ?>
                <div class="tps-nores" role="status"><?php echo esc_html__('Ничего не найдено. Попробуйте упростить запрос или выбрать другие типы материалов.', 'copella-topics'); ?></div>
            <?php else: ?>
                <div class="tps-list" role="list">
                    <?php foreach ($results as $row): ?>
                        <?php
                            $pid = $row['id'];
                            $pt  = get_post_type($pid);
                            $titleRaw = get_the_title($pid);
                            $title = copella_topics_highlight($titleRaw, $row['tokens']);
                            $thumb = get_the_post_thumbnail_url($pid, 'medium');
                            $excerptRaw = $row['excerpt'];
                            $excerptShort = wp_trim_words(wp_strip_all_tags($excerptRaw), 18, '…');
                            $excerpt = copella_topics_highlight($excerptShort, $row['tokens']);
                            $url = get_permalink($pid);
                            $date = mysql2date(get_option('date_format'), get_post_field('post_date', $pid));
                            $scorePercent = min(100, max(10, (int) round($row['score'] * 100)));
                            $cat_terms = get_the_terms($pid, 'category');
                            $cats = array();
                            if (is_array($cat_terms)) { foreach ($cat_terms as $t) { $cats[] = (string) $t->name; } }
                            $plBadges = copella_topics_collect_playlist_badges_for_post($pid);
                            $plTags = isset($plBadges['tags']) ? (array)$plBadges['tags'] : array();
                            $plGroups = isset($plBadges['groups']) ? (array)$plBadges['groups'] : array();
                        ?>
                        <a class="tps-item" role="listitem" href="<?php echo esc_url($url); ?>" title="<?php echo esc_attr(wp_strip_all_tags($titleRaw)); ?>">
                            <span class="tps-thumb" aria-hidden="true">
                                <?php if ($thumb): ?><img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy"/><?php else: ?><span class="tps-fallback"></span><?php endif; ?>
                            </span>
                            <span class="tps-body">
                                <span class="tps-ttl"><?php echo $title; ?></span>
                                <span class="tps-meta-line"><span class="tps-date"><?php echo esc_html($date); ?></span></span>
                                <?php if (!empty($cats) || !empty($plTags) || !empty($plGroups)): ?>
                                    <span class="tps-tax">
                                        <?php foreach ($cats as $nm): ?>
                                            <span class="tps-tax-chip tps-tax-cat"><?php echo copella_topics_highlight($nm, $row['tokens']); ?></span>
                                        <?php endforeach; ?>
                                        <?php foreach ($plGroups as $nm): ?>
                                            <span class="tps-tax-chip tps-tax-group"><?php echo copella_topics_highlight($nm, $row['tokens']); ?></span>
                                        <?php endforeach; ?>
                                        <?php foreach ($plTags as $nm): ?>
                                            <span class="tps-tax-chip tps-tax-tag"><?php echo copella_topics_highlight($nm, $row['tokens']); ?></span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($excerpt): ?><span class="tps-excerpt"><?php echo $excerpt; ?></span><?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php
                $maxPages = max(1, (int) ceil($totalFound / $perPage));
                if ($maxPages > 1):
                    $baseUrl = remove_query_arg('tp_page');
                ?>
                    <nav class="tps-pager" aria-label="<?php echo esc_attr__('Пагинация результатов', 'copella-topics'); ?>">
                        <?php for ($i = 1; $i <= $maxPages; $i++):
                            $url = esc_url(add_query_arg('tp_page', (string) $i, $baseUrl));
                            $isActive = $i === $page;
                        ?>
                            <a class="tps-pg <?php echo $isActive ? 'is-active' : ''; ?>" href="<?php echo $url; ?>"><?php echo (int) $i; ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <style>
        .tp-search{--tps-gap:12px;--tps-radius:14px;--tps-card-bg:#171717;--tps-muted:#a1a1a1;color:#fff;font-family:'Gilroy-SemiBold','Gilroy-Bold',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;padding-left:16px;padding-right:16px}
        .tp-search .tps-bar{display:flex;gap:8px;align-items:center;margin:0 0 10px 0}
        .tp-search .tps-params{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:38px;padding:0 16px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:#0f0f0f;color:#fff;font-family:'Gilroy-SemiBold','Gilroy-Bold',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px;font-weight:600}
        .tp-search .tps-params-text{display:none}
        @media (min-width: 480px){.tp-search .tps-params-text{display:inline}}
        .tp-search .tps-bar input[type=search]{flex:1;min-width:0;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.08);background:#0f0f0f;color:#fff;font-family:'Gilroy-SemiBold','Gilroy-Bold',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;font-size:15px}
        .tp-search .tps-bar input[type=search]::placeholder{color:rgba(255,255,255,.55)}
        .tp-search .tps-bar .tps-btn{display:inline-flex;align-items:center;justify-content:center;height:38px;width:44px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:#0f0f0f;color:#fff}
        .tp-search .tps-filters{display:none}
        .tp-search .tps-filter-group{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .tp-search .tps-filter-label{opacity:.8}
        .tp-search .tps-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.06);border:0;border-radius:12px;padding:6px 10px;color:#fff}
        .tp-search .tps-chip input{accent-color:#ff653a}
        .tp-search .tps-meta{display:flex;gap:8px;align-items:center;margin:6px 0 10px 0}
        .tp-search .tps-count{font-weight:600}
        .tp-search .tps-query{opacity:.7}
        .tp-search .tps-list{display:flex;flex-direction:column;gap:10px}
        .tp-search .tps-item{display:flex;gap:12px;align-items:center;color:inherit;text-decoration:none;border-radius:12px;padding:8px 10px;background:var(--tps-card-bg);border:1px solid rgba(255,255,255,.06)}
        .tp-search .tps-item:hover{background:rgba(255,255,255,.04)}
        .tp-search .tps-thumb{width:76px;flex:0 0 auto;aspect-ratio:16/9;border-radius:10px;background:#0f0f0f;overflow:hidden;position:relative}
        .tp-search .tps-thumb img{width:100%;height:100%;object-fit:cover;display:block}
        @supports not (aspect-ratio: 1 / 1){.tp-search .tps-thumb{padding-top:56%}.tp-search .tps-thumb img{position:absolute;inset:0}}
        .tp-search .tps-fallback{display:block;width:100%;height:100%;background:linear-gradient(135deg,#222,#111)}
        .tp-search .tps-body{display:flex;flex-direction:column;gap:4px;min-width:0}
        .tp-search .tps-ttl{display:block;font-weight:700;font-family:'Gilroy-Bold','Gilroy-SemiBold',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .tp-search .tps-meta-line{display:flex;gap:8px;align-items:center;opacity:.7;font-size:12px}
        .tp-search .tps-tax{display:flex;gap:6px;flex-wrap:wrap;margin-top:2px}
        .tp-search .tps-tax-chip{display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,.06);color:#fff;font-size:12px}
        .tp-search .tps-tax-cat{border:1px solid rgba(255,255,255,.1)}
        .tp-search .tps-tax-group{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.08)}
        .tp-search .tps-tax-tag{border:1px dashed rgba(255,255,255,.15)}
        .tp-search .tps-excerpt{display:block;margin-top:0;color:#e6e6e6;opacity:.85;font-size:12.5px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .tp-search mark{background:#ff653a;color:#111;padding:0 2px;border-radius:3px}
        .tp-search .tps-pager{display:flex;gap:8px;justify-content:center;margin-top:14px}
        .tp-search .tps-pg{padding:6px 10px;background:#0f0f0f;border:1px solid rgba(255,255,255,.08);border-radius:10px;color:#fff;text-decoration:none}
        .tp-search .tps-pg.is-active{background:#fff;color:#111;border-color:#fff}
        .tp-search .tps-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;text-align:center;opacity:.9;font-family:'Gilroy-SemiBold','Gilroy-Bold',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif}
        .tp-search .tps-search-icon{display:flex;align-items:center;justify-content:center;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.06);border:2px solid rgba(255,255,255,.1);margin-bottom:24px;color:rgba(255,255,255,.6);animation:tps-pulse 2s ease-in-out infinite}
        .tp-search .tps-search-icon svg{display:block}
        .tp-search .tps-search-icon svg circle{stroke-dasharray:60;stroke-dashoffset:60;animation:tps-scan 2.5s ease-in-out infinite}
        .tp-search .tps-search-icon svg path{animation:tps-bob 3s ease-in-out infinite}
        .tp-search .tps-empty-title{margin:0 0 12px 0;font-size:24px;font-weight:700;color:#fff;font-family:'Gilroy-Bold','Gilroy-SemiBold',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif}
        .tp-search .tps-empty-desc{margin:0;font-size:16px;color:rgba(255,255,255,.7);line-height:1.5;max-width:400px}
        @keyframes tps-pulse{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.05);opacity:.85}}
        @keyframes tps-scan{0%{stroke-dashoffset:60;opacity:.7}50%{stroke-dashoffset:30;opacity:1}100%{stroke-dashoffset:0;opacity:.7}}
        @keyframes tps-bob{0%,100%{transform:translateY(0)}50%{transform:translateY(-2px)}}
        @media (max-width: 640px){.tp-search{padding-left:12px;padding-right:12px}}
        @media (max-width: 1024px) and (min-width: 641px){.tp-search{padding-left:16px;padding-right:16px}}

        /* Modal sheet for filters */
        .tp-search .tps-modal[hidden]{display:none}
        .tp-search .tps-modal{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);display:grid;place-items:end center;padding:16px}
        .tp-search .tps-sheet{width:min(680px,100%);background:#0f0f0f;border:1px solid rgba(255,255,255,.1);border-radius:16px;overflow:hidden;color:#fff}
        .tp-search .tps-sheet-h{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08);font-weight:700}
        .tp-search .tps-close{background:transparent;border:0;color:#fff;width:34px;height:34px;border-radius:10px}
        .tp-search .tps-sheet-b{padding:12px 14px;display:flex;flex-direction:column;gap:14px}
        .tp-search .tps-chips{display:flex;gap:8px;flex-wrap:wrap}
        .tp-search .tps-segment{display:inline-flex;background:rgba(255,255,255,.06);border-radius:12px;padding:4px}
        .tp-search .tps-seg{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:10px;color:#fff}
        .tp-search .tps-seg input{display:none}
        .tp-search .tps-seg input:checked + span{background:#fff;color:#111;border-radius:8px;padding:4px 8px}
        .tp-search .tps-sheet-f{display:flex;gap:8px;justify-content:flex-end;padding:12px 14px;border-top:1px solid rgba(255,255,255,.08)}
        .tp-search .tps-reset{background:transparent;border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;padding:8px 12px}
        .tp-search .tps-apply{background:#fff;border:0;border-radius:10px;color:#111;padding:8px 12px}
        </style>
        <script>
        (function(){
          var root=document.getElementById('<?php echo esc_js($uid); ?>');
          if(!root) return;
          var form=root.querySelector('.tps-form');
          if(!form) return;
          var modal=root.querySelector('#<?php echo esc_js($uid); ?>_filters');
          var btnParams=root.querySelector('.tps-params');
          var btnClose=modal ? modal.querySelector('.tps-close') : null;
          var btnApply=modal ? modal.querySelector('.tps-apply') : null;
          var btnReset=modal ? modal.querySelector('.tps-reset') : null;

          function openModal(){ if(!modal) return; modal.hidden=false; if(btnParams){ btnParams.setAttribute('aria-expanded','true'); } }
          function closeModal(){ if(!modal) return; modal.hidden=true; if(btnParams){ btnParams.setAttribute('aria-expanded','false'); } }
          if(btnParams){ btnParams.addEventListener('click', openModal); }
          if(btnClose){ btnClose.addEventListener('click', closeModal); }
          if(modal){ modal.addEventListener('click', function(e){ if(e.target===modal){ closeModal(); } }); }

          if(btnApply){ btnApply.addEventListener('click', function(){
            var pageInput=form.querySelector('input[name="tp_page"]');
            if(pageInput){ pageInput.value='1'; }
            form.submit();
            closeModal();
          }); }
          if(btnReset){ btnReset.addEventListener('click', function(){
            var checks=form.querySelectorAll('input[name="tp_types[]"]');
            checks.forEach(function(c){ c.checked=false; });
          }); }
        })();
        </script>
    </section>
    <?php
    return ob_get_clean();
}

function copella_topics_sanitize_query_text(string $q, int $maxLen = 120): string
{
    $q = trim((string) wp_strip_all_tags($q));
    if ($q === '') { return ''; }
    if (function_exists('mb_substr')) {
        $q = mb_substr($q, 0, $maxLen, 'UTF-8');
    } else {
        $q = substr($q, 0, $maxLen);
    }
    return $q;
}

function copella_topics_extract_tokens(string $text): array
{
    $l = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $raw = preg_split('~[^\p{L}\p{N}\-]+~u', $l) ?: array();
    $stop = array('и','в','на','по','для','о','у','к','с','из','а','но','как','the','and','or','to','of','in','on','for','a','an');
    $tokens = array();
    foreach ($raw as $w) {
        $w = trim($w);
        if ($w === '' || in_array($w, $stop, true)) { continue; }
        if (function_exists('mb_strlen') ? (mb_strlen($w, 'UTF-8') < 2) : (strlen($w) < 2)) { continue; }
        $tokens[] = copella_topics_simple_stem($w);
    }
    $tokens = array_values(array_unique($tokens));
    return $tokens;
}

function copella_topics_simple_stem(string $w): string
{
    if (preg_match('~[а-яё]~u', $w)) {
        return copella_topics_simple_stem_ru($w);
    }
    return copella_topics_simple_stem_en($w);
}

function copella_topics_simple_stem_ru(string $w): string
{
    $w = function_exists('mb_strtolower') ? mb_strtolower($w, 'UTF-8') : strtolower($w);
    $endings = array('иями','ями','иях','ях','ация','ации','ацию','аций','ами','ями','его','ого','ему','ому','ее','ие','ые','ое','ией','ией','ий','ый','ой','ем','ом','ам','ям','ах','ях','ию','ью','ия','ья','ии','ьи','иям','ьям','иях','ьях','ая','яя','ою','ею','ую');
    foreach ($endings as $e) {
        $len = function_exists('mb_strlen') ? mb_strlen($e, 'UTF-8') : strlen($e);
        $tail = function_exists('mb_substr') ? mb_substr($w, -$len, null, 'UTF-8') : substr($w, -$len);
        if ($tail === $e && ((function_exists('mb_strlen') ? mb_strlen($w, 'UTF-8') : strlen($w)) > $len + 1)) {
            return function_exists('mb_substr') ? mb_substr($w, 0, (function_exists('mb_strlen') ? mb_strlen($w, 'UTF-8') : strlen($w)) - $len, 'UTF-8') : substr($w, 0, strlen($w) - $len);
        }
    }
    return $w;
}

function copella_topics_simple_stem_en(string $w): string
{
    $w = strtolower($w);
    foreach (array('ing','edly','edly','ness','ment','tion','sion','able','ible','ally','ies','ied','ed','es','s') as $e) {
        if (substr($w, -strlen($e)) === $e && strlen($w) > strlen($e) + 1) {
            return substr($w, 0, -strlen($e));
        }
    }
    return $w;
}

function copella_topics_human_pt(string $pt): string
{
    switch ($pt) {
        case 'topic': return __('Тема', 'copella-topics');
        case 'topic_playlist': return __('Плейлист', 'copella-topics');
        case 'page': return __('Страница', 'copella-topics');
        default: return __('Публикация', 'copella-topics');
    }
}

function copella_topics_score_post(int $postId, array $tokens, string $queryText): array
{
    $title = get_the_title($postId);
    $excerpt = get_the_excerpt($postId);
    if ($excerpt === '') {
        $excerpt = wp_trim_words(strip_shortcodes(strip_tags((string) get_post_field('post_content', $postId))), 40, '…');
    }
    $content = (string) get_post_field('post_content', $postId);
    $contentStripped = wp_strip_all_tags($content);
    $pt = get_post_type($postId) ?: 'post';

    $l = function($s){ return function_exists('mb_strtolower') ? mb_strtolower((string) $s, 'UTF-8') : strtolower((string) $s); };
    $titleL = $l($title);
    $excerptL = $l($excerpt);
    $contentL = $l($contentStripped);

    $phraseBonus = 0;
    $qtL = $l($queryText);
    if ($qtL !== '' && (strpos($titleL, $qtL) !== false)) { $phraseBonus += 6; }
    if ($qtL !== '' && (strpos($excerptL, $qtL) !== false)) { $phraseBonus += 2; }

    $titleHits = copella_topics_count_hits($titleL, $tokens);
    $excerptHits = copella_topics_count_hits($excerptL, $tokens);
    $contentHits = copella_topics_count_hits($contentL, $tokens);

    $taxHits = 0;
    $terms = array();
    $taxes = array('category','post_tag');
    foreach ($taxes as $tx) {
        $t = get_the_terms($postId, $tx);
        if (is_array($t)) { $terms = array_merge($terms, $t); }
    }
    if (!empty($terms)) {
        $names = array();
        foreach ($terms as $term) { $names[] = (string) $term->name; }
        $namesL = $l(implode(' ', $names));
        $taxHits = copella_topics_count_hits($namesL, $tokens);
    }

    $dateU = get_post_time('U', true, $postId);
    $nowU = current_time('timestamp');
    $ageDays = max(0, ($nowU - (int) $dateU) / 86400);
    $recency = max(0.0, 1.0 - min(730.0, $ageDays) / 730.0);

    $ptWeight = 1.0;
    if ($pt === 'topic') { $ptWeight = 1.15; }
    elseif ($pt === 'page') { $ptWeight = 0.95; }
    elseif ($pt === 'topic_playlist') { $ptWeight = 0.9; }

    $score = 0.0;
    $score += $titleHits * 4.0;
    $score += $excerptHits * 2.5;
    $score += $contentHits * 1.0;
    $score += $taxHits * 3.0;
    $score += $phraseBonus;
    $score += $recency * 2.0;
    $score *= $ptWeight;

    $scoreNorm = 1.0 - exp(-$score / 6.0);

    $badges = array();
    if ($titleHits > 0) { $badges[] = __('в заголовке', 'copella-topics'); }
    if ($excerptHits > 0) { $badges[] = __('в описании', 'copella-topics'); }
    if ($taxHits > 0) { $badges[] = __('в тегах/категориях', 'copella-topics'); }
    if ($phraseBonus > 0) { $badges[] = __('фразовое совпадение', 'copella-topics'); }

    return array(
        'id'      => $postId,
        'score'   => $scoreNorm,
        'date'    => (int) $dateU,
        'badges'  => $badges,
        'tokens'  => $tokens,
        'excerpt' => $excerpt,
    );
}

function copella_topics_count_hits(string $haystackL, array $tokens): int
{
    $hits = 0;
    foreach ($tokens as $t) {
        $pattern = '~\b' . preg_quote($t, '~') . '(?:[\p{L}\p{N}]*)\b~u';
        if (preg_match_all($pattern, $haystackL, $m)) { $hits += (int) count($m[0]); }
    }
    return $hits;
}

function copella_topics_highlight(string $text, array $tokens): string
{
    if ($text === '' || empty($tokens)) { return esc_html($text); }
    $clean = wp_strip_all_tags($text);
    $safe = esc_html($clean);
    foreach ($tokens as $t) {
        $pattern = '~(\b)(' . preg_quote($t, '~') . '([\p{L}\p{N}]*)\b)~iu';
        $safe = preg_replace($pattern, '$1<mark>$2</mark>', (string) $safe);
    }
    return $safe;
}

function copella_topics_search_form_action_url(string $override = ''): string
{
    // If override action is given, use it
    if (!empty($override)) {
        return $override;
    }
    // Prefer the current URL (including path) without pagination to keep the user on the same page
    $base = remove_query_arg(array('tp_page'));
    // If action becomes empty (e.g. in some builders), fallback to home_url
    $current = (string) $base;
    if ($current === '' || $current === home_url() || $current === site_url()) {
        // Try to use the current request URI to preserve the page where shortcode lives
        $req = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ($req !== '') {
            $action = home_url($req);
        } else {
            $action = home_url('/');
        }
    } else {
        $action = $current;
    }
    return $action;
}

/**
 * Collect playlist badges for a given post:
 * - tags from playlist items (tag=...)
 * - group titles from playlists (# Group)
 */
function copella_topics_collect_playlist_badges_for_post(int $postId): array
{
    $tags = array();
    $groups = array();

    $q = new WP_Query(array(
        'post_type' => 'topic_playlist',
        'post_status' => array('publish','private','draft'),
        'posts_per_page' => 300,
        'no_found_rows' => true,
        'fields' => 'ids',
    ));
    if ($q->have_posts()) {
        foreach ($q->posts as $pid) {
            $videos_raw = (string) get_post_meta((int)$pid, COPELLA_PLAYLIST_META_ITEMS, true);
            if ($videos_raw === '') { continue; }
            $rawLines = preg_split("/\r\n|\r|\n/", $videos_raw);
            $currentGroup = '';
            if (is_array($rawLines)) {
                foreach ($rawLines as $rawLine) {
                    $line = trim((string) $rawLine);
                    if ($line === '') { continue; }
                    if (preg_match('~^#+\s*(.+)$~u', $line, $m)) {
                        $currentGroup = trim((string) $m[1]);
                        continue;
                    }
                    $parts = array_map('trim', explode('|', $line));
                    $idOrUrl = array_shift($parts);
                    $matchedPostId = 0;
                    if (ctype_digit($idOrUrl)) { $matchedPostId = (int) $idOrUrl; }
                    else { $matchedPostId = url_to_postid($idOrUrl); }
                    if ($matchedPostId !== $postId) { continue; }
                    $metaTag = '';
                    foreach ($parts as $p) {
                        if (stripos($p, 'tag=') === 0) { $metaTag = trim(substr($p, 4)); }
                    }
                    if ($metaTag !== '') { $tags[] = $metaTag; }
                    if ($currentGroup !== '') { $groups[] = $currentGroup; }
                }
            }
        }
    }
    wp_reset_postdata();
    return array(
        'tags' => array_values(array_unique(array_filter($tags))),
        'groups' => array_values(array_unique(array_filter($groups))),
    );
}


