<?php

// This file contains code to integrate with Polylang, a popular multilingual plugin for WordPress. 
// It ensures that the correct pages are used for GiveWP's success, failure, and history pages based on the current language.

add_filter(
    'give_get_success_page_uri',
    function () {
        $give_options = give_get_settings();
        $page_id = $give_options['success_page'];

        if (function_exists('pll_get_post')):
            $page_id = pll_get_post($page_id);
        endif;

        $success_page = isset($page_id) ? get_permalink(absint($page_id)) : get_bloginfo('url');

        return $success_page;
    }
    ,
    10,
    1
);

add_filter('give_get_failed_transaction_uri', function () {
    $give_options = give_get_settings();
    $page_id = $give_options['failure_page'];

    if (function_exists('pll_get_post')):
        $page_id = pll_get_post($page_id);
    endif;

    $success_page = isset($page_id) ? get_permalink(absint($page_id)) : get_bloginfo('url');

    return $success_page;
}, 10, 1);

add_filter('give_get_history_page_uri', function () {
    $give_options = give_get_settings();
    $page_id = $give_options['history_page'];

    if (function_exists('pll_get_post')):
        $page_id = pll_get_post($page_id);
    endif;

    $success_page = isset($page_id) ? get_permalink(absint($page_id)) : get_bloginfo('url');

    return $success_page;
}, 10, 1);