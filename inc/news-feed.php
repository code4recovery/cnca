<?php

add_shortcode(
    'cnca_news_feed',
    function () {
        $categories = get_categories();

        $output = '';

        foreach ($categories as $category) {
            $posts = get_posts([
                'category' => $category->term_id,
            ]);

            $output .= '<h2>' . esc_html($category->name) . '</h2>';

            foreach ($posts as $post) {
                $output .= '<article><h3>' .
                    esc_html($post->post_title) . '</h3>' .
                    apply_filters('the_content', $post->post_content) . '</article>';
            }
        }

        return '<div id="cnca-news-feed">' . $output . '</div>';
    }
);