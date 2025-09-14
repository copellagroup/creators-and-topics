<?php

if (!defined('ABSPATH')) {
    exit;
}

function copella_topics_register_cpt(): void
{
    $labels = array(
        'name'               => __('Темы', 'copella-topics'),
        'singular_name'      => __('Тема', 'copella-topics'),
        'menu_name'          => __('Темы', 'copella-topics'),
        'name_admin_bar'     => __('Тема', 'copella-topics'),
        'add_new'            => __('Добавить тему', 'copella-topics'),
        'add_new_item'       => __('Добавить новую тему', 'copella-topics'),
        'new_item'           => __('Новая тема', 'copella-topics'),
        'edit_item'          => __('Редактировать тему', 'copella-topics'),
        'view_item'          => __('Просмотреть тему', 'copella-topics'),
        'all_items'          => __('Все темы', 'copella-topics'),
        'search_items'       => __('Искать темы', 'copella-topics'),
        'parent_item_colon'  => __('Родительские темы:', 'copella-topics'),
        'not_found'          => __('Тем не найдено.', 'copella-topics'),
        'not_found_in_trash' => __('В корзине тем не найдено.', 'copella-topics'),
    );

    // Support for title, thumbnail, and editor (Gutenberg)
    $supports = array('title', 'thumbnail', 'editor');

    register_post_type('topic', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'topics'),
        'menu_icon' => 'dashicons-playlist-audio',
        'supports' => $supports,
        'show_in_rest' => true,
    ));

    $pl_labels = array(
        'name'               => __('Плейлисты', 'copella-topics'),
        'singular_name'      => __('Плейлист', 'copella-topics'),
        'menu_name'          => __('Плейлисты', 'copella-topics'),
        'add_new'            => __('Добавить плейлист', 'copella-topics'),
        'add_new_item'       => __('Добавить новый плейлист', 'copella-topics'),
        'new_item'           => __('Новый плейлист', 'copella-topics'),
        'edit_item'          => __('Редактировать плейлист', 'copella-topics'),
        'view_item'          => __('Просмотреть плейлист', 'copella-topics'),
        'all_items'          => __('Все плейлисты', 'copella-topics'),
        'search_items'       => __('Искать плейлисты', 'copella-topics'),
        'not_found'          => __('Плейлистов не найдено.', 'copella-topics'),
    );
    register_post_type('topic_playlist', array(
        'labels' => $pl_labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-list-view',
        'supports' => array('title', 'thumbnail'),
        'show_in_rest' => true,
    ));

    // Authors CPT
    $au_labels = array(
        'name'               => __('Авторы', 'copella-topics'),
        'singular_name'      => __('Автор', 'copella-topics'),
        'menu_name'          => __('Авторы', 'copella-topics'),
        'add_new'            => __('Добавить автора', 'copella-topics'),
        'add_new_item'       => __('Добавить нового автора', 'copella-topics'),
        'new_item'           => __('Новый автор', 'copella-topics'),
        'edit_item'          => __('Редактировать автора', 'copella-topics'),
        'view_item'          => __('Просмотреть автора', 'copella-topics'),
        'all_items'          => __('Все авторы', 'copella-topics'),
        'search_items'       => __('Искать авторов', 'copella-topics'),
        'not_found'          => __('Авторов не найдено.', 'copella-topics'),
    );
    register_post_type('topic_author', array(
        'labels' => $au_labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-groups',
        // No editor; we allow excerpt for short bio and thumbnail for avatar
        'supports' => array('title', 'thumbnail', 'excerpt'),
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'authors'),
    ));

    // Register author_category taxonomy for grouping authors
    $cat_labels = array(
        'name'              => __('Категории авторов', 'copella-topics'),
        'singular_name'     => __('Категория автора', 'copella-topics'),
        'menu_name'         => __('Категории авторов', 'copella-topics'),
        'search_items'      => __('Поиск категорий', 'copella-topics'),
        'all_items'         => __('Все категории', 'copella-topics'),
        'parent_item'       => __('Родительская категория', 'copella-topics'),
        'parent_item_colon' => __('Родительская категория:', 'copella-topics'),
        'edit_item'         => __('Редактировать категорию', 'copella-topics'),
        'update_item'       => __('Обновить категорию', 'copella-topics'),
        'add_new_item'      => __('Добавить новую категорию', 'copella-topics'),
        'new_item_name'     => __('Название новой категории', 'copella-topics'),
    );
    
    register_taxonomy('author_category', 'topic_author', array(
        'hierarchical'      => true,
        'labels'            => $cat_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'show_in_rest'      => true,
        'rewrite'           => array('slug' => 'author-category'),
    ));

    // Removed legacy author_category taxonomy registration

    // Creators CPT
    $creator_labels = array(
        'name'               => __('Креаторы', 'copella-topics'),
        'singular_name'      => __('Креатор', 'copella-topics'),
        'menu_name'          => __('Креаторы', 'copella-topics'),
        'name_admin_bar'     => __('Креатор', 'copella-topics'),
        'add_new'            => __('Добавить креатора', 'copella-topics'),
        'add_new_item'       => __('Добавить нового креатора', 'copella-topics'),
        'new_item'           => __('Новый креатор', 'copella-topics'),
        'edit_item'          => __('Редактировать креатора', 'copella-topics'),
        'view_item'          => __('Просмотреть креатора', 'copella-topics'),
        'all_items'          => __('Все креаторы', 'copella-topics'),
        'search_items'       => __('Искать креаторов', 'copella-topics'),
        'parent_item_colon'  => __('Родительские креаторы:', 'copella-topics'),
        'not_found'          => __('Креаторов не найдено.', 'copella-topics'),
        'not_found_in_trash' => __('В корзине креаторов не найдено.', 'copella-topics'),
    );

    register_post_type('creator', array(
        'labels' => $creator_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'creators'),
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'thumbnail', 'editor', 'excerpt'),
        'show_in_rest' => true,
        'show_in_menu' => true,
        'menu_position' => 26, // После авторов
        'capability_type' => 'post',
        'hierarchical' => false,
        'publicly_queryable' => true,
        'query_var' => true,
        'can_export' => true,
    ));

    // Creator categories taxonomy
    $creator_cat_labels = array(
        'name'              => __('Категории креаторов', 'copella-topics'),
        'singular_name'     => __('Категория креатора', 'copella-topics'),
        'menu_name'         => __('Категории креаторов', 'copella-topics'),
        'search_items'      => __('Поиск категорий', 'copella-topics'),
        'all_items'         => __('Все категории', 'copella-topics'),
        'parent_item'       => __('Родительская категория', 'copella-topics'),
        'parent_item_colon' => __('Родительская категория:', 'copella-topics'),
        'edit_item'         => __('Редактировать категорию', 'copella-topics'),
        'update_item'       => __('Обновить категорию', 'copella-topics'),
        'add_new_item'      => __('Добавить новую категорию', 'copella-topics'),
        'new_item_name'     => __('Название новой категории', 'copella-topics'),
    );
    
    register_taxonomy('creator_category', 'creator', array(
        'hierarchical'      => true,
        'labels'            => $creator_cat_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'show_in_rest'      => true,
        'rewrite'           => array('slug' => 'creator-category'),
    ));
}

add_action('init', 'copella_topics_register_cpt');

