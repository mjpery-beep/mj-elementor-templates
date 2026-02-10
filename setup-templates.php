<?php
/**
 * Configurer les templates MJET pour affichage global.
 *
 * Accès via: /wp-content/plugins/elementor-supertool/setup-templates.php?key=mjet_setup
 */

require_once dirname( __DIR__, 3 ) . '/wp-load.php';

if ( ! isset( $_GET['key'] ) || 'mjet_setup' !== $_GET['key'] ) {
	wp_die( 'Accès non autorisé.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Admin requis.' );
}

header( 'Content-Type: text/html; charset=utf-8' );

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Configuration Templates MJET</title>';
echo '<style>body{font-family:sans-serif;padding:20px;max-width:900px;margin:0 auto}';
echo '.success{color:green}.error{color:red}.info{color:blue}';
echo 'table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}';
echo 'th{background:#f5f5f5}.btn{display:inline-block;padding:10px 20px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;margin:5px}</style></head><body>';
echo '<h1>Configuration des Templates MJET</h1>';

// Récupérer les templates MJET
$mjet_templates = get_posts( array(
	'post_type'      => 'mjet-template',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft', 'private' ),
) );

if ( empty( $mjet_templates ) ) {
	echo '<p class="error">Aucun template MJET trouvé. Importez d\'abord vos templates UAE.</p>';
	echo '</body></html>';
	exit;
}

// Configurer un template
if ( isset( $_GET['configure'] ) ) {
	$template_id = intval( $_GET['configure'] );
	$template = get_post( $template_id );
	
	if ( $template && 'mjet-template' === $template->post_type ) {
		// Définir la condition "Site entier"
		$include_locations = array(
			'rule'    => array( 'basic-global' ),
			'specific' => array(),
		);
		
		update_post_meta( $template_id, 'mjet_target_include_locations', $include_locations );
		
		// Pas d'exclusions
		update_post_meta( $template_id, 'mjet_target_exclude_locations', array(
			'rule'    => array(),
			'specific' => array(),
		) );
		
		// Tous les utilisateurs
		update_post_meta( $template_id, 'mjet_target_user_roles', array() );
		
		echo '<div class="success" style="padding:15px;background:#d4edda;border-radius:4px;margin-bottom:20px">';
		echo '<strong>✓ Template "' . esc_html( $template->post_title ) . '" configuré pour s\'afficher sur tout le site !</strong>';
		echo '</div>';
	}
}

// Configurer tous les templates
if ( isset( $_GET['configure_all'] ) ) {
	$count = 0;
	foreach ( $mjet_templates as $template ) {
		$type = get_post_meta( $template->ID, 'mjet_template_type', true );
		
		// Configurer seulement les headers et footers
		if ( in_array( $type, array( 'type_header', 'type_footer', 'type_before_footer' ), true ) ) {
			$include_locations = array(
				'rule'    => array( 'basic-global' ),
				'specific' => array(),
			);
			
			update_post_meta( $template->ID, 'mjet_target_include_locations', $include_locations );
			update_post_meta( $template->ID, 'mjet_target_exclude_locations', array(
				'rule'    => array(),
				'specific' => array(),
			) );
			update_post_meta( $template->ID, 'mjet_target_user_roles', array() );
			
			$count++;
		}
	}
	
	echo '<div class="success" style="padding:15px;background:#d4edda;border-radius:4px;margin-bottom:20px">';
	echo '<strong>✓ ' . $count . ' template(s) configuré(s) pour s\'afficher sur tout le site !</strong>';
	echo '</div>';
	
	// Rafraîchir la liste
	$mjet_templates = get_posts( array(
		'post_type'      => 'mjet-template',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'draft', 'private' ),
	) );
}

echo '<h2>Vos templates</h2>';
echo '<table>';
echo '<tr><th>ID</th><th>Titre</th><th>Type</th><th>Statut</th><th>Conditions d\'affichage</th><th>Actions</th></tr>';

$type_labels = array(
	'type_header'           => 'Header',
	'type_before_footer'    => 'Before Footer',
	'type_footer'           => 'Footer',
	'custom'                => 'Custom Block',
	'type_single_page'      => 'Single Page',
	'type_single_post'      => 'Single Post',
	'type_single_product'   => 'Single Product',
	'type_archive'          => 'Archive',
	'type_products_archive' => 'Products Archive',
	'type_search'           => 'Search Results',
	'type_404'              => '404 Page',
);

