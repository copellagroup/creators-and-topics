<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [topic_playlist] - Render topic playlist
 */
function copella_topics_render_playlist(array $atts = []): string
{
    $a = shortcode_atts(array(
        'playlist_id' => '0',
        'id' => '0',
        'title' => '',
        'aspect_ratio' => '1/1',
        'card_radius' => '18',
        'container_radius' => '28',
        'container_bg' => '#171717',
        'layout' => 'list',
        'grid_min' => '220',
        'cover' => 'true',
        'cover_align' => 'right',
        'cover_height' => '220',
        'cover_radius' => '',
        'collapsed' => 'true',
        'default_tag' => '',
    ), $atts, 'topic_playlist');

    $playlist_id = (int) ($a['playlist_id'] ?: $a['id']);
    if ($playlist_id <= 0 && get_post_type() === 'topic_playlist') {
        $playlist_id = get_the_ID();
    }
    if ($playlist_id <= 0) {
        return '';
    }

    $title = trim((string) $a['title']);
    if ($title === '') {
        $title = get_the_title($playlist_id);
    }
    $aspectRatio = preg_replace('~[^0-9/\.]+~', '', (string) $a['aspect_ratio']);
    $cardRadius = (int) $a['card_radius'];
    $containerRadius = (int) $a['container_radius'];
    $containerBg = sanitize_hex_color((string) $a['container_bg']) ?: '#171717';
    $layout = strtolower(trim((string) $a['layout'])) === 'grid' ? 'grid' : 'list';
    $gridMin = max(160, (int) $a['grid_min']);

    $coverEnabled = filter_var($a['cover'], FILTER_VALIDATE_BOOLEAN);
    $coverAlign = in_array(strtolower((string)$a['cover_align']), array('left','right'), true) ? strtolower((string)$a['cover_align']) : 'right';
    $coverHeight = max(180, (int) $a['cover_height']);
    $coverRadius = (string)$a['cover_radius'] !== '' ? (int)$a['cover_radius'] : $containerRadius;
    $isCollapsed = filter_var($a['collapsed'], FILTER_VALIDATE_BOOLEAN);
    $defaultTag = trim((string) $a['default_tag']);

    $groupsMap = array();
    $videos_raw = (string) get_post_meta($playlist_id, COPELLA_PLAYLIST_META_ITEMS, true);
    $rawLines = preg_split("/\r\n|\r|\n/", $videos_raw);
    $currentGroup = '_default';
    if (is_array($rawLines)) {
        foreach ($rawLines as $rawLine) {
            $line = trim((string) $rawLine);
            if ($line === '') { continue; }
            if (preg_match('~^#+\s*(.+)$~u', $line, $m)) {
                $currentGroup = trim((string) $m[1]);
                if ($currentGroup === '') { $currentGroup = '_default'; }
                if (!array_key_exists($currentGroup, $groupsMap)) { $groupsMap[$currentGroup] = array(); }
                continue;
            }

            $meta = array('tag' => '', 'color' => '');
            $parts = array_map('trim', explode('|', $line));
            $idOrUrl = array_shift($parts);
            foreach ($parts as $p) {
                if (stripos($p, 'tag=') === 0) { $meta['tag'] = trim(substr($p, 4)); }
                if (stripos($p, 'color=') === 0) { $c = trim(substr($p, 6)); $meta['color'] = sanitize_hex_color($c) ?: $c; }
            }

            $item = array('href' => '', 'title' => '', 'thumb' => '', 'date' => '', 'tag' => $meta['tag'], 'color' => $meta['color']);
            if (ctype_digit($idOrUrl)) { $pid = (int) $idOrUrl; } else { $pid = url_to_postid($idOrUrl); }
            if ($pid > 0) {
                $item['href'] = get_permalink($pid);
                $item['title'] = get_the_title($pid);
                $item['thumb'] = get_the_post_thumbnail_url($pid, 'medium');
                $item['date'] = get_the_date('', $pid);
            } else {
                $item['href'] = esc_url($idOrUrl);
                $item['title'] = wp_parse_url($idOrUrl, PHP_URL_HOST) ?: $idOrUrl;
                $item['thumb'] = '';
            }

            if ($item['tag'] === '' && $defaultTag !== '') {
                $item['tag'] = $defaultTag;
            }

            if (!array_key_exists($currentGroup, $groupsMap)) { $groupsMap[$currentGroup] = array(); }
            $groupsMap[$currentGroup][] = $item;
        }
    }

    $groupNames = array_keys($groupsMap);
    if (empty($groupNames)) {
        $groupsMap['_default'] = array();
        $groupNames = array('_default');
    }
    $hasNamedGroups = array_filter($groupNames, function ($g) { return $g !== '_default'; });
    $useTabs = count($hasNamedGroups) > 1;

    $uid = 'topic_pl_' . wp_generate_password(8, false, false);
    ob_start();
    ?>
    <section id="<?php echo esc_attr($uid); ?>" class="copella-playlist<?php echo $coverEnabled ? ' has-cover' : ''; ?><?php echo $isCollapsed ? ' is-collapsed' : ''; ?>" data-pl-init="1" style="<?php echo esc_attr(sprintf('--pl-container-bg:%s;--pl-container-radius:%dpx;--pl-card-radius:%dpx;--pl-aspect:%s;--pl-grid-min:%dpx;--pl-cover-height:%dpx;--pl-cover-radius:%dpx;', $containerBg, $containerRadius, $cardRadius, $aspectRatio, $gridMin, $coverHeight, $coverRadius)); ?>">
        <?php if ($coverEnabled):
            $coverImage = get_the_post_thumbnail_url($playlist_id, 'large');
            if (!$coverImage) {
                $firstThumb = '';
                foreach ($groupsMap as $items) {
                    if (!empty($items) && !empty($items[0]['thumb'])) { $firstThumb = $items[0]['thumb']; break; }
                }
                $coverImage = $firstThumb ?: '';
            }
            $coverDesc = get_the_excerpt($playlist_id);
        ?>
        <div class="pl-cover pl-cover--<?php echo esc_attr($coverAlign); ?>"<?php echo $coverImage ? '' : ' data-empty-cover="1"'; ?>>
            <?php if ($coverImage): ?>
            <div class="pl-cover-media" aria-hidden="true">
                <img src="<?php echo esc_url($coverImage); ?>" alt="" loading="lazy"/>
            </div>
            <?php endif; ?>
            <div class="pl-cover-overlay" aria-hidden="true"></div>
            <div class="pl-cover-content">
                <h3 class="pl-cover-title"><?php echo esc_html($title); ?></h3>
                <?php if (!empty($coverDesc)): ?>
                    <p class="pl-cover-desc"><?php echo esc_html($coverDesc); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="pl-header" role="button" tabindex="0" aria-expanded="false" aria-controls="<?php echo esc_attr($uid); ?>_panels">
            <h3 class="pl-title"><?php echo esc_html($title); ?></h3>
            <span class="pl-arrow" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </div>
        <?php if ($useTabs): ?>
            <div class="pl-tabsbar" aria-label="<?php echo esc_attr__('Категории плейлиста', 'copella-topics'); ?>">
                <button class="pl-tabs-nav pl-prev" type="button" aria-label="<?php echo esc_attr__('Назад', 'copella-topics'); ?>" title="<?php echo esc_attr__('Назад', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="pl-tabs-viewport">
                    <div class="pl-tabs" role="tablist">
                        <?php
                        $tabIndex = 0;
                        foreach ($groupNames as $gName):
                            if ($gName === '_default') { continue; }
                            $isActive = ($tabIndex === 0);
                            ?>
                            <button type="button" class="pl-tab <?php echo $isActive ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($uid . '_panel_' . $tabIndex); ?>" id="<?php echo esc_attr($uid . '_tab_' . $tabIndex); ?>" data-pl-index="<?php echo (int) $tabIndex; ?>">
                                <?php echo esc_html($gName); ?>
                            </button>
                            <?php
                            $tabIndex++;
                        endforeach;
                        ?>
                    </div>
                </div>
                <button class="pl-tabs-nav pl-next" type="button" aria-label="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>" title="<?php echo esc_attr__('Вперед', 'copella-topics'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        <?php endif; ?>

        <div class="pl-panels" id="<?php echo esc_attr($uid); ?>_panels">
            <?php
            $panelIndex = 0;
            $renderGroups = !empty($hasNamedGroups) ? array_values(array_filter($groupNames, function($g){ return $g !== '_default'; })) : array('_default');
            foreach ($renderGroups as $gName):
                $groupItems = isset($groupsMap[$gName]) ? (array) $groupsMap[$gName] : array();
                $isActive = ($panelIndex === 0);
                ?>
                <div class="pl-list <?php echo $layout === 'grid' ? 'is-grid ' : ''; ?><?php echo $isActive ? 'is-active' : ''; ?>" role="list" id="<?php echo esc_attr($uid . '_panel_' . $panelIndex); ?>" aria-labelledby="<?php echo esc_attr($uid . '_tab_' . $panelIndex); ?>" data-pl-index="<?php echo (int) $panelIndex; ?>">
                    <?php if (empty($groupItems)): ?>
                        <div class="pl-empty" role="status"><?php echo esc_html__('Тут ничего нет, но мы уже работаем над добавлением контента в этот плейлист.', 'copella-topics'); ?></div>
                    <?php else: ?>
                        <?php foreach ($groupItems as $it): ?>
                        <a class="pl-item" role="listitem" href="<?php echo esc_url($it['href']); ?>" title="<?php echo esc_attr($it['title']); ?>">
                            <span class="pl-thumb" aria-hidden="true">
                                <?php if (!empty($it['thumb'])): ?>
                                    <img src="<?php echo esc_url($it['thumb']); ?>" alt="" loading="lazy"/>
                                <?php else: ?>
                                    <span class="pl-fallback"></span>
                                <?php endif; ?>
                            </span>
                            <span class="pl-item-body">
                                <span class="pl-item-title"><?php echo esc_html($it['title']); ?></span>
                                <?php if (!empty($it['tag'])): ?>
                                    <span class="pl-item-badge"<?php echo !empty($it['color']) ? ' style="background:' . esc_attr($it['color']) . ';"' : ''; ?>><?php echo esc_html($it['tag']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($it['date'])): ?>
                                    <span class="pl-item-meta"><?php echo esc_html($it['date']); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php
                $panelIndex++;
            endforeach;
            ?>
        </div>
        
        <script>
        (function(){
            var root = document.getElementById('<?php echo esc_js($uid); ?>');
            if(!root) return;
            var header = root.querySelector('.pl-header');
            var panels = root.querySelector('.pl-panels');
            if(!header || !panels) return;
            var tabs = Array.prototype.slice.call(root.querySelectorAll('.pl-tab'));
            var animating = false;
            var tabsTrack = root.querySelector('.pl-tabs');
            var tabsPrev = root.querySelector('.pl-tabsbar .pl-prev');
            var tabsNext = root.querySelector('.pl-tabsbar .pl-next');

            function activeList(){
                return panels.querySelector('.pl-list.is-active') || panels.querySelector('.pl-list');
            }

            function setPanelsHeightTo(el){
                if(!el) return;
                var height = el.scrollHeight;
                if (height > 0) {
                    panels.style.height = height + 'px';
                }
            }

            function expand(){
                if(animating) return; animating = true;
                root.classList.remove('is-collapsed');
                header.setAttribute('aria-expanded', 'true');
                var current = activeList();
                panels.style.overflow = 'hidden';
                panels.style.height = '0px';
                panels.style.opacity = '0';
                panels.style.transition = 'height 280ms ease, opacity 280ms ease';
                requestAnimationFrame(function(){
                    if (current && current.scrollHeight > 0) {
                        panels.style.height = current.scrollHeight + 'px';
                    }
                    panels.style.opacity = '1';
                });
                panels.addEventListener('transitionend', function tidy(ev){
                    if (ev.propertyName !== 'height') return;
                    panels.style.transition = '';
                    panels.style.height = '';
                    panels.style.overflow = '';
                    panels.style.opacity = '';
                    animating = false;
                    panels.removeEventListener('transitionend', tidy);
                });
            }

            function collapse(){
                if(animating) return; animating = true;
                header.setAttribute('aria-expanded', 'false');
                var current = activeList();
                panels.style.overflow = 'hidden';
                if (current && current.scrollHeight > 0) {
                    panels.style.height = current.scrollHeight + 'px';
                }
                panels.style.opacity = '1';
                panels.style.transition = 'height 280ms ease, opacity 280ms ease';
                requestAnimationFrame(function(){
                    panels.style.height = '0px';
                    panels.style.opacity = '0';
                });
                panels.addEventListener('transitionend', function tidy(ev){
                    if (ev.propertyName !== 'height') return;
                    root.classList.add('is-collapsed');
                    panels.style.transition = '';
                    panels.style.height = '0px';
                    panels.style.opacity = '0';
                    panels.style.overflow = 'hidden';
                    animating = false;
                    panels.removeEventListener('transitionend', tidy);
                });
            }

            function toggle(ev){
                var targetRoot = ev && ev.currentTarget ? ev.currentTarget.closest('.copella-playlist') : root;
                if (targetRoot !== root) return;
                if(root.classList.contains('is-collapsed')) expand(); else collapse();
            }
            header.addEventListener('click', toggle);
            header.addEventListener('keydown', function(e){ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); toggle(e); } });

            function activateTab(index){
                var next = panels.querySelector('.pl-list[data-pl-index="'+ index +'"]');
                var current = activeList();
                if(!next || next === current) return;

                tabs.forEach(function(t){
                    var isActive = (t.getAttribute('data-pl-index') === String(index));
                    t.classList.toggle('is-active', isActive);
                    t.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                panels.style.overflow = 'hidden';
                panels.style.transition = 'height 220ms ease';
                panels.style.height = current ? current.scrollHeight + 'px' : '0px';
                requestAnimationFrame(function(){
                    if (current) current.classList.remove('is-active');
                    next.classList.add('is-active');
                    setPanelsHeightTo(next);
                });
                panels.addEventListener('transitionend', function tidy(ev){
                    if (ev.propertyName !== 'height') return;
                    panels.style.transition = '';
                    panels.style.overflow = '';
                    panels.style.height = '';
                    panels.removeEventListener('transitionend', tidy);
                });
            }

            root.addEventListener('click', function(e){
                var el = e.target && e.target.closest ? e.target.closest('.pl-tab') : null;
                if(!el || !root.contains(el)) return;
                var idx = el.getAttribute('data-pl-index');
                activateTab(idx);
            });
            root.addEventListener('keydown', function(e){
                var el = e.target && e.target.closest ? e.target.closest('.pl-tab') : null;
                if(!el || !root.contains(el)) return;
                if(e.key==='Enter' || e.key===' '){ e.preventDefault(); activateTab(el.getAttribute('data-pl-index')); }
            });

            var initList = activeList();
            if (root.classList.contains('is-collapsed')) {
                header.setAttribute('aria-expanded', 'false');
                panels.style.height = '0px';
                panels.style.overflow = 'hidden';
                panels.style.opacity = '0';
            } else if (initList) {
                if (initList.scrollHeight > 0) {
                    panels.style.height = initList.scrollHeight + 'px';
                }
            }

            window.addEventListener('resize', function(){
                var curr = activeList();
                if (curr && !root.classList.contains('is-collapsed')) {
                    if (curr.scrollHeight > 0) {
                        panels.style.height = curr.scrollHeight + 'px';
                    }
                }
            });

            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function(entries){
                    entries.forEach(function(entry){
                        if (entry.isIntersecting) {
                            var curr = activeList();
                            if (curr && !root.classList.contains('is-collapsed')) {
                                setPanelsHeightTo(curr);
                            }
                        }
                    });
                }, { threshold: 0.01 });
                io.observe(root);
            }

            function getTabsScrollAmount(){ return Math.max(120, root.clientWidth/3 + 20); }
            function updateTabsButtons(){
                if(!tabsTrack || !tabsPrev || !tabsNext) return;
                var max = tabsTrack.scrollWidth - tabsTrack.clientWidth - 1;
                tabsPrev.disabled = tabsTrack.scrollLeft <= 1;
                tabsNext.disabled = tabsTrack.scrollLeft >= max;
                var tabsbar = root.querySelector('.pl-tabsbar');
                if(tabsbar){ tabsbar.classList.toggle('has-scroll', max > 0); }
            }
            function scrollTabsBy(dir){ if(tabsTrack){ tabsTrack.scrollBy({ left: dir * getTabsScrollAmount(), behavior: 'smooth' }); } }
            if(tabsPrev && tabsNext){
                tabsPrev.addEventListener('click', function(){ scrollTabsBy(-1); });
                tabsNext.addEventListener('click', function(){ scrollTabsBy(1); });
            }
            if(tabsTrack){
                tabsTrack.addEventListener('scroll', updateTabsButtons, { passive:true });
            }
            window.addEventListener('resize', updateTabsButtons);
            window.addEventListener('load', updateTabsButtons);
            updateTabsButtons();
        })();
        </script>
    </section>
    <?php
    return ob_get_clean();
}
