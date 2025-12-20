<?php
/**
 * Gestion des règles de ciblage pour MJ Elementor Templates.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe pour gérer les règles de ciblage des templates.
 */
class MJET_Target_Rules {

	/**
	 * Instance unique.
	 *
	 * @var MJET_Target_Rules|null
	 */
	private static $instance = null;

	/**
	 * Cache des templates.
	 *
	 * @var array
	 */
	private static $templates_cache = array();

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJET_Target_Rules
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé.
	 */
	private function __construct() {}

	/**
	 * Récupérer les options de localisation.
	 *
	 * @return array
	 */
	public function get_location_options() {
		$options = array(
			'basic'         => array(
				'label' => __( 'Basique', 'mj-elementor-templates' ),
				'value' => array(
					'basic-global'    => __( 'Site entier', 'mj-elementor-templates' ),
					'basic-singulars' => __( 'Toutes les pages singulières', 'mj-elementor-templates' ),
					'basic-archives'  => __( 'Toutes les archives', 'mj-elementor-templates' ),
				),
			),
			'special-pages' => array(
				'label' => __( 'Pages spéciales', 'mj-elementor-templates' ),
				'value' => array(
					'special-404'    => __( 'Page 404', 'mj-elementor-templates' ),
					'special-search' => __( 'Page de recherche', 'mj-elementor-templates' ),
					'special-blog'   => __( 'Page du blog', 'mj-elementor-templates' ),
					'special-front'  => __( 'Page d\'accueil', 'mj-elementor-templates' ),
				),
			),
			'post'          => array(
				'label' => __( 'Articles', 'mj-elementor-templates' ),
				'value' => array(
					'post|all'     => __( 'Tous les articles', 'mj-elementor-templates' ),
					'post|archive' => __( 'Archive des articles', 'mj-elementor-templates' ),
				),
			),
			'page'          => array(
				'label' => __( 'Pages', 'mj-elementor-templates' ),
				'value' => array(
					'page|all' => __( 'Toutes les pages', 'mj-elementor-templates' ),
				),
			),
		);

		// Ajouter les types de posts personnalisés.
		$custom_post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);

		foreach ( $custom_post_types as $post_type ) {
			$options[ $post_type->name ] = array(
				'label' => $post_type->label,
				'value' => array(
					$post_type->name . '|all' => sprintf(
						/* translators: %s: post type name */
						__( 'Tous les %s', 'mj-elementor-templates' ),
						$post_type->label
					),
				),
			);

			if ( $post_type->has_archive ) {
				$options[ $post_type->name ]['value'][ $post_type->name . '|archive' ] = sprintf(
					/* translators: %s: post type name */
					__( 'Archive %s', 'mj-elementor-templates' ),
					$post_type->label
				);
			}
		}

