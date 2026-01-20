<?php
/**
 * Plugin Name: MJ Elementor Templates
 * Plugin URI:  https://www.mj-pery.be
 * Description: Créez des en-têtes, pieds de page et blocs personnalisés avec Elementor et affichez-les sur votre site. Équivalent simplifié de UAE Header Footer Builder.
 * Author:      MJ Pery
 * Author URI:  https://www.mj-pery.be
 * Text Domain: mj-elementor-templates
 * Domain Path: /languages
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Elementor tested up to: 3.33
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MJET_VERSION', '1.0.0' );
define( 'MJET_FILE', __FILE__ );
define( 'MJET_DIR', plugin_dir_path( __FILE__ ) );
define( 'MJET_URL', plugins_url( '/', __FILE__ ) );
define( 'MJET_PATH', plugin_basename( __FILE__ ) );

/**
 * Classe principale du plugin MJ Elementor Templates.
 */
final class MJ_Elementor_Templates {

	/**
	 * Instance unique.
	 *
	 * @var MJ_Elementor_Templates|null
	 */
	private static $instance = null;

	/**
	 * Instance Elementor Frontend.
	 *
	 * @var \Elementor\Frontend|null
	 */
	private static $elementor_instance = null;

	/**
	 * Template du thème actuel.
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJ_Elementor_Templates
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
		$this->template = get_template();

		// Vérifier si Elementor est disponible.
		$is_elementor_callable = ( defined( 'ELEMENTOR_VERSION' ) && is_callable( 'Elementor\Plugin::instance' ) );
		$required_version      = '3.5.0';
		$is_elementor_outdated = $is_elementor_callable && ! version_compare( ELEMENTOR_VERSION, $required_version, '>=' );

		if ( ! $is_elementor_callable || $is_elementor_outdated ) {
			add_action( 'admin_notices', array( $this, 'elementor_missing_notice' ) );
			return;
		}

		self::$elementor_instance = \Elementor\Plugin::instance();

		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Inclure les fichiers nécessaires.
	 */
	private function includes() {
		require_once MJET_DIR . 'includes/class-mjet-target-rules.php';
		require_once MJET_DIR . 'includes/class-mjet-admin.php';
		require_once MJET_DIR . 'includes/mjet-functions.php';
		require_once MJET_DIR . 'includes/class-mjet-widgets-loader.php';
		require_once MJET_DIR . 'includes/class-mjet-security-tweaks.php';
		require_once MJET_DIR . 'includes/class-mjet-login-customizer.php';
		require_once MJET_DIR . 'includes/class-mjet-theme-manager.php';

		// Migration depuis UAE (si UAE est installé).
		if ( is_admin() ) {
			require_once MJET_DIR . 'includes/class-mjet-uae-migration.php';
		}
	}

	/**
	 * Initialiser les hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_shortcode( 'mjet_template', array( $this, 'render_template_shortcode' ) );
		MJET_Security_Tweaks::init();
		MJET_Login_Customizer::init();
		MJET_Theme_Manager::init();

		// Compatibilité thème.
		$this->setup_theme_support();

		// Corriger les templates existants (une seule fois).
		add_action( 'admin_init', array( $this, 'maybe_fix_templates' ) );
	}

	/**
	 * Corriger les templates existants si nécessaire (une seule fois).
	 */
	public function maybe_fix_templates() {
		$version = get_option( 'mjet_template_fix_version', '0' );
		if ( version_compare( $version, '1.0.1', '<' ) ) {
			mjet_fix_existing_templates();
			mjet_add_elementor_cpt_support();
			update_option( 'mjet_template_fix_version', '1.0.1' );
			$version = '1.0.1';
		}

		if ( version_compare( $version, '1.0.2', '<' ) ) {
			mjet_apply_default_display_rules();
			update_option( 'mjet_template_fix_version', '1.0.2' );
		}
	}

