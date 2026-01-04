<?php
/**
 * Widget Bloc MJ Template pour Elementor.
 *
 * @package mj-elementor-templates
 */

namespace MJET\Widgets;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget pour insérer un template MJ de type bloc dans Elementor.
 */
class MJET_Template_Block extends Widget_Base {

	/**
	 * Templates disponibles.
	 *
	 * @var array
	 */
	private $templates = array();

	/**
	 * Constructeur.
	 *
	 * @param array $data Données du widget.
	 * @param mixed $args Arguments supplémentaires.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		$this->templates = $this->get_template_options();
	}

	/**
	 * Identifiant unique.
	 */
	public function get_name() {
		return 'mjet-template-block';
	}

	/**
	 * Titre du widget.
	 */
	public function get_title() {
		return __( 'Bloc MJ Template', 'mj-elementor-templates' );
	}

	/**
	 * Icône Elementor.
	 */
	public function get_icon() {
		return 'eicon-library-save';
	}

	/**
	 * Catégories Elementor.
	 */
	public function get_categories() {
		return array( 'mjet-widgets' );
	}

	/**
	 * Mots-clés.
	 */
	public function get_keywords() {
		return array( 'mjet', 'template', 'block', 'shortcode' );
	}

	/**
	 * Déclaration des contrôles.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Bloc MJ Template', 'mj-elementor-templates' ),
			)
		);

		$options = $this->templates;

		$this->add_control(
			'template_id',
			array(
				'label'       => __( 'Template', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $options,
				'label_block' => true,
				'placeholder' => __( 'Sélectionnez un template…', 'mj-elementor-templates' ),
				'description' => __( 'Insère un template MJ enregistré en tant que "Custom Block".', 'mj-elementor-templates' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		if ( empty( $options ) ) {
			$this->add_control(
				'templates_notice',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => sprintf(
						/* translators: %s: link to create a new template. */
						__( 'Aucun bloc disponible. <a href="%s" target="_blank" rel="noopener noreferrer">Créer un template MJ</a> en sélectionnant le type "Custom Block".', 'mj-elementor-templates' ),
						esc_url( admin_url( 'post-new.php?post_type=mjet-template&mjet_type=custom' ) )
					),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Affichage du widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$template_id = $this->resolve_template_id( $settings );

		if ( ! $this->is_template_renderable( $template_id ) ) {
			if ( Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-warning">' . esc_html__( 'Sélectionnez un template MJ valide.', 'mj-elementor-templates' ) . '</div>';
			}
			return;
		}

		$this->enqueue_template_css( $template_id );

		echo Plugin::instance()->frontend->get_builder_content_for_display( $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Convertit la valeur du contrôle en ID exploitable.
	 *
	 * @param array $settings Réglages du widget.
	 * @return int
	 */
	private function resolve_template_id( $settings ) {
		if ( empty( $settings['template_id'] ) ) {
			return 0;
		}

		$value = $settings['template_id'];

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return absint( $value );
	}

	/**
	 * Vérifie qu'un template peut être rendu.
	 *
	 * @param int $template_id ID à vérifier.
	 * @return bool
	 */
	private function is_template_renderable( $template_id ) {
		if ( ! $template_id ) {
			return false;
		}

		$post = get_post( $template_id );

		if ( ! $post || 'mjet-template' !== $post->post_type ) {
			return false;
		}

		if ( post_password_required( $post ) ) {
			return false;
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $template_id ) ) {
			return false;
		}

		$allowed_types = (array) apply_filters( 'mjet_widget_template_block_allowed_types', array_keys( $this->get_template_type_labels() ) );
		$template_type = get_post_meta( $template_id, 'mjet_template_type', true );

		if ( empty( $template_type ) ) {
			$template_type = 'custom';
		}

		if ( ! empty( $allowed_types ) && ! in_array( $template_type, $allowed_types, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Charge les CSS Elementor du template.
	 *
	 * @param int $template_id Template ciblé.
	 */
	private function enqueue_template_css( $template_id ) {
		if ( ! $template_id ) {
			return;
		}

		if ( class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
			$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
			$css_file->enqueue();
		}
	}

	/**
	 * Récupère la liste des templates disponibles.
	 *
	 * @return array
	 */
	private function get_template_options() {
		$allowed_types = (array) apply_filters( 'mjet_widget_template_block_allowed_types', array_keys( $this->get_template_type_labels() ) );

		$statuses = (array) apply_filters(
			'mjet_widget_template_block_statuses',
			array( 'publish', 'private', 'draft', 'pending', 'future' )
		);

		$query = new \WP_Query(
			array(
				'post_type'      => 'mjet-template',
				'post_status'    => $statuses,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		$options = array();

		$type_labels = $this->get_template_type_labels();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $template_id ) {
				if ( 'publish' !== get_post_status( $template_id ) && ! current_user_can( 'edit_post', $template_id ) ) {
					continue;
				}

				$template_type = get_post_meta( $template_id, 'mjet_template_type', true );
				if ( empty( $template_type ) ) {
					$template_type = 'custom';
				}

				if ( ! empty( $allowed_types ) && ! in_array( $template_type, $allowed_types, true ) ) {
					continue;
				}

				$type_label = isset( $type_labels[ $template_type ] ) ? $type_labels[ $template_type ] : ucfirst( str_replace( '_', ' ', $template_type ) );
				$options[ $template_id ] = sprintf( '%s — %s', $type_label, get_the_title( $template_id ) );
			}
		}

		wp_reset_postdata();

		return $options;
	}

	/**
	 * Retourne les labels des types de template.
	 *
	 * @return array
	 */
	private function get_template_type_labels() {
		$types = array(
			'type_header'           => __( 'Header', 'mj-elementor-templates' ),
			'type_before_footer'    => __( 'Before Footer', 'mj-elementor-templates' ),
			'type_footer'           => __( 'Footer', 'mj-elementor-templates' ),
			'custom'                => __( 'Custom Block', 'mj-elementor-templates' ),
			'type_single_page'      => __( 'Single Page', 'mj-elementor-templates' ),
			'type_single_post'      => __( 'Single Post', 'mj-elementor-templates' ),
			'type_archive'          => __( 'Archive', 'mj-elementor-templates' ),
			'type_search'           => __( 'Search Results Page', 'mj-elementor-templates' ),
			'type_single_product'   => __( 'Single Product', 'mj-elementor-templates' ),
			'type_products_archive' => __( 'Products Archive', 'mj-elementor-templates' ),
			'type_404'              => __( '404 Page', 'mj-elementor-templates' ),
		);

		if ( ! post_type_exists( 'product' ) ) {
			unset( $types['type_single_product'], $types['type_products_archive'] );
		}

		return apply_filters( 'mjet_template_type_options', $types );
	}
}
