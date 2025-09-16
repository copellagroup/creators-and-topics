<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue admin assets for playlist editor
 */
function copella_playlist_admin_enqueue(string $hook): void
{
    // Load only on post editor screens
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || ($screen->post_type !== 'topic_playlist' && $screen->post_type !== 'topic_author')) {
        return;
    }

    $js_path = COPELLA_TOPICS_PLUGIN_DIR . 'assets/js/playlist-admin.js';
    wp_enqueue_script(
        'copella-playlist-admin',
        COPELLA_TOPICS_PLUGIN_URL . 'assets/js/playlist-admin.js',
        array('jquery'),
        file_exists($js_path) ? (string) filemtime($js_path) : '0.1.0',
        true
    );

    wp_localize_script('copella-playlist-admin', 'CopellaPlAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('copella_pl_search'),
        'i18n'    => array(
            'searchPlaceholder' => __('Поиск записей…', 'copella-topics'),
            'searchButton'      => __('Найти', 'copella-topics'),
            'addSelected'       => __('Добавить выбранные', 'copella-topics'),
            'selectAll'         => __('Выбрать все', 'copella-topics'),
            'nothingFound'      => __('Ничего не найдено', 'copella-topics'),
        ),
    ));
}
add_action('admin_enqueue_scripts', 'copella_playlist_admin_enqueue');

/**
 * AJAX: search posts to add into playlist
 */
function copella_playlist_ajax_search(): void
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'forbidden'), 403);
    }
    check_ajax_referer('copella_pl_search', 'nonce');

    $s = isset($_REQUEST['s']) ? sanitize_text_field((string) $_REQUEST['s']) : '';
    $pt = isset($_REQUEST['post_type']) ? sanitize_text_field((string) $_REQUEST['post_type']) : 'post';
    $tax = isset($_REQUEST['tax']) ? sanitize_text_field((string) $_REQUEST['tax']) : '';
    $terms = isset($_REQUEST['terms']) ? sanitize_text_field((string) $_REQUEST['terms']) : '';
    $limit = isset($_REQUEST['limit']) ? max(1, min(100, (int) $_REQUEST['limit'])) : 30;

    $postTypes = array_filter(array_map('trim', explode(',', $pt ?: 'post')));
    if (empty($postTypes)) { $postTypes = array('post'); }

    $args = array(
        'post_type'      => count($postTypes) === 1 ? $postTypes[0] : $postTypes,
        'post_status'    => 'publish',
        's'              => $s,
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'suppress_filters' => false,
    );

    $termSlugs = array_filter(array_map('trim', explode(',', $terms)));
    if ($tax !== '' && !empty($termSlugs)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => $tax,
                'field'    => 'slug',
                'terms'    => $termSlugs,
                'operator' => 'IN',
            )
        );
    }

    $q = new WP_Query($args);
    $items = array();
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $items[] = array(
                'id'    => $pid,
                'title' => get_the_title($pid),
                'link'  => get_permalink($pid),
                'thumb' => get_the_post_thumbnail_url($pid, 'thumbnail'),
                'date'  => get_the_date('', $pid),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array('items' => $items));
}
add_action('wp_ajax_copella_playlist_search', 'copella_playlist_ajax_search');

// Button on Projects (Authors) list to create Author Page (special page with shortcode)
add_action('restrict_manage_posts', function(string $post_type): void{
    if ($post_type !== 'topic_author') return;
    $url = admin_url('edit.php?post_type=topic_author&page=copella-author-create-page');
    echo '<a href="' . esc_url($url) . '" class="page-title-action">' . esc_html__('Создать страницу автора', 'copella-topics') . '</a>';
});

add_action('admin_menu', function(): void{
    add_submenu_page(
        'edit.php?post_type=topic_author',
        __('Создать страницу автора', 'copella-topics'),
        __('Создать страницу автора', 'copella-topics'),
        'edit_posts',
        'copella-author-create-page',
        function(): void {
            if (!current_user_can('edit_posts')) { wp_die(__('Недостаточно прав', 'copella-topics')); }
            $created = false; $page_id = 0;
            if (isset($_POST['cp_author_page_nonce']) && wp_verify_nonce((string)$_POST['cp_author_page_nonce'], 'cp_author_page')) {
                $aid = isset($_POST['author_id']) ? absint((string)$_POST['author_id']) : 0;
                if ($aid > 0) {
                    $title = get_the_title($aid);
                    $shortcode = '[author_page id="' . $aid . '"]';
                    $page_id = wp_insert_post([
                        'post_title' => $title,
                        'post_type' => 'page',
                        'post_status' => 'publish',
                        'post_content' => "<!-- wp:shortcode -->\n$shortcode\n<!-- /wp:shortcode -->",
                    ]);
                    if ($page_id && !is_wp_error($page_id)) { 
                        update_post_meta($aid, COPELLA_AUTHOR_META_PAGE_ID, (int)$page_id);
                        $created = true; 
                    }
                }
            }
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('Создать страницу автора', 'copella-topics'); ?></h1>
                <?php if ($created): ?>
                    <div class="updated"><p><?php echo sprintf(__('Страница создана: %s', 'copella-topics'), '<a href="' . esc_url(get_edit_post_link($page_id)) . '">' . esc_html(get_the_title($page_id)) . '</a>'); ?></p></div>
                <?php endif; ?>
                <form method="post">
                    <?php wp_nonce_field('cp_author_page', 'cp_author_page_nonce'); ?>
                    <p>
                        <label for="cp_author_select"><strong><?php _e('Выберите автора', 'copella-topics'); ?></strong></label><br/>
                        <select id="cp_author_select" name="author_id" style="min-width:260px">
                            <?php
                            $authors = get_posts(['post_type'=>'topic_author','post_status'=>'any','posts_per_page'=>200,'orderby'=>'title','order'=>'ASC']);
                            foreach ($authors as $a) {
                                echo '<option value="' . (int)$a->ID . '">' . esc_html($a->post_title) . ' (#' . (int)$a->ID . ')</option>';
                            }
                            ?>
                        </select>
                    </p>
                    <p><button type="submit" class="button button-primary"><?php _e('Создать страницу', 'copella-topics'); ?></button></p>
                </form>
            </div>
            <?php
        }
    );
});

