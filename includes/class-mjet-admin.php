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
							<option value="type_header" <?php selected( $template_type, 'type_header' ); ?>><?php esc_html_e( 'Header', 'mj-elementor-templates' ); ?></option>
							<option value="type_before_footer" <?php selected( $template_type, 'type_before_footer' ); ?>><?php esc_html_e( 'Before Footer', 'mj-elementor-templates' ); ?></option>
							<option value="type_footer" <?php selected( $template_type, 'type_footer' ); ?>><?php esc_html_e( 'Footer', 'mj-elementor-templates' ); ?></option>
							<option value="custom" <?php selected( $template_type, 'custom' ); ?>><?php esc_html_e( 'Custom Block', 'mj-elementor-templates' ); ?></option>
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
		if ( isset( $_POST['mjet_template_type'] ) ) {
			update_post_meta( $post_id, 'mjet_template_type', sanitize_text_field( wp_unslash( $_POST['mjet_template_type'] ) ) );
		}

		// Sauvegarder les règles d'affichage.
		if ( isset( $_POST['mjet_target_include_locations'] ) ) {
			update_post_meta( $post_id, 'mjet_target_include_locations', $this->sanitize_location_rules( $_POST['mjet_target_include_locations'] ) );
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
				$labels = array(
					'type_header'        => __( 'Header', 'mj-elementor-templates' ),
					'type_footer'        => __( 'Footer', 'mj-elementor-templates' ),
					'type_before_footer' => __( 'Before Footer', 'mj-elementor-templates' ),
					'custom'             => __( 'Custom Block', 'mj-elementor-templates' ),
				);
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
