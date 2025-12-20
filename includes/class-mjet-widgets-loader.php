<?php
/**
 * Chargement des widgets Elementor MJET.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe pour charger les widgets Elementor.
 */
class MJET_Widgets_Loader {

	/**
	 * Instance unique.
	 *
	 * @var MJET_Widgets_Loader|null
	 */
	private static $instance = null;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJET_Widgets_Loader
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
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Enregistrer la catégorie MJET.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Gestionnaire d'éléments.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'mjet-widgets',
			array(
				'title' => __( 'MJ Templates', 'mj-elementor-templates' ),
				'icon'  => 'eicon-header',
			)
		);
	}

	/**
	 * Enregistrer les widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Gestionnaire de widgets.
	 */
	public function register_widgets( $widgets_manager ) {
		// Inclure et enregistrer les widgets.
		require_once MJET_DIR . 'includes/widgets/class-mjet-nav-menu.php';
		require_once MJET_DIR . 'includes/widgets/class-mjet-youtube-channel.php';

		$widgets_manager->register( new \MJET\Widgets\MJET_Nav_Menu() );
		$widgets_manager->register( new \MJET\Widgets\MJET_Youtube_Channel() );
	}

	/**
	 * Enregistrer les scripts.
	 */
	public function register_scripts() {
		wp_register_script(
			'mjet-nav-menu',
			MJET_URL . 'assets/js/mjet-nav-menu.js',
			array( 'jquery' ),
			MJET_VERSION,
			true
		);
	}

	/**
	 * Enqueue les styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'mjet-nav-menu',
			MJET_URL . 'assets/css/mjet-nav-menu.css',
			array(),
			MJET_VERSION
		);

		wp_enqueue_style(
			'mjet-youtube-channel',
			MJET_URL . 'assets/css/mjet-youtube-channel.css',
			array(),
			MJET_VERSION
		);
	}
}

// Initialiser le loader.
MJET_Widgets_Loader::instance();
