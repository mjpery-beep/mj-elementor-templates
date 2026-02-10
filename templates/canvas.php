<?php
/**
 * Template Canvas pour MJ Elementor Templates.
 *
 * Ce fichier est une copie du template Canvas d'Elementor,
 * assurant que the_content() est correctement appelé.
 *
 * @package elementor-supertool
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// S'assurer qu'Elementor est chargé.
if ( ! class_exists( '\Elementor\Plugin' ) ) {
	wp_die( 'Elementor n\'est pas actif.' );
}

\Elementor\Plugin::$instance->frontend->add_body_class( 'elementor-template-canvas' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo wp_get_document_title(); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
	<?php
	// Garder cette ligne après wp_head() pour s'assurer qu'elle n'est pas écrasée.
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />';
	?>
</head>
<body <?php body_class(); ?>>
	<?php
	wp_body_open();

	/**
	 * Avant le contenu du template Canvas.
	 *
	 * @since 1.0.0
	 */
	do_action( 'elementor/page_templates/canvas/before_content' );

	// Appeler the_content() pour qu'Elementor puisse s'y attacher.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;

	/**
	 * Après le contenu du template Canvas.
	 *
	 * @since 1.0.0
	 */
	do_action( 'elementor/page_templates/canvas/after_content' );

	wp_footer();
	?>
</body>
</html>
