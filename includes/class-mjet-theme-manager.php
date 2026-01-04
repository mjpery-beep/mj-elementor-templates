<?php
/**
 * Theme manager to map Elementor templates to WordPress locations.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds theme-builder style locations (single, archives, special pages).
 */
class MJET_Theme_Manager {

    /**
     * Cached singular template (content replacement).
     *
     * @var array{type:string,id:int}
     */
    private static $singular_template = array(
        'type' => '',
        'id'   => 0,
    );

    /**
     * Cached full-page template (template_include override).
     *
     * @var array{type:string,id:int}
     */
    private static $full_template = array(
        'type' => '',
        'id'   => 0,
    );

    /**
     * Tracks IDs whose CSS has already been enqueued to avoid duplicates.
     *
     * @var int[]
     */
    private static $enqueued_css = array();

    /**
     * Cache resolved template IDs per type for the current request.
     *
     * @var array<string,int>
     */
    private static $resolved_ids = array();

    /**
     * Runtime context used by the theme-builder template file.
     *
     * @var array{type:string,id:int}
     */
    private static $render_context = array(
        'type' => '',
        'id'   => 0,
    );

    /**
     * Bootstrap hooks.
     */
    public static function init() {
        add_action( 'wp', array( __CLASS__, 'bootstrap' ), 8 );
        add_filter( 'the_content', array( __CLASS__, 'filter_singular_content' ), 999 );
        add_filter( 'template_include', array( __CLASS__, 'override_template' ), 99 );
    }

    /**
     * Determine which templates apply on the current front-end request.
     */
    public static function bootstrap() {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( is_singular( 'mjet-template' ) ) {
            return;
        }

        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return;
        }

        self::$singular_template = array( 'type' => '', 'id' => 0 );
        self::$full_template     = array( 'type' => '', 'id' => 0 );
        self::$render_context    = array( 'type' => '', 'id' => 0 );
        self::$resolved_ids      = array();

        $singular_type = self::detect_singular_type();
        if ( $singular_type ) {
            $template_id = self::resolve_template_id( $singular_type );
            if ( $template_id ) {
                self::$singular_template = array(
                    'type' => $singular_type,
                    'id'   => $template_id,
                );
                self::maybe_enqueue_css( $template_id );
            }
        }

        $full_type = self::detect_full_template_type();
        if ( $full_type ) {
            $template_id = self::resolve_template_id( $full_type );
            if ( $template_id ) {
                self::$full_template = array(
                    'type' => $full_type,
                    'id'   => $template_id,
                );
                self::maybe_enqueue_css( $template_id );
            }
        }
    }

    /**
     * Replace the_content for singular entries when a template is mapped.
     *
     * @param string $content Original content.
     * @return string
     */
    public static function filter_singular_content( $content ) {
        if ( ! self::$singular_template['id'] ) {
            return $content;
        }

        if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $queried_id = get_queried_object_id();
        if ( $queried_id && (int) $queried_id !== (int) get_the_ID() ) {
            return $content;
        }

        $template_id = self::$singular_template['id'];
        $rendered    = self::get_elementor_content( $template_id );

        if ( '' === trim( $rendered ) ) {
            return $content;
        }

        return $rendered;
    }

    /**
     * Override the main template for archive/search/404 like contexts.
     *
     * @param string $template Original template path.
     * @return string
     */
    public static function override_template( $template ) {
        if ( ! self::$full_template['id'] ) {
            return $template;
        }

        if ( is_feed() || is_embed() ) {
            return $template;
        }

        $override = MJET_DIR . 'templates/theme-builder.php';
        if ( ! file_exists( $override ) ) {
            return $template;
        }

        self::$render_context = self::$full_template;
        return $override;
    }

    /**
     * Return the template currently being rendered via theme-builder override.
     *
     * @return int
     */
    public static function get_current_template_id() {
        return isset( self::$render_context['id'] ) ? (int) self::$render_context['id'] : 0;
    }

    /**
     * Return the type key (type_*) of the current template override.
     *
     * @return string
     */
    public static function get_current_template_type() {
        return isset( self::$render_context['type'] ) ? self::$render_context['type'] : '';
    }

    /**
     * Render the current template override content.
     *
     * @return string
     */
    public static function render_current_template() {
        $template_id = self::get_current_template_id();
        if ( ! $template_id ) {
            return '';
        }

        return self::get_elementor_content( $template_id );
    }

    /**
     * Detects singular mapping type based on WordPress conditionals.
     *
     * @return string
     */
    private static function detect_singular_type() {
        if ( ! is_singular() ) {
            return '';
        }

        if ( post_type_exists( 'product' ) && is_singular( 'product' ) ) {
            return 'type_single_product';
        }

        if ( is_singular( 'page' ) ) {
            return 'type_single_page';
        }

        if ( is_singular( 'post' ) ) {
            return 'type_single_post';
        }

        return '';
    }

    /**
     * Detect contexts requiring full template overrides.
     *
     * @return string
     */
    private static function detect_full_template_type() {
        if ( is_404() ) {
            return 'type_404';
        }

        if ( is_search() ) {
            return 'type_search';
        }

        if ( post_type_exists( 'product' ) ) {
            $is_wc_archive = is_post_type_archive( 'product' ) || is_tax( array( 'product_cat', 'product_tag' ) );

            if ( function_exists( 'is_shop' ) ) {
                $is_wc_archive = $is_wc_archive || ( is_shop() && ! is_singular() );
            }

            if ( $is_wc_archive ) {
                return 'type_products_archive';
            }
        }

        if ( is_post_type_archive() || is_home() || is_date() || is_category() || is_tag() || is_author() || is_tax() ) {
            return 'type_archive';
        }

        return '';
    }

    /**
     * Resolve template ID for a given type with per-request caching.
     *
     * @param string $type Template type meta key.
     * @return int
     */
    private static function resolve_template_id( $type ) {
        if ( isset( self::$resolved_ids[ $type ] ) ) {
            return self::$resolved_ids[ $type ];
        }

        $template_id = MJ_Elementor_Templates::get_template_id( $type );
        $template_id = $template_id ? absint( $template_id ) : 0;

        self::$resolved_ids[ $type ] = $template_id;
        return self::$resolved_ids[ $type ];
    }

    /**
     * Retrieve Elementor-rendered content for a template.
     *
     * @param int $template_id Template ID.
     * @return string
     */
    private static function get_elementor_content( $template_id ) {
        if ( ! $template_id || ! class_exists( '\\Elementor\\Plugin' ) ) {
            return '';
        }

            return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );
    }

    /**
     * Enqueue Elementor CSS for the provided template.
     *
     * @param int $template_id Template identifier.
     */
    private static function maybe_enqueue_css( $template_id ) {
        $template_id = absint( $template_id );

        if ( ! $template_id || in_array( $template_id, self::$enqueued_css, true ) ) {
            return;
        }

        if ( class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
                $css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
            $css_file->enqueue();
            self::$enqueued_css[] = $template_id;
        }
    }
}
