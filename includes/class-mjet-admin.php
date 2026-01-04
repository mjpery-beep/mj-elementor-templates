<?php
/**
 * Admin pour MJ Elementor Templates.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe d'administration pour gérer le post type et les métaboxes.
 */
class MJET_Admin {

	/**
	 * Instance unique.
	 *
	 * @var MJET_Admin|null
	 */
	private static $instance = null;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJET_Admin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Retourne la liste des types de templates disponibles.
	 *
	 * @return array
	 */
	private function get_template_type_options() {
		$types = array(
			'type_header'           => __( 'Header', 'mj-elementor-templates' ),
			'type_before_footer'    => __( 'Before Footer', 'mj-elementor-templates' ),
			'type_footer'           => __( 'Footer', 'mj-elementor-templates' ),
			'custom'                => __( 'Custom Block', 'mj-elementor-templates' ),
			'type_single_page'      => __( 'Single Page', 'mj-elementor-templates' ),
			'type_single_post'      => __( 'Single Post', 'mj-elementor-templates' ),
			'type_archive'          => __( 'Archive', 'mj-elementor-templates' ),
			'type_search'           => __( 'Search Results Page', 'mj-elementor-templates' ),
			'type_single_product'   => __( 'Produit (WooCommerce)', 'mj-elementor-templates' ),
			'type_products_archive' => __( 'Products Archive', 'mj-elementor-templates' ),
			'type_404'              => __( '404 Page', 'mj-elementor-templates' ),
		);

		if ( ! post_type_exists( 'product' ) ) {
			unset( $types['type_single_product'], $types['type_products_archive'] );
		}

		return apply_filters( 'mjet_template_type_options', $types );
	}

	/**
	 * Retourne les règles d'inclusion par défaut selon le type.
	 *
	 * @param string $type Type de template.
	 * @return array
	 */
	public static function get_default_include_rules_for_type( $type ) {
		switch ( $type ) {
			case 'type_404':
				return array(
					'rule'     => array( 'special-404' ),
					'specific' => array(),
				);

			case 'type_search':
				return array(
					'rule'     => array( 'special-search' ),
					'specific' => array(),
				);

			case 'type_archive':
				return array(
					'rule'     => array( 'basic-archives' ),
					'specific' => array(),
				);

			case 'type_single_page':
				return array(
					'rule'     => array( 'page|all' ),
					'specific' => array(),
				);

			case 'type_single_post':
				return array(
					'rule'     => array( 'post|all' ),
					'specific' => array(),
				);

			case 'type_single_product':
				return array(
					'rule'     => array( 'product|all' ),
					'specific' => array(),
				);

			case 'type_products_archive':
				return array(
					'rule'     => array( 'product|archive' ),
					'specific' => array(),
				);

			default:
				return array();
		}
	}

