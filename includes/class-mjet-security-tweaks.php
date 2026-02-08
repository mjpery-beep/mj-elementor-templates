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
        /**
         * Remove unnecessary discovery links that expose implementation details.
         * For comprehensive security hardening, consider using a dedicated security plugin that offers features like firewall, malware scanning, and login protection.
         */
        add_action( 'init', array( __CLASS__, 'harden_head' ), 0 );
        /**
         * Hide WordPress version to prevent targeted attacks based on known vulnerabilities.
         * Note: This is a basic obfuscation technique and should not be solely relied upon for security. Always keep WordPress and plugins updated.
         */
        add_filter( 'the_generator', '__return_empty_string' );
        /**
         * Replace detailed login errors with a generic message to prevent username enumeration and information disclosure.
         * For enhanced login security, consider implementing features like two-factor authentication and login attempt limits using a dedicated security plugin.
         */
        add_filter( 'login_errors', array( __CLASS__, 'filter_login_errors' ) );
        /**
         * Add protective HTTP headers and hide pingback endpoint.
         * Note: For comprehensive security hardening, consider using a dedicated security plugin that offers features like firewall, malware scanning, and login protection.
         */
        add_filter( 'wp_headers', array( __CLASS__, 'filter_headers' ) );
        /**
         * Disable XML-RPC to prevent abuse and potential vulnerabilities.
         * If XML-RPC is needed for specific features, consider using a plugin that selectively enables it with proper security measures.
         */
        add_filter( 'xmlrpc_enabled', '__return_false' );
        /** 
         * Disable author archive access to prevent user enumeration via ?author=N 
         * */
        add_action( 'template_redirect', array( __CLASS__, 'block_author_enumeration' ), 1 );
        /**
         * Restrict REST API user endpoints to authenticated users with proper capabilities.
         * This prevents user enumeration and exposure of user data via the REST API.
         */
        add_filter( 'rest_endpoints', array( __CLASS__, 'restrict_rest_user_endpoints' ) );
        /** 
         * Disable URL guessing on 404s to prevent unwanted redirects and potential information disclosure.
         * Also ensure proper 404 status and headers to prevent service worker caching issues.
         */
        add_filter( 'redirect_canonical', array( __CLASS__, 'disable_url_guessing' ) );
        add_filter( 'redirect_guess_404_permalink', '__return_false' );
        add_action( 'template_redirect', array( __CLASS__, 'handle_404_headers' ), 0 );
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

    /**
     * Disable WordPress URL guessing on 404 pages.
     *
     * Prevents WordPress from automatically redirecting to similar URLs,
     * which can cause unwanted behavior and potential security issues.
     *
     * @param string|false $redirect_url The redirect URL.
     * @return string|false
     */
    public static function disable_url_guessing( $redirect_url ) {
        if ( is_404() ) {
            return false;
        }

        return $redirect_url;
    }

    /**
     * Ensure 404 pages return proper status and headers to prevent service worker caching.
     */
    public static function handle_404_headers() {
        if ( is_404() ) {
            status_header( 404 );
            nocache_headers();
        }
    }
}
