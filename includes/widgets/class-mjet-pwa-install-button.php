<?php
/**
 * Widget Bouton d'installation PWA pour Elementor.
 *
 * @package mj-elementor-templates
 */

namespace MJET\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget affichant un bouton d'installation d'application PWA.
 */
class MJET_PWA_Install_Button extends Widget_Base {

	/**
	 * Identifiant unique du widget.
	 */
	public function get_name() {
		return 'mjet-pwa-install-button';
	}

	/**
	 * Titre affiche dans Elementor.
	 */
	public function get_title() {
		return __( 'Bouton Installation PWA', 'mj-elementor-templates' );
	}

	/**
	 * Icône Elementor.
	 */
	public function get_icon() {
		return 'eicon-device-mobile';
	}

	/**
	 * Categories Elementor.
	 */
	public function get_categories() {
		return array( 'mjet-widgets' );
	}

	/**
	 * Mots-cles de recherche.
	 */
	public function get_keywords() {
		return array( 'pwa', 'app', 'install', 'application', 'mjet' );
	}

	/**
	 * Dependances scripts.
	 */
	public function get_script_depends() {
		return array( 'mjet-pwa-install' );
	}

	/**
	 * Dependances styles.
	 */
	public function get_style_depends() {
		return array( 'mjet-pwa-install' );
	}

