<?php

const CNCA_GROUP_LOOKUP = "cnca_group_lookup";
const CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH = 100;

add_action('rest_api_init', function () {
    register_rest_route('cnca/v1', '/group-lookup', [
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            $search = $request->get_param('search');

            if (!is_string($search)) {
                return new WP_Error('invalid_group_search', __('Enter a search term.', 'cnca'), ['status' => 400]);
            }

            $search = preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', sanitize_text_field($search));

            if (!is_string($search) || $search === '') {
                return new WP_Error('invalid_group_search', __('Enter a search term.', 'cnca'), ['status' => 400]);
            }

            $search_length = preg_match_all('/./us', $search);
            if ($search_length === false) {
                return new WP_Error('invalid_group_search', __('Enter a search term.', 'cnca'), ['status' => 400]);
            }

            if ($search_length > CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH) {
                return new WP_Error(
                    'group_search_too_long',
                    sprintf(
                        /* translators: %d is the maximum search length. */
                        __('Search terms must be %d characters or fewer.', 'cnca'),
                        CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH
                    ),
                    ['status' => 400]
                );
            }

            $escaped_search = str_replace(['\\', '"'], ['\\\\', '\\"'], $search);
            $formula = 'SEARCH(LOWER("' . $escaped_search . '"), LOWER({Meeting}))';

            $response = wp_remote_get(add_query_arg('filterByFormula', $formula, CNCA_AIRTABLE_BASE_URL), [
                'headers' => [
                    'Authorization' => 'Bearer ' . CNCA_AIRTABLE_API_KEY,
                    'Accept' => 'application/json',
                ],
            ]);

            if (is_wp_error($response)) {
                return new WP_Error('group_lookup_upstream_error', __('An error occurred while searching.', 'cnca'), ['status' => 502]);
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code < 200 || $response_code >= 300) {
                return new WP_Error('group_lookup_upstream_error', __('An error occurred while searching.', 'cnca'), ['status' => 502]);
            }

            $results = json_decode(wp_remote_retrieve_body($response), true);

            if (!is_array($results) || !isset($results['records']) || !is_array($results['records'])) {
                return new WP_Error('group_lookup_upstream_error', __('An error occurred while searching.', 'cnca'), ['status' => 502]);
            }

            $groups = [];
            foreach ($results['records'] as $record) {
                $fields = $record['fields'] ?? null;
                if (
                    !is_array($fields) ||
                    !isset($fields['name'], $fields['city'], $fields['region']) ||
                    !is_string($fields['name']) ||
                    !is_string($fields['city']) ||
                    !is_string($fields['region'])
                ) {
                    return new WP_Error('group_lookup_upstream_error', __('An error occurred while searching.', 'cnca'), ['status' => 502]);
                }

                $groups[] = [
                    'name' => $fields['name'],
                    'city' => $fields['city'],
                    'id' => $fields['GSO_ID'] ?? '',
                    'district' => $fields['region'] === 'Marin' ? '10' : '06',
                ];
            }

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
                'loading' => __('Searching groups...', 'cnca'),
                'no_results' => __('No groups found.', 'cnca'),
                'empty_search' => __('Enter a search term.', 'cnca'),
                /* translators: %d is the maximum search length. */
                'search_too_long' => sprintf(__('Search terms must be %d characters or fewer.', 'cnca'), CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH),
                'max_search_length' => CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH,
            ]
        ]);
    }

}, 9999);

add_shortcode(
    CNCA_GROUP_LOOKUP,
    fn() => '
    <form id="group-lookup">
        <input name="search" type="search" placeholder="' . esc_attr__('Search groups...', 'cnca') . '" maxlength="' . CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH . '" required>
        <input type="submit" value="' . esc_attr__('Search', 'cnca') . '">
    </form>
    <div id="group-lookup-results"></div>
    '
);