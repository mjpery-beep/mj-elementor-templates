<?php
/**
 * Compatibilité par défaut pour les thèmes non supportés nativement.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe de compatibilité par défaut.
 */
class MJET_Default_Compat {

	/**
	 * Instance unique.
	 *
	 * @var MJET_Default_Compat|null
	 */
	private static $instance = null;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJET_Default_Compat
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur.
	 */
	private function __construct() {
		add_action( 'wp', array( $this, 'hooks' ), 99 );
	}

	/**
	 * Enregistrer les hooks.
	 */
	public function hooks() {
		if ( mjet_header_enabled() ) {
			add_action( 'get_header', array( $this, 'override_header' ), 99 );
			add_filter( 'show_admin_bar', array( $this, 'filter_admin_bar' ) );
		}

		if ( mjet_footer_enabled() ) {
			add_action( 'get_footer', array( $this, 'override_footer' ), 99 );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'wp_footer', array( $this, 'render_before_footer' ), 5 );
		}
	}

	/**
	 * Filtre pour la barre d'admin (garder visible si nécessaire).
	 *
	 * @param bool $show_admin_bar Afficher ou non.
	 * @return bool
	 */
	public function filter_admin_bar( $show_admin_bar ) {
		return $show_admin_bar;
	}

	/**
	 * Remplacer le header.
	 *
	 * @param string $name Nom du header.
	 */
	public function override_header( $name ) {
		require MJET_DIR . 'includes/themes/templates/header.php';
		
		$templates = array();
		if ( '' !== $name ) {
			$templates[] = "header-{$name}.php";
		}
		$templates[] = 'header.php';
		
		// Bloquer le chargement du header du thème.
		remove_all_actions( 'wp_head' );
		ob_start();
		locate_template( $templates, true );
		ob_end_clean();
	}

	/**
	 * Remplacer le footer.
	 *
	 * @param string $name Nom du footer.
	 */
	public function override_footer( $name ) {
		require MJET_DIR . 'includes/themes/templates/footer.php';
		
		$templates = array();
		if ( '' !== $name ) {
			$templates[] = "footer-{$name}.php";
		}
		$templates[] = 'footer.php';
		
		// Bloquer le chargement du footer du thème.
		remove_all_actions( 'wp_footer' );
		ob_start();
		locate_template( $templates, true );
		ob_end_clean();
	}

	/**
	 * Afficher le before footer.
	 */
	public function render_before_footer() {
		mjet_render_before_footer();
	}
}

// Initialiser la compatibilité par défaut.
MJET_Default_Compat::instance();