// AJAX: cpplayer reports live status: on/off for project (topic_author)
function copella_topics_report_live_ajax(): void
{
    $aid = isset($_POST['author_id']) ? absint((string) $_POST['author_id']) : 0;
    $status = isset($_POST['status']) ? strtolower(sanitize_text_field((string) $_POST['status'])) : '';
    if ($aid <= 0 || ($status !== 'on' && $status !== 'off')) {
        wp_send_json_error(['message' => 'bad_args'], 400);
    }
    set_transient('copella_topics_live_' . $aid, $status === 'on' ? 1 : 0, 120);
    wp_send_json_success(['ok' => 1]);
}
add_action('wp_ajax_nopriv_copella_topics_report_live', 'copella_topics_report_live_ajax');
add_action('wp_ajax_copella_topics_report_live', 'copella_topics_report_live_ajax');

// Register Help page under Playlists
add_action('admin_menu', function(): void{
    add_submenu_page(
        'edit.php?post_type=topic_playlist',
        __('Copella Topics — Справка', 'copella-topics'),
        __('Справка', 'copella-topics'),
        'edit_posts',
        'copella-topics-help',
        function(): void {
            ?>
            <div class="wrap copella-topics-help">
                <h1>Copella Topics — Справка</h1>
                <style>
                    .copella-topics-help code{background:#f6f7f7;padding:2px 6px;border-radius:4px}
                    .copella-topics-help pre{background:#f6f7f7;padding:10px 12px;border-radius:6px;overflow:auto}
                    .copella-topics-help h2{margin-top:28px}
                    .copella-topics-help ul{list-style:disc;padding-left:20px}
                </style>

                <h2>Шорткод горячих тем</h2>
                <p><code>[hot_topics]</code></p>
                <p>Параметры:</p>
                <ul>
                    <li><strong>title</strong> — заголовок блока.</li>
                    <li><strong>posts_per_page</strong> — сколько тем выводить.</li>
                    <li><strong>aspect_ratio</strong> — аспект карточки, например <code>1/1</code>, <code>4/3</code>.</li>
                    <li><strong>card_radius</strong>, <strong>gap</strong>, <strong>container_radius</strong>, <strong>container_bg</strong>, <strong>card_placeholder</strong>.</li>
                    <li><strong>stagger</strong> (true/false), <strong>stagger_offset</strong>.</li>
                </ul>
                <pre>[hot_topics title="Горячие темы" posts_per_page="10" aspect_ratio="1/1" gap="40"]</pre>

                <h2>Шорткод плейлиста</h2>
                <p><code>[topic_playlist]</code></p>
                <p>Параметры (ручной режим):</p>
                <ul>
                    <li><strong>playlist_id</strong> — ID плейлиста (обязателен вне записи плейлиста).</li>
                    <li><strong>title</strong> — заголовок (если пусто, берётся заголовок записи плейлиста).</li>
                    <li><strong>layout</strong> — <code>list</code> или <code>grid</code>.</li>
                    <li><strong>grid_min</strong> — минимальная ширина колонки в grid (по умолчанию 220).</li>
                    <li><strong>aspect_ratio</strong>, <strong>card_radius</strong>, <strong>container_radius</strong>, <strong>container_bg</strong>.</li>
                    <li><strong>cover</strong> (true/false) — показать обложку плейлиста, <strong>cover_align</strong> (<code>left</code>/<code>right</code>), <strong>cover_height</strong> (px), <strong>cover_radius</strong> (px).</li>
                    <li><strong>collapsed</strong> (true/false) — стартовое состояние (по умолчанию свернут). Внутри блока авторов плейлисты отображаются <em>раскрытыми</em> и <em>без обложки</em>.</li>
                </ul>
                <pre>[topic_playlist playlist_id="123" layout="grid" grid_min="280" aspect_ratio="1/1"]</pre>
                <pre>[topic_playlist playlist_id="123" cover="true" cover_align="right" cover_height="220" collapsed="false"]</pre>

                <h2>Как заполнять плейлист</h2>
                <ul>
                    <li>Каждая строка — <strong>ID</strong> записи WordPress или <strong>ссылка</strong>.</li>
                    <li>Строка, начинающаяся с <code>#</code>, создаёт <strong>заголовок группы</strong> (вкладку).</li>
                    <li>Вверху метабокса есть инструмент «Поиск и добавление»: найдите записи, отметьте галочки и нажмите «Добавить выбранные» — ID добавятся в список автоматически.</li>
                    <li>Если групп больше одной, плейлист автоматически покажет <strong>вкладки категорий</strong> с горизонтальной прокруткой.</li>
                </ul>

                <h2>CSS-утилиты для раскладки</h2>
                <ul>
                    <li><code>cp-grid-2</code> — строгая сетка из двух колонок (рекомендуется для двух плейлистов рядом). На ширине &lt; 1280px превращается в одну колонку.</li>
                    <li><code>cp-masonry</code> — «колонки» (мейсонри). Может оставлять правую пустой при разной высоте блоков.</li>
                </ul>
                <p>Использование: добавьте класс к обёртке (например, блоку «Группа»), которая содержит два шорткода.</p>
                <pre>&lt;div class="cp-grid-2"&gt;
  [topic_playlist playlist_id="6150"]
  [topic_playlist playlist_id="6136"]
&lt;/div&gt;</pre>

                <h2>Подсказки</h2>
                <ul>
                    <li>Для группирования внутри плейлиста используйте строки с <code># Заголовок</code>.</li>
                    <li>Если сетка «grid» ведёт себя странно, убедитесь, что нет конфликтующих классов от темы/блоков.</li>
                </ul>

                <h2>Шорткод авторов (список)</h2>
                <p><code>[playlist_authors]</code></p>
                <p>Параметры:</p>
                <ul>
                    <li><strong>title</strong> — заголовок блока.</li>
                    <li><strong>posts_per_page</strong> — сколько авторов выводить.</li>
                    <li><strong>columns</strong> — кол-во колонок в сетке (1–4).</li>
                    <li><strong>gap</strong> — отступ между карточками в сетке (px).</li>
                    <li><strong>cover_ratio</strong> — соотношение сторон обложки автора (по умолчанию <code>16/9</code>).</li>
                </ul>
                <p>Пример:</p>
                <pre>[playlist_authors]</pre>
                <p>Авторы отображаются в сетке. Кнопка со стрелкой на карточке сворачивает/разворачивает описание и вложенные плейлисты.</p>

                <h2>Шорткод одного автора</h2>
                <p><code>[playlist_author]</code></p>
                <p>Параметры:</p>
                <ul>
                    <li><strong>author_id</strong> — ID автора (обязателен вне записи автора).</li>
                    <li><strong>cover_ratio</strong> — соотношение сторон обложки (по умолчанию <code>16/9</code>).</li>
                </ul>
                <pre>[playlist_author author_id="42" cover_ratio="16/9"]</pre>
                <p>Внутри блока автора назначенные плейлисты рендерятся <em>без обложки</em> и сразу <em>раскрытыми</em>.</p>

                <h2>Расширенный поиск</h2>
                <p><code>[tp_search]</code> — красивая индексная страница результатов с релевантностью, подсветкой совпадений и пагинацией.</p>
                <p>Параметры:</p>
                <ul>
                    <li><strong>post_types</strong> — список типов: <code>post,topic,page,topic_playlist</code>.</li>
                    <li><strong>per_page</strong> — результатов на странице (по умолчанию 12, максимум 40).</li>
                    <li><strong>sort</strong> — <code>relevance</code> или <code>date</code>.</li>
                    <li><strong>placeholder</strong> — плейсхолдер строки поиска.</li>
                </ul>
                <p>Пример вставки: <code>[tp_search post_types="post,topic,page" per_page="12" sort="relevance"]</code></p>
                <p><em>Алгоритм</em>: токенизация RU/EN, простое «стеммирование», фильтрация стоп-слов, оценка совпадений по заголовку/описанию/контенту/таксономиям, бонус за точное вхождение фразы, бонус «свежести» по дате. Результаты ранжируются по нормализованному скору.</p>

                <h2>Категории авторов</h2>
                <p>Для группировки авторов в режиме вкладок используется таксономия <strong>Категории авторов</strong> (<code>author_category</code>). Её можно редактировать в админке на экране «Авторы».</p>
                <ul>
                    <li>Назначьте авторам нужные категории.</li>
                    <li>Используйте <code>categories</code> в шорткоде, чтобы ограничить набор вкладок по слагам.</li>
                </ul>
            </div>
            <?php
        }
    );
});

// Add visible button on Authors list screen
// moved to author-page-admin.php

// Quick action: Create Author Page (button on authors list)
// moved to author-page-admin.php