	/**
	 * Constructeur.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 50 );
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'manage_mjet-template_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_mjet-template_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_type_filter_dropdown' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_admin_list_by_type' ) );
		add_filter( 'single_template', array( $this, 'load_canvas_template' ) );
		add_filter( 'template_include', array( $this, 'force_canvas_template' ), 99999 );
		add_action( 'template_redirect', array( $this, 'block_template_frontend' ) );

		// Charger les icônes Elementor dans l'éditeur.
		add_action( 'elementor/init', array( $this, 'load_elementor_admin' ), 0 );

		// Forcer le template Canvas pour Elementor.
		add_action( 'wp_insert_post', array( $this, 'set_canvas_template_on_create' ), 10, 2 );
		add_filter( 'get_post_metadata', array( $this, 'force_canvas_template_meta' ), 10, 4 );

		// Ajouter le post type au support CPT d'Elementor.
		add_action( 'elementor/init', array( $this, 'add_elementor_cpt_support' ) );

		// Forcer le document Elementor à utiliser le canvas.
		add_action( 'elementor/document/after_save', array( $this, 'ensure_canvas_after_save' ), 10, 2 );
	}

	/**
	 * S'assurer que le template Canvas est défini après chaque sauvegarde.
	 *
	 * @param \Elementor\Core\Base\Document $document Document Elementor.
	 * @param array                         $data     Données de sauvegarde.
	 */
	public function ensure_canvas_after_save( $document, $data ) {
		$post_id = $document->get_main_id();
		$post = get_post( $post_id );

		if ( $post && 'mjet-template' === $post->post_type ) {
			// Forcer le template Canvas directement dans la base.
			global $wpdb;
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_value' => 'elementor_canvas' ),
				array(
					'post_id'  => $post_id,
					'meta_key' => '_wp_page_template',
				)
			);

			// Si le meta n'existe pas, le créer.
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_wp_page_template'",
				$post_id
			) );

			if ( ! $exists ) {
				$wpdb->insert(
					$wpdb->postmeta,
					array(
						'post_id'    => $post_id,
						'meta_key'   => '_wp_page_template',
						'meta_value' => 'elementor_canvas',
					)
				);
			}
		}
	}

	/**
	 * Ajouter le post type mjet-template au support CPT d'Elementor.
	 */
	public function add_elementor_cpt_support() {
		$cpt_support = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );

		if ( ! is_array( $cpt_support ) ) {
			$cpt_support = array( 'page', 'post' );
		}

		if ( ! in_array( 'mjet-template', $cpt_support, true ) ) {
			$cpt_support[] = 'mjet-template';
			update_option( 'elementor_cpt_support', $cpt_support );
		}
	}

	/**
	 * Définir le template Canvas lors de la création d'un nouveau post.
	 *
	 * @param int     $post_id ID du post.
	 * @param WP_Post $post    Objet post.
	 */
	public function set_canvas_template_on_create( $post_id, $post ) {
		if ( 'mjet-template' !== $post->post_type ) {
			return;
		}

		// Définir le template Elementor Canvas.
		$current_template = get_post_meta( $post_id, '_wp_page_template', true );
		if ( empty( $current_template ) || 'default' === $current_template ) {
			update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
		}
	}

	/**
	 * Forcer le meta _wp_page_template à elementor_canvas pour les templates MJET.
	 *
	 * @param mixed  $value     Valeur actuelle.
	 * @param int    $object_id ID de l'objet.
	 * @param string $meta_key  Clé du meta.
	 * @param bool   $single    Si on veut une seule valeur.
	 * @return mixed
	 */
	public function force_canvas_template_meta( $value, $object_id, $meta_key, $single ) {
		static $is_checking = array();

		if ( '_wp_page_template' !== $meta_key ) {
			return $value;
		}

		// Éviter la récursion.
		if ( isset( $is_checking[ $object_id ] ) ) {
			return $value;
		}

		$is_checking[ $object_id ] = true;

		// Vérifier le post type sans déclencher get_post_meta.
		global $wpdb;
		$post_type = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_type FROM {$wpdb->posts} WHERE ID = %d",
			$object_id
		) );

		unset( $is_checking[ $object_id ] );

		if ( 'mjet-template' !== $post_type ) {
			return $value;
		}

		// Retourner elementor_canvas pour forcer l'utilisation du template Canvas.
		return $single ? 'elementor_canvas' : array( 'elementor_canvas' );
	}

	/**
	 * Charger les scripts admin pour Elementor.
	 */
	public function load_elementor_admin() {
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_elementor_icons' ) );
	}

	/**
	 * Enqueue icônes Elementor.
	 */
	public function enqueue_elementor_icons() {
		wp_enqueue_style( 'mjet-icons', MJET_URL . 'assets/css/mjet-icons.css', array(), MJET_VERSION );
	}

	/**
	 * Enregistrer le post type mjet-template.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Templates Elementor', 'mj-elementor-templates' ),
			'singular_name'      => __( 'Template Elementor', 'mj-elementor-templates' ),
			'menu_name'          => __( 'MJ Templates', 'mj-elementor-templates' ),
			'name_admin_bar'     => __( 'Template Elementor', 'mj-elementor-templates' ),
			'add_new'            => __( 'Ajouter', 'mj-elementor-templates' ),
			'add_new_item'       => __( 'Ajouter un template', 'mj-elementor-templates' ),
			'new_item'           => __( 'Nouveau template', 'mj-elementor-templates' ),
			'edit_item'          => __( 'Modifier le template', 'mj-elementor-templates' ),
			'view_item'          => __( 'Voir le template', 'mj-elementor-templates' ),
			'all_items'          => __( 'Tous les templates', 'mj-elementor-templates' ),
			'search_items'       => __( 'Rechercher des templates', 'mj-elementor-templates' ),
			'not_found'          => __( 'Aucun template trouvé.', 'mj-elementor-templates' ),
			'not_found_in_trash' => __( 'Aucun template dans la corbeille.', 'mj-elementor-templates' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-editor-kitchensink',
			'supports'            => array( 'title', 'thumbnail', 'elementor' ),
			'menu_position'       => 5,
			'rewrite'             => array( 'slug' => 'mjet-template', 'with_front' => false ),
			'capabilities'        => array(
				'edit_post'              => 'manage_options',
				'read_post'              => 'read',
				'delete_post'            => 'manage_options',
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'create_posts'           => 'manage_options',
			),
		);

		register_post_type( 'mjet-template', $args );
	}

	/**
	 * Ajouter le menu admin.
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'MJ Templates', 'mj-elementor-templates' ),
			__( 'MJ Templates', 'mj-elementor-templates' ),
			'manage_options',
			'mjet-templates',
			array( $this, 'render_settings_page' ),
			'dashicons-schedule',
			59
		);

		add_submenu_page(
			'mjet-templates',
			__( 'Gestionnaire de thème', 'mj-elementor-templates' ),
			__( 'Gestionnaire de thème', 'mj-elementor-templates' ),
			'manage_options',
			'mjet-theme-manager',
			array( $this, 'render_theme_manager_page' )
		);

		add_submenu_page(
			'mjet-templates',
			__( 'Tous les templates', 'mj-elementor-templates' ),
			__( 'Tous les templates', 'mj-elementor-templates' ),
			'manage_options',
			'edit.php?post_type=mjet-template'
		);

		add_submenu_page(
			'mjet-templates',
			__( 'Ajouter', 'mj-elementor-templates' ),
			__( 'Ajouter', 'mj-elementor-templates' ),
			'manage_options',
			'post-new.php?post_type=mjet-template'
		);
	}

	/**
	 * Page Gestionnaire de thème.
	 */
	public function render_theme_manager_page() {
		$template_types = $this->get_template_type_options();
		unset( $template_types['custom'] );

		?>
		<div class="wrap mjet-theme-manager">
			<h1><?php esc_html_e( 'Gestionnaire de thème', 'mj-elementor-templates' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Attribuez vos templates MJET aux différentes zones du site (header, archives, pages produits, etc.).', 'mj-elementor-templates' ); ?>
			</p>

			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Type', 'mj-elementor-templates' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Templates assignés', 'mj-elementor-templates' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions rapides', 'mj-elementor-templates' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $template_types as $type_key => $type_label ) :
						$templates = get_posts( array(
							'post_type'      => 'mjet-template',
							'posts_per_page' => -1,
							'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
							'orderby'        => 'date',
							'order'          => 'DESC',
							'meta_query'     => array(
								array(
									'key'   => 'mjet_template_type',
									'value' => $type_key,
								),
							),
						) );

						$create_link = add_query_arg(
							array(
								'post_type' => 'mjet-template',
								'mjet_type' => $type_key,
							),
							admin_url( 'post-new.php' )
						);

						$list_link = add_query_arg(
							array(
								'post_type'        => 'mjet-template',
								'mjet_type_filter' => $type_key,
							),
							admin_url( 'edit.php' )
						);
						?>
						<tr>
							<th scope="row" class="mjet-theme-manager__type">
								<?php echo esc_html( $type_label ); ?>
							</th>
							<td class="mjet-theme-manager__templates">
								<?php if ( ! empty( $templates ) ) : ?>
									<ul class="mjet-theme-manager__template-list">
										<?php foreach ( $templates as $template ) :
											$edit_link = get_edit_post_link( $template->ID );
											$status_object = get_post_status_object( $template->post_status );
											$status_label  = $status_object ? $status_object->label : $template->post_status;
											$include       = get_post_meta( $template->ID, 'mjet_target_include_locations', true );
											$exclude       = get_post_meta( $template->ID, 'mjet_target_exclude_locations', true );
											$user_roles    = get_post_meta( $template->ID, 'mjet_target_user_roles', true );

											$include_rules = array();
											if ( ! empty( $include['rule'] ) && is_array( $include['rule'] ) ) {
												foreach ( $include['rule'] as $rule_key ) {
													$include_rules[] = MJET_Target_Rules::get_location_label( $rule_key );
												}
											}

											if ( empty( $include_rules ) ) {
												$include_rules[] = __( 'Aucune règle définie', 'mj-elementor-templates' );
											}

											if ( ! empty( $include['specific'] ) && is_array( $include['specific'] ) ) {
												$include_rules[] = sprintf(
													_n( '%d page spécifique', '%d pages spécifiques', count( $include['specific'] ), 'mj-elementor-templates' ),
													count( $include['specific'] )
												);
											}

											$exclude_rules = array();
											if ( ! empty( $exclude['rule'] ) && is_array( $exclude['rule'] ) ) {
												foreach ( $exclude['rule'] as $rule_key ) {
													$exclude_rules[] = MJET_Target_Rules::get_location_label( $rule_key );
												}
											}

											if ( ! empty( $exclude['specific'] ) && is_array( $exclude['specific'] ) ) {
												$exclude_rules[] = sprintf(
													_n( '%d page exclue', '%d pages exclues', count( $exclude['specific'] ), 'mj-elementor-templates' ),
													count( $exclude['specific'] )
												);
											}

											$roles_summary = __( 'Tous les utilisateurs', 'mj-elementor-templates' );
											if ( ! empty( $user_roles ) && is_array( $user_roles ) ) {
												$roles_labels = array();
												foreach ( $user_roles as $role_key ) {
													if ( 'all' === $role_key ) {
														$roles_labels = array( __( 'Tous les utilisateurs', 'mj-elementor-templates' ) );
														break;
													}
													$roles_labels[] = MJET_Target_Rules::get_user_role_label( $role_key );
												}
												if ( ! empty( $roles_labels ) ) {
													$roles_summary = implode( ', ', array_unique( $roles_labels ) );
												}
											}

											$is_global = ! empty( $include['rule'] ) && in_array( 'basic-global', (array) $include['rule'], true );
											?>
											<li>
												<div class="mjet-theme-manager__template-header">
													<a href="<?php echo esc_url( $edit_link ); ?>" class="mjet-theme-manager__template-title">
														<?php echo esc_html( get_the_title( $template ) ); ?>
													</a>
													<?php if ( 'publish' !== $template->post_status ) : ?>
														<span class="mjet-theme-manager__badge mjet-theme-manager__badge--muted"><?php echo esc_html( $status_label ); ?></span>
													<?php endif; ?>
													<?php if ( $is_global ) : ?>
														<span class="mjet-theme-manager__badge mjet-theme-manager__badge--success"><?php esc_html_e( 'Site entier', 'mj-elementor-templates' ); ?></span>
													<?php endif; ?>
												</div>
												<div class="mjet-theme-manager__meta">
													<strong><?php esc_html_e( 'Règles', 'mj-elementor-templates' ); ?>:</strong>
													<?php echo esc_html( implode( ' · ', array_unique( $include_rules ) ) ); ?>
												</div>
												<?php if ( ! empty( $exclude_rules ) ) : ?>
													<div class="mjet-theme-manager__meta">
														<strong><?php esc_html_e( 'Exclusions', 'mj-elementor-templates' ); ?>:</strong>
														<?php echo esc_html( implode( ' · ', array_unique( $exclude_rules ) ) ); ?>
													</div>
												<?php endif; ?>
												<div class="mjet-theme-manager__meta">
													<strong><?php esc_html_e( 'Rôles', 'mj-elementor-templates' ); ?>:</strong>
													<?php echo esc_html( $roles_summary ); ?>
												</div>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<p class="description mjet-theme-manager__empty">
										<?php esc_html_e( 'Aucun template assigné pour ce type.', 'mj-elementor-templates' ); ?>
									</p>
								<?php endif; ?>
							</td>
							<td class="mjet-theme-manager__actions">
								<a class="button button-primary" href="<?php echo esc_url( $create_link ); ?>">
									<?php esc_html_e( 'Créer un template', 'mj-elementor-templates' ); ?>
								</a>
								<a class="button" href="<?php echo esc_url( $list_link ); ?>">
									<?php esc_html_e( 'Voir la liste', 'mj-elementor-templates' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Affiche un filtre par type dans la liste des templates.
	 */
	public function render_type_filter_dropdown() {
		global $typenow;

		if ( 'mjet-template' !== $typenow ) {
			return;
		}

		$template_types = $this->get_template_type_options();
		$current_type  = isset( $_GET['mjet_type_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['mjet_type_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<label class="screen-reader-text" for="mjet_type_filter">&nbsp;</label>
		<select name="mjet_type_filter" id="mjet_type_filter" class="postform">
			<option value=""><?php esc_html_e( 'Tous les types', 'mj-elementor-templates' ); ?></option>
			<?php foreach ( $template_types as $type_key => $type_label ) : ?>
				<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $current_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filtre la liste admin selon le type sélectionné.
	 *
	 * @param WP_Query $query Requête courante.
	 */
	public function filter_admin_list_by_type( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;
		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( 'mjet-template' !== $post_type ) {
			return;
		}

		if ( empty( $_GET['mjet_type_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$type           = sanitize_text_field( wp_unslash( $_GET['mjet_type_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$template_types = $this->get_template_type_options();

		if ( ! isset( $template_types[ $type ] ) ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'   => 'mjet_template_type',
			'value' => $type,
		);

		$query->set( 'meta_query', $meta_query );
	}
	/**
	 * Page des paramètres.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MJ Elementor Templates', 'mj-elementor-templates' ); ?></h1>
			<div class="mjet-welcome-panel">
				<div class="mjet-welcome-panel-content">
					<h2><?php esc_html_e( 'Bienvenue dans MJ Elementor Templates !', 'mj-elementor-templates' ); ?></h2>
					<p class="about-description">
						<?php esc_html_e( 'Créez des en-têtes, pieds de page et blocs personnalisés avec Elementor et affichez-les sur votre site.', 'mj-elementor-templates' ); ?>
					</p>
					<div class="mjet-welcome-panel-column-container">
						<div class="mjet-welcome-panel-column">
							<h3><?php esc_html_e( 'Premiers pas', 'mj-elementor-templates' ); ?></h3>
							<ul>
								<li>
									<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mjet-template' ) ); ?>">
										<span class="dashicons dashicons-plus-alt"></span>
										<?php esc_html_e( 'Créer un nouveau template', 'mj-elementor-templates' ); ?>
									</a>
								</li>
								<li>
									<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mjet-template' ) ); ?>">
										<span class="dashicons dashicons-list-view"></span>
										<?php esc_html_e( 'Voir tous les templates', 'mj-elementor-templates' ); ?>
									</a>
								</li>
							</ul>
						</div>
						<div class="mjet-welcome-panel-column">
							<h3><?php esc_html_e( 'Types de templates', 'mj-elementor-templates' ); ?></h3>
							<ul>
								<li><strong><?php esc_html_e( 'Header', 'mj-elementor-templates' ); ?></strong> - <?php esc_html_e( 'En-tête personnalisé', 'mj-elementor-templates' ); ?></li>
								<li><strong><?php esc_html_e( 'Footer', 'mj-elementor-templates' ); ?></strong> - <?php esc_html_e( 'Pied de page personnalisé', 'mj-elementor-templates' ); ?></li>
								<li><strong><?php esc_html_e( 'Before Footer', 'mj-elementor-templates' ); ?></strong> - <?php esc_html_e( 'Section avant le pied de page', 'mj-elementor-templates' ); ?></li>
								<li><strong><?php esc_html_e( 'Custom Block', 'mj-elementor-templates' ); ?></strong> - <?php esc_html_e( 'Bloc via shortcode', 'mj-elementor-templates' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<?php
			$available_widgets = MJET_Widgets_Loader::get_widget_catalog();
			?>
			<div class="mjet-widgets-panel">
				<h2><?php esc_html_e( 'Widgets MJ Templates', 'mj-elementor-templates' ); ?></h2>
				<?php if ( ! empty( $available_widgets ) ) : ?>
					<ul class="mjet-widgets-list">
						<?php foreach ( $available_widgets as $widget ) :
							$keywords = array();
							if ( ! empty( $widget['keywords'] ) && is_array( $widget['keywords'] ) ) {
								foreach ( $widget['keywords'] as $keyword ) {
									$keywords[] = sanitize_text_field( $keyword );
								}
							}
							?>
							<li class="mjet-widgets-list-item">
								<?php if ( ! empty( $widget['icon'] ) ) : ?>
									<span class="mjet-widget-icon" aria-hidden="true">
										<i class="<?php echo esc_attr( $widget['icon'] ); ?>"></i>
									</span>
								<?php endif; ?>
								<div class="mjet-widget-meta">
									<span class="mjet-widget-title"><?php echo esc_html( $widget['title'] ); ?></span>
									<span class="mjet-widget-slug"><?php printf( esc_html__( 'Identifiant : %s', 'mj-elementor-templates' ), esc_html( $widget['name'] ) ); ?></span>
									<?php if ( ! empty( $keywords ) ) : ?>
										<span class="mjet-widget-keywords"><?php printf( esc_html__( 'Mots-clés : %s', 'mj-elementor-templates' ), esc_html( implode( ', ', $keywords ) ) ); ?></span>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Aucun widget disponible pour le moment.', 'mj-elementor-templates' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<style>
			.mjet-welcome-panel {
				background: #fff;
				border: 1px solid #c3c4c7;
				margin: 20px 0;
				padding: 20px;
			}
			.mjet-welcome-panel h2 {
				margin: 0 0 10px;
			}
			.mjet-welcome-panel-column-container {
				display: flex;
				gap: 40px;
				margin-top: 20px;
			}
			.mjet-welcome-panel-column {
				flex: 1;
			}
			.mjet-welcome-panel-column ul {
				margin: 0;
				padding: 0;
			}
			.mjet-welcome-panel-column li {
				margin: 10px 0;
			}
			.mjet-welcome-panel-column a {
				text-decoration: none;
				display: flex;
				align-items: center;
				gap: 5px;
			}
			.mjet-widgets-panel {
				margin-top: 30px;
				background: #fff;
				border: 1px solid #c3c4c7;
				padding: 20px;
				border-radius: 4px;
			}
			.mjet-widgets-panel h2 {
				margin-top: 0;
				margin-bottom: 15px;
			}
			.mjet-widgets-list {
				list-style: none;
				margin: 0;
				padding: 0;
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
				gap: 16px;
			}
			.mjet-widgets-list-item {
				display: flex;
				gap: 12px;
				align-items: flex-start;
				background: #f9f9f9;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				padding: 12px;
				min-height: 90px;
			}
			.mjet-widget-icon {
				display: inline-flex;
				width: 32px;
				height: 32px;
				border-radius: 50%;
				background: #2271b1;
				color: #fff;
				align-items: center;
				justify-content: center;
				font-size: 16px;
			}
			.mjet-widget-meta {
				display: flex;
				flex-direction: column;
				gap: 4px;
			}
			.mjet-widget-title {
				font-weight: 600;
			}
			.mjet-widget-slug,
			.mjet-widget-keywords {
				font-size: 12px;
				color: #555d66;
			}
		</style>
		<?php
	}

	/**
	 * Enregistrer la métabox.
	 */
	public function register_metabox() {
		add_meta_box(
			'mjet-meta-box',
			__( 'Options du template', 'mj-elementor-templates' ),
			array( $this, 'render_metabox' ),
			'mjet-template',
			'normal',
			'high'
		);
	}

	/**
	 * Afficher la métabox.
	 *
	 * @param WP_Post $post Post actuel.
	 */
	public function render_metabox( $post ) {
		$values            = get_post_custom( $post->ID );
		$template_type     = isset( $values['mjet_template_type'] ) ? esc_attr( $values['mjet_template_type'][0] ) : '';
		$display_on_canvas = isset( $values['mjet_display_on_canvas'] ) && '1' === $values['mjet_display_on_canvas'][0];
		$template_types    = $this->get_template_type_options();

		if ( empty( $template_type ) && isset( $_GET['mjet_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested_type = sanitize_text_field( wp_unslash( $_GET['mjet_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $template_types[ $requested_type ] ) ) {
				$template_type = $requested_type;
			}
		}

		wp_nonce_field( 'mjet_meta_nonce', 'mjet_meta_nonce' );

		// Récupérer les valeurs des règles d'affichage.
		$include_locations = get_post_meta( $post->ID, 'mjet_target_include_locations', true );
		$exclude_locations = get_post_meta( $post->ID, 'mjet_target_exclude_locations', true );
		$user_roles        = get_post_meta( $post->ID, 'mjet_target_user_roles', true );

		if ( empty( $include_locations ) ) {
			$include_locations = array();
		}
		if ( empty( $exclude_locations ) ) {
			$exclude_locations = array();
		}
		if ( empty( $user_roles ) ) {
			$user_roles = array();
		}
		?>
		<table class="form-table mjet-options-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="mjet_template_type"><?php esc_html_e( 'Type de template', 'mj-elementor-templates' ); ?></label>
					</th>
					<td>
						<select name="mjet_template_type" id="mjet_template_type" class="regular-text">
							<option value=""><?php esc_html_e( '— Sélectionner —', 'mj-elementor-templates' ); ?></option>
							<?php foreach ( $template_types as $type_key => $type_label ) : ?>
								<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $template_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr class="mjet-display-rules">
					<th scope="row">
						<label><?php esc_html_e( 'Afficher sur', 'mj-elementor-templates' ); ?></label>
					</th>
					<td>
						<?php $this->render_location_select( 'mjet_target_include_locations', $include_locations ); ?>
					</td>
				</tr>

				<tr class="mjet-display-rules">
					<th scope="row">
						<label><?php esc_html_e( 'Exclure de', 'mj-elementor-templates' ); ?></label>
					</th>
					<td>
						<?php $this->render_location_select( 'mjet_target_exclude_locations', $exclude_locations ); ?>
					</td>
				</tr>

				<tr class="mjet-display-rules">
					<th scope="row">
						<label><?php esc_html_e( 'Rôles utilisateurs', 'mj-elementor-templates' ); ?></label>
					</th>
					<td>
						<?php $this->render_user_roles_select( $user_roles ); ?>
					</td>
				</tr>

				<tr class="mjet-shortcode-row">
					<th scope="row">
						<label><?php esc_html_e( 'Shortcode', 'mj-elementor-templates' ); ?></label>
					</th>
					<td>
						<input type="text" readonly value="[mjet_template id='<?php echo esc_attr( $post->ID ); ?>']" class="regular-text code" onclick="this.select();">
						<p class="description"><?php esc_html_e( 'Copiez ce shortcode pour insérer ce template n\'importe où.', 'mj-elementor-templates' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="mjet_display_on_canvas"><?php esc_html_e( 'Activer sur Canvas ?', 'mj-elementor-templates' ); ?></label>
					</th>
					<td>
						<input type="checkbox" name="mjet_display_on_canvas" id="mjet_display_on_canvas" value="1" <?php checked( $display_on_canvas ); ?>>
						<span class="description"><?php esc_html_e( 'Afficher ce template sur les pages utilisant le template Canvas d\'Elementor.', 'mj-elementor-templates' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Afficher le sélecteur de locations.
	 *
	 * @param string $name      Nom du champ.
	 * @param array  $selected  Valeurs sélectionnées.
	 */
	private function render_location_select( $name, $selected ) {
		$locations = MJET_Target_Rules::get_instance()->get_location_options();
		$rule      = isset( $selected['rule'] ) && is_array( $selected['rule'] ) ? $selected['rule'] : array();
		$specific  = isset( $selected['specific'] ) && is_array( $selected['specific'] ) ? array_map( 'absint', $selected['specific'] ) : array();
		$is_exclusion = ( 'mjet_target_exclude_locations' === $name );
		?>
		<div class="mjet-location-select">
			<select name="<?php echo esc_attr( $name ); ?>[rule][]" class="mjet-location-rule" multiple style="width: 100%; min-height: 100px;">
				<?php foreach ( $locations as $group_key => $group ) : ?>
					<optgroup label="<?php echo esc_attr( $group['label'] ); ?>">
						<?php foreach ( $group['value'] as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php echo in_array( $option_key, $rule, true ) ? 'selected' : ''; ?>>
								<?php echo esc_html( $option_label ); ?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Maintenez Ctrl/Cmd pour sélectionner plusieurs options.', 'mj-elementor-templates' ); ?></p>
		</div>
		<?php if ( $is_exclusion ) : ?>
			<?php
			$field_id = sanitize_html_class( $name . '_specific' );
			$pages    = get_pages(
				array(
					'sort_column' => 'post_title',
					'sort_order'  => 'ASC',
					'post_status' => array( 'publish', 'private' ),
				)
			);
			?>
			<?php if ( ! empty( $pages ) ) : ?>
				<div class="mjet-location-specific">
					<label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Pages spécifiques à exclure', 'mj-elementor-templates' ); ?></label>
					<select name="<?php echo esc_attr( $name ); ?>[specific][]" id="<?php echo esc_attr( $field_id ); ?>" multiple style="width: 100%; min-height: 120px;">
						<?php foreach ( $pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( (int) $page->ID, $specific, true ), true ); ?>>
								<?php echo esc_html( $page->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Ces pages seront exclues même si elles correspondent aux règles ci-dessus.', 'mj-elementor-templates' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Afficher le sélecteur de rôles utilisateurs.
	 *
	 * @param array $selected Rôles sélectionnés.
	 */
	private function render_user_roles_select( $selected ) {
		$roles = MJET_Target_Rules::get_instance()->get_user_role_options();
		?>
		<select name="mjet_target_user_roles[]" class="mjet-user-roles" multiple style="width: 100%; min-height: 80px;">
			<?php foreach ( $roles as $role_key => $role_label ) : ?>
				<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, (array) $selected, true ) ? 'selected' : ''; ?>>
					<?php echo esc_html( $role_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Laisser vide pour tous les utilisateurs.', 'mj-elementor-templates' ); ?></p>
		<?php
	}

	/**
	 * Sauvegarder les métadonnées.
	 *
	 * @param int $post_id ID du post.
	 */
	public function save_meta( $post_id ) {
		// Vérifications de sécurité.
		if ( ! isset( $_POST['mjet_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mjet_meta_nonce'] ) ), 'mjet_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sauvegarder le type de template.
		$template_types = $this->get_template_type_options();

		if ( isset( $_POST['mjet_template_type'] ) ) {
			$type_value = sanitize_text_field( wp_unslash( $_POST['mjet_template_type'] ) );

			if ( '' === $type_value ) {
				delete_post_meta( $post_id, 'mjet_template_type' );
			} elseif ( isset( $template_types[ $type_value ] ) ) {
				update_post_meta( $post_id, 'mjet_template_type', $type_value );
			} else {
				$type_value = get_post_meta( $post_id, 'mjet_template_type', true );
			}
		}

		$auto_include_rules = self::get_default_include_rules_for_type( $type_value );

		// Sauvegarder les règles d'affichage.
		if ( isset( $_POST['mjet_target_include_locations'] ) ) {
			$include_rules = $this->sanitize_location_rules( $_POST['mjet_target_include_locations'] );
			if ( empty( $include_rules ) && ! empty( $auto_include_rules ) ) {
				$include_rules = $auto_include_rules;
			}
			if ( empty( $include_rules ) ) {
				delete_post_meta( $post_id, 'mjet_target_include_locations' );
			} else {
				update_post_meta( $post_id, 'mjet_target_include_locations', $include_rules );
			}
		} elseif ( ! empty( $auto_include_rules ) ) {
			update_post_meta( $post_id, 'mjet_target_include_locations', $auto_include_rules );
		} else {
			delete_post_meta( $post_id, 'mjet_target_include_locations' );
		}

		if ( isset( $_POST['mjet_target_exclude_locations'] ) ) {
			update_post_meta( $post_id, 'mjet_target_exclude_locations', $this->sanitize_location_rules( $_POST['mjet_target_exclude_locations'] ) );
		} else {
			delete_post_meta( $post_id, 'mjet_target_exclude_locations' );
		}

		if ( isset( $_POST['mjet_target_user_roles'] ) ) {
			update_post_meta( $post_id, 'mjet_target_user_roles', array_map( 'sanitize_text_field', $_POST['mjet_target_user_roles'] ) );
		} else {
			delete_post_meta( $post_id, 'mjet_target_user_roles' );
		}

		// Sauvegarder l'option Canvas.
		if ( isset( $_POST['mjet_display_on_canvas'] ) ) {
			update_post_meta( $post_id, 'mjet_display_on_canvas', '1' );
		} else {
			delete_post_meta( $post_id, 'mjet_display_on_canvas' );
		}
	}

	/**
	 * Nettoyer les règles de location.
	 *
	 * @param array $data Données brutes.
	 * @return array
	 */
	private function sanitize_location_rules( $data ) {
		$sanitized = array();
		if ( isset( $data['rule'] ) && is_array( $data['rule'] ) ) {
			$sanitized['rule'] = array_map( 'sanitize_text_field', $data['rule'] );
		}
		if ( isset( $data['specific'] ) && is_array( $data['specific'] ) ) {
			$specific_ids = array_filter( array_map( 'absint', $data['specific'] ) );
			if ( ! empty( $specific_ids ) ) {
				$sanitized['specific'] = array_values( array_unique( $specific_ids ) );
			}
		}
		return $sanitized;
	}

	/**
	 * Ajouter des colonnes à la liste.
	 *
	 * @param array $columns Colonnes existantes.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['mjet_type']          = __( 'Type', 'mj-elementor-templates' );
		$columns['mjet_display_rules'] = __( 'Règles d\'affichage', 'mj-elementor-templates' );
		$columns['mjet_shortcode']     = __( 'Shortcode', 'mj-elementor-templates' );
		$columns['date']               = $date;

		return $columns;
	}

	/**
	 * Afficher le contenu des colonnes.
	 *
	 * @param string $column  Nom de la colonne.
	 * @param int    $post_id ID du post.
	 */
	public function render_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'mjet_type':
					$type   = get_post_meta( $post_id, 'mjet_template_type', true );
					$labels = $this->get_template_type_options();
					echo isset( $labels[ $type ] ) ? esc_html( $labels[ $type ] ) : '—';
					break;

			case 'mjet_display_rules':
				$locations = get_post_meta( $post_id, 'mjet_target_include_locations', true );
				if ( ! empty( $locations['rule'] ) ) {
					echo '<strong>' . esc_html__( 'Afficher:', 'mj-elementor-templates' ) . '</strong> ';
					echo esc_html( implode( ', ', $locations['rule'] ) );
				} else {
					echo '—';
				}
				break;

			case 'mjet_shortcode':
				echo '<input type="text" readonly value="[mjet_template id=\'' . esc_attr( $post_id ) . '\']" style="width: 200px;" onclick="this.select();">';
				break;
		}
	}

	/**
	 * Charger le template Canvas pour l'édition.
	 *
	 * @param string $single_template Template actuel.
	 * @return string
	 */
	public function load_canvas_template( $single_template ) {
		global $post;

		if ( ! $post || 'mjet-template' !== $post->post_type ) {
			return $single_template;
		}

		// Utiliser notre template Canvas personnalisé.
		$mjet_canvas = MJET_DIR . 'templates/canvas.php';
		if ( file_exists( $mjet_canvas ) ) {
			return $mjet_canvas;
		}

		// Fallback vers le template Canvas d'Elementor si la constante est définie.
		if ( defined( 'ELEMENTOR_PATH' ) ) {
			$elementor_2_0_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';
			if ( file_exists( $elementor_2_0_canvas ) ) {
				return $elementor_2_0_canvas;
			}

			// Ancien chemin Elementor (avant 2.0).
			$elementor_canvas = ELEMENTOR_PATH . '/includes/page-templates/canvas.php';
			if ( file_exists( $elementor_canvas ) ) {
				return $elementor_canvas;
			}
		}

		return $single_template;
	}

	/**
	 * Forcer le template Canvas via template_include (priorité très haute).
	 *
	 * @param string $template Template actuel.
	 * @return string
	 */
	public function force_canvas_template( $template ) {
		global $post;

		if ( ! $post || 'mjet-template' !== $post->post_type ) {
			return $template;
		}

		// Si on est en mode édition/preview Elementor ou sur un singular mjet-template.
		if ( is_singular( 'mjet-template' ) || isset( $_GET['elementor-preview'] ) ) {
			// Utiliser notre template Canvas personnalisé.
			$mjet_canvas = MJET_DIR . 'templates/canvas.php';
			if ( file_exists( $mjet_canvas ) ) {
				return $mjet_canvas;
			}

			// Fallback vers le template Canvas d'Elementor.
			if ( defined( 'ELEMENTOR_PATH' ) ) {
				$elementor_2_0_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';
				if ( file_exists( $elementor_2_0_canvas ) ) {
					return $elementor_2_0_canvas;
				}
			}
		}

		return $template;
	}

	/**
	 * Bloquer l'accès frontend aux templates.
	 */
	public function block_template_frontend() {
		if ( is_singular( 'mjet-template' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_safe_redirect( home_url(), 301 );
			exit;
		}
	}

	/**
	 * Enqueue scripts admin.
	 *
	 * @param string $hook Hook de la page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		if ( 'mjet-template' === $post_type && in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			wp_enqueue_style( 'mjet-admin', MJET_URL . 'assets/css/mjet-admin.css', array(), MJET_VERSION );
			wp_enqueue_script( 'mjet-admin', MJET_URL . 'assets/js/mjet-admin.js', array( 'jquery' ), MJET_VERSION, true );
		}
	}
}

// Initialiser la classe admin.
MJET_Admin::instance();