foreach ( $mjet_templates as $template ) {
	$type = get_post_meta( $template->ID, 'mjet_template_type', true );
	$include = get_post_meta( $template->ID, 'mjet_target_include_locations', true );
	
	$type_label = isset( $type_labels[ $type ] ) ? $type_labels[ $type ] : $type;
	
	// Vérifier si configuré pour site entier
	$is_global = false;
	if ( ! empty( $include['rule'] ) && in_array( 'basic-global', $include['rule'], true ) ) {
		$is_global = true;
	}
	
	$conditions_text = $is_global 
		? '<span class="success">✓ Site entier</span>' 
		: '<span class="error">✗ Non configuré</span>';
	
	echo '<tr>';
	echo '<td>' . $template->ID . '</td>';
	echo '<td>' . esc_html( $template->post_title ) . '</td>';
	echo '<td>' . esc_html( $type_label ) . '</td>';
	echo '<td>' . ( $template->post_status === 'publish' ? '<span class="success">Publié</span>' : $template->post_status ) . '</td>';
	echo '<td>' . $conditions_text . '</td>';
	echo '<td>';
	if ( ! $is_global ) {
		echo '<a href="?key=mjet_setup&configure=' . $template->ID . '" class="btn" style="padding:5px 10px;font-size:12px">Configurer</a>';
	}
	echo ' <a href="' . esc_url( admin_url( 'post.php?post=' . $template->ID . '&action=edit' ) ) . '" target="_blank" style="font-size:12px">Éditer</a>';
	echo '</td>';
	echo '</tr>';
}

echo '</table>';

// Bouton configurer tous
$unconfigured = array_filter( $mjet_templates, function( $t ) {
	$include = get_post_meta( $t->ID, 'mjet_target_include_locations', true );
	return empty( $include['rule'] ) || ! in_array( 'basic-global', $include['rule'], true );
} );

if ( ! empty( $unconfigured ) ) {
	echo '<p style="margin-top:20px">';
	echo '<a href="?key=mjet_setup&configure_all=1" class="btn">Configurer tous les templates pour affichage global</a>';
	echo '</p>';
}

// Vérifier les templates actifs
echo '<h2>Vérification des templates actifs</h2>';

if ( class_exists( 'MJ_Elementor_Templates' ) ) {
	$header_id = MJ_Elementor_Templates::get_settings( 'type_header' );
	$footer_id = MJ_Elementor_Templates::get_settings( 'type_footer' );
	$before_footer_id = MJ_Elementor_Templates::get_settings( 'type_before_footer' );
	
	echo '<ul>';
	echo '<li><strong>Header actif :</strong> ' . ( $header_id ? 'ID ' . $header_id . ' ✓' : '<span class="error">Aucun</span>' ) . '</li>';
	echo '<li><strong>Footer actif :</strong> ' . ( $footer_id ? 'ID ' . $footer_id . ' ✓' : '<span class="error">Aucun</span>' ) . '</li>';
	echo '<li><strong>Before Footer actif :</strong> ' . ( $before_footer_id ? 'ID ' . $before_footer_id : '<span class="info">Aucun (optionnel)</span>' ) . '</li>';
	echo '</ul>';
	
	if ( ! $header_id && ! $footer_id ) {
		echo '<p class="error"><strong>Problème :</strong> Aucun template actif. Assurez-vous que :</p>';
		echo '<ol>';
		echo '<li>Les templates ont le type correct (type_header ou type_footer)</li>';
		echo '<li>Les templates sont publiés (pas en brouillon)</li>';
		echo '<li>Les conditions d\'affichage sont configurées (voir ci-dessus)</li>';
		echo '</ol>';
	}
} else {
	echo '<p class="error">La classe MJ_Elementor_Templates n\'est pas chargée.</p>';
}

echo '<p style="margin-top:30px"><a href="' . esc_url( home_url() ) . '" target="_blank" class="btn">Voir le site</a></p>';

echo '</body></html>';