	/**
	 * Enregistrement des contrôles Elementor.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Contrôles de contenu.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Bouton', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'button_label',
			array(
				'label'       => __( 'Texte du bouton', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Installer l\'application', 'mj-elementor-templates' ),
				'placeholder' => __( 'Installer l\'application', 'mj-elementor-templates' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'button_size',
			array(
				'label'   => __( 'Taille du bouton', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'xs' => __( 'XS', 'mj-elementor-templates' ),
					'sm' => __( 'S', 'mj-elementor-templates' ),
					'md' => __( 'M', 'mj-elementor-templates' ),
					'lg' => __( 'L', 'mj-elementor-templates' ),
					'xl' => __( 'XL', 'mj-elementor-templates' ),
				),
				'default' => 'md',
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'   => __( 'Icone', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::ICONS,
				'skin'    => 'inline',
				'default' => array(
					'value'   => '',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'icon_position',
			array(
				'label'   => __( 'Position de l\'icone', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'before' => array(
						'title' => __( 'Avant', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-left',
					),
					'after'  => array(
						'title' => __( 'Apres', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default' => 'before',
				'condition' => array(
					'button_icon[value]!' => '',
				),
			)
		);

		$this->add_control(
			'alignment',
			array(
				'label'       => __( 'Alignement', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::CHOOSE,
				'options'     => array(
					'left'   => array(
						'title' => __( 'Gauche', 'mj-elementor-templates' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Centre', 'mj-elementor-templates' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Droite', 'mj-elementor-templates' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'     => 'left',
				'toggle'      => false,
				'prefix_class' => 'mjet-pwa-install-align-%s',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'messages_section',
			array(
				'label' => __( 'Messages', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'pending_message',
			array(
				'label'       => __( 'Message d\'attente', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Ajoutez cette application a votre ecran d\'accueil des que votre navigateur le propose.', 'mj-elementor-templates' ),
				'rows'        => 2,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'prompting_message',
			array(
				'label'       => __( 'Message de confirmation', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Confirmez l\'installation dans la fenetre de votre navigateur.', 'mj-elementor-templates' ),
				'rows'        => 2,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'installed_message',
			array(
				'label'       => __( 'Message installe', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Application installee. Vous la trouverez dans vos applications.', 'mj-elementor-templates' ),
				'rows'        => 2,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'unsupported_message',
			array(
				'label'       => __( 'Message non supporte', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Installation non disponible sur ce navigateur.', 'mj-elementor-templates' ),
				'rows'        => 2,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'dismissed_message',
			array(
				'label'       => __( 'Message installation annulee', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Installation annulee. Vous pourrez reessayer plus tard.', 'mj-elementor-templates' ),
				'rows'        => 2,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'error_message',
			array(
				'label'       => __( 'Message d\'erreur', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'Impossible d\'installer l\'application. Veuillez reessayer.', 'mj-elementor-templates' ),
				'rows'        => 2,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Contrôles de style.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'button_style_section',
			array(
				'label' => __( 'Bouton', 'mj-elementor-templates' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button',
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Marge interne', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-pwa-install__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_border_radius',
			array(
				'label'      => __( 'Rayon des bordures', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-pwa-install__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_colors' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => __( 'Normal', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Couleur du texte', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-pwa-install__button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'button_background',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => __( 'Survol', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => __( 'Couleur du texte', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-pwa-install__button:not(:disabled):hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'button_hover_background',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button:not(:disabled):hover',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'button_hover_border',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button:not(:disabled):hover',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_hover_box_shadow',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__button:not(:disabled):hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'icon_spacing',
			array(
				'label'      => __( 'Espacement icone', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-pwa-install__button' => '--mjet-pwa-install-icon-gap: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'button_icon[value]!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => __( 'Taille icone', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 8,
						'max' => 48,
					),
					'em' => array(
						'min'  => 0.25,
						'max'  => 3,
						'step' => 0.05,
					),
					'rem' => array(
						'min'  => 0.25,
						'max'  => 3,
						'step' => 0.05,
					),
				),
				'default'    => array(
					'size' => 0.75,
					'unit' => 'rem',
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-pwa-install__button' => '--mjet-pwa-install-icon-size: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'button_icon[value]!' => '',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Couleur icone', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-pwa-install__icon'      => 'color: {{VALUE}};',
					'{{WRAPPER}} .mjet-pwa-install__icon svg'  => 'fill: {{VALUE}}; stroke: {{VALUE}};',
				),
				'condition' => array(
					'button_icon[value]!' => '',
				),
			)
		);

		$this->add_control(
			'icon_hover_color',
			array(
				'label'     => __( 'Couleur icone (survol)', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-pwa-install__button:not(:disabled):hover .mjet-pwa-install__icon'     => 'color: {{VALUE}};',
					'{{WRAPPER}} .mjet-pwa-install__button:not(:disabled):hover .mjet-pwa-install__icon svg' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
				),
				'condition' => array(
					'button_icon[value]!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'button_gap',
			array(
				'label'      => __( 'Espacement vertical', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
					'em' => array(
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-pwa-install' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'status_style_section',
			array(
				'label' => __( 'Message', 'mj-elementor-templates' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'status_typography',
				'selector' => '{{WRAPPER}} .mjet-pwa-install__status',
			)
		);

		$this->add_control(
			'status_color',
			array(
				'label'     => __( 'Couleur du texte', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-pwa-install__status' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'status_margin_top',
			array(
				'label'      => __( 'Marge superieure', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
					'em' => array(
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-pwa-install__status' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Rendu du widget cote frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$button_label      = $this->prepare_message( $settings, 'button_label', __( 'Installer l\'application', 'mj-elementor-templates' ) );
		$pending_message   = $this->prepare_message( $settings, 'pending_message', __( 'Ajoutez cette application a votre ecran d\'accueil des que votre navigateur le propose.', 'mj-elementor-templates' ) );
		$prompting_message = $this->prepare_message( $settings, 'prompting_message', __( 'Confirmez l\'installation dans la fenetre de votre navigateur.', 'mj-elementor-templates' ) );
		$installed_message = $this->prepare_message( $settings, 'installed_message', __( 'Application installee. Vous la trouverez dans vos applications.', 'mj-elementor-templates' ) );
		$unsupported       = $this->prepare_message( $settings, 'unsupported_message', __( 'Installation non disponible sur ce navigateur.', 'mj-elementor-templates' ) );
		$dismissed         = $this->prepare_message( $settings, 'dismissed_message', __( 'Installation annulee. Vous pourrez reessayer plus tard.', 'mj-elementor-templates' ) );
		$error             = $this->prepare_message( $settings, 'error_message', __( 'Impossible d\'installer l\'application. Veuillez reessayer.', 'mj-elementor-templates' ) );

		$icon_position = ( isset( $settings['icon_position'] ) && 'after' === $settings['icon_position'] ) ? 'after' : 'before';
		$button_size   = isset( $settings['button_size'] ) ? $settings['button_size'] : 'md';
		$is_editor     = false;
		if ( class_exists( '\\Elementor\\Plugin' ) && isset( Plugin::$instance->editor ) && method_exists( Plugin::$instance->editor, 'is_edit_mode' ) ) {
			$is_editor = Plugin::$instance->editor->is_edit_mode();
		}

		$icon_html = '';
		if ( ! empty( $settings['button_icon']['value'] ) ) {
			$icon_class = 'mjet-pwa-install__icon';
			$icon_class .= ( 'after' === $icon_position ) ? ' mjet-pwa-install__icon--after' : ' mjet-pwa-install__icon--before';
			$icon_class .= ' elementor-button-icon';
			$icon_class .= ( 'after' === $icon_position ) ? ' elementor-align-icon-right' : ' elementor-align-icon-left';
			ob_start();
			Icons_Manager::render_icon(
				$settings['button_icon'],
				array(
					'aria-hidden' => 'true',
					'class'       => $icon_class,
				)
			);
			$icon_html = ob_get_clean();
		}

		$this->add_render_attribute( 'wrapper', 'class', 'mjet-pwa-install' );
		$this->add_render_attribute( 'wrapper', 'data-state', 'pending' );
		$this->add_render_attribute( 'wrapper', 'data-install-text', $button_label );
		$this->add_render_attribute( 'wrapper', 'data-pending-text', $pending_message );
		$this->add_render_attribute( 'wrapper', 'data-prompting-text', $prompting_message );
		$this->add_render_attribute( 'wrapper', 'data-installed-text', $installed_message );
		$this->add_render_attribute( 'wrapper', 'data-unsupported-text', $unsupported );
		$this->add_render_attribute( 'wrapper', 'data-dismissed-text', $dismissed );
		$this->add_render_attribute( 'wrapper', 'data-error-text', $error );
		if ( $is_editor ) {
			$this->add_render_attribute( 'wrapper', 'data-preview-mode', '1' );
		}

		$button_classes = array( 'mjet-pwa-install__button', 'elementor-button', 'elementor-button-link' );
		if ( in_array( $button_size, array( 'xs', 'sm', 'md', 'lg', 'xl' ), true ) ) {
			$button_classes[] = 'elementor-size-' . $button_size;
		}
		if ( $icon_html ) {
			$button_classes[] = 'mjet-pwa-install__button--has-icon';
		}
		if ( ! $is_editor ) {
			$button_classes[] = 'mjet-pwa-install__button--hidden';
		}
		$this->add_render_attribute( 'button', 'class', $button_classes );
		$this->add_render_attribute( 'button', 'type', 'button' );
		$this->add_render_attribute( 'button', 'aria-label', $button_label );

		$this->add_render_attribute( 'status', 'class', 'mjet-pwa-install__status' );
		$this->add_render_attribute( 'status', 'role', 'status' );
		$this->add_render_attribute( 'status', 'aria-live', 'polite' );

		if ( '' === $pending_message ) {
			$this->add_render_attribute( 'status', 'hidden', 'hidden' );
		}

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<button <?php echo $this->get_render_attribute_string( 'button' ); ?>>
				<span class="elementor-button-content-wrapper">
					<?php
					if ( $icon_html && 'before' === $icon_position ) {
						echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					<span class="mjet-pwa-install__label elementor-button-text"><?php echo esc_html( $button_label ); ?></span>
					<?php
					if ( $icon_html && 'after' === $icon_position ) {
						echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</span>
			</button>
			<p <?php echo $this->get_render_attribute_string( 'status' ); ?>><?php echo esc_html( $pending_message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Prepare un message de controle pour l'affichage.
	 *
	 * @param array  $settings Paramètres Elementor du widget.
	 * @param string $key      Indice du message.
	 * @param string $default  Valeur par defaut.
	 * @return string
	 */
	private function prepare_message( $settings, $key, $default = '' ) {
		if ( empty( $settings[ $key ] ) ) {
			return $default;
		}

		$value = $settings[ $key ];

		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		return sanitize_text_field( $value );
	}
}
