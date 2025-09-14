<?php
/**
 * Template for displaying single creator pages
 */

get_header(); ?>

<div class="container">
    <div class="content">
        <?php while (have_posts()) : the_post(); ?>
            <?php echo do_shortcode('[creator_page creator_id="' . get_the_ID() . '"]'); ?>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>