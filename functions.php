<?php

add_theme_support('appearance-tools');
add_theme_support('responsive-embeds');

add_theme_support('editor-color-palette', [
    [
        'name' => 'very light gray',
        'slug' => 'very-light-gray',
        'color' => '#eee',
    ],
]);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('cnca', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('cnca-header', get_template_directory_uri() . '/css/header.css');
    wp_enqueue_style('cnca-content', get_template_directory_uri() . '/css/content.css');
});

add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary' => 'Primary Menu'
    ]);
});

add_action('widgets_init', function () {
    register_sidebar([
        'name' => 'Sidebar',
        'id' => 'cnca-sidebar',
        'before_widget' => '<div class="cnca-widget">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="cnca-widget-title">',
        'after_title' => '</h2>',
    ]);
});
