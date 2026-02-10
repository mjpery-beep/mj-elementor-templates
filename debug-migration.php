<?php
/**
 * Debug de la migration Elementor.
 *
 * Accès via: /wp-content/plugins/elementor-supertool/debug-migration.php?key=mjet_debug_mig
 */

// Chemin: plugins/elementor-supertool/debug-migration.php -> wp-load.php
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

if ( ! isset( $_GET['key'] ) || 'mjet_debug_mig' !== $_GET['key'] ) {
	wp_die( 'Accès non autorisé.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Admin requis.' );
}

header( 'Content-Type: text/html; charset=utf-8' );

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Debug Migration</title>';
echo '<style>body{font-family:sans-serif;padding:20px;max-width:1200px;margin:0 auto}';
echo '.success{color:green}.error{color:red}.info{color:blue}';
echo 'pre{background:#f5f5f5;padding:10px;overflow-x:auto;max-height:300px;font-size:11px}</style></head><body>';
echo '<h1>Debug Migration Elementor</h1>';

// 1. Vérifier quels templates sont actifs
echo '<h2>1. Templates MJET actifs sur le site</h2>';

if ( class_exists( 'MJ_Elementor_Templates' ) ) {
	$header_id = MJ_Elementor_Templates::get_settings( 'type_header' );
	$footer_id = MJ_Elementor_Templates::get_settings( 'type_footer' );
	$before_footer_id = MJ_Elementor_Templates::get_settings( 'type_before_footer' );
	
	echo '<ul>';
	echo '<li><strong>Header ID:</strong> ' . ( $header_id ?: '<span class="error">Aucun</span>' ) . '</li>';
	echo '<li><strong>Footer ID:</strong> ' . ( $footer_id ?: '<span class="error">Aucun</span>' ) . '</li>';
	echo '<li><strong>Before Footer ID:</strong> ' . ( $before_footer_id ?: '<span class="info">Aucun (optionnel)</span>' ) . '</li>';
	echo '</ul>';
} else {
	echo '<p class="error">Classe MJ_Elementor_Templates non trouvée!</p>';
}

// 2. Récupérer les templates MJET
echo '<h2>2. Tous les templates MJET</h2>';

$mjet_templates = get_posts( array(
	'post_type'      => 'mjet-template',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft', 'private' ),
) );

if ( empty( $mjet_templates ) ) {
	echo '<p class="error">Aucun template MJET trouvé!</p>';
} else {
	echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">';
	echo '<tr><th>ID</th><th>Titre</th><th>Statut</th><th>Type</th><th>Conditions Include</th><th>Elementor Data</th></tr>';
	
	foreach ( $mjet_templates as $template ) {
		$type = get_post_meta( $template->ID, 'mjet_template_type', true );
		$include = get_post_meta( $template->ID, 'mjet_target_include_locations', true );
		$elementor_data = get_post_meta( $template->ID, '_elementor_data', true );
		
		$type_label = $type ?: '<span class="error">Non défini!</span>';
		$include_label = $include ? print_r( $include, true ) : '<span class="error">Non défini!</span>';
		$data_ok = ! empty( $elementor_data ) && is_string( $elementor_data );
		
		echo '<tr>';
		echo '<td>' . $template->ID . '</td>';
		echo '<td>' . esc_html( $template->post_title ) . '</td>';
		echo '<td>' . $template->post_status . '</td>';
		echo '<td>' . $type_label . '</td>';
		echo '<td><pre style="max-width:200px;overflow:auto;font-size:10px">' . esc_html( $include_label ) . '</pre></td>';
		echo '<td>' . ( $data_ok ? '<span class="success">OK</span>' : '<span class="error">ERREUR</span>' ) . '</td>';
		echo '</tr>';
	}
	echo '</table>';
}

// 3. Récupérer les templates UAE pour comparaison
echo '<h2>3. Templates UAE (source)</h2>';

$uae_templates = get_posts( array(
	'post_type'      => 'elementor-hf',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft', 'private' ),
) );

if ( empty( $uae_templates ) ) {
	echo '<p class="info">Aucun template UAE trouvé.</p>';
} else {
	echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">';
	echo '<tr><th>ID</th><th>Titre</th><th>Type UAE</th><th>Conditions UAE</th></tr>';
	
	foreach ( $uae_templates as $template ) {
		$type = get_post_meta( $template->ID, 'ehf_template_type', true );
		$include = get_post_meta( $template->ID, 'ehf_target_include_locations', true );
		
		echo '<tr>';
		echo '<td>' . $template->ID . '</td>';
		echo '<td>' . esc_html( $template->post_title ) . '</td>';
		echo '<td>' . esc_html( $type ?: 'N/A' ) . '</td>';
		echo '<td><pre style="max-width:300px;overflow:auto;font-size:10px">' . esc_html( print_r( $include, true ) ) . '</pre></td>';
		echo '</tr>';
	}
	echo '</table>';
}

// 4. Vérifier le thème
echo '<h2>4. Thème actif</h2>';
$theme = wp_get_theme();
echo '<p>Thème: <strong>' . esc_html( $theme->get( 'Name' ) ) . '</strong></p>';
if ( $theme->parent() ) {
	echo '<p>Thème parent: <strong>' . esc_html( $theme->parent()->get( 'Name' ) ) . '</strong></p>';
}

// 5. Vérifier les hooks
echo '<h2>5. Actions de footer enregistrées</h2>';
global $wp_filter;
$footer_hooks = array( 'wp_footer', 'hello_elementor_footer' );
foreach ( $footer_hooks as $hook ) {
	echo '<h4>' . $hook . '</h4>';
	if ( isset( $wp_filter[ $hook ] ) ) {
		echo '<ul>';
		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $id => $callback ) {
				$name = is_array( $callback['function'] ) 
					? ( is_object( $callback['function'][0] ) ? get_class( $callback['function'][0] ) : $callback['function'][0] ) . '::' . $callback['function'][1]
					: ( is_string( $callback['function'] ) ? $callback['function'] : 'Closure' );
				echo '<li>Priorité ' . $priority . ': ' . esc_html( $name ) . '</li>';
			}
		}
		echo '</ul>';
	} else {
		echo '<p class="info">Aucune action enregistrée</p>';
	}
}

echo '</body></html>';
