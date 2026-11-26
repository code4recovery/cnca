<?php

const CNCA_GROUP_LOOKUP = "cnca_group_lookup";
const CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH = 100;
const CNCA_GROUP_LOOKUP_CACHE = 'cnca_group_lookup_groups';
const CNCA_GROUP_LOOKUP_LAST_REFRESHED = 'cnca_group_lookup_last_refreshed';
const CNCA_GROUP_LOOKUP_SYNC_HOOK = 'cnca_group_lookup_sync';

function cnca_group_lookup_sync_groups(): void
{
    if (!defined('CNCA_AIRTABLE_API_KEY') || !defined('CNCA_AIRTABLE_BASE_URL')) {
        error_log('CNCA group lookup sync failed: Airtable configuration is missing.');
        return;
    }

    $groups = [];
    $offset = null;
    $seen_offsets = [];

    do {
        $query = ['pageSize' => 100];
        if ($offset !== null) {
            $query['offset'] = $offset;
        }

        $response = wp_remote_get(add_query_arg($query, CNCA_AIRTABLE_BASE_URL), [
            'headers' => [
                'Authorization' => 'Bearer ' . CNCA_AIRTABLE_API_KEY,
                'Accept' => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            error_log('CNCA group lookup sync failed: Airtable request error ' . $response->get_error_code() . '.');
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code < 200 || $response_code >= 300) {
            error_log('CNCA group lookup sync failed: Airtable returned HTTP ' . $response_code . '.');
            return;
        }

        $page = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($page) || !isset($page['records']) || !is_array($page['records'])) {
            error_log('CNCA group lookup sync failed: Airtable returned an invalid response.');
            return;
        }

        foreach ($page['records'] as $record) {
            $fields = is_array($record) && is_array($record['fields'] ?? null)
                ? $record['fields']
                : [];

            $groups[] = [
                'name' => $fields['Group Name'] ?? '',
                'id' => $fields['Group ID'] ?? '',
                'fnv' => $fields['FNV'] ?? '',
                'district' => $fields['District'] ?? '',
                'sub_district' => $fields['Sub District'] ?? '',
                'location' => $fields['Group Location'] ?? '',
                'type_format' => $fields['Type/Format'] ?? '',
                'address' => $fields['Group Address'] ?? '',
                'city' => $fields['Group City'] ?? '',
                'status' => $fields['Group Status'] ?? '',
            ];
        }

        $offset = $page['offset'] ?? null;
        if ($offset !== null) {
            if (!is_string($offset) || $offset === '' || isset($seen_offsets[$offset])) {
                error_log('CNCA group lookup sync failed: Airtable returned an invalid pagination offset.');
                return;
            }
            $seen_offsets[$offset] = true;
        }
    } while ($offset !== null);

    update_option(CNCA_GROUP_LOOKUP_CACHE, $groups, false);
    update_option(CNCA_GROUP_LOOKUP_LAST_REFRESHED, time(), false);
}

function cnca_group_lookup_searchable_value($value): string
{
    if (is_array($value)) {
        return implode(' ', array_filter(array_map('cnca_group_lookup_searchable_value', $value), 'strlen'));
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    return is_scalar($value) ? (string) $value : '';
}

function cnca_group_lookup_contains(string $value, string $search): bool
{
    if (function_exists('mb_stripos')) {
        return mb_stripos($value, $search, 0, 'UTF-8') !== false;
    }

    return stripos($value, $search) !== false;
}

add_action('init', function () {
    if (!wp_next_scheduled(CNCA_GROUP_LOOKUP_SYNC_HOOK)) {
        $scheduled = wp_schedule_event(time(), 'hourly', CNCA_GROUP_LOOKUP_SYNC_HOOK);
        if (is_wp_error($scheduled)) {
            error_log('CNCA group lookup sync could not be scheduled: ' . $scheduled->get_error_code() . '.');
        } elseif ($scheduled === false) {
            error_log('CNCA group lookup sync could not be scheduled.');
        }
    }
});
add_action(CNCA_GROUP_LOOKUP_SYNC_HOOK, 'cnca_group_lookup_sync_groups');
add_action('switch_theme', function () {
    wp_clear_scheduled_hook(CNCA_GROUP_LOOKUP_SYNC_HOOK);
});

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

            $field = $request->get_param('field');
            if ($field === null || $field === '') {
                $field = 'group_name';
            }

            $search_fields = [
                'group_name' => 'name',
                'city' => 'city',
                'location' => 'location',
                'address' => 'address',
                'district' => 'district',
                'group_id' => 'id',
                'fnv' => 'fnv',
            ];
            if (!is_string($field) || !isset($search_fields[$field])) {
                return new WP_Error(
                    'invalid_group_search_field',
                    __('Choose a valid search field.', 'cnca'),
                    ['status' => 400]
                );
            }

            $groups = get_option(CNCA_GROUP_LOOKUP_CACHE, null);
            if (!is_array($groups)) {
                return new WP_Error(
                    'group_lookup_unavailable',
                    __('An error occurred while searching.', 'cnca'),
                    ['status' => 503]
                );
            }

            $matches = array_filter($groups, function ($group) use ($search_fields, $field, $search) {
                return is_array($group)
                    && cnca_group_lookup_contains(
                        cnca_group_lookup_searchable_value($group[$search_fields[$field]] ?? ''),
                        $search
                    );
            });

            $last_refreshed = get_option(CNCA_GROUP_LOOKUP_LAST_REFRESHED, null);

            return rest_ensure_response([
                'groups' => array_values($matches),
                'refreshed_ago' => is_numeric($last_refreshed) && (int) $last_refreshed > 0
                    ? human_time_diff((int) $last_refreshed, time())
                    : null,
            ]);
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
                'address' => __('Address', 'cnca'),
                'city' => __('City', 'cnca'),
                'district' => __('District', 'cnca'),
                'error' => __('An error occurred while searching.', 'cnca'),
                'fnv' => __('FNV (legacy number)', 'cnca'),
                'id' => __('Group ID', 'cnca'),
                'location' => __('Location', 'cnca'),
                'loading' => __('Searching groups...', 'cnca'),
                'no_results' => __('No groups found.', 'cnca'),
                'refresh_time_unavailable' => __('Refresh time unavailable.', 'cnca'),
                /* translators: %s is the elapsed time since the last successful refresh. */
                'refreshed_ago' => __('Last refreshed: %s ago', 'cnca'),
                'empty_search' => __('Enter a search term.', 'cnca'),
                'status' => __('Status', 'cnca'),
                'sub_district' => __('Sub District', 'cnca'),
                'type_format' => __('Type/Format', 'cnca'),
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
        <select name="field" aria-label="' . esc_attr__('Search by', 'cnca') . '">
            <option value="group_name">' . esc_html__('Group Name', 'cnca') . '</option>
            <option value="city">' . esc_html__('City', 'cnca') . '</option>
            <option value="location">' . esc_html__('Location', 'cnca') . '</option>
            <option value="address">' . esc_html__('Address', 'cnca') . '</option>
            <option value="district">' . esc_html__('District', 'cnca') . '</option>
            <option value="group_id">' . esc_html__('Group ID', 'cnca') . '</option>
            <option value="fnv">' . esc_html__('FNV (legacy number)', 'cnca') . '</option>
        </select>
        <input name="search" type="search" placeholder="' . esc_attr__('Search groups...', 'cnca') . '" maxlength="' . CNCA_GROUP_LOOKUP_MAX_SEARCH_LENGTH . '" required>
        <input type="submit" value="' . esc_attr__('Search', 'cnca') . '">
    </form>
    <div id="group-lookup-results"></div>
    '
);