<?php
/**
 * Module Sticky pour les containers Elementor natifs.
 *
 * @package mj-elementor-templates
 */

namespace MJET\Modules;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajoute un panneau Sticky à tous les containers Elementor.
 */
class Container_Sticky {
	/**
	 * Instance unique.
	 *
	 * @var Container_Sticky|null
	 */
	private static $instance = null;

	/**
	 * Seuil par défaut avant activation du sticky.
	 */
	private const DEFAULT_THRESHOLD = 0;

	/**
	 * Durée par défaut des transitions.
	 */
	private const DEFAULT_TRANSITION = 300;

	/**
	 * Récupère l'instance unique.
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
		add_action( 'elementor/element/container/section_effects/after_section_end', array( $this, 'register_controls' ), 10, 2 );
		add_action( 'elementor/frontend/container/before_render', array( $this, 'before_render' ) );
		add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Ajoute la section Sticky dans l'onglet Avancé des containers.
	 *
	 * @param Element_Base $element Instance du container.
	 * @param array        $args    Arguments du hook.
	 */
	public function register_controls( Element_Base $element, array $args ) {
		if ( 'container' !== $element->get_name() ) {
			return;
		}

		$element->start_controls_section(
			'mjet_sticky_section',
			array(
				'label' => __( 'Sticky', 'mj-elementor-templates' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'mjet_sticky_enable',
			array(
				'label'        => __( 'Activer Sticky', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$element->add_control(
			'mjet_sticky_threshold',
			array(
				'label'      => __( 'Point de déclenchement (px)', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 600,
					),
				),
				'default'    => array(
					'size' => self::DEFAULT_THRESHOLD,
				),
				'condition'  => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_control(
			'mjet_sticky_offset',
			array(
				'label'      => __( 'Décalage supérieur (px)', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'default'    => array(
					'size' => 0,
				),
				'condition'  => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_control(
			'mjet_sticky_scroll_background',
			array(
				'label'     => __( 'Fond à l activation', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_control(
			'mjet_sticky_scroll_background_image',
			array(
				'label'     => __( 'Image lors du scroll', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => array( 'active' => false ),
				'condition' => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_control(
			'mjet_sticky_scroll_height',
			array(
				'label'      => __( 'Hauteur lors du scroll (px)', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 40,
						'max' => 400,
					),
				),
				'condition'  => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_control(
			'mjet_sticky_animation',
			array(
				'label'   => __( 'Animation', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'none'  => __( 'Aucune', 'mj-elementor-templates' ),
					'fade'  => __( 'Fondu', 'mj-elementor-templates' ),
					'slide' => __( 'Glissement', 'mj-elementor-templates' ),
				),
				'default' => 'none',
				'condition' => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'mjet_sticky_border',
				'selector'  => '{{WRAPPER}}.mjet-sticky--scrolled',
				'condition' => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'      => 'mjet_sticky_box_shadow',
				'selector'  => '{{WRAPPER}}.mjet-sticky--scrolled',
				'condition' => array(
					'mjet_sticky_enable' => 'yes',
				),
			)
		);

		$element->add_control(
			'mjet_sticky_custom_css',
			array(
				'label'       => __( 'CSS personnalisé (sticky)', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::CODE,
				'language'    => 'css',
				'rows'        => 8,
				'render_type' => 'none',
				'condition'   => array(
					'mjet_sticky_enable' => 'yes',
				),
				'description' => __( 'Utilisez selector, {{WRAPPER}} / __WRAPPER_STICKY__ ou {{WRAPPER_NO_STICKY}} / __WRAPPER_NO_STICKY__ pour cibler le conteneur sticky. Les variantes *_NO_STICKY s\'appliquent uniquement lorsque le conteneur n\'est pas sticky.', 'mj-elementor-templates' ),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Ajuste les attributs de rendu du container.
	 *
	 * @param Element_Base $element Container actuel.
	 */
	public function before_render( Element_Base $element ) {
		if ( 'container' !== $element->get_name() ) {
			return;
		}

		$settings = $element->get_settings_for_display();

		if ( 'yes' !== ( $settings['mjet_sticky_enable'] ?? '' ) ) {
			return;
		}

		$element->add_render_attribute( '_wrapper', 'class', 'mjet-sticky-container' );
		$element->add_render_attribute( '_wrapper', 'data-mjet-sticky', 'true' );
		wp_enqueue_style( 'mjet-container-sticky' );
		wp_enqueue_script( 'mjet-container-sticky' );
		$threshold_value = $settings['mjet_sticky_threshold']['size'] ?? null;
		if ( null === $threshold_value || ! is_numeric( $threshold_value ) ) {
			$threshold_value = self::DEFAULT_THRESHOLD;
		}
		$threshold_value = max( 0, (int) $threshold_value );
		$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-threshold', (string) $threshold_value );
		$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-transition', (string) self::DEFAULT_TRANSITION );

		$offset_value = $settings['mjet_sticky_offset']['size'] ?? 0;
		if ( ! is_numeric( $offset_value ) ) {
			$offset_value = 0;
		}
		$offset_value = max( 0, (int) $offset_value );
		$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-offset', $offset_value . 'px' );

		$background = $settings['mjet_sticky_scroll_background'] ?? '';
		if ( ! empty( $background ) ) {
			$sanitized_background = sanitize_hex_color( $background );
			if ( $sanitized_background ) {
				$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-bg', $sanitized_background );
			} else {
				$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-bg', sanitize_text_field( $background ) );
			}
		}

		$background_image = $settings['mjet_sticky_scroll_background_image']['url'] ?? '';
		if ( ! empty( $background_image ) ) {
			$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-bg-image', esc_url_raw( $background_image ) );
		}

		$height_value = $settings['mjet_sticky_scroll_height']['size'] ?? null;
		if ( null !== $height_value && is_numeric( $height_value ) ) {
			$height_value = max( 0, (int) $height_value );
			$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-height', $height_value . 'px' );
		}

		$animation = $settings['mjet_sticky_animation'] ?? 'none';
		if ( in_array( $animation, array( 'fade', 'slide' ), true ) ) {
			$element->add_render_attribute( '_wrapper', 'data-mjet-sticky-animation', $animation );
		}

		$custom_css = isset( $settings['mjet_sticky_custom_css'] ) ? trim( (string) $settings['mjet_sticky_custom_css'] ) : '';
		if ( ! empty( $custom_css ) ) {
			$wrapper_selector = '.elementor-element-' . $element->get_id() . '.mjet-sticky--scrolled';
			$prepared_css     = str_replace(
				array(
					'{{WRAPPER}}',
					'__WRAPPER_STICKY__',
					'{{WRAPPER_NO_STICKY}}',
					'__WRAPPER_NO_STICKY__',
					'selector',
				),
				array(
					$wrapper_selector,
					$wrapper_selector,
					'.elementor-element-' . $element->get_id(),
					'.elementor-element-' . $element->get_id(),
					$wrapper_selector,
				),
				$custom_css
			);

			if ( false === strpos( $prepared_css, '{' ) ) {
				$prepared_css = $wrapper_selector . ' {' . $prepared_css . "\n}";
			}

			$prepared_css = trim( $prepared_css );
			if ( '' !== $prepared_css ) {
				wp_add_inline_style( 'mjet-container-sticky', $prepared_css );
			}
		}
	}

	/**
	 * Enqueue des assets sur le front.
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'mjet-container-sticky' );
		wp_enqueue_script( 'mjet-container-sticky' );
	}

	/**
	 * Enqueue des assets dans l'éditeur Elementor.
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_style( 'mjet-container-sticky' );
		wp_enqueue_script( 'mjet-container-sticky' );
	}

}
