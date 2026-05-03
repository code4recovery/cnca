<form role="search" method="get" id="cnca-search" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text">Search for:</span>
        <input type="search" placeholder="Search this site..." value="<?php echo get_search_query(); ?>" name="s" />
    </label>
</form>