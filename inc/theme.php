<?php

add_action('after_setup_theme', function () {
    add_theme_support('appearance-tools');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-color-palette', [
        [
            'name' => 'very light gray',
            'slug' => 'very-light-gray',
            'color' => '#eee',
        ],
    ]);
    add_theme_support('editor-styles');
    add_editor_style();

    load_theme_textdomain('cnca', get_template_directory() . '/languages');
    register_nav_menus([
        'primary' => 'Primary Menu'
    ]);
});

add_action('init', function () {
    // remove unnecessary scripts from the frontend
    if (!is_admin()) {
        wp_deregister_script('jquery-ui-core');
        wp_deregister_script('jquery-migrate');
    }
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
    wp_enqueue_script('cnca', get_template_directory_uri() . '/js/sidebar.js', [], CNCA_VERSION, true);

    // remove global styles and scripts from the frontend to improve performance
    if (is_admin()) {
        return;
    }

    $styles = [
        'givewp-campaign-blocks-fonts',
        'give-styles',
        'givewp-design-system-foundation',
        'give_ffm_frontend_styles',
        'give_ffm_datepicker_styles',
        'give-square-frontend',
        'simcal-qtip',
        'simcal-default-calendar-grid',
    ];
    foreach ($styles as $style) {
        wp_dequeue_style($style);
    }
    $scripts = [
        'give-square-frontend',
        'give-square-payment-form',
        'give',
        'givewp-entities-public',
        'plupload-handlers',
        'simcal-default-calendar',
        'simcal-qtip',
        'simplecalendar-imagesloaded',
    ];
    foreach ($scripts as $script) {
        wp_dequeue_script($script);
    }
}, 9999);

// remove extra wordpress stuff to reduce page size
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

// custom favicon and meta description
add_action('wp_head', function () {
    echo '
    <link rel="icon" href="' . get_template_directory_uri() . '/logo.webp" type="image/webp" />
    <link rel="shortcut icon" href="' . get_template_directory_uri() . '/logo.webp" type="image/webp" />
    <meta name="description" content="' . esc_attr__('Serving Alcoholics Anonymous through the General Service structure. Supporting GSRs, DCMs, and Area trusted servants in carrying the message.', 'cnca') . '">
    ';
});

// remove the default site icon so we can use our own
add_filter('get_site_icon_url', '__return_false');

