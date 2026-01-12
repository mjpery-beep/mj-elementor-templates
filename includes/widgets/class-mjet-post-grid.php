<?php
/**
 * Widget Post Grid Elementor.
 *
 * @package mj-elementor-templates
 */

namespace MJET\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Grid widget inspired by Essential Addons.
 */
class MJET_Post_Grid extends Widget_Base {
	/**
	 * Widget slug.
	 */
	public function get_name() {
		return 'mjet-post-grid';
	}

	/**
	 * Widget title.
	 */
	public function get_title() {
		return __( 'Post Grid', 'mj-elementor-templates' );
	}

	/**
	 * Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Widget category.
	 */
	public function get_categories() {
		return array( 'mjet-widgets' );
	}

	/**
	 * Search keywords.
	 */
	public function get_keywords() {
		return array( 'post', 'grid', 'blog', 'articles', 'mjet' );
	}

	/**
	 * Style dependencies.
	 */
	public function get_style_depends() {
		return array( 'mjet-post-grid' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$this->register_query_section();
		$this->register_layout_section();
		$this->register_content_section();
		$this->register_style_sections();
	}

	/**
	 * Query controls.
	 */
	private function register_query_section() {
		$post_types = $this->get_public_post_types();

		$this->start_controls_section(
			'mjet_post_grid_section_query',
			array(
				'label' => __( 'Query', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label' => __( 'Post Type', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SELECT,
				'options' => $post_types,
				'default' => 'post',
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label' => __( 'Posts Per Page', 'mj-elementor-templates' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 6,
				'placeholder' => 6,
				'condition' => array( 'post_type!' => 'attachment' ),
			)
		);

		$this->add_control(
			'offset',
			array(
				'label' => __( 'Offset', 'mj-elementor-templates' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 0,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label' => __( 'Order By', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SELECT,
				'options' => $this->get_orderby_options(),
				'default' => 'date',
			)
		);

		$this->add_control(
			'order',
			array(
				'label' => __( 'Order', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SELECT,
				'options' => array(
					'ASC' => __( 'Ascending', 'mj-elementor-templates' ),
					'DESC' => __( 'Descending', 'mj-elementor-templates' ),
				),
				'default' => 'DESC',
			)
		);

		$this->add_control(
			'include_ids',
			array(
				'label' => __( 'Include IDs', 'mj-elementor-templates' ),
				'type' => Controls_Manager::TEXT,
				'description' => __( 'Comma-separated list of post IDs to include.', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'exclude_ids',
			array(
				'label' => __( 'Exclude IDs', 'mj-elementor-templates' ),
				'type' => Controls_Manager::TEXT,
				'description' => __( 'Comma-separated list of post IDs to exclude.', 'mj-elementor-templates' ),
			)
		);

		if ( isset( $post_types['post'] ) ) {
			$categories = $this->get_terms_for_control( 'category' );

			$this->add_control(
				'categories',
				array(
					'label' => __( 'Categories', 'mj-elementor-templates' ),
					'type' => Controls_Manager::SELECT2,
					'options' => $categories,
					'multiple' => true,
					'condition' => array( 'post_type' => 'post' ),
				)
			);
		}

		$this->add_control(
			'ignore_sticky',
			array(
				'label' => __( 'Ignore Sticky Posts', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'mj-elementor-templates' ),
				'label_off' => __( 'No', 'mj-elementor-templates' ),
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Layout controls.
	 */
	private function register_layout_section() {
		$this->start_controls_section(
			'mjet_post_grid_section_layout',
			array(
				'label' => __( 'Layout', 'mj-elementor-templates' ),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label' => __( 'Columns', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SELECT,
				'options' => array(
					'1' => __( '1', 'mj-elementor-templates' ),
					'2' => __( '2', 'mj-elementor-templates' ),
					'3' => __( '3', 'mj-elementor-templates' ),
					'4' => __( '4', 'mj-elementor-templates' ),
				),
				'default' => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'prefix_class' => 'mjet-post-grid--columns-%s',
			)
		);

		$this->add_responsive_control(
			'gutter',
			array(
				'label' => __( 'Gutter', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default' => array(
					'size' => 24,
				),
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid' => '--mjet-post-grid-gutter: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_alignment',
			array(
				'label' => __( 'Content Alignment', 'mj-elementor-templates' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => array(
					'flex-start' => array(
						'title' => __( 'Left', 'mj-elementor-templates' ),
						'icon' => 'eicon-h-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'mj-elementor-templates' ),
						'icon' => 'eicon-h-align-center',
					),
					'flex-end' => array(
						'title' => __( 'Right', 'mj-elementor-templates' ),
						'icon' => 'eicon-h-align-right',
					),
				),
				'default' => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__card' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content toggles.
	 */
	private function register_content_section() {
		$this->start_controls_section(
			'mjet_post_grid_section_content',
			array(
				'label' => __( 'Content', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label' => __( 'Featured Image', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name' => 'thumbnail',
				'default' => 'medium_large',
				'exclude' => array( 'custom' ),
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_control(
			'fallback_image',
			array(
				'label' => __( 'Fallback Image', 'mj-elementor-templates' ),
				'type' => Controls_Manager::MEDIA,
				'description' => __( 'Used when a post has no featured image.', 'mj-elementor-templates' ),
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_control(
			'image_crop',
			array(
				'label' => __( 'Crop Image', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'mj-elementor-templates' ),
				'label_off' => __( 'No', 'mj-elementor-templates' ),
				'default' => 'no',
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_control(
			'image_ratio',
			array(
				'label' => __( 'Aspect Ratio', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SELECT,
				'options' => array(
					'16-9' => '16:9',
					'4-3' => '4:3',
					'1-1' => '1:1',
					'3-4' => '3:4',
					'21-9' => '21:9',
				),
				'default' => '16-9',
				'condition' => array(
					'show_image' => 'yes',
					'image_crop' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label' => __( 'Title', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label' => __( 'Title HTML Tag', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SELECT,
				'options' => array(
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'p' => 'p',
					'div' => 'div',
				),
				'default' => 'h3',
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'show_meta',
			array(
				'label' => __( 'Meta', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_author',
			array(
				'label' => __( 'Show Author', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
				'condition' => array( 'show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label' => __( 'Show Date', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
				'condition' => array( 'show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label' => __( 'Excerpt', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label' => __( 'Excerpt Length', 'mj-elementor-templates' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 20,
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'show_read_more',
			array(
				'label' => __( 'Read More Button', 'mj-elementor-templates' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'mj-elementor-templates' ),
				'label_off' => __( 'Hide', 'mj-elementor-templates' ),
				'default' => 'yes',
			)
		);

		$this->add_control(
			'read_more_text',
			array(
				'label' => __( 'Read More Text', 'mj-elementor-templates' ),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Read More', 'mj-elementor-templates' ),
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style sections.
	 */
	private function register_style_sections() {
		$this->start_controls_section(
			'mjet_post_grid_style_card',
			array(
				'label' => __( 'Card', 'mj-elementor-templates' ),
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name' => 'card_background',
				'fields_options' => array(
					'background' => array(
						'default' => 'classic',
					),
				),
				'options' => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .mjet-post-grid__card',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'card_border',
				'selector' => '{{WRAPPER}} .mjet-post-grid__card',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label' => __( 'Border Radius', 'mj-elementor-templates' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'card_shadow',
				'selector' => '{{WRAPPER}} .mjet-post-grid__card',
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label' => __( 'Padding', 'mj-elementor-templates' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'mjet_post_grid_style_title',
			array(
				'label' => __( 'Title', 'mj-elementor-templates' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name' => 'title_typography',
				'selector' => '{{WRAPPER}} .mjet-post-grid__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label' => __( 'Color', 'mj-elementor-templates' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label' => __( 'Hover Color', 'mj-elementor-templates' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'mjet_post_grid_style_meta',
			array(
				'label' => __( 'Meta', 'mj-elementor-templates' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_meta' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name' => 'meta_typography',
				'selector' => '{{WRAPPER}} .mjet-post-grid__meta',
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label' => __( 'Color', 'mj-elementor-templates' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__meta, {{WRAPPER}} .mjet-post-grid__meta a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'mjet_post_grid_style_excerpt',
			array(
				'label' => __( 'Excerpt', 'mj-elementor-templates' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name' => 'excerpt_typography',
				'selector' => '{{WRAPPER}} .mjet-post-grid__excerpt',
			)
		);

		$this->add_control(
			'excerpt_color',
			array(
				'label' => __( 'Color', 'mj-elementor-templates' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__excerpt' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'mjet_post_grid_style_read_more',
			array(
				'label' => __( 'Read More', 'mj-elementor-templates' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name' => 'read_more_typography',
				'selector' => '{{WRAPPER}} .mjet-post-grid__read-more',
			)
		);

		$this->add_control(
			'read_more_color',
			array(
				'label' => __( 'Color', 'mj-elementor-templates' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__read-more' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'read_more_hover_color',
			array(
				'label' => __( 'Hover Color', 'mj-elementor-templates' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-post-grid__read-more:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$query = $this->build_query( $settings );

		if ( ! $query->have_posts() ) {
			printf( '<p class="mjet-post-grid__empty">%s</p>', esc_html__( 'No posts found.', 'mj-elementor-templates' ) );
			return;
		}

		$wrapper_classes = array_map( 'sanitize_html_class', $this->build_grid_wrapper_classes( $settings ) );

		echo '<div class="' . esc_attr( implode( ' ', array_unique( $wrapper_classes ) ) ) . '">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$this->render_card( $settings );
		}

		wp_reset_postdata();

		echo '</div>';
	}

	/**
	 * Render single card.
	 *
	 * @param array $settings Widget settings.
	 */
	private function render_card( array $settings ) {
		echo '<article class="mjet-post-grid__item">';

		if ( 'yes' === $settings['show_image'] ) {
			$this->render_thumbnail( $settings );
		}

		echo '<div class="mjet-post-grid__card">';
		echo '<div class="mjet-post-grid__content">';

		if ( 'yes' === $settings['show_title'] ) {
			$this->render_title( $settings );
		}

		if ( 'yes' === $settings['show_meta'] ) {
			$this->render_meta( $settings );
		}

		if ( 'yes' === $settings['show_excerpt'] ) {
			$this->render_excerpt( $settings );
		}

		if ( 'yes' === $settings['show_read_more'] ) {
			$this->render_read_more( $settings );
		}

		echo '</div>';
		echo '</div>';
		echo '</article>';
	}

	/**
	 * Render thumbnail.
	 */
	private function render_thumbnail( array $settings ) {
		$thumbnail_html = '';
		$image_id = has_post_thumbnail() ? get_post_thumbnail_id() : 0;

		if ( $image_id ) {
			$thumbnail_html = $this->build_image_html( $settings, $image_id );
		} elseif ( ! empty( $settings['fallback_image']['id'] ) ) {
			$thumbnail_html = $this->build_image_html( $settings, (int) $settings['fallback_image']['id'] );
		} elseif ( ! empty( $settings['fallback_image']['url'] ) ) {
			$thumbnail_html = sprintf(
				'<img src="%1$s" alt="" loading="lazy" />',
				esc_url( $settings['fallback_image']['url'] )
			);
		}

		if ( empty( $thumbnail_html ) ) {
			return;
		}

		$media_classes = array_map( 'sanitize_html_class', $this->get_media_classes( $settings ) );
		$media_class_attr = esc_attr( implode( ' ', $media_classes ) );

		echo '<div class="' . $media_class_attr . '">' . wp_kses_post( $thumbnail_html ) . '</div>';
	}

	/**
	 * Build image markup honoring the selected size control.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $attachment_id Attachment ID.
	 */
	private function build_image_html( array $settings, $attachment_id ) {
		if ( empty( $attachment_id ) ) {
			return '';
		}

		$image_settings = $settings;
		$image_settings['thumbnail'] = array(
			'id' => $attachment_id,
			'url' => wp_get_attachment_url( $attachment_id ),
		);

		// Respect attachment alt text when available.
		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( $alt_text ) {
			$image_settings['thumbnail']['alt'] = $alt_text;
		}

		return Group_Control_Image_Size::get_attachment_image_html( $image_settings, 'thumbnail', 'thumbnail' );
	}

	/**
	 * Retrieve media wrapper classes with optional crop ratio.
	 */
	private function get_media_classes( array $settings ) {
		$classes = array( 'mjet-post-grid__media' );

		if ( isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'] ) {
			$classes[] = 'mjet-post-grid__media--crop';

			$ratio_class = $this->get_ratio_class( $settings );
			if ( $ratio_class ) {
				$classes[] = $ratio_class;
			}
		}

		return $classes;
	}

	/**
	 * Sanitize aspect ratio selection for CSS class usage.
	 */
	private function get_ratio_class( array $settings ) {
		$allowed = array( '16-9', '4-3', '1-1', '3-4', '21-9' );
		$ratio = isset( $settings['image_ratio'] ) ? $settings['image_ratio'] : '16-9';

		if ( ! in_array( $ratio, $allowed, true ) ) {
			$ratio = '16-9';
		}

		return 'mjet-post-grid__media--ratio-' . $ratio;
	}

	/**
	 * Render title.
	 */
	private function render_title( array $settings ) {
		$tag = $this->sanitize_tag( $settings['title_tag'] );
		$title = get_the_title();

		if ( ! $title ) {
			return;
		}

		echo sprintf(
			'<%1$s class="mjet-post-grid__title"><a href="%2$s">%3$s</a></%1$s>',
			esc_html( $tag ),
			esc_url( get_permalink() ),
			esc_html( $title )
		);
	}

	/**
	 * Render meta.
	 */
	private function render_meta( array $settings ) {
		$parts = array();

		if ( 'yes' === $settings['show_author'] ) {
			$parts[] = sprintf(
				'<span class="mjet-post-grid__meta-item">%s</span>',
				esc_html( get_the_author() )
			);
		}

		if ( 'yes' === $settings['show_date'] ) {
			$parts[] = sprintf(
				'<span class="mjet-post-grid__meta-item">%s</span>',
				esc_html( get_the_date() )
			);
		}

		if ( empty( $parts ) ) {
			return;
		}

		echo '<div class="mjet-post-grid__meta">' . implode( ' | ', $parts ) . '</div>';
	}

	/**
	 * Render excerpt.
	 */
	private function render_excerpt( array $settings ) {
		$length = absint( $settings['excerpt_length'] );
		$content = get_the_excerpt();

		if ( empty( $content ) ) {
			$content = get_the_content();
		}

		if ( $length > 0 ) {
			$content = wp_trim_words( wp_strip_all_tags( $content ), $length, '...' );
		} else {
			$content = wp_strip_all_tags( $content );
		}

		echo '<div class="mjet-post-grid__excerpt">' . esc_html( $content ) . '</div>';
	}

	/**
	 * Render read more link.
	 */
	private function render_read_more( array $settings ) {
		$label = $settings['read_more_text'];

		if ( '' === $label ) {
			return;
		}

		echo sprintf(
			'<a class="mjet-post-grid__read-more" href="%s">%s</a>',
			esc_url( get_permalink() ),
			esc_html( $label )
		);
	}

	/**
	 * Build query.
	 */
	private function build_query( array $settings ) {
		$args = array(
			'post_type' => $settings['post_type'],
			'post_status' => 'publish',
			'posts_per_page' => empty( $settings['posts_per_page'] ) ? -1 : intval( $settings['posts_per_page'] ),
			'orderby' => $settings['orderby'],
			'order' => $settings['order'],
			'offset' => intval( $settings['offset'] ),
			'ignore_sticky_posts' => ( 'yes' === $settings['ignore_sticky'] ),
			'no_found_rows' => true,
		);

		$include_ids = $this->explode_ids( $settings['include_ids'] );
		if ( ! empty( $include_ids ) ) {
			$args['post__in'] = $include_ids;
			$args['orderby'] = 'post__in';
			$args['posts_per_page'] = count( $include_ids );
		}

		$exclude_ids = $this->explode_ids( $settings['exclude_ids'] );
		if ( ! empty( $exclude_ids ) ) {
			$args['post__not_in'] = $exclude_ids;
		}

		if ( 'post' === $settings['post_type'] && ! empty( $settings['categories'] ) ) {
			$args['category__in'] = array_map( 'intval', (array) $settings['categories'] );
		}

		return new WP_Query( $args );
	}

	/**
	 * Helper: explode IDs.
	 */
	private function explode_ids( $value ) {
		if ( empty( $value ) ) {
			return array();
		}

		$ids = array_filter( array_map( 'trim', explode( ',', $value ) ) );

		return array_map( 'intval', $ids );
	}

	/**
	 * Helper: sanitize tag.
	 */
	private function sanitize_tag( $tag ) {
		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' );

		if ( in_array( strtolower( $tag ), $allowed, true ) ) {
			return strtolower( $tag );
		}

		return 'h3';
	}

	/**
	 * Build responsive grid wrapper classes.
	 */
	private function build_grid_wrapper_classes( array $settings ) {
		$classes = array( 'mjet-post-grid' );

		$desktop = $this->normalize_columns_value( isset( $settings['columns'] ) ? $settings['columns'] : null, '3' );
		$tablet = $this->normalize_columns_value( isset( $settings['columns_tablet'] ) ? $settings['columns_tablet'] : null, $desktop );
		$mobile = $this->normalize_columns_value( isset( $settings['columns_mobile'] ) ? $settings['columns_mobile'] : null, $tablet );

		$classes[] = 'mjet-post-grid--columns-desktop-' . $desktop;
		$classes[] = 'mjet-post-grid--columns-tablet-' . $tablet;
		$classes[] = 'mjet-post-grid--columns-mobile-' . $mobile;

		return $classes;
	}

	/**
	 * Normalize column values to allowed range with fallback.
	 */
	private function normalize_columns_value( $value, $fallback ) {
		$allowed = array( '1', '2', '3', '4' );
		$value = (string) ( $value ?? '' );

		if ( in_array( $value, $allowed, true ) ) {
			return $value;
		}

		return $fallback;
	}

	/**
	 * Retrieve public post types.
	 */
	private function get_public_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$options = array();

		foreach ( $post_types as $type => $object ) {
			$options[ $type ] = $object->labels->singular_name;
		}

		return $options;
	}

	/**
	 * Retrieve orderby options.
	 */
	private function get_orderby_options() {
		return array(
			'date' => __( 'Date', 'mj-elementor-templates' ),
			'title' => __( 'Title', 'mj-elementor-templates' ),
			'menu_order' => __( 'Menu Order', 'mj-elementor-templates' ),
			'modified' => __( 'Last Modified', 'mj-elementor-templates' ),
			'rand' => __( 'Random', 'mj-elementor-templates' ),
			'comment_count' => __( 'Comment Count', 'mj-elementor-templates' ),
		);
	}

	/**
	 * Retrieve term options for select2.
	 */
	private function get_terms_for_control( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}
}
