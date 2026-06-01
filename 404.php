<?php
get_header();

$contact_us_url = '/contact-us';

if (function_exists('pll_current_language')) {
    if (pll_current_language() == 'es') {
        $contact_us_url = '/es/contactanos';
    }
}
?>
<main>
    <section>
        <article>
            <article>
                <h1><?php _e('Page not found', 'cnca') ?></h1>
                <p>
                    <?php
                    // translators: %s is a URL to the contact us page
                    printf(__('No page was found at this address. Please try a search or <a href="%s">contact the Web Committee</a>.', 'cnca'), $contact_us_url);
                    ?>
                </p>
            </article>
        </article>
    </section>
    <aside>
        <?php dynamic_sidebar('cnca-sidebar') ?>
    </aside>
</main>
<?php
get_footer();