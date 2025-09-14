<?php
/**
 * Template for displaying creator archive pages
 */

get_header(); ?>

<div class="container">
    <div class="content">
        <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
        
        <div class="creators-grid">
            <?php while (have_posts()) : the_post(); ?>
                <div class="creator-card">
                    <a href="<?php the_permalink(); ?>" class="creator-link">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="creator-thumb">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="creator-info">
                            <h2 class="creator-name"><?php the_title(); ?></h2>
                            <?php if (has_excerpt()): ?>
                                <div class="creator-excerpt"><?php the_excerpt(); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php
        // Pagination
        the_posts_pagination(array(
            'prev_text' => __('Предыдущая', 'copella-creators'),
            'next_text' => __('Следующая', 'copella-creators'),
        ));
        ?>
    </div>
</div>

<style>
.creators-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin: 30px 0;
}

.creator-card {
    background: #171717;
    border-radius: 20px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.creator-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.creator-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

.creator-thumb {
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.creator-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.creator-card:hover .creator-thumb img {
    transform: scale(1.05);
}

.creator-info {
    padding: 20px;
    color: #fff;
}

.creator-name {
    margin: 0 0 10px 0;
    font-size: 20px;
    font-weight: 700;
    font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
}

.creator-excerpt {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.5;
}

.page-title {
    font-size: 32px;
    font-weight: 800;
    font-family: 'Gilroy-Bold', 'Gilroy-SemiBold', system-ui, sans-serif;
    color: #fff;
    margin: 20px 0;
    text-align: center;
}

@media (max-width: 768px) {
    .creators-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .creator-info {
        padding: 15px;
    }
    
    .creator-name {
        font-size: 18px;
    }
    
    .page-title {
        font-size: 24px;
    }
}
</style>

<?php get_footer(); ?>