<?php
/**
 * Script de migration directe des templates UAE vers MJET.
 * À exécuter une seule fois via wp-cli ou en incluant dans functions.php temporairement.
 *
 * Usage: wp eval-file wp-content/plugins/mj-elementor-templates/migrate-uae.php
 *
 * @package mj-elementor-templates
 */

// Charger WordPress si pas déjà chargé.
if ( ! defined( 'ABSPATH' ) ) {
	// Trouver wp-load.php.
	$wp_load = dirname( __FILE__ ) . '/../../../wp-load.php';
	if ( file_exists( $wp_load ) ) {
		require_once $wp_load;
	} else {
		die( 'WordPress non trouvé.' );
	}
}

/**
 * Exécuter la migration.
 */
function mjet_run_migration() {
	global $wpdb;

	echo "=== Migration des templates UAE vers MJ Elementor Templates ===\n\n";

	// Récupérer tous les templates UAE.
	$uae_templates = get_posts(
		array(
			'post_type'      => 'elementor-hf',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'private' ),
		)
	);

	if ( empty( $uae_templates ) ) {
		echo "Aucun template UAE trouvé.\n";
		return;
	}

	echo "Trouvé " . count( $uae_templates ) . " template(s) UAE.\n\n";

	$migrated = 0;
	$errors   = 0;

	foreach ( $uae_templates as $old_post ) {
		echo "Migration: {$old_post->post_title} (ID: {$old_post->ID})... ";

		// Créer le nouveau post.
		$new_post_data = array(
			'post_title'   => $old_post->post_title,
			'post_content' => $old_post->post_content,
			'post_status'  => $old_post->post_status,
			'post_type'    => 'mjet-template',
			'post_author'  => $old_post->post_author,
		);

		$new_id = wp_insert_post( $new_post_data );

		if ( is_wp_error( $new_id ) ) {
			echo "ERREUR: " . $new_id->get_error_message() . "\n";
			$errors++;
			continue;
		}

		// Copier les métadonnées Elementor.
		$elementor_meta_keys = array(
			'_elementor_data',
			'_elementor_edit_mode',
			'_elementor_page_settings',
			'_elementor_css',
			'_wp_page_template',
			'_thumbnail_id',
		);

		foreach ( $elementor_meta_keys as $meta_key ) {
			$meta_value = get_post_meta( $old_post->ID, $meta_key, true );
			if ( ! empty( $meta_value ) ) {
				update_post_meta( $new_id, $meta_key, $meta_value );
			}
		}

		// Convertir les métadonnées UAE -> MJET.
		$meta_mapping = array(
			'ehf_template_type'            => 'mjet_template_type',
			'ehf_target_include_locations' => 'mjet_target_include_locations',
			'ehf_target_exclude_locations' => 'mjet_target_exclude_locations',
			'ehf_target_user_roles'        => 'mjet_target_user_roles',
			'display-on-canvas-template'   => 'mjet_display_on_canvas',
		);

		foreach ( $meta_mapping as $old_key => $new_key ) {
			$value = get_post_meta( $old_post->ID, $old_key, true );
			if ( ! empty( $value ) || '0' === $value ) {
				update_post_meta( $new_id, $new_key, $value );
			}
		}

		echo "OK (Nouveau ID: $new_id)\n";
		$migrated++;
	}

	echo "\n=== Résultat ===\n";
	echo "Templates migrés: $migrated\n";
	echo "Erreurs: $errors\n";

	if ( $migrated > 0 ) {
		echo "\nVous pouvez maintenant:\n";
		echo "1. Activer le plugin MJ Elementor Templates\n";
		echo "2. Désactiver le plugin UAE (Header Footer Elementor)\n";
		echo "3. Vérifier vos templates dans MJ Templates > Tous les templates\n";
	}
}

// Exécuter si appelé directement.
if ( php_sapi_name() === 'cli' || defined( 'WP_CLI' ) ) {
	mjet_run_migration();
}