	/**
	 * Charger les traductions.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mj-elementor-templates', false, dirname( MJET_PATH ) . '/languages' );
	}

	/**
	 * Enqueue scripts et styles frontend.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'mjet-style', MJET_URL . 'assets/css/mjet-frontend.css', array(), MJET_VERSION );

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$elementor = \Elementor\Plugin::instance();
			if ( method_exists( $elementor->frontend, 'enqueue_styles' ) ) {
				$elementor->frontend->enqueue_styles();
			}
		}

		// Charger les CSS des templates actifs.
		$this->enqueue_template_css( mjet_get_header_id() );
		$this->enqueue_template_css( mjet_get_footer_id() );
		$this->enqueue_template_css( mjet_get_before_footer_id() );
	}

	/**
	 * Enqueue CSS pour un template Elementor.
	 *
	 * @param int|false $template_id ID du template.
	 */
	private function enqueue_template_css( $template_id ) {
		if ( ! $template_id ) {
			return;
		}

		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
			$css_file->enqueue();
		}
		$this->enable_template_assets( $template_id );
	}

	/**
	 * Active les assets conditionnels Elementor associés au template.
	 *
	 * @param int $template_id ID du template.
	 * @return void
	 */
	private function enable_template_assets( $template_id ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$assets_meta_key = '_elementor_page_assets';
		if ( class_exists( '\Elementor\Core\Base\Elements_Iteration_Actions\Assets' ) ) {
			$assets_meta_key = \Elementor\Core\Base\Elements_Iteration_Actions\Assets::ASSETS_META_KEY;
		}

		$assets = get_post_meta( $template_id, $assets_meta_key, true );
		if ( empty( $assets ) || ! is_array( $assets ) ) {
			return;
		}

		$elementor = \Elementor\Plugin::instance();
		if ( isset( $elementor->assets_loader ) && method_exists( $elementor->assets_loader, 'enable_assets' ) ) {
			$elementor->assets_loader->enable_assets( $assets );
		}
	}
	/**
	 * Ajouter des classes au body.
	 *
	 * @param array $classes Classes existantes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( mjet_header_enabled() ) {
			$classes[] = 'mjet-header';
		}
		if ( mjet_footer_enabled() ) {
			$classes[] = 'mjet-footer';
		}
		$classes[] = 'mjet-template-' . $this->template;
		return $classes;
	}

	/**
	 * Configurer le support du thème.
	 */
	private function setup_theme_support() {
		// Liste des thèmes supportés nativement (incluant les thèmes enfants).
		$supported_themes = array(
			'hello-elementor',
			'hello-biz',
			'astra',
			'generatepress',
			'oceanwp',
			'kadence',
			'neve',
			'blocksy',
		);

		// Vérifier aussi le thème parent.
		$theme        = wp_get_theme();
		$parent_theme = '';
		if ( $theme->parent() ) {
			$parent_theme = $theme->parent()->get_template();
		}

		$is_supported = in_array( $this->template, $supported_themes, true ) 
			|| in_array( $parent_theme, $supported_themes, true );

		if ( $is_supported ) {
			require_once MJET_DIR . 'includes/themes/class-mjet-theme-compat.php';
		} else {
			// Support par défaut pour tous les thèmes.
			add_action( 'init', array( $this, 'setup_fallback_support' ) );
		}
	}

	/**
	 * Support par défaut pour les thèmes non supportés.
	 */
	public function setup_fallback_support() {
		if ( ! current_theme_supports( 'mj-elementor-templates' ) ) {
			require_once MJET_DIR . 'includes/themes/class-mjet-default-compat.php';
		}
	}

	/**
	 * Afficher le contenu du header.
	 */
	public static function get_header_content() {
		$header_id = mjet_get_header_id();
		if ( $header_id ) {
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $header_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Afficher le contenu du footer.
	 */
	public static function get_footer_content() {
		$footer_id = mjet_get_footer_id();
		if ( $footer_id ) {
			echo '<div class="footer-width-fixer">';
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $footer_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}
	}

	/**
	 * Afficher le contenu avant le footer.
	 */
	public static function get_before_footer_content() {
		$before_footer_id = mjet_get_before_footer_id();
		if ( $before_footer_id ) {
			echo '<div class="footer-width-fixer">';
			echo self::$elementor_instance->frontend->get_builder_content_for_display( $before_footer_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}
	}

	/**
	 * Shortcode pour afficher un template.
	 *
	 * @param array $atts Attributs du shortcode.
	 * @return string
	 */
	public function render_template_shortcode( $atts ) {
		$atts = shortcode_atts(
			array( 'id' => '' ),
			$atts,
			'mjet_template'
		);

		$id = ! empty( $atts['id'] ) ? intval( $atts['id'] ) : 0;
		if ( empty( $id ) ) {
			return '';
		}

		// Vérifier les permissions.
		if ( ! current_user_can( 'edit_post', $id ) ) {
			$post_status = get_post_status( $id );
			if ( in_array( $post_status, array( 'draft', 'private', 'pending' ), true ) || post_password_required( $id ) ) {
				return '';
			}
		}

		// Charger les styles du template.
		$this->enqueue_template_css( $id );

		return self::$elementor_instance->frontend->get_builder_content_for_display( $id );
	}

	/**
	 * Récupérer les paramètres d'un template.
	 *
	 * @param string $setting Nom du paramètre.
	 * @return mixed
	 */
	public static function get_settings( $setting = '' ) {
		if ( in_array( $setting, array( 'type_header', 'type_footer', 'type_before_footer' ), true ) ) {
			return self::get_template_id( $setting );
		}
		return '';
	}

	/**
	 * Récupérer l'ID d'un template par type.
	 *
	 * @param string $type Type de template.
	 * @return int|string
	 */
	public static function get_template_id( $type ) {
		$option = array(
			'location'  => 'mjet_target_include_locations',
			'exclusion' => 'mjet_target_exclude_locations',
			'users'     => 'mjet_target_user_roles',
		);

		$templates = MJET_Target_Rules::get_instance()->get_posts_by_conditions( 'mjet-template', $option );

		foreach ( $templates as $template ) {
			if ( get_post_meta( absint( $template['id'] ), 'mjet_template_type', true ) === $type ) {
				// Support Polylang.
				if ( function_exists( 'pll_current_language' ) ) {
					if ( pll_current_language( 'slug' ) === pll_get_post_language( $template['id'], 'slug' ) ) {
						return $template['id'];
					}
				} else {
					return $template['id'];
				}
			}
		}

		return '';
	}

	/**
	 * Notice si Elementor n'est pas installé.
	 */
	public function elementor_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$message = sprintf(
			/* translators: %1$s: plugin name, %2$s: Elementor */
			__( 'Le plugin %1$s requiert %2$s pour fonctionner.', 'mj-elementor-templates' ),
			'<strong>MJ Elementor Templates</strong>',
			'<strong>Elementor</strong>'
		);

		printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
	}
}

/**
 * Initialiser le plugin.
 */
function mjet_init() {
	MJ_Elementor_Templates::instance();
}
add_action( 'plugins_loaded', 'mjet_init' );

/**
 * Actions à l'activation.
 */
function mjet_activation() {
	// Flush rewrite rules à l'activation.
	flush_rewrite_rules();

	// Corriger les templates existants pour avoir le bon template Canvas.
	mjet_fix_existing_templates();

	// Ajouter le post type au support CPT d'Elementor.
	mjet_add_elementor_cpt_support();
}
register_activation_hook( MJET_FILE, 'mjet_activation' );

/**
 * Corriger les templates existants pour utiliser le template Canvas.
 */
function mjet_fix_existing_templates() {
	global $wpdb;

	$templates = get_posts( array(
		'post_type'      => 'mjet-template',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	) );

	foreach ( $templates as $template_id ) {
		// Supprimer l'ancien meta s'il existe.
		delete_post_meta( $template_id, '_wp_page_template' );
		// Ajouter le nouveau meta.
		add_post_meta( $template_id, '_wp_page_template', 'elementor_canvas', true );

		// S'assurer que le mode édition Elementor est défini à 'builder'.
		delete_post_meta( $template_id, '_elementor_edit_mode' );
		add_post_meta( $template_id, '_elementor_edit_mode', 'builder', true );

		// Si pas de version Elementor, la définir.
		$version = get_post_meta( $template_id, '_elementor_version', true );
		if ( empty( $version ) && defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $template_id, '_elementor_version', ELEMENTOR_VERSION );
		}

		// Si pas de données Elementor, initialiser avec un tableau vide.
		$data = get_post_meta( $template_id, '_elementor_data', true );
		if ( empty( $data ) ) {
			update_post_meta( $template_id, '_elementor_data', '[]' );
		}
	}
}

