<?php
/**
 * Debug des templates MJET.
 *
 * Accès via: /wp-content/plugins/elementor-supertool/debug.php?key=mjet_debug
 */

// Chemin: plugins/elementor-supertool/debug.php -> wp-load.php
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

if ( ! isset( $_GET['key'] ) || 'mjet_debug' !== $_GET['key'] ) {
	wp_die( 'Accès non autorisé.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Admin requis.' );
}

header( 'Content-Type: text/html; charset=utf-8' );

echo '<h1>Debug MJET Templates</h1>';

// 1. Vérifier le post type
echo '<h2>1. Post Type mjet-template</h2>';
$pt = get_post_type_object( 'mjet-template' );
if ( $pt ) {
	echo '<p style="color:green;">✓ Post type enregistré</p>';
	echo '<ul>';
	echo '<li>public: ' . ( $pt->public ? 'true' : 'false' ) . '</li>';
	echo '<li>publicly_queryable: ' . ( $pt->publicly_queryable ? 'true' : 'false' ) . '</li>';
	echo '<li>rewrite: ' . ( $pt->rewrite ? json_encode( $pt->rewrite ) : 'false' ) . '</li>';
	echo '</ul>';
} else {
	echo '<p style="color:red;">✗ Post type NON enregistré</p>';
}

// 2. Vérifier un template spécifique
echo '<h2>2. Template ID 3201</h2>';
$post = get_post( 3201 );
if ( $post ) {
	echo '<p>Titre: ' . esc_html( $post->post_title ) . '</p>';
	echo '<p>Post type: ' . esc_html( $post->post_type ) . '</p>';
	echo '<p>Status: ' . esc_html( $post->post_status ) . '</p>';
	echo '<p>Slug: ' . esc_html( $post->post_name ) . '</p>';
	
	// Metas importants
	$metas = array(
		'_wp_page_template',
		'_elementor_edit_mode',
		'_elementor_data',
		'_elementor_version',
		'mjet_template_type',
	);
	echo '<h3>Metas:</h3><ul>';
	foreach ( $metas as $key ) {
		$val = get_post_meta( 3201, $key, true );
		$display = is_string( $val ) ? ( strlen( $val ) > 100 ? substr( $val, 0, 100 ) . '...' : $val ) : json_encode( $val );
		echo '<li><strong>' . esc_html( $key ) . ':</strong> <code>' . esc_html( $display ?: '(vide)' ) . '</code></li>';
	}
	echo '</ul>';
	
	// URL du template
	$url = get_permalink( 3201 );
	echo '<p>URL: <a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a></p>';
} else {
	echo '<p style="color:red;">✗ Post 3201 non trouvé</p>';
}

// 3. Rewrite rules
echo '<h2>3. Rewrite Rules pour mjet-template</h2>';
global $wp_rewrite;
$rules = $wp_rewrite->wp_rewrite_rules();
$found = false;
if ( $rules ) {
	foreach ( $rules as $pattern => $query ) {
		if ( strpos( $query, 'mjet-template' ) !== false || strpos( $pattern, 'mjet-template' ) !== false ) {
			echo '<p><code>' . esc_html( $pattern ) . '</code> => <code>' . esc_html( $query ) . '</code></p>';
			$found = true;
		}
	}
}
if ( ! $found ) {
	echo '<p style="color:red;">✗ Aucune rewrite rule pour mjet-template trouvée!</p>';
	echo '<p><strong>Solution:</strong> Allez dans Réglages → Permaliens et cliquez sur "Enregistrer les modifications"</p>';
}

// 4. Flusher les rules maintenant
echo '<h2>4. Flush des rewrite rules</h2>';
if ( isset( $_GET['flush'] ) ) {
	flush_rewrite_rules( true );
	echo '<p style="color:green;">✓ Rewrite rules flushées! Rechargez la page.</p>';
} else {
	echo '<p><a href="?key=mjet_debug&flush=1">Cliquer ici pour flusher les rewrite rules</a></p>';
}

// 5. Vérifier Elementor CPT support
echo '<h2>5. Elementor CPT Support</h2>';
$cpt = get_option( 'elementor_cpt_support', array() );
echo '<pre>' . print_r( $cpt, true ) . '</pre>';
if ( in_array( 'mjet-template', $cpt ) ) {
	echo '<p style="color:green;">✓ mjet-template dans le support CPT</p>';
} else {
	echo '<p style="color:red;">✗ mjet-template PAS dans le support CPT</p>';
}
