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
	 * Definitions des widgets disponibles.
	 *
	 * @var array
	 */
	private static $widget_definitions = array(
		array(
			'file'  => 'includes/widgets/class-mjet-nav-menu.php',
			'class' => '\\MJET\\Widgets\\MJET_Nav_Menu',
		),
		array(
			'file'  => 'includes/widgets/class-mjet-youtube-channel.php',
			'class' => '\\MJET\\Widgets\\MJET_Youtube_Channel',
		),
		array(
			'file'  => 'includes/widgets/class-mjet-template-block.php',
			'class' => '\\MJET\\Widgets\\MJET_Template_Block',
		),
	);

	/**
	 * Widgets enregistres.
	 *
	 * @var array
	 */
	private static $registered_widgets = array();

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
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_styles' ) );

		$this->bootstrap_extensions();
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
		foreach ( self::$widget_definitions as $definition ) {
			$this->maybe_include_widget_file( $definition['file'] );

			if ( ! class_exists( $definition['class'] ) ) {
				continue;
			}

			$widget_instance = new $definition['class']();
			$widgets_manager->register( $widget_instance );

			self::$registered_widgets[ $widget_instance->get_name() ] = array(
				'name'     => $widget_instance->get_name(),
				'title'    => $widget_instance->get_title(),
				'icon'     => $widget_instance->get_icon(),
				'keywords' => method_exists( $widget_instance, 'get_keywords' ) ? (array) $widget_instance->get_keywords() : array(),
			);
		}
	}

	/**
	 * Retourne le catalogue des widgets disponibles.
	 *
	 * @return array
	 */
	public static function get_widget_catalog() {
		$catalog = array();

		foreach ( self::$widget_definitions as $definition ) {
			$file = trailingslashit( MJET_DIR ) . ltrim( $definition['file'], '/\\' );
			if ( file_exists( $file ) ) {
				require_once $file;
			}

			if ( ! class_exists( $definition['class'] ) ) {
				continue;
			}

			$instance = new $definition['class']();

			$catalog[] = array(
				'name'     => $instance->get_name(),
				'title'    => $instance->get_title(),
				'icon'     => $instance->get_icon(),
				'keywords' => method_exists( $instance, 'get_keywords' ) ? (array) $instance->get_keywords() : array(),
			);
		}

		return $catalog;
	}

	/**
	 * Inclut le fichier du widget si besoin.
	 *
	 * @param string $relative_path Chemin relatif.
	 */
	private function maybe_include_widget_file( $relative_path ) {
		$file = trailingslashit( MJET_DIR ) . ltrim( $relative_path, '/\\' );

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Retourne une version basée sur le filemtime pour forcer l actualisation des assets.
	 *
	 * @param string $relative_path Chemin relatif depuis la racine du plugin.
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$path = trailingslashit( MJET_DIR ) . ltrim( $relative_path, '/\\' );

		if ( file_exists( $path ) ) {
			$modified = filemtime( $path );
			if ( $modified ) {
				return (string) $modified;
			}
		}

		return defined( 'MJET_VERSION' ) ? MJET_VERSION : time();
	}

	/**
	 * Enregistrer les scripts.
	 */
	public function register_scripts() {
		$nav_menu_version      = $this->get_asset_version( 'assets/js/mjet-nav-menu.js' );
		$container_sticky_version = $this->get_asset_version( 'assets/js/mjet-container-sticky.js' );

		wp_register_script(
			'mjet-nav-menu',
			MJET_URL . 'assets/js/mjet-nav-menu.js',
			array( 'jquery' ),
			$nav_menu_version,
			true
		);

		wp_register_script(
			'mjet-container-sticky',
			MJET_URL . 'assets/js/mjet-container-sticky.js',
			array( 'elementor-frontend' ),
			$container_sticky_version,
			true
		);
	}

	/**
	 * Enregistrer les styles.
	 */
	public function register_styles() {
		$nav_menu_version        = $this->get_asset_version( 'assets/css/mjet-nav-menu.css' );
		$container_sticky_version = $this->get_asset_version( 'assets/css/mjet-container-sticky.css' );
		$youtube_version          = $this->get_asset_version( 'assets/css/mjet-youtube-channel.css' );

		wp_register_style(
			'mjet-nav-menu',
			MJET_URL . 'assets/css/mjet-nav-menu.css',
			array(),
			$nav_menu_version
		);

		wp_register_style(
			'mjet-container-sticky',
			MJET_URL . 'assets/css/mjet-container-sticky.css',
			array(),
			$container_sticky_version
		);

		wp_register_style(
			'mjet-youtube-channel',
			MJET_URL . 'assets/css/mjet-youtube-channel.css',
			array(),
			$youtube_version
		);
	}

	/**
	 * Enqueue les styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'mjet-nav-menu'
		);

		wp_enqueue_style(
			'mjet-container-sticky'
		);

		wp_enqueue_style(
			'mjet-youtube-channel'
		);
	}

	/**
	 * Charge les extensions front/back additionnelles.
	 */
	private function bootstrap_extensions() {
		$this->maybe_include_widget_file( 'includes/modules/class-mjet-container-sticky.php' );

		if ( class_exists( '\\MJET\\Modules\\Container_Sticky' ) ) {
			\MJET\Modules\Container_Sticky::instance();
		}
	}
}

// Initialiser le loader.
MJET_Widgets_Loader::instance();
