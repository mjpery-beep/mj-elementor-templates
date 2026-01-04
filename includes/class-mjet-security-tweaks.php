<?php
/**
 * Security hardening for MJ Elementor Templates.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Applies a small set of defensive tweaks without relying on external plugins.
 */
class MJET_Security_Tweaks {

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'harden_head' ), 0 );
        add_filter( 'the_generator', '__return_empty_string' );
        add_filter( 'login_errors', array( __CLASS__, 'filter_login_errors' ) );
        add_filter( 'wp_headers', array( __CLASS__, 'filter_headers' ) );
        add_filter( 'xmlrpc_enabled', '__return_false' );
        add_action( 'template_redirect', array( __CLASS__, 'block_author_enumeration' ), 1 );
        add_filter( 'rest_endpoints', array( __CLASS__, 'restrict_rest_user_endpoints' ) );
    }

    /**
     * Remove unnecessary discovery links that expose implementation details.
     */
    public static function harden_head() {
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'rsd_link' );
    }

    /**
     * Replace detailed login errors with a generic message.
     *
     * @param string $message Original login error.
     * @return string
     */
    public static function filter_login_errors( $message ) {
        if ( empty( $message ) ) {
            return $message;
        }

        return __( 'Identifiants invalides.', 'mj-elementor-templates' );
    }

    /**
     * Add protective HTTP headers and hide pingback endpoint.
     *
     * @param array $headers Current headers.
     * @return array
     */
    public static function filter_headers( $headers ) {
        if ( isset( $headers['X-Pingback'] ) ) {
            unset( $headers['X-Pingback'] );
        }

        if ( empty( $headers['X-Frame-Options'] ) ) {
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
        }

        if ( empty( $headers['X-Content-Type-Options'] ) ) {
            $headers['X-Content-Type-Options'] = 'nosniff';
        }

        if ( empty( $headers['Referrer-Policy'] ) ) {
            $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        }

        if ( is_ssl() && empty( $headers['Strict-Transport-Security'] ) ) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }

    /**
     * Prevent anonymous user enumeration via the public author query.
     */
    public static function block_author_enumeration() {
        if ( is_admin() || is_user_logged_in() ) {
            return;
        }

        if ( ! isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $author = sanitize_text_field( wp_unslash( $_GET['author'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_numeric( $author ) ) {
            wp_safe_redirect( home_url(), 301 );
            exit;
        }
    }

    /**
     * Hide the default users endpoints from unauthenticated REST requests.
     *
     * @param array $endpoints Registered REST endpoints.
     * @return array
     */
    public static function restrict_rest_user_endpoints( $endpoints ) {
        if ( is_user_logged_in() && current_user_can( 'list_users' ) ) {
            return $endpoints;
        }

        unset( $endpoints['/wp/v2/users'] );
        unset( $endpoints['/wp/v2/users/(?P<id>[\\d]+)'] );
        unset( $endpoints['/wp/v2/users/me'] );

        return $endpoints;
    }
}
