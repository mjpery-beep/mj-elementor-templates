<?php
/**
 * Fonctions d'aide pour MJ Elementor Templates.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vérifie si le header est activé.
 *
 * @return bool
 */
function mjet_header_enabled() {
	$header_id = MJ_Elementor_Templates::get_settings( 'type_header' );
	$enabled   = ! empty( $header_id );
	return apply_filters( 'mjet_header_enabled', $enabled );
}

/**
 * Vérifie si le footer est activé.
 *
 * @return bool
 */
function mjet_footer_enabled() {
	$footer_id = MJ_Elementor_Templates::get_settings( 'type_footer' );
	$enabled   = ! empty( $footer_id );
	return apply_filters( 'mjet_footer_enabled', $enabled );
}

/**
 * Vérifie si le before footer est activé.
 *
 * @return bool
 */
function mjet_before_footer_enabled() {
	$before_footer_id = MJ_Elementor_Templates::get_settings( 'type_before_footer' );
	$enabled          = ! empty( $before_footer_id );
	return apply_filters( 'mjet_before_footer_enabled', $enabled );
}

/**
 * Récupère l'ID du header.
 *
 * @return int|false
 */
function mjet_get_header_id() {
	$header_id = MJ_Elementor_Templates::get_settings( 'type_header' );
	if ( empty( $header_id ) ) {
		$header_id = false;
	}
	return apply_filters( 'mjet_get_header_id', $header_id );
}

/**
 * Récupère l'ID du footer.
 *
 * @return int|false
 */
function mjet_get_footer_id() {
	$footer_id = MJ_Elementor_Templates::get_settings( 'type_footer' );
	if ( empty( $footer_id ) ) {
		$footer_id = false;
	}
	return apply_filters( 'mjet_get_footer_id', $footer_id );
}

/**
 * Récupère l'ID du before footer.
 *
 * @return int|false
 */
function mjet_get_before_footer_id() {
	$before_footer_id = MJ_Elementor_Templates::get_settings( 'type_before_footer' );
	if ( empty( $before_footer_id ) ) {
		$before_footer_id = false;
	}
	return apply_filters( 'mjet_get_before_footer_id', $before_footer_id );
}

/**
 * Affiche le header.
 */
function mjet_render_header() {
	if ( false === apply_filters( 'mjet_enable_render_header', true ) ) {
		return;
	}
	?>
	<header id="masthead" class="mjet-header" itemscope="itemscope" itemtype="https://schema.org/WPHeader">
		<p class="main-title mjet-hidden" itemprop="headline">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
				<?php bloginfo( 'name' ); ?>
			</a>
		</p>
		<?php MJ_Elementor_Templates::get_header_content(); ?>
	</header>
	<?php
}

/**
 * Affiche le footer.
 */
function mjet_render_footer() {
	if ( false === apply_filters( 'mjet_enable_render_footer', true ) ) {
		return;
	}
	?>
	<footer id="colophon" class="mjet-footer" itemscope="itemscope" itemtype="https://schema.org/WPFooter">
		<?php MJ_Elementor_Templates::get_footer_content(); ?>
	</footer>
	<?php
}

/**
 * Affiche le before footer.
 */
function mjet_render_before_footer() {
	if ( false === apply_filters( 'mjet_enable_render_before_footer', true ) ) {
		return;
	}
	?>
	<div class="mjet-before-footer">
		<?php MJ_Elementor_Templates::get_before_footer_content(); ?>
	</div>
	<?php
}

/**
 * Vérifie si l'affichage sur Canvas est activé pour un template.
 *
 * @param int $template_id ID du template.
 * @return bool
 */
function mjet_is_canvas_enabled( $template_id ) {
	return '1' === get_post_meta( $template_id, 'mjet_display_on_canvas', true );
}

/**
 * Vérifie si on est sur un template Canvas Elementor.
 *
 * @return bool
 */
function mjet_is_canvas_template() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}

	$document = \Elementor\Plugin::instance()->documents->get_current();
	if ( $document ) {
		$template = $document->get_meta( '_wp_page_template' );
		return 'elementor_canvas' === $template;
	}

	return false;
}

/**
 * Récupère le contenu d'un template par son ID.
 *
 * @param int $template_id ID du template.
 * @return string
 */
function mjet_get_template_content( $template_id ) {
	if ( ! class_exists( '\Elementor\Plugin' ) || empty( $template_id ) ) {
		return '';
	}

	return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );
}
