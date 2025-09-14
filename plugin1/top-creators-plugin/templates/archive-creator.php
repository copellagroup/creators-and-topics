<?php
/**
 * Шаблон для архива креаторов
 * Список всех креаторов в стиле Topics Plugin
 */

get_header(); ?>

<div class="copella-creators-archive">
    <header class="cp-archive-header">
        <h1 class="cp-archive-title"><?php _e('Все креаторы', 'copella-creators'); ?></h1>
        <div class="cp-archive-description">
            <?php _e('Познакомьтесь с нашими талантливыми креаторами', 'copella-creators'); ?>
        </div>
    </header>

    <div class="cp-archive-content">
        <?php if (have_posts()) : ?>
            <div class="cp-creators-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php
                    $creator_id = get_the_ID();
                    $name = get_the_title();
                    $avatar_url = get_the_post_thumbnail_url($creator_id, 'medium');
                    $excerpt = get_post_field('post_excerpt', $creator_id);
                    $specialization = (string) get_post_meta($creator_id, COPELLA_CREATOR_META_SPECIALIZATION, true);
                    $age = (int) get_post_meta($creator_id, COPELLA_CREATOR_META_AGE, true);
                    $social_networks = copella_get_creator_social_networks($creator_id);
                    ?>
                    
                    <article class="cp-creator-card">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="cp-creator-link">
                            <div class="cp-creator-thumb">
                                <?php if ($avatar_url): ?>
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" />
                                <?php else: ?>
                                    <div class="cp-creator-thumb-placeholder">
                                        <?php echo esc_html(mb_substr($name, 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="cp-creator-info">
                                <h2 class="cp-creator-name"><?php echo esc_html($name); ?></h2>
                                <?php if ($specialization): ?>
                                    <div class="cp-creator-specialization"><?php echo esc_html($specialization); ?></div>
                                <?php endif; ?>
                                <?php if ($age): ?>
                                    <div class="cp-creator-age"><?php echo esc_html($age); ?> <?php _e('лет', 'copella-creators'); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($excerpt)): ?>
                                    <div class="cp-creator-excerpt"><?php echo esc_html($excerpt); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($social_networks)): ?>
                                    <div class="cp-creator-social">
                                        <?php foreach (array_slice($social_networks, 0, 3) as $social): ?>
                                            <span class="cp-creator-social-icon"><?php echo esc_html($social['icon']); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($social_networks) > 3): ?>
                                            <span class="cp-creator-social-more">+<?php echo count($social_networks) - 3; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Пагинация -->
            <div class="cp-archive-pagination">
                <?php
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => __('← Предыдущая', 'copella-creators'),
                    'next_text' => __('Следующая →', 'copella-creators'),
                ));
                ?>
            </div>
        <?php else : ?>
            <div class="cp-archive-empty">
                <h2><?php _e('Креаторы не найдены', 'copella-creators'); ?></h2>
                <p><?php _e('Пока нет созданных креаторов.', 'copella-creators'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Стили для архива креаторов в стиле Topics Plugin */
.copella-creators-archive {
    color: #fff;
    font-family: 'Gilroy-SemiBold', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    background: #171717;
    min-height: 100vh;
}

/* Заголовок архива */
.copella-creators-archive .cp-archive-header {
    text-align: center;
    margin-bottom: 50px;
}

.copella-creators-archive .cp-archive-title {
    margin: 0 0 16px 0;
    font-size: 48px;
    font-weight: 700;
    font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.copella-creators-archive .cp-archive-description {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

/* Сетка креаторов */
.copella-creators-archive .cp-creators-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

/* Карточка креатора */
.copella-creators-archive .cp-creator-card {
    background: rgba(255, 255, 255, 0.06);
    border-radius: 20px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.copella-creators-archive .cp-creator-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

.copella-creators-archive .cp-creator-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

/* Миниатюра креатора */
.copella-creators-archive .cp-creator-thumb {
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
    position: relative;
}

.copella-creators-archive .cp-creator-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.copella-creators-archive .cp-creator-card:hover .cp-creator-thumb img {
    transform: scale(1.05);
}

.copella-creators-archive .cp-creator-thumb-placeholder {
    width: 100%;
    height: 100%;
    background: #FF653A;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    color: #fff;
}

/* Информация о креаторе */
.copella-creators-archive .cp-creator-info {
    padding: 24px;
}

.copella-creators-archive .cp-creator-name {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 700;
    font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
    color: #fff;
    line-height: 1.2;
}

.copella-creators-archive .cp-creator-specialization {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 8px;
    font-weight: 600;
}

.copella-creators-archive .cp-creator-age {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 12px;
    font-weight: 500;
}

.copella-creators-archive .cp-creator-excerpt {
    font-size: 14px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Социальные сети */
.copella-creators-archive .cp-creator-social {
    display: flex;
    gap: 8px;
    align-items: center;
}

.copella-creators-archive .cp-creator-social-icon {
    font-size: 16px;
    opacity: 0.8;
}

.copella-creators-archive .cp-creator-social-more {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

/* Пагинация */
.copella-creators-archive .cp-archive-pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.copella-creators-archive .cp-archive-pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 16px;
    margin: 0 4px;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.copella-creators-archive .cp-archive-pagination .page-numbers:hover,
.copella-creators-archive .cp-archive-pagination .page-numbers.current {
    background: #FF653A;
    color: #fff;
}

/* Пустое состояние */
.copella-creators-archive .cp-archive-empty {
    text-align: center;
    padding: 60px 20px;
}

.copella-creators-archive .cp-archive-empty h2 {
    margin: 0 0 16px 0;
    font-size: 32px;
    font-weight: 700;
    color: #fff;
}

.copella-creators-archive .cp-archive-empty p {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .copella-creators-archive {
        padding: 30px 16px;
    }

    .copella-creators-archive .cp-archive-title {
        font-size: 36px;
    }

    .copella-creators-archive .cp-archive-description {
        font-size: 16px;
    }

    .copella-creators-archive .cp-creators-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
}

@media (max-width: 768px) {
    .copella-creators-archive {
        padding: 20px 12px;
    }

    .copella-creators-archive .cp-archive-title {
        font-size: 28px;
    }

    .copella-creators-archive .cp-archive-description {
        font-size: 14px;
    }

    .copella-creators-archive .cp-creators-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .copella-creators-archive .cp-creator-info {
        padding: 20px;
    }

    .copella-creators-archive .cp-creator-name {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .copella-creators-archive .cp-archive-title {
        font-size: 24px;
    }

    .copella-creators-archive .cp-creator-info {
        padding: 16px;
    }

    .copella-creators-archive .cp-creator-name {
        font-size: 16px;
    }

    .copella-creators-archive .cp-creator-excerpt {
        font-size: 13px;
    }
}
</style>

<?php get_footer(); ?>