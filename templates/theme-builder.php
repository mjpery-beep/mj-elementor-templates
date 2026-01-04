<?php
/**
 * Fallback template used by the MJET theme manager locations.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$template_id   = MJET_Theme_Manager::get_current_template_id();
$template_type = MJET_Theme_Manager::get_current_template_type();

if ( 'type_404' === $template_type ) {
    status_header( 404 );
    nocache_headers();
}

get_header();
?>
<main id="primary" class="mjet-theme-builder">
    <?php
    /**
     * Fires before the MJET theme manager renders the template content.
     *
     * @param int    $template_id   Template identifier.
     * @param string $template_type Template type key.
     */
    do_action( 'mjet/theme_manager/before_content', $template_id, $template_type );

    $rendered = MJET_Theme_Manager::render_current_template();
    if ( '' !== $rendered ) {
        echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Fires after the MJET theme manager renders the template content.
     *
     * @param int    $template_id   Template identifier.
     * @param string $template_type Template type key.
     */
    do_action( 'mjet/theme_manager/after_content', $template_id, $template_type );
    ?>
</main>
<?php
wp_reset_postdata();
get_footer();
