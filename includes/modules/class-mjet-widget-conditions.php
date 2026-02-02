<?php
/**
 * Conditions d'affichage Elementor.
 *
 * @package mj-elementor-templates
 */

namespace MJET\Modules;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajoute une section Conditions à tous les widgets Elementor.
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
	 * Constructeur.
	 */
	private function __construct() {
		add_action( 'elementor/element/common/section_advanced/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/element/container/section_effects/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/element/column/section_advanced/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_filter( 'elementor/frontend/widget/should_render', array( $this, 'filter_should_render' ), 10, 2 );
		add_filter( 'elementor/frontend/section/should_render', array( $this, 'filter_should_render' ), 10, 2 );
		add_filter( 'elementor/frontend/column/should_render', array( $this, 'filter_should_render' ), 10, 2 );
		add_filter( 'elementor/frontend/container/should_render', array( $this, 'filter_should_render' ), 10, 2 );
		add_filter( 'elementor/frontend/element/should_render', array( $this, 'filter_should_render' ), 10, 2 );
		add_action( 'elementor/frontend/element/before_render', array( $this, 'maybe_hide_element' ), 10, 1 );
		add_filter( 'elementor/widget/render_content', array( $this, 'filter_widget_output' ), 10, 2 );
	}

	/**
	 * Ajoute la section Conditions dans l'onglet Avancé.
	 *
	 * @param Element_Base $element    Widget courant.
	 * @param string       $section_id Identifiant de la section.
	 */
	public function register_controls( Element_Base $element, $section_id ) {
		if ( method_exists( $element, 'get_controls' ) ) {
			$controls = $element->get_controls();
			if ( isset( $controls['mjet_widget_show_only_logged_in'] ) || isset( $controls['mjet_widget_show_only_logged_out'] ) ) {
				return;
			}
		}

		$element->start_controls_section(
			'mjet_widget_conditions_section',
			array(
				'label' => __( 'Conditions', 'mj-elementor-templates' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'mjet_widget_show_only_logged_in',
			array(
				'label'        => __( "Afficher seulement si l'utilisateur est connecté", 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$element->add_control(
			'mjet_widget_show_only_logged_out',
			array(
				'label'        => __( "Afficher seulement si l'utilisateur est déconnecté", 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Masque le widget si nécessaire.
	 *
	 * @param string       $content Contenu HTML du widget.
	 * @param Element_Base $element Instance du widget.
	 *
	 * @return string
	 */
	public function filter_widget_output( $content, $element ) {
		if ( ! $element instanceof Element_Base ) {
			return $content;
		}

		if ( $this->should_display( $element ) ) {
			return $content;
		}

		if ( $this->is_elementor_editor() ) {
			$visibility = $this->get_visibility_requirements( $element );
			$message    = esc_html__( "Ce widget est masqué par ses conditions d'affichage.", 'mj-elementor-templates' );

			if ( $visibility['require_login'] && $visibility['require_logout'] ) {
				$message = esc_html__( 'Ce widget combine des conditions incompatibles.', 'mj-elementor-templates' );
			} elseif ( $visibility['require_login'] ) {
				$message = esc_html__( 'Ce widget est visible uniquement pour les utilisateurs connectés.', 'mj-elementor-templates' );
			} elseif ( $visibility['require_logout'] ) {
				$message = esc_html__( 'Ce widget est visible uniquement pour les utilisateurs déconnectés.', 'mj-elementor-templates' );
			}

			return '<div class="elementor-alert elementor-alert-info">' . $message . '</div>';
		}

		return '';
	}

	/**
	 * Filtre should_render des éléments Elementor.
	 *
	 * @param bool         $should_render Valeur actuelle.
	 * @param Element_Base $element       Élément en cours.
	 *
	 * @return bool
	 */
	public function filter_should_render( $should_render, Element_Base $element ) {
		if ( ! $should_render ) {
			return false;
		}

		if ( $this->should_display( $element ) ) {
			return true;
		}

		return $this->is_elementor_editor();
	}

	/**
	 * Intercepte le rendu pour les éléments non autorisés.
	 *
	 * @param Element_Base $element Élément Elementor en cours.
	 */
	public function maybe_hide_element( Element_Base $element ): void {
		if ( $this->is_elementor_editor() ) {
			return;
		}

		if ( $this->should_display( $element ) ) {
			return;
		}

		if ( method_exists( $element, 'set_should_render' ) ) {
			$element->set_should_render( false );
		}

		if ( method_exists( $element, 'add_render_attribute' ) ) {
			$element->add_render_attribute( '_wrapper', 'style', 'display:none !important;' );
		}
	}

	/**
	 * Détermine si l'élément doit être affiché.
	 *
	 * @param Element_Base $element Élément Elementor en cours.
	 *
	 * @return bool
	 */
	private function should_display( Element_Base $element ): bool {
		$settings = array();

		$visibility = $this->get_visibility_requirements( $element );

		if ( $visibility['require_login'] && ! is_user_logged_in() ) {
			return false;
		}

		if ( $visibility['require_logout'] && is_user_logged_in() ) {
			return false;
		}

		return true;
	}

	/**
	 * Calcule les contraintes d'affichage associées à l'élément.
	 *
	 * @param Element_Base $element Élément Elementor en cours.
	 *
	 * @return array{
	 *     require_login: bool,
	 *     require_logout: bool,
	 * }
	 */
	private function get_visibility_requirements( Element_Base $element ): array {
		$settings = array();

		if ( method_exists( $element, 'get_settings' ) ) {
			$settings = (array) $element->get_settings();
		}

		if ( method_exists( $element, 'get_settings_for_display' ) ) {
			$display_settings = (array) $element->get_settings_for_display();

			foreach ( array( 'mjet_widget_show_only_logged_in', 'mjet_widget_show_only_logged_out' ) as $key ) {
				if ( ! array_key_exists( $key, $settings ) && isset( $display_settings[ $key ] ) ) {
					$settings[ $key ] = $display_settings[ $key ];
				}
			}
		}

		return array(
			'require_login'  => isset( $settings['mjet_widget_show_only_logged_in'] ) && 'yes' === $settings['mjet_widget_show_only_logged_in'],
			'require_logout' => isset( $settings['mjet_widget_show_only_logged_out'] ) && 'yes' === $settings['mjet_widget_show_only_logged_out'],
		);
	}

	/**
	 * Détecte le mode éditeur Elementor.
	 */
	private function is_elementor_editor(): bool {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return false;
		}

		$plugin = Plugin::$instance;

		if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}

		if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}

		return false;
	}
}