/**
 * Appliquer les règles d'affichage par défaut pour les types spéciaux.
 */
function mjet_apply_default_display_rules() {
	if ( ! class_exists( 'MJET_Admin' ) ) {
		return;
	}

	$templates = get_posts( array(
		'post_type'      => 'mjet-template',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'fields'         => 'ids',
	) );

	if ( empty( $templates ) ) {
		return;
	}

	foreach ( $templates as $template_id ) {
		$type = get_post_meta( $template_id, 'mjet_template_type', true );
		if ( empty( $type ) ) {
			continue;
		}

		$default_rules = MJET_Admin::get_default_include_rules_for_type( $type );
		if ( empty( $default_rules ) ) {
			continue;
		}

		$current_rules = get_post_meta( $template_id, 'mjet_target_include_locations', true );
		$needs_rules   = empty( $current_rules ) || empty( $current_rules['rule'] ) || ! is_array( $current_rules['rule'] );

		if ( $needs_rules ) {
			update_post_meta( $template_id, 'mjet_target_include_locations', $default_rules );
		}
	}
}

/**
 * Ajouter le post type au support CPT d'Elementor.
 */
function mjet_add_elementor_cpt_support() {
	$cpt_support = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );

	if ( ! is_array( $cpt_support ) ) {
		$cpt_support = array( 'page', 'post' );
	}

	if ( ! in_array( 'mjet-template', $cpt_support, true ) ) {
		$cpt_support[] = 'mjet-template';
		update_option( 'elementor_cpt_support', $cpt_support );
	}
}

/**
 * Actions à la désactivation.
 */
function mjet_deactivation() {
	flush_rewrite_rules();
}
register_deactivation_hook( MJET_FILE, 'mjet_deactivation' );
