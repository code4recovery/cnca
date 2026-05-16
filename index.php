<?php
get_header();
?>
<main>
    <section>
        <?php if (have_posts()) {
            while (have_posts()) {
                the_post(); ?>
                <article>
                    <h1><?php the_title() ?></h1>
                    <?php the_content() ?>
                </article>
            <?php }
        } ?>
    </section>
    <aside>
        <?php dynamic_sidebar('cnca-sidebar') ?>
    </aside>
</main>
<?php
get_footer();