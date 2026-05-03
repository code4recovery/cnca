<?php
get_header();
?>
<div id="cnca-content">
    <main>
        <?php if (have_posts()) {
            while (have_posts()) {
                the_post(); ?>
                <h1><?php the_title() ?></h1>
                <div><?php the_content() ?></div>
                <?php
            }
        }
        ?>
    </main>
    <aside>
        <?php dynamic_sidebar('cnca-sidebar'); ?>
    </aside>
</div>
<?php
get_footer();