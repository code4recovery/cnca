<form role="search" method="get" id="cnca-search" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text">
            <?php _e('Search for:', 'cnca') ?>
        </span>
        <input type="search" placeholder="<?php esc_attr_e('Search this site...', 'cnca') ?>"
            value="<?php echo get_search_query(); ?>" name="s" />
    </label>
</form>