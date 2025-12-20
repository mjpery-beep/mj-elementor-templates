<?php
/**
 * Widget Menu Navigation pour Elementor.
 *
 * @package mj-elementor-templates
 */

namespace MJET\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget Menu Navigation.
 */
class MJET_Nav_Menu extends Widget_Base {

	/**
	 * Liste des menus disponibles.
	 *
	 * @var array
	 */
	private $menus = array();

	/**
	 * Constructeur.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		$this->menus = $this->get_available_menus();
	}

	/**
	 * Nom du widget.
	 */
	public function get_name() {
		return 'mjet-nav-menu';
	}

	/**
	 * Titre du widget.
	 */
	public function get_title() {
		return __( 'Menu Navigation', 'mj-elementor-templates' );
	}

	/**
	 * Icône du widget.
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * Catégories.
	 */
	public function get_categories() {
		return array( 'mjet-widgets' );
	}

	/**
	 * Mots-clés.
	 */
	public function get_keywords() {
		return array( 'menu', 'nav', 'navigation', 'header', 'mjet' );
	}

	/**
	 * Scripts dépendants.
	 */
	public function get_script_depends() {
		return array( 'mjet-nav-menu' );
	}

	/**
	 * Styles dépendants.
	 */
	public function get_style_depends() {
		return array( 'mjet-nav-menu' );
	}

	/**
	 * Récupérer les menus disponibles.
	 */
	private function get_available_menus() {
		$menus   = wp_get_nav_menus();
		$options = array();

		foreach ( $menus as $menu ) {
			$options[ $menu->slug ] = $menu->name;
		}

		return $options;
	}

