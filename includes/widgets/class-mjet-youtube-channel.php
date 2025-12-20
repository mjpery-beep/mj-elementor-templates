<?php
/**
 * Widget Chaine YouTube pour Elementor.
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
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget listant les videos d'une chaine YouTube.
 */
class MJET_Youtube_Channel extends Widget_Base {

	/**
	 * Nom du widget.
	 */
	public function get_name() {
		return 'mjet-youtube-channel';
	}

	/**
	 * Titre du widget.
	 */
	public function get_title() {
		return __( 'Chaine YouTube', 'mj-elementor-templates' );
	}

	/**
	 * Icone du widget.
	 */
	public function get_icon() {
		return 'eicon-youtube';
	}

	/**
	 * Categories du widget.
	 */
	public function get_categories() {
		return array( 'mjet-widgets' );
	}

	/**
	 * Mots cles.
	 */
	public function get_keywords() {
		return array( 'youtube', 'video', 'channel', 'media', 'mjet' );
	}

	/**
	 * Styles requis.
	 */
	public function get_style_depends() {
		return array( 'mjet-youtube-channel' );
	}

	/**
	 * Enregistrer les controles.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Controles de contenu.
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'section_source',
			array(
				'label' => __( 'Source', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'api_key',
			array(
				'label'       => __( 'Cle API YouTube', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'AIzaSy...', 'mj-elementor-templates' ),
				'description' => __( 'Generez une cle API dans Google Cloud Console (YouTube Data API v3).', 'mj-elementor-templates' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'channel_id',
			array(
				'label'       => __( 'ID de la chaine', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'UCxxxxxxxxxxxx', 'mj-elementor-templates' ),
				'description' => __( 'Utilisez l\'ID unique de la chaine YouTube.', 'mj-elementor-templates' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'max_results',
			array(
				'label'   => __( 'Nombre de videos', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
				'input_attrs' => array(
					'min' => 1,
					'max' => 50,
				),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Disposition', 'mj-elementor-templates' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid' => __( 'Grille', 'mj-elementor-templates' ),
					'list' => __( 'Liste', 'mj-elementor-templates' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'           => __( 'Colonnes', 'mj-elementor-templates' ),
				'type'            => Controls_Manager::SLIDER,
				'size_units'      => array( '' ),
				'range'           => array(
					'' => array(
						'min'  => 1,
						'max'  => 6,
						'step' => 1,
					),
				),
				'default'        => array(
					'size' => 3,
				),
				'tablet_default' => array(
					'size' => 2,
				),
				'mobile_default' => array(
					'size' => 1,
				),
				'selectors'      => array(
					'{{WRAPPER}} .mjet-youtube-channel[data-layout="grid"]' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
				),
				'condition'      => array(
					'layout' => 'grid',
				),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Afficher le titre', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'        => __( 'Afficher la date', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => __( 'Afficher la description', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'description_length',
			array(
				'label'     => __( 'Longueur description', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 20,
				'condition' => array(
					'show_description' => 'yes',
				),
				'input_attrs' => array(
					'min' => 5,
					'max' => 100,
				),
			)
		);

		$this->add_control(
			'show_watch_button',
			array(
				'label'        => __( 'Afficher le bouton', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'watch_button_text',
			array(
				'label'     => __( 'Texte du bouton', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Voir sur YouTube', 'mj-elementor-templates' ),
				'condition' => array(
					'show_watch_button' => 'yes',
				),
			)
		);

		$this->add_control(
			'open_in_new_tab',
			array(
				'label'        => __( 'Ouvrir dans un nouvel onglet', 'mj-elementor-templates' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Oui', 'mj-elementor-templates' ),
				'label_off'    => __( 'Non', 'mj-elementor-templates' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'cache_duration',
			array(
				'label'       => __( 'Cache (minutes)', 'mj-elementor-templates' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 60,
				'input_attrs' => array(
					'min'  => 5,
					'step' => 5,
				),
				'description' => __( 'Duree pendant laquelle les videos sont memorisees.', 'mj-elementor-templates' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Controles de style.
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'section_layout_style',
			array(
				'label' => __( 'Disposition', 'mj-elementor-templates' ),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => __( 'Espace horizontal', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-youtube-channel' => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => __( 'Espace vertical', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-youtube-channel' => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_card_style',
			array(
				'label' => __( 'Carte video', 'mj-elementor-templates' ),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Marge interne', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-youtube-channel__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_background_heading',
			array(
				'label' => __( 'Arriere-plan', 'mj-elementor-templates' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'card_background',
				'label'    => __( 'Arriere-plan', 'mj-elementor-templates' ),
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__item',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__item',
			)
		);

		$this->add_responsive_control(
			'card_border_radius',
			array(
				'label'      => __( 'Rayon', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-youtube-channel__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__item',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_style',
			array(
				'label' => __( 'Titre', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => __( 'Couleur (hover)', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__title',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_meta_style',
			array(
				'label' => __( 'Metas', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__meta',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_description_style',
			array(
				'label' => __( 'Description', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Couleur', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__description' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__description',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			array(
				'label' => __( 'Bouton', 'mj-elementor-templates' ),
				'condition' => array(
					'show_watch_button' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__cta',
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => __( 'Normal', 'mj-elementor-templates' ),
			)
		);

		$this->add_control(
			'button_color',
			array(
				'label'     => __( 'Couleur du texte', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__cta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_background',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__cta' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__cta',
			)
		);

		$this->add_responsive_control(
			'button_border_radius',
			array(
				'label'      => __( 'Rayon', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-youtube-channel__cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Marge interne', 'mj-elementor-templates' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .mjet-youtube-channel__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
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
			'button_hover_color',
			array(
				'label'     => __( 'Couleur du texte', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__cta:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_background',
			array(
				'label'     => __( 'Couleur de fond', 'mj-elementor-templates' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mjet-youtube-channel__cta:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_hover_shadow',
				'selector' => '{{WRAPPER}} .mjet-youtube-channel__cta:hover',
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	/**
	 * Rendu du widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$layout   = isset( $settings['layout'] ) && 'list' === $settings['layout'] ? 'list' : 'grid';

		$videos = $this->get_videos( $settings );

		if ( is_wp_error( $videos ) ) {
			echo '<div class="mjet-youtube-channel__notice">' . esc_html( $videos->get_error_message() ) . '</div>';
			return;
		}

		if ( empty( $videos ) ) {
			echo '<div class="mjet-youtube-channel__notice">' . esc_html__( 'Aucune video a afficher.', 'mj-elementor-templates' ) . '</div>';
			return;
		}

		$this->add_render_attribute( 'wrapper', 'class', 'mjet-youtube-channel' );
		$this->add_render_attribute( 'wrapper', 'data-layout', $layout );

		$open_new_tab = isset( $settings['open_in_new_tab'] ) && 'yes' === $settings['open_in_new_tab'];
		$link_target  = $open_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ' rel="noopener"';
		$show_title   = ! isset( $settings['show_title'] ) || 'yes' === $settings['show_title'];
		$show_date    = isset( $settings['show_date'] ) && 'yes' === $settings['show_date'];
		$show_desc    = isset( $settings['show_description'] ) && 'yes' === $settings['show_description'];
		$desc_length  = isset( $settings['description_length'] ) ? max( 5, absint( $settings['description_length'] ) ) : 20;
		$show_button  = isset( $settings['show_watch_button'] ) && 'yes' === $settings['show_watch_button'];
		$button_text  = ! empty( $settings['watch_button_text'] ) ? $settings['watch_button_text'] : __( 'Voir sur YouTube', 'mj-elementor-templates' );

		echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';

		foreach ( $videos as $video ) {
			$video_id    = isset( $video['id'] ) ? $video['id'] : '';
			$title       = isset( $video['title'] ) ? $video['title'] : '';
			$description = isset( $video['description'] ) ? $video['description'] : '';
			$thumb_url   = isset( $video['thumbnail'] ) ? $video['thumbnail'] : '';
			$date        = isset( $video['published_at'] ) ? $video['published_at'] : '';

			$video_url = 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id );

			echo '<article class="mjet-youtube-channel__item">';

			if ( $thumb_url ) {
				echo '<a class="mjet-youtube-channel__thumbnail" href="' . esc_url( $video_url ) . '"' . $link_target . '>';
				echo '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $title ) . '">';
				echo '<span class="mjet-youtube-channel__play" aria-hidden="true"></span>';
				echo '</a>';
			}

			echo '<div class="mjet-youtube-channel__content">';

			if ( $show_title && $title ) {
				echo '<h3 class="mjet-youtube-channel__title"><a href="' . esc_url( $video_url ) . '"' . $link_target . '>' . esc_html( $title ) . '</a></h3>';
			}

			if ( $show_date && $date ) {
				$timestamp = strtotime( $date );
				if ( $timestamp ) {
					echo '<div class="mjet-youtube-channel__meta">' . esc_html( date_i18n( get_option( 'date_format', 'F j, Y' ), $timestamp ) ) . '</div>';
				}
			}

			if ( $show_desc && $description ) {
				$trimmed = wp_trim_words( wp_strip_all_tags( $description ), $desc_length, '…' );
				echo '<div class="mjet-youtube-channel__description">' . esc_html( $trimmed ) . '</div>';
			}

			if ( $show_button ) {
				echo '<a class="mjet-youtube-channel__cta" href="' . esc_url( $video_url ) . '"' . $link_target . '>' . esc_html( $button_text ) . '</a>';
			}

			echo '</div>';
			echo '</article>';
		}

		echo '</div>';
	}

	/**
	 * Recupere les videos.
	 *
	 * @param array $settings Parametres du widget.
	 * @return array|\WP_Error
	 */
	private function get_videos( $settings ) {
		$api_key    = isset( $settings['api_key'] ) ? trim( $settings['api_key'] ) : '';
		$channel_id = isset( $settings['channel_id'] ) ? trim( $settings['channel_id'] ) : '';
		$max        = isset( $settings['max_results'] ) ? max( 1, min( 50, absint( $settings['max_results'] ) ) ) : 6;
		$cache      = isset( $settings['cache_duration'] ) ? max( 5, absint( $settings['cache_duration'] ) ) : 60;

		if ( empty( $api_key ) || empty( $channel_id ) ) {
			if ( $this->is_preview_mode() ) {
				return $this->get_preview_videos();
			}

			return new \WP_Error( 'mjet_youtube_missing_settings', __( 'Renseignez la cle API et l\'ID de chaine.', 'mj-elementor-templates' ) );
		}

		$transient_key = 'mjet_youtube_' . md5( $channel_id . '|' . $max . '|' . $api_key );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'key'        => $api_key,
				'channelId'  => $channel_id,
				'part'       => 'snippet',
				'order'      => 'date',
				'maxResults' => $max,
				'type'       => 'video',
			),
			'https://www.googleapis.com/youtube/v3/search'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );

		if ( 200 !== $status || empty( $body ) ) {
			return new \WP_Error( 'mjet_youtube_http_error', __( 'Impossible de recuperer les videos YouTube.', 'mj-elementor-templates' ) );
		}

		$data = json_decode( $body, true );

		if ( ! isset( $data['items'] ) || ! is_array( $data['items'] ) ) {
			return new \WP_Error( 'mjet_youtube_invalid_data', __( 'La reponse YouTube est invalide.', 'mj-elementor-templates' ) );
		}

		$videos = array();

		foreach ( $data['items'] as $item ) {
			if ( empty( $item['id']['videoId'] ) ) {
				continue;
			}

			$video_id = sanitize_text_field( $item['id']['videoId'] );
			$snippet  = isset( $item['snippet'] ) ? $item['snippet'] : array();

			$videos[] = array(
				'id'           => $video_id,
				'title'        => isset( $snippet['title'] ) ? sanitize_text_field( $snippet['title'] ) : '',
				'description'  => isset( $snippet['description'] ) ? $snippet['description'] : '',
				'published_at' => isset( $snippet['publishedAt'] ) ? $snippet['publishedAt'] : '',
				'thumbnail'    => isset( $snippet['thumbnails']['high']['url'] ) ? esc_url_raw( $snippet['thumbnails']['high']['url'] ) : ( isset( $snippet['thumbnails']['medium']['url'] ) ? esc_url_raw( $snippet['thumbnails']['medium']['url'] ) : '' ),
			);
		}

		set_transient( $transient_key, $videos, $cache * MINUTE_IN_SECONDS );

		return $videos;
	}

	/**
	 * Videos factices pour l'editeur Elementor.
	 *
	 * @return array
	 */
	private function get_preview_videos() {
		return array(
			array(
				'id'           => 'dQw4w9WgXcQ',
				'title'        => __( 'Video exemple 1', 'mj-elementor-templates' ),
				'description'  => __( 'Description demo pour illustrer la liste des videos.', 'mj-elementor-templates' ),
				'published_at' => gmdate( 'c', strtotime( '-3 days' ) ),
				'thumbnail'    => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
			),
			array(
				'id'           => '9bZkp7q19f0',
				'title'        => __( 'Video exemple 2', 'mj-elementor-templates' ),
				'description'  => __( 'Utilisez vos propres donnees en renseignant la cle API et la chaine.', 'mj-elementor-templates' ),
				'published_at' => gmdate( 'c', strtotime( '-10 days' ) ),
				'thumbnail'    => 'https://i.ytimg.com/vi/9bZkp7q19f0/hqdefault.jpg',
			),
			array(
				'id'           => '3JZ_D3ELwOQ',
				'title'        => __( 'Video exemple 3', 'mj-elementor-templates' ),
				'description'  => __( 'Apercu pour lediteur Elementor sans cle API.', 'mj-elementor-templates' ),
				'published_at' => gmdate( 'c', strtotime( '-20 days' ) ),
				'thumbnail'    => 'https://i.ytimg.com/vi/3JZ_D3ELwOQ/hqdefault.jpg',
			),
		);
	}

	/**
	 * Determine si l'on est en previsualisation Elementor.
	 *
	 * @return bool
	 */
	private function is_preview_mode() {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$plugin = Plugin::$instance;

			if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
				return true;
			}

			if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
				return true;
			}
		}

		return false;
	}
}
