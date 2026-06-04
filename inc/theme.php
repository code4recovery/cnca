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

add_action(
    'wp_default_scripts',
    function ($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, ['jquery-migrate']);
            }
        }
    }
);

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

// add language switcher to the primary menu with globe icon
add_filter(
    'wp_nav_menu_items',
    function ($items, $args) {
        if ($args->theme_location === 'primary' && function_exists('pll_the_languages')) {
            $translations = pll_the_languages(['raw' => 1, 'hide_current' => 1]);
            foreach ($translations as $translation) {
                $items .= '<li class="lang-item">
                    <a href="' . esc_url($translation['url']) . '">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>' . esc_html($translation['name']) .
                    '</a></li>';
            }
        }
        return $items;
    },
    10,
    2
);

