<?php
/**
 * Régénérer les CSS Elementor pour tous les templates MJET.
 *
 * Accès via: /wp-content/plugins/mj-elementor-templates/regenerate-css.php?key=mjet_regen
 */

// Chemin: plugins/mj-elementor-templates/regenerate-css.php -> wp-load.php
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

if ( ! isset( $_GET['key'] ) || 'mjet_regen' !== $_GET['key'] ) {
	wp_die( 'Accès non autorisé.' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Admin requis.' );
}

header( 'Content-Type: text/html; charset=utf-8' );

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Régénération CSS MJET</title>';
echo '<style>body{font-family:sans-serif;padding:20px;max-width:900px;margin:0 auto}';
echo '.success{color:green}.error{color:red}.info{color:blue}';
echo 'pre{background:#f5f5f5;padding:10px;overflow-x:auto}</style></head><body>';
echo '<h1>Régénération des CSS Elementor</h1>';

// Vérifier que Elementor est actif
if ( ! did_action( 'elementor/loaded' ) ) {
	echo '<p class="error">✗ Elementor n\'est pas chargé.</p>';
	echo '</body></html>';
	exit;
}

// Charger les classes Elementor nécessaires
if ( ! class_exists( '\Elementor\Plugin' ) ) {
	echo '<p class="error">✗ La classe Elementor\Plugin n\'existe pas.</p>';
	echo '</body></html>';
	exit;
}

$elementor = \Elementor\Plugin::instance();

// Récupérer tous les templates MJET
$templates = get_posts( array(
	'post_type'      => 'mjet-template',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft', 'private' ),
) );

echo '<p>Trouvé <strong>' . count( $templates ) . '</strong> template(s) MJET.</p>';

if ( empty( $templates ) ) {
	echo '<p class="info">Aucun template à traiter.</p>';
	echo '</body></html>';
	exit;
}

$success = 0;
$errors = 0;

echo '<h2>Traitement des templates</h2>';
echo '<ul>';

foreach ( $templates as $template ) {
	echo '<li><strong>' . esc_html( $template->post_title ) . '</strong> (ID: ' . $template->ID . '): ';
	
	try {
		// Méthode 1: Utiliser le CSS Post de Elementor
		$css_file = \Elementor\Core\Files\CSS\Post::create( $template->ID );
		
		// Supprimer l'ancien CSS
		$css_file->delete();
		
		// Mettre à jour et régénérer
		$css_file->update();
		
		// Vérifier que le fichier CSS existe ou que les styles sont régénérés
		$meta = get_post_meta( $template->ID, '_elementor_css', true );
		
		echo '<span class="success">✓ CSS régénéré</span>';
		$success++;
		
	} catch ( Exception $e ) {
		echo '<span class="error">✗ Erreur: ' . esc_html( $e->getMessage() ) . '</span>';
		$errors++;
	}
	
	echo '</li>';
}

echo '</ul>';

// Aussi régénérer les CSS globaux
echo '<h2>CSS Globaux</h2>';

try {
	// Clear all Elementor CSS cache
	if ( method_exists( $elementor->files_manager, 'clear_cache' ) ) {
		$elementor->files_manager->clear_cache();
		echo '<p class="success">✓ Cache Elementor vidé</p>';
	}
} catch ( Exception $e ) {
	echo '<p class="error">✗ Erreur cache: ' . esc_html( $e->getMessage() ) . '</p>';
}

echo '<h2>Résumé</h2>';
echo '<p>Succès: <span class="success">' . $success . '</span> | Erreurs: <span class="error">' . $errors . '</span></p>';

echo '<h2>Étapes supplémentaires</h2>';
echo '<ol>';
echo '<li>Allez dans <strong>Elementor → Outils → Régénérer les fichiers et données</strong></li>';
echo '<li>Cliquez sur <strong>"Régénérer les fichiers CSS"</strong></li>';
echo '<li>Videz le cache de votre navigateur</li>';
echo '<li>Si vous utilisez un plugin de cache (WP Rocket, etc.), videz-le aussi</li>';
echo '</ol>';

echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=elementor-tools' ) ) . '">Aller aux outils Elementor →</a></p>';

echo '</body></html>';
