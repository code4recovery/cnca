<?php
get_header();
?>

<main>
    <section>

        <?php if (have_posts()) { ?>

            <header>
                <h1>
                    <?php
                    /* translators: %s: search query. */
                    printf(esc_html__('Results for: %s', 'cnca'), '<span>' . get_search_query() . '</span>');
                    ?>
                </h1>
            </header>

            <?php
            while (have_posts()) {
                the_post();
                ?>
                <article>
                    <h2>
                        <a href="<?php the_permalink() ?>">
                            <?php the_title() ?>
                        </a>
                    </h2>
                    <?php the_excerpt() ?>
                </article>
                <?php

            }

            the_posts_navigation();

        } else { ?>
            <h1><?php _e('Nothing Found', 'cnca') ?></h1>
            <p>
                <?php _e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'cnca') ?>
            </p>
        <?php } ?>

    </section>

    <aside>
        <?php dynamic_sidebar('cnca-sidebar') ?>
    </aside>

</main>

<?php
get_footer();