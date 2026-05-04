<!doctype html>
<html <?php language_attributes() ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php is_front_page() ? bloginfo('name') : wp_title('') ?></title>
    <?php wp_head() ?>
</head>

<body <?php body_class() ?>>

    <div id="cnca-container">

        <nav id="cnca-primary-navigation" role="navigation">
            <input type="checkbox" id="cnca-menu-toggle">

            <label for="cnca-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <?php wp_nav_menu([
                'container' => false,
                'theme_location' => 'primary',
            ]) ?>
        </nav>

        <div id="cnca-logo">
            <img src="<?php echo get_template_directory_uri() ?>/img/logo.png" width="799" height="798" alt="CNCA Logo">
        </div>