	/**
	 * Enregistrer les contrôles.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Contrôles de contenu.
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'section_menu',
			array(
				'label' => __( 'Menu', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'menu',
			array(
				'label'       => __( 'Menu', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->menus,
				'default'     => array_key_exists( 'primary', $this->menus ) ? 'primary' : ( ! empty( $this->menus ) ? array_keys( $this->menus )[0] : '' ),
				'description' => sprintf(
					/* translators: %s: link to menus screen */
					__( 'Allez dans %s pour gérer vos menus.', 'mj-elementor-templates' ),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						admin_url( 'nav-menus.php' ),
						__( 'Apparence > Menus', 'mj-elementor-templates' )
					)
				),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Disposition', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'horizontal',
				'options' => array(
					'horizontal' => __( 'Horizontal', 'mj-elementor-templates' ),
					'vertical'   => __( 'Vertical', 'mj-elementor-templates' ),
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'        => __( 'Alignement', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'flex-start' => array(
						'title' => __( 'Gauche', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => __( 'Centre', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Droite', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-right',
					),
					'space-between' => array(
						'title' => __( 'Justifié', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-stretch',
					),
				),
				'default'      => 'flex-start',
				'selectors'    => array(
					'{{WRAPPER}} .mjet-nav-menu' => 'justify-content: {{VALUE}};',
				),
				'condition'    => array(
					'layout' => 'horizontal',
				),
			)
		);

		$this->add_control(
			'pointer',
			array(
				'label'   => __( 'Indicateur', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'underline',
				'options' => array(
					'none'       => __( 'Aucun', 'mj-elementor-templates' ),
					'underline'  => __( 'Soulignement', 'mj-elementor-templates' ),
					'background' => __( 'Fond', 'mj-elementor-templates' ),
					'text'       => __( 'Texte', 'mj-elementor-templates' ),
				),
			)
		);

		$this->end_controls_section();

		// Section mobile
		$this->start_controls_section(
			'section_mobile',
			array(
				'label' => __( 'Menu Mobile', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'mobile_breakpoint',
			array(
				'label'   => __( 'Point de rupture', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'tablet',
				'options' => array(
					'mobile' => __( 'Mobile (< 768px)', 'mj-elementor-templates' ),
					'tablet' => __( 'Tablette (< 1025px)', 'mj-elementor-templates' ),
					'none'   => __( 'Aucun (toujours afficher)', 'mj-elementor-templates' ),
				),
			)
		);

		$this->add_control(
			'toggle_icon',
			array(
				'label'     => __( 'Icône du toggle', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-bars',
					'library' => 'fa-solid',
				),
				'condition' => array(
					'mobile_breakpoint!' => 'none',
				),
			)
		);

		$this->add_control(
			'toggle_icon_close',
			array(
				'label'     => __( 'Icône fermer', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-times',
					'library' => 'fa-solid',
				),
				'condition' => array(
					'mobile_breakpoint!' => 'none',
				),
			)
		);

		$this->add_responsive_control(
			'toggle_align',
			array(
				'label'     => __( 'Alignement toggle', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Gauche', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => __( 'Centre', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Droite', 'mj-elementor-templates' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'   => 'flex-end',
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-toggle-wrap' => 'justify-content: {{VALUE}};',
				),
				'condition' => array(
					'mobile_breakpoint!' => 'none',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Contrôles de style.
	 */
	protected function register_style_controls() {
		// Style des items
		$this->start_controls_section(
			'section_style_items',
			array(
				'label' => __( 'Items du menu', 'mj-elementor-templates' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'menu_typography',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				),
				'selector' => '{{WRAPPER}} .mjet-nav-menu .menu-item a',
			)
		);

		$this->add_responsive_control(
			'menu_item_spacing',
			array(
				'label'      => __( 'Espacement', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'size' => 15,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-nav-menu--horizontal .menu-item' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mjet-nav-menu--vertical .menu-item' => 'margin-top: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'menu_item_padding',
			array(
				'label'      => __( 'Padding', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-nav-menu .menu-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_menu_item_style' );

		$this->start_controls_tab(
			'tab_menu_item_normal',
			array(
				'label' => __( 'Normal', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'menu_item_color',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_TEXT,
				),
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .menu-item a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'menu_item_bg_color',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .menu-item a' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_menu_item_hover',
			array(
				'label' => __( 'Survol', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'menu_item_color_hover',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_ACCENT,
				),
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .menu-item a:hover, {{WRAPPER}} .mjet-nav-menu .menu-item a:focus' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'menu_item_bg_color_hover',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .menu-item a:hover, {{WRAPPER}} .mjet-nav-menu .menu-item a:focus' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pointer_color_hover',
			array(
				'label'     => __( 'Couleur indicateur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_ACCENT,
				),
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu--pointer-underline .menu-item a::after' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'pointer' => 'underline',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_menu_item_active',
			array(
				'label' => __( 'Actif', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'menu_item_color_active',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .current-menu-item > a, {{WRAPPER}} .mjet-nav-menu .current-menu-ancestor > a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'menu_item_bg_color_active',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .current-menu-item > a, {{WRAPPER}} .mjet-nav-menu .current-menu-ancestor > a' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// Style du sous-menu
		$this->start_controls_section(
			'section_style_submenu',
			array(
				'label' => __( 'Sous-menu', 'mj-elementor-templates' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'submenu_bg_color',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-menu .sub-menu' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'submenu_border',
				'selector' => '{{WRAPPER}} .mjet-nav-menu .sub-menu',
			)
		);

		$this->add_responsive_control(
			'submenu_border_radius',
			array(
				'label'      => __( 'Border Radius', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-nav-menu .sub-menu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'submenu_box_shadow',
				'selector' => '{{WRAPPER}} .mjet-nav-menu .sub-menu',
			)
		);

		$this->end_controls_section();

		// Style du toggle mobile
		$this->start_controls_section(
			'section_style_toggle',
			array(
				'label'     => __( 'Toggle Mobile', 'mj-elementor-templates' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'mobile_breakpoint!' => 'none',
				),
			)
		);

		$this->add_responsive_control(
			'toggle_size',
			array(
				'label'      => __( 'Taille', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 15,
						'max' => 50,
					),
				),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-nav-toggle' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mjet-nav-toggle svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'toggle_color',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-toggle'     => 'color: {{VALUE}};',
					'{{WRAPPER}} .mjet-nav-toggle svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'toggle_bg_color',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-nav-toggle' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'toggle_padding',
			array(
				'label'      => __( 'Padding', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-nav-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'toggle_border_radius',
			array(
				'label'      => __( 'Border Radius', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-nav-toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Rendu du widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['menu'] ) ) {
			return;
		}

		$menu_args = array(
			'menu'            => $settings['menu'],
			'container'       => 'nav',
			'container_class' => 'mjet-nav-menu-container',
			'menu_class'      => $this->get_menu_class( $settings ),
			'menu_id'         => 'mjet-menu-' . $this->get_id(),
			'echo'            => false,
			'fallback_cb'     => '__return_empty_string',
			'walker'          => new \Walker_Nav_Menu(),
		);

		$menu_html = wp_nav_menu( $menu_args );

		if ( empty( $menu_html ) ) {
			return;
		}

		$breakpoint_class = 'mjet-nav-menu--breakpoint-' . $settings['mobile_breakpoint'];

		$this->add_render_attribute(
			'wrapper',
			'class',
			array(
				'mjet-nav-menu-wrapper',
				$breakpoint_class,
			)
		);

		$this->add_render_attribute( 'wrapper', 'data-breakpoint', $settings['mobile_breakpoint'] );

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<?php if ( 'none' !== $settings['mobile_breakpoint'] ) : ?>
				<div class="mjet-nav-toggle-wrap">
					<button class="mjet-nav-toggle" aria-label="<?php esc_attr_e( 'Menu', 'mj-elementor-templates' ); ?>" aria-expanded="false">
						<span class="mjet-nav-toggle-icon mjet-nav-toggle-open">
							<?php \Elementor\Icons_Manager::render_icon( $settings['toggle_icon'], array( 'aria-hidden' => 'true' ) ); ?>
						</span>
						<span class="mjet-nav-toggle-icon mjet-nav-toggle-close">
							<?php \Elementor\Icons_Manager::render_icon( $settings['toggle_icon_close'], array( 'aria-hidden' => 'true' ) ); ?>
						</span>
					</button>
				</div>
			<?php endif; ?>

			<?php echo $menu_html; ?>
		</div>
		<?php
	}

	/**
	 * Obtenir les classes du menu.
	 */
	private function get_menu_class( $settings ) {
		$classes = array(
			'mjet-nav-menu',
			'mjet-nav-menu--' . $settings['layout'],
		);

		if ( 'none' !== $settings['pointer'] ) {
			$classes[] = 'mjet-nav-menu--pointer-' . $settings['pointer'];
		}

		return implode( ' ', $classes );
	}
}
