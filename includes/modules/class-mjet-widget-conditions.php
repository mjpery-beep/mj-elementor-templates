<?php
/**
 * Conditions d'affichage Elementor basées sur le statut de connexion.
 * Utilise des classes CSS pour un masquage instantané (sans cache).
 *
 * @package mj-elementor-templates
 */

namespace MJET\Modules;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Element_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajoute des conditions d'affichage connecté/déconnecté à tous les éléments Elementor.
 * Fonctionne via CSS basé sur la classe body "logged-in" de WordPress.
 */
class Widget_Conditions {

	/**
	 * Instance unique.
	 *
	 * @var Widget_Conditions|null
	 */
	private static $instance = null;

	/**
	 * Retourne l'instance unique.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé.
	 */
	private function __construct() {
		// Enregistrement des contrôles via hooks spécifiques.
		add_action( 'elementor/element/column/section_advanced/after_section_end', array( $this, 'add_condition_fields' ), 10, 2 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', array( $this, 'add_condition_fields' ), 10, 2 );
		add_action( 'elementor/element/common/_section_style/after_section_end', array( $this, 'add_condition_fields' ), 10, 2 );
		add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'add_condition_fields' ), 10, 2 );

		// Ajouter la classe CSS sur l'élément avant le rendu.
		add_action( 'elementor/frontend/widget/before_render', array( $this, 'add_render_attributes' ), 10, 1 );
		add_action( 'elementor/frontend/section/before_render', array( $this, 'add_render_attributes' ), 10, 1 );
		add_action( 'elementor/frontend/column/before_render', array( $this, 'add_render_attributes' ), 10, 1 );
		add_action( 'elementor/frontend/container/before_render', array( $this, 'add_render_attributes' ), 10, 1 );

		// Injecter le CSS dans le frontend.
		add_action( 'wp_head', array( $this, 'print_frontend_css' ), 100 );
	}

	/**
	 * Ajoute les champs de condition dans l'onglet Avancé.
	 *
	 * @param mixed  $element    Élément Elementor.
	 * @param string $section_id ID de la section.
	 */
	public function add_condition_fields( $element, $section_id = null ): void {
		$element->start_controls_section(
			'mjet_user_conditions_section',
			array(
				'tab'   => Controls_Manager::TAB_ADVANCED,
				'label' => __( 'Conditions Utilisateur', 'mj-elementor-templates' ),
			)
		);

		$element->add_control(
			'mjet_user_condition',
			array(
				'label'        => __( 'Afficher pour', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => '',
				'options'      => array(
					''           => __( 'Tous les visiteurs', 'mj-elementor-templates' ),
					'logged_in'  => __( 'Utilisateurs connectés uniquement', 'mj-elementor-templates' ),
					'logged_out' => __( 'Visiteurs déconnectés uniquement', 'mj-elementor-templates' ),
				),
				'render_type'  => 'template',
				'prefix_class' => 'mjet-show-',
			)
		);

		$element->add_control(
			'mjet_condition_info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'L\'élément sera masqué/affiché instantanément selon l\'état de connexion (pas de cache).', 'mj-elementor-templates' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array(
					'mjet_user_condition!' => '',
				),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Ajoute les attributs de rendu (classe CSS) sur l'élément.
	 *
	 * @param Element_Base $element Élément Elementor.
	 */
	public function add_render_attributes( $element ): void {
		$settings  = $element->get_settings_for_display();
		$condition = isset( $settings['mjet_user_condition'] ) ? $settings['mjet_user_condition'] : '';

		if ( empty( $condition ) ) {
			return;
		}

		// Ajouter une classe pour identifier les éléments avec condition.
		$element->add_render_attribute( '_wrapper', 'class', 'mjet-has-condition' );
	}

	/**
	 * Imprime le CSS pour gérer la visibilité basée sur la classe body "logged-in".
	 * WordPress ajoute automatiquement la classe "logged-in" au body quand l'utilisateur est connecté.
	 */
	public function print_frontend_css(): void {
		// Ne pas afficher dans l'éditeur.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			if ( ! empty( Plugin::$instance->editor ) && Plugin::$instance->editor->is_edit_mode() ) {
				return;
			}
			if ( ! empty( Plugin::$instance->preview ) && Plugin::$instance->preview->is_preview_mode() ) {
				return;
			}
		}
		?>
		<style id="mjet-user-conditions-css">
			/* Masquer les éléments "connectés uniquement" quand l'utilisateur n'est PAS connecté */
			body:not(.logged-in) .mjet-show-logged_in {
				display: none !important;
			}
			
			/* Masquer les éléments "déconnectés uniquement" quand l'utilisateur EST connecté */
			body.logged-in .mjet-show-logged_out {
				display: none !important;
			}
		</style>
		<?php
	}
}
