<?php

const CNCA_VERSION = '1.0.8';
const CNCA_GROUP_LOOKUP = "cnca_group_lookup";

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
        wp_deregister_script('jquery');
        wp_deregister_script('jquery-ui-core');
        wp_register_script('jquery', false);
    }
});

add_action('rest_api_init', function () {
    register_rest_route('cnca/v1', '/group-lookup', [
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            $search = $request->get_param('search');

            $response = wp_remote_get(CNCA_AIRTABLE_BASE_URL . "&filterByFormula=SEARCH(LOWER('{$search}'), LOWER({Meeting}))", [
                'headers' => [
                    'Authorization' => 'Bearer ' . CNCA_AIRTABLE_API_KEY,
                    'Accept' => 'application/json',
                ],
            ]);

            $results = json_decode($response['body']) ?? [];

            $groups = array_map(
                fn($record) => [

                    'name' => $record->fields->name,
                    'city' => $record->fields->city,
                    'id' => $record->fields->GSO_ID ?? '',
                    'district' => $record->fields->region === 'Marin' ? '10' : '06',
                ],
                $results->records ?? []
            );

            return rest_ensure_response($groups);
        },
        'permission_callback' => fn(WP_REST_Request $request) =>
            wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')
        ,
    ]);
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
    global $post;

    wp_enqueue_style('cnca', get_template_directory_uri() . '/style.css', [], CNCA_VERSION);
    wp_enqueue_script('cnca', get_template_directory_uri() . '/js/sidebar.js', [], CNCA_VERSION, true);

    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, CNCA_GROUP_LOOKUP)) {
        wp_enqueue_script(CNCA_GROUP_LOOKUP, get_template_directory_uri() . '/js/group-lookup.js', [], CNCA_VERSION, true);
        wp_localize_script(CNCA_GROUP_LOOKUP, 'cnca', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'group_lookup' => [
                'district' => __('District', 'cnca'),
                'error' => __('An error occurred while searching.', 'cnca'),
                'id' => __('Group ID', 'cnca'),
                'location' => __('Location', 'cnca'),
                'no_results' => __('No groups found.', 'cnca'),
            ]
        ]);
    }

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

remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

add_action('wp_head', function () {
    echo '
    <link rel="icon" href="' . get_template_directory_uri() . '/logo.webp" type="image/webp" />
    <link rel="shortcut icon" href="' . get_template_directory_uri() . '/logo.webp" type="image/webp" />
    ';
});

add_filter('get_site_icon_url', '__return_false');

add_shortcode(
    CNCA_GROUP_LOOKUP,
    fn() => '
    <form id="group-lookup">
        <input name="search" type="search" placeholder="' . esc_attr__('Search groups...', 'cnca') . '" required>
        <input type="submit" value="' . esc_attr__('Search', 'cnca') . '">
    </form>
    <div id="group-lookup-results"></div>
    '
);