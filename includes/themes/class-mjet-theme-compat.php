<?php
/**
 * Compatibilité pour les thèmes supportés nativement.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe de compatibilité pour les thèmes populaires.
 */
class MJET_Theme_Compat {

	/**
	 * Instance unique.
	 *
	 * @var MJET_Theme_Compat|null
	 */
	private static $instance = null;

	/**
	 * Thème actuel.
	 *
	 * @var string
	 */
	private $template;

	/**
	 * Thème parent.
	 *
	 * @var string
	 */
	private $parent_template;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJET_Theme_Compat
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
		$this->template        = get_template();
		$this->parent_template = get_template();
		
		// Vérifier si c'est un thème enfant.
		$theme = wp_get_theme();
		if ( $theme->parent() ) {
			$this->parent_template = $theme->parent()->get_template();
		}

		$this->init_theme_support();
	}

	/**
	 * Initialiser le support du thème.
	 */
	private function init_theme_support() {
		// Vérifier d'abord par le thème parent pour les thèmes enfants.
		$theme_to_check = $this->parent_template;
		
		switch ( $theme_to_check ) {
			case 'hello-elementor':
			case 'hello-biz':
				$this->hello_elementor_support();
				break;

			case 'astra':
				$this->astra_support();
				break;

			case 'generatepress':
				$this->generatepress_support();
				break;

			case 'oceanwp':
				$this->oceanwp_support();
				break;

			case 'kadence':
				$this->kadence_support();
				break;

			case 'neve':
				$this->neve_support();
				break;

			case 'blocksy':
				$this->blocksy_support();
				break;
		}
	}

	/**
	 * Support Hello Elementor et thèmes enfants (hello-biz, etc.).
	 */
	private function hello_elementor_support() {
		add_action( 'template_redirect', array( $this, 'hello_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'hello_setup_footer' ) );
	}

	/**
	 * Configurer le header pour Hello Elementor.
	 */
	public function hello_setup_header() {
		if ( mjet_header_enabled() ) {
			// Supprimer les actions du thème Hello Elementor.
			remove_action( 'hello_elementor_header', 'hello_elementor_header_template' );
			
			// Supprimer aussi les actions potentielles de hello-biz.
			remove_action( 'hello_biz_header', 'hello_biz_header_template' );
			
			// Désactiver le header par défaut via le filtre hello-biz.
			add_filter( 'hello-plus-theme/display-default-header', '__return_false' );
			
			// Ajouter notre header.
			add_action( 'hello_elementor_header', 'mjet_render_header' );
			add_action( 'hello_biz_header', 'mjet_render_header' );
			
			// Pour hello-biz, injecter après wp_body_open.
			add_action( 'wp_body_open', 'mjet_render_header', 20 );
		}
	}

	/**
	 * Configurer le footer pour Hello Elementor.
	 */
	public function hello_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'hello_elementor_footer', 'hello_elementor_footer_template' );
			add_action( 'hello_elementor_footer', 'mjet_render_footer' );
			
			// Désactiver le footer par défaut via le filtre hello-biz.
			add_filter( 'hello-plus-theme/display-default-footer', '__return_false' );
			
			// Pour hello-biz, injecter avant wp_footer.
			add_action( 'wp_footer', 'mjet_render_footer', 5 );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'hello_elementor_footer', 'mjet_render_before_footer', 5 );
			add_action( 'wp_footer', 'mjet_render_before_footer', 4 );
		}
	}

	/**
	 * Support Astra.
	 */
	private function astra_support() {
		add_action( 'template_redirect', array( $this, 'astra_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'astra_setup_footer' ) );
	}

	/**
	 * Configurer le header pour Astra.
	 */
	public function astra_setup_header() {
		if ( mjet_header_enabled() ) {
			remove_action( 'astra_header', 'astra_header_markup' );
			add_action( 'astra_header', 'mjet_render_header' );
		}
	}

	/**
	 * Configurer le footer pour Astra.
	 */
	public function astra_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'astra_footer', 'astra_footer_markup' );
			add_action( 'astra_footer', 'mjet_render_footer' );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'astra_footer', 'mjet_render_before_footer', 5 );
		}
	}

	/**
	 * Support GeneratePress.
	 */
	private function generatepress_support() {
		add_action( 'template_redirect', array( $this, 'generatepress_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'generatepress_setup_footer' ) );
	}

	/**
	 * Configurer le header pour GeneratePress.
	 */
	public function generatepress_setup_header() {
		if ( mjet_header_enabled() ) {
			remove_action( 'generate_header', 'generate_construct_header' );
			add_action( 'generate_header', 'mjet_render_header' );
		}
	}

	/**
	 * Configurer le footer pour GeneratePress.
	 */
	public function generatepress_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'generate_footer', 'generate_construct_footer_widgets' );
			remove_action( 'generate_footer', 'generate_construct_footer' );
			add_action( 'generate_footer', 'mjet_render_footer' );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'generate_footer', 'mjet_render_before_footer', 5 );
		}
	}

	/**
	 * Support OceanWP.
	 */
	private function oceanwp_support() {
		add_action( 'template_redirect', array( $this, 'oceanwp_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'oceanwp_setup_footer' ) );
	}

	/**
	 * Configurer le header pour OceanWP.
	 */
	public function oceanwp_setup_header() {
		if ( mjet_header_enabled() ) {
			remove_action( 'ocean_top_bar', 'oceanwp_top_bar_template' );
			remove_action( 'ocean_header', 'oceanwp_header_template' );
			add_action( 'ocean_header', 'mjet_render_header' );
		}
	}

	/**
	 * Configurer le footer pour OceanWP.
	 */
	public function oceanwp_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'ocean_footer', 'oceanwp_footer_template' );
			add_action( 'ocean_footer', 'mjet_render_footer' );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'ocean_footer', 'mjet_render_before_footer', 5 );
		}
	}

	/**
	 * Support Kadence.
	 */
	private function kadence_support() {
		add_action( 'template_redirect', array( $this, 'kadence_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'kadence_setup_footer' ) );
	}

	/**
	 * Configurer le header pour Kadence.
	 */
	public function kadence_setup_header() {
		if ( mjet_header_enabled() ) {
			remove_action( 'kadence_header', 'Kadence\header_markup' );
			add_action( 'kadence_header', 'mjet_render_header' );
		}
	}

	/**
	 * Configurer le footer pour Kadence.
	 */
	public function kadence_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'kadence_footer', 'Kadence\footer_markup' );
			add_action( 'kadence_footer', 'mjet_render_footer' );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'kadence_footer', 'mjet_render_before_footer', 5 );
		}
	}

	/**
	 * Support Neve.
	 */
	private function neve_support() {
		add_action( 'template_redirect', array( $this, 'neve_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'neve_setup_footer' ) );
	}

	/**
	 * Configurer le header pour Neve.
	 */
	public function neve_setup_header() {
		if ( mjet_header_enabled() ) {
			remove_action( 'neve_do_header', 'neve_header_output' );
			add_action( 'neve_do_header', 'mjet_render_header' );
		}
	}

	/**
	 * Configurer le footer pour Neve.
	 */
	public function neve_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'neve_do_footer', 'neve_footer_output' );
			add_action( 'neve_do_footer', 'mjet_render_footer' );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'neve_do_footer', 'mjet_render_before_footer', 5 );
		}
	}

	/**
	 * Support Blocksy.
	 */
	private function blocksy_support() {
		add_action( 'template_redirect', array( $this, 'blocksy_setup_header' ) );
		add_action( 'template_redirect', array( $this, 'blocksy_setup_footer' ) );
	}

	/**
	 * Configurer le header pour Blocksy.
	 */
	public function blocksy_setup_header() {
		if ( mjet_header_enabled() ) {
			remove_action( 'blocksy:header:before', 'blocksy_output_header' );
			add_action( 'blocksy:header:before', 'mjet_render_header' );
		}
	}

	/**
	 * Configurer le footer pour Blocksy.
	 */
	public function blocksy_setup_footer() {
		if ( mjet_footer_enabled() ) {
			remove_action( 'blocksy:footer:after', 'blocksy_output_footer' );
			add_action( 'blocksy:footer:after', 'mjet_render_footer' );
		}

		if ( mjet_before_footer_enabled() ) {
			add_action( 'blocksy:footer:after', 'mjet_render_before_footer', 5 );
		}
	}
}

// Initialiser la compatibilité du thème.
MJET_Theme_Compat::instance();