		return apply_filters( 'mjet_target_location_options', $options );
	}

	/**
	 * Récupérer les options de rôles utilisateurs.
	 *
	 * @return array
	 */
	public function get_user_role_options() {
		global $wp_roles;

		$options = array(
			'all'        => __( 'Tous', 'mj-elementor-templates' ),
			'logged-in'  => __( 'Connecté', 'mj-elementor-templates' ),
			'logged-out' => __( 'Déconnecté', 'mj-elementor-templates' ),
		);

		if ( isset( $wp_roles ) ) {
			foreach ( $wp_roles->get_names() as $role_key => $role_name ) {
				$options[ $role_key ] = translate_user_role( $role_name );
			}
		}

		return apply_filters( 'mjet_target_user_role_options', $options );
	}

	/**
	 * Récupérer les posts correspondant aux conditions.
	 *
	 * @param string $post_type Type de post.
	 * @param array  $option    Options de métadonnées.
	 * @return array
	 */
	public function get_posts_by_conditions( $post_type, $option ) {
		$cache_key = $post_type . '_' . md5( wp_json_encode( $option ) );

		if ( isset( self::$templates_cache[ $cache_key ] ) ) {
			return self::$templates_cache[ $cache_key ];
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => $option['location'],
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$matching_templates = array();

		foreach ( $posts as $post ) {
			$include_locations = get_post_meta( $post->ID, $option['location'], true );
			$exclude_locations = get_post_meta( $post->ID, $option['exclusion'], true );
			$user_roles        = get_post_meta( $post->ID, $option['users'], true );

			// Vérifier si le template correspond à la page actuelle.
			if ( $this->check_location_rules( $include_locations, $exclude_locations ) ) {
				if ( $this->check_user_role_rules( $user_roles ) ) {
					$matching_templates[] = array(
						'id'       => $post->ID,
						'priority' => 10,
					);
				}
			}
		}

		self::$templates_cache[ $cache_key ] = $matching_templates;

		return $matching_templates;
	}

	/**
	 * Vérifier les règles de localisation.
	 *
	 * @param array $include Locations à inclure.
	 * @param array $exclude Locations à exclure.
	 * @return bool
	 */
	private function check_location_rules( $include, $exclude ) {
		$include_match = false;
		$exclude_match = false;
		$current_id    = get_queried_object_id();
		$include_specific = isset( $include['specific'] ) && is_array( $include['specific'] ) ? array_map( 'absint', $include['specific'] ) : array();
		$exclude_specific = isset( $exclude['specific'] ) && is_array( $exclude['specific'] ) ? array_map( 'absint', $exclude['specific'] ) : array();

		// Vérifier les inclusions.
		if ( ! empty( $include['rule'] ) && is_array( $include['rule'] ) ) {
			foreach ( $include['rule'] as $rule ) {
				if ( $this->is_current_location( $rule ) ) {
					$include_match = true;
					break;
				}
			}
		}

		if ( ! $include_match && $current_id && ! empty( $include_specific ) ) {
			if ( in_array( (int) $current_id, $include_specific, true ) ) {
				$include_match = true;
			}
		}

		// Vérifier les exclusions.
		if ( $include_match && ! empty( $exclude['rule'] ) && is_array( $exclude['rule'] ) ) {
			foreach ( $exclude['rule'] as $rule ) {
				if ( $this->is_current_location( $rule ) ) {
					$exclude_match = true;
					break;
				}
			}
		}

		if ( $include_match && ! $exclude_match && $current_id && ! empty( $exclude_specific ) ) {
			if ( in_array( (int) $current_id, $exclude_specific, true ) ) {
				$exclude_match = true;
			}
		}

		return $include_match && ! $exclude_match;
	}

	/**
	 * Vérifier si la localisation correspond à la page actuelle.
	 *
	 * @param string $rule Règle de localisation.
	 * @return bool
	 */
	private function is_current_location( $rule ) {
		$result = false;

		// Règles basiques.
		switch ( $rule ) {
			case 'basic-global':
				$result = true;
				break;

			case 'basic-singulars':
				$result = is_singular();
				break;

			case 'basic-archives':
				$result = is_archive();
				break;

			case 'special-404':
				$result = is_404();
				break;

			case 'special-search':
				$result = is_search();
				break;

			case 'special-blog':
				$result = is_home();
				break;

			case 'special-front':
				$result = is_front_page();
				break;

			default:
				// Règles pour types de posts.
				if ( strpos( $rule, '|' ) !== false ) {
					list( $post_type, $scope ) = explode( '|', $rule );

					if ( 'all' === $scope ) {
						$result = is_singular( $post_type );
					} elseif ( 'archive' === $scope ) {
						$result = is_post_type_archive( $post_type );
					}
				}
				break;
		}

		return apply_filters( 'mjet_is_current_location', $result, $rule );
	}

	/**
	 * Vérifier les règles de rôles utilisateurs.
	 *
	 * @param array $rules Règles de rôles.
	 * @return bool
	 */
	private function check_user_role_rules( $rules ) {
		// Si pas de règles, autoriser tous les utilisateurs.
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return true;
		}

		// Si "all" est dans les règles, autoriser tous.
		if ( in_array( 'all', $rules, true ) ) {
			return true;
		}

		$is_logged_in = is_user_logged_in();

		// Vérifier logged-in / logged-out.
		if ( in_array( 'logged-in', $rules, true ) && $is_logged_in ) {
			return true;
		}

		if ( in_array( 'logged-out', $rules, true ) && ! $is_logged_in ) {
			return true;
		}

		// Vérifier les rôles spécifiques.
		if ( $is_logged_in ) {
			$current_user = wp_get_current_user();
			foreach ( $current_user->roles as $role ) {
				if ( in_array( $role, $rules, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Récupérer le libellé d'une localisation.
	 *
	 * @param string $key Clé de la localisation.
	 * @return string
	 */
	public static function get_location_label( $key ) {
		$options = self::get_instance()->get_location_options();

		foreach ( $options as $group ) {
			if ( isset( $group['value'][ $key ] ) ) {
				return $group['value'][ $key ];
			}
		}

		return $key;
	}

	/**
	 * Récupérer le libellé d'un rôle utilisateur.
	 *
	 * @param string $key Clé du rôle.
	 * @return string
	 */
	public static function get_user_role_label( $key ) {
		$options = self::get_instance()->get_user_role_options();
		return isset( $options[ $key ] ) ? $options[ $key ] : $key;
	}
}
