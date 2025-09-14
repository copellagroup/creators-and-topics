<?php

if (!defined('ABSPATH')) {
    exit;
}

function copella_creators_register_cpt(): void
{
    $labels = array(
        'name'               => __('Креаторы', 'copella-creators'),
        'singular_name'      => __('Креатор', 'copella-creators'),
        'menu_name'          => __('Креаторы', 'copella-creators'),
        'name_admin_bar'     => __('Креатор', 'copella-creators'),
        'add_new'            => __('Добавить креатора', 'copella-creators'),
        'add_new_item'       => __('Добавить нового креатора', 'copella-creators'),
        'new_item'           => __('Новый креатор', 'copella-creators'),
        'edit_item'          => __('Редактировать креатора', 'copella-creators'),
        'view_item'          => __('Просмотреть креатора', 'copella-creators'),
        'all_items'          => __('Все креаторы', 'copella-creators'),
        'search_items'       => __('Искать креаторов', 'copella-creators'),
        'parent_item_colon'  => __('Родительские креаторы:', 'copella-creators'),
        'not_found'          => __('Креаторов не найдено.', 'copella-creators'),
        'not_found_in_trash' => __('В корзине креаторов не найдено.', 'copella-creators'),
    );

    // Support for title, thumbnail, and editor (Gutenberg)
    $supports = array('title', 'thumbnail', 'editor', 'excerpt');

    register_post_type('creator', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'creators'),
        'menu_icon' => 'dashicons-groups',
        'supports' => $supports,
        'show_in_rest' => true,
    ));
}

add_action('init', 'copella_creators_register_cpt');