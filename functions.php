<?php

const CNCA_VERSION = '1.0.7';

add_theme_support('appearance-tools');
add_theme_support('responsive-embeds');
add_theme_support('editor-color-palette', [
    [
        'name' => 'very light gray',
        'slug' => 'very-light-gray',
        'color' => '#eee',
    ],
]);

add_action('after_setup_theme', function () {
    load_theme_textdomain('cnca', get_template_directory() . '/languages');
    register_nav_menus([
        'primary' => 'Primary Menu'
    ]);
});

add_action('enqueue_block_editor_assets', function () {
    add_theme_support('editor-styles');
    add_editor_style();
});

add_action('widgets_init', function () {
    foreach (['cnca-sidebar' => 'Sidebar', 'cnca-footer' => 'Footer'] as $id => $name) {
        register_sidebar([
            'name' => $name,
            'id' => $id,
            'before_widget' => '<div>',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
        ]);
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('cnca', get_template_directory_uri() . '/style.css', [], CNCA_VERSION);
    wp_enqueue_script('cnca', get_template_directory_uri() . '/script.js', [], CNCA_VERSION, true);
});

add_action('wp_head', function () {
    echo '<link rel="icon" href="' . get_template_directory_uri() . '/logo.png" type="image/png">';
});
