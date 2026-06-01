<?php

const CNCA_GROUP_LOOKUP = "cnca_group_lookup";

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


add_action('wp_enqueue_scripts', function () {
    global $post;

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

}, 9999);

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