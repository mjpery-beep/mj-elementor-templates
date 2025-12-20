<?php
/**
 * Script de correction des templates MJET.
 *
 * Accès via: /wp-content/plugins/mj-elementor-templates/fix-templates.php?key=mjet_fix_2024
 *
 * @package mj-elementor-templates
 */

// Charger WordPress.
// Chemin: plugins/mj-elementor-templates/fix-templates.php -> wp-load.php
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

// Sécurité basique.
if ( ! isset( $_GET['key'] ) || 'mjet_fix_2024' !== $_GET['key'] ) {
	wp_die( 'Accès non autorisé.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Vous devez être administrateur.' );
}

header( 'Content-Type: text/html; charset=utf-8' );

echo '<h1>Correction des templates MJET</h1>';

// 1. Vérifier et ajouter le support CPT Elementor.
$cpt_support = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
echo '<h2>Support CPT Elementor actuel:</h2>';
echo '<pre>' . print_r( $cpt_support, true ) . '</pre>';

if ( ! is_array( $cpt_support ) ) {
	$cpt_support = array( 'page', 'post' );
}

if ( ! in_array( 'mjet-template', $cpt_support, true ) ) {
	$cpt_support[] = 'mjet-template';
	update_option( 'elementor_cpt_support', $cpt_support );
	echo '<p style="color: green;">✓ Post type mjet-template ajouté au support CPT Elementor.</p>';
} else {
	echo '<p style="color: blue;">ℹ Post type mjet-template déjà dans le support CPT Elementor.</p>';
}

// 2. Corriger les templates.
$templates = get_posts( array(
	'post_type'      => 'mjet-template',
	'posts_per_page' => -1,
	'post_status'    => 'any',
) );

echo '<h2>Templates trouvés: ' . count( $templates ) . '</h2>';

foreach ( $templates as $template ) {
	echo '<h3>Template #' . $template->ID . ' - ' . esc_html( $template->post_title ) . '</h3>';

	// Afficher les metas actuels.
	$current_template = get_post_meta( $template->ID, '_wp_page_template', true );
	$edit_mode = get_post_meta( $template->ID, '_elementor_edit_mode', true );
	$elementor_data = get_post_meta( $template->ID, '_elementor_data', true );
	$elementor_version = get_post_meta( $template->ID, '_elementor_version', true );

	echo '<p>Template actuel: <code>' . esc_html( $current_template ?: '(vide)' ) . '</code></p>';
	echo '<p>Mode édition: <code>' . esc_html( $edit_mode ?: '(vide)' ) . '</code></p>';
	echo '<p>Version Elementor: <code>' . esc_html( $elementor_version ?: '(vide)' ) . '</code></p>';
	echo '<p>Données Elementor: <code>' . ( ! empty( $elementor_data ) ? 'Présentes (' . strlen( $elementor_data ) . ' chars)' : '(vide)' ) . '</code></p>';

	// Supprimer et recréer le meta template.
	delete_post_meta( $template->ID, '_wp_page_template' );
	add_post_meta( $template->ID, '_wp_page_template', 'elementor_canvas', true );
	echo '<p style="color: green;">✓ _wp_page_template défini à elementor_canvas</p>';

	// S'assurer que le mode édition est défini à 'builder'.
	delete_post_meta( $template->ID, '_elementor_edit_mode' );
	add_post_meta( $template->ID, '_elementor_edit_mode', 'builder', true );
	echo '<p style="color: green;">✓ _elementor_edit_mode défini à builder</p>';

	// S'assurer que la version Elementor est définie.
	if ( empty( $elementor_version ) && defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $template->ID, '_elementor_version', ELEMENTOR_VERSION );
		echo '<p style="color: green;">✓ _elementor_version défini à ' . ELEMENTOR_VERSION . '</p>';
	}

	// Si pas de données Elementor, initialiser avec un tableau vide.
	if ( empty( $elementor_data ) ) {
		update_post_meta( $template->ID, '_elementor_data', '[]' );
		echo '<p style="color: orange;">⚠ _elementor_data initialisé à [] (vide)</p>';
	}

	// Vérification.
	$new_template = get_post_meta( $template->ID, '_wp_page_template', true );
	$new_edit_mode = get_post_meta( $template->ID, '_elementor_edit_mode', true );
	echo '<p>Nouveau template: <code>' . esc_html( $new_template ) . '</code></p>';
	echo '<p>Nouveau mode édition: <code>' . esc_html( $new_edit_mode ) . '</code></p>';

	echo '<hr>';
}

// 3. Vérifier les données après correction.
echo '<h2>Vérification finale du support CPT:</h2>';
$cpt_support_final = get_option( 'elementor_cpt_support' );
echo '<pre>' . print_r( $cpt_support_final, true ) . '</pre>';

// 4. Régénérer les CSS Elementor si possible.
if ( class_exists( '\Elementor\Plugin' ) ) {
	echo '<h2>Régénération des fichiers CSS Elementor...</h2>';
	try {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		echo '<p style="color: green;">✓ Cache CSS Elementor vidé.</p>';
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Erreur: ' . esc_html( $e->getMessage() ) . '</p>';
	}
}

// 5. Flusher les rewrite rules (IMPORTANT pour éviter les 404).
echo '<h2>Flush des rewrite rules...</h2>';
flush_rewrite_rules( true );
echo '<p style="color: green;">✓ Rewrite rules flushées.</p>';

// 6. Vérifier que le post type est bien enregistré.
echo '<h2>Vérification du post type mjet-template:</h2>';
$post_type_obj = get_post_type_object( 'mjet-template' );
if ( $post_type_obj ) {
	echo '<p style="color: green;">✓ Post type mjet-template enregistré.</p>';
	echo '<p>Public: ' . ( $post_type_obj->public ? 'Oui' : 'Non' ) . '</p>';
	echo '<p>Show UI: ' . ( $post_type_obj->show_ui ? 'Oui' : 'Non' ) . '</p>';
	if ( isset( $post_type_obj->rewrite ) && is_array( $post_type_obj->rewrite ) ) {
		echo '<p>Rewrite slug: ' . esc_html( $post_type_obj->rewrite['slug'] ?? 'mjet-template' ) . '</p>';
	}
} else {
	echo '<p style="color: red;">✗ Post type mjet-template NON enregistré!</p>';
}

echo '<p><a href="' . admin_url( 'edit.php?post_type=mjet-template' ) . '">Retour aux templates</a></p>';
echo '<p><strong>Important:</strong> Videz le cache de votre navigateur et réessayez d\'éditer un template.</p>';
