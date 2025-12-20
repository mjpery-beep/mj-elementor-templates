<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="mjet-site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Aller au contenu', 'mj-elementor-templates' ); ?></a>

	<?php mjet_render_header(); ?>

	<div id="content" class="site-content">
