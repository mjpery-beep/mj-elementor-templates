<?php
/**
 * Custom branding for the WordPress login screen.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Applies the theme identity to the WordPress login page.
 */
class MJET_Login_Customizer {

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_branding' ) );
        add_filter( 'login_headerurl', array( __CLASS__, 'login_header_url' ) );
        add_filter( 'login_headertext', array( __CLASS__, 'login_header_text' ) );
    }

    /**
     * Inject custom CSS for the login screen.
     */
    public static function enqueue_branding() {
        $logo_url = self::get_logo_url();
        $brand_color = apply_filters( 'mjet/login/brand_color', self::get_brand_color() );

        $rules = array();

        if ( $logo_url ) {
            $logo_width = (int) apply_filters( 'mjet/login/logo_width', 160 );
            $logo_height = (int) apply_filters( 'mjet/login/logo_height', 160 );

            $rules[] = sprintf(
                '#login h1 a {background-image:url("%1$s");background-size:contain;background-position:center;background-repeat:no-repeat;width:%2$dpx;height:%3$dpx;}
.login h1 {margin-bottom:24px;}',
                esc_url_raw( $logo_url ),
                max( $logo_width, 1 ),
                max( $logo_height, 1 )
            );
        }

        if ( $brand_color ) {
            $hover_color = self::get_hover_color( $brand_color );

            $rules[] = sprintf(
                '.wp-core-ui .button-primary {background-color:%1$s;border-color:%1$s;box-shadow:none;}
.wp-core-ui .button-primary:focus,.wp-core-ui .button-primary:hover {background-color:%2$s;border-color:%2$s;}
.login #nav a,.login #backtoblog a {color:%1$s;}',
                $brand_color,
                $hover_color
            );
        }

        if ( empty( $rules ) ) {
            return;
        }

        wp_register_style( 'mjet-login-branding', false, array(), MJET_VERSION );
        wp_enqueue_style( 'mjet-login-branding' );
        wp_add_inline_style( 'mjet-login-branding', implode( ' ', $rules ) );
    }

    /**
     * Resolve the logo URL to display on the login page.
     *
     * @return string
     */
    private static function get_logo_url() {
        $logo_id = (int) get_theme_mod( 'custom_logo' );
        if ( $logo_id > 0 ) {
            $logo = wp_get_attachment_image_src( $logo_id, 'full' );
            if ( is_array( $logo ) && ! empty( $logo[0] ) ) {
                return (string) $logo[0];
            }
        }

        $site_icon = get_site_icon_url( 192 );
        if ( $site_icon ) {
            return (string) $site_icon;
        }

        /**
         * Allow third-parties to provide a fallback logo.
         */
        $fallback = apply_filters( 'mjet/login/fallback_logo', '' );
        if ( is_string( $fallback ) ) {
            return $fallback;
        }

        return '';
    }

    /**
     * Retrieve a primary brand color if available.
     *
     * @return string
     */
    private static function get_brand_color() {
        $candidates = array(
            get_theme_mod( 'mj_brand_primary_color' ),
            get_theme_mod( 'accent_color' ),
        );

        foreach ( $candidates as $candidate ) {
            $sanitized = is_string( $candidate ) ? sanitize_hex_color( $candidate ) : '';
            if ( $sanitized ) {
                return $sanitized;
            }
        }

        return '';
    }

    /**
     * Compute a hover color derived from the base brand color.
     *
     * @param string $color Hexadecimal color.
     * @return string
     */
    private static function get_hover_color( $color ) {
        $base = sanitize_hex_color( $color );
        if ( ! $base ) {
            return $color;
        }

        $rgb = sscanf( $base, '#%02x%02x%02x' );
        if ( ! is_array( $rgb ) || 3 !== count( $rgb ) ) {
            return $base;
        }

        list( $r, $g, $b ) = array_map( 'intval', $rgb );
        $factor = 0.85;

        $r = max( min( (int) floor( $r * $factor ), 255 ), 0 );
        $g = max( min( (int) floor( $g * $factor ), 255 ), 0 );
        $b = max( min( (int) floor( $b * $factor ), 255 ), 0 );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Point the login logo to the site homepage.
     *
     * @param string $url Original URL.
     * @return string
     */
    public static function login_header_url( $url ) {
        unset( $url );
        return home_url( '/' );
    }

    /**
     * Replace the login logo title attribute with the site name.
     *
     * @param string $text Original text.
     * @return string
     */
    public static function login_header_text( $text ) {
        unset( $text );
        return get_bloginfo( 'name' );
    }
}
