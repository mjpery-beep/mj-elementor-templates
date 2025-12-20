<?php
/**
 * Script de migration des templates UAE vers MJ Elementor Templates.
 *
 * @package mj-elementor-templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe de migration des templates UAE.
 */
class MJET_UAE_Migration {

	/**
	 * Instance unique.
	 *
	 * @var MJET_UAE_Migration|null
	 */
	private static $instance = null;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return MJET_UAE_Migration
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
		add_action( 'admin_menu', array( $this, 'add_migration_page' ), 100 );
		add_action( 'admin_init', array( $this, 'handle_migration' ) );
		add_action( 'admin_notices', array( $this, 'show_migration_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_uae_available_notice' ) );
	}

	/**
	 * Ajouter la page de migration.
	 */
	public function add_migration_page() {
		// Vérifier si UAE est installé et s'il y a des templates à migrer.
		if ( ! $this->has_uae_templates() ) {
			return;
		}

		add_submenu_page(
			'mjet-templates',
			__( 'Importer depuis UAE', 'mj-elementor-templates' ),
			__( 'Importer depuis UAE', 'mj-elementor-templates' ),
			'manage_options',
			'mjet-uae-migration',
			array( $this, 'render_migration_page' )
		);
	}

	/**
	 * Vérifier si des templates UAE existent.
	 *
	 * @return bool
	 */
	private function has_uae_templates() {
		$posts = get_posts(
			array(
				'post_type'      => 'elementor-hf',
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'draft', 'private' ),
			)
		);

		return ! empty( $posts );
	}

	/**
	 * Récupérer tous les templates UAE.
	 *
	 * @return array
	 */
	public function get_uae_templates() {
		return get_posts(
			array(
				'post_type'      => 'elementor-hf',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'private' ),
			)
		);
	}

	/**
	 * Afficher la page de migration.
	 */
	public function render_migration_page() {
		$templates         = $this->get_uae_templates();
		$migrated_count    = get_option( 'mjet_migrated_templates_count', 0 );
		$already_migrated  = get_option( 'mjet_migrated_template_ids', array() );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Importer les templates depuis UAE', 'mj-elementor-templates' ); ?></h1>
			
			<?php if ( isset( $_GET['reset'] ) && '1' === $_GET['reset'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Statut de migration réinitialisé. Vous pouvez maintenant ré-importer les templates.', 'mj-elementor-templates' ); ?></p>
				</div>
			<?php endif; ?>
			
			<?php if ( empty( $templates ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'Aucun template UAE trouvé.', 'mj-elementor-templates' ); ?></p>
				</div>
			<?php else : ?>
				<div class="mjet-migration-info">
					<p><?php printf( esc_html__( '%d template(s) UAE trouvé(s).', 'mj-elementor-templates' ), count( $templates ) ); ?></p>
					<?php if ( $migrated_count > 0 ) : ?>
						<p class="description">
							<?php printf( esc_html__( '%d template(s) déjà migré(s).', 'mj-elementor-templates' ), $migrated_count ); ?>
							—
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mjet-uae-migration&mjet_reset_migration=1' ), 'mjet_reset_migration' ) ); ?>" 
							   onclick="return confirm('<?php esc_attr_e( 'Êtes-vous sûr ? Cela permettra de ré-importer tous les templates UAE.', 'mj-elementor-templates' ); ?>');">
								<?php esc_html_e( 'Réinitialiser le statut de migration', 'mj-elementor-templates' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 40px;"><input type="checkbox" id="mjet-select-all"></th>
							<th><?php esc_html_e( 'Titre', 'mj-elementor-templates' ); ?></th>
							<th><?php esc_html_e( 'Type', 'mj-elementor-templates' ); ?></th>
							<th><?php esc_html_e( 'Statut', 'mj-elementor-templates' ); ?></th>
							<th><?php esc_html_e( 'Migration', 'mj-elementor-templates' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $templates as $template ) : 
							$type = get_post_meta( $template->ID, 'ehf_template_type', true );
							$is_migrated = in_array( $template->ID, $already_migrated, true );
							$type_labels = array(
								'type_header'        => __( 'Header', 'mj-elementor-templates' ),
								'type_footer'        => __( 'Footer', 'mj-elementor-templates' ),
								'type_before_footer' => __( 'Before Footer', 'mj-elementor-templates' ),
								'custom'             => __( 'Custom Block', 'mj-elementor-templates' ),
							);
						?>
							<tr>
								<td>
									<input type="checkbox" name="mjet_templates[]" value="<?php echo esc_attr( $template->ID ); ?>" 
										class="mjet-template-checkbox" <?php echo $is_migrated ? 'disabled' : ''; ?>>
								</td>
								<td>
									<strong><?php echo esc_html( $template->post_title ); ?></strong>
									<div class="row-actions">
										<a href="<?php echo esc_url( get_edit_post_link( $template->ID ) ); ?>" target="_blank">
											<?php esc_html_e( 'Voir', 'mj-elementor-templates' ); ?>
										</a>
									</div>
								</td>
								<td><?php echo isset( $type_labels[ $type ] ) ? esc_html( $type_labels[ $type ] ) : '—'; ?></td>
								<td><?php echo esc_html( get_post_status_object( $template->post_status )->label ); ?></td>
								<td>
									<?php if ( $is_migrated ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
										<?php esc_html_e( 'Migré', 'mj-elementor-templates' ); ?>
									<?php else : ?>
										<span class="dashicons dashicons-minus" style="color: #dba617;"></span>
										<?php esc_html_e( 'En attente', 'mj-elementor-templates' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<form method="post" action="" style="margin-top: 20px;">
					<?php wp_nonce_field( 'mjet_migrate_templates', 'mjet_migrate_nonce' ); ?>
					<input type="hidden" name="mjet_action" value="migrate_templates">
					<div id="mjet-selected-templates"></div>
					
					<p class="submit">
						<button type="submit" class="button button-primary" id="mjet-migrate-btn" disabled>
							<?php esc_html_e( 'Migrer les templates sélectionnés', 'mj-elementor-templates' ); ?>
						</button>
						
						<button type="submit" class="button" name="mjet_migrate_all" value="1">
							<?php esc_html_e( 'Migrer tous les templates', 'mj-elementor-templates' ); ?>
						</button>
					</p>
				</form>

				<script>
				jQuery(document).ready(function($) {
					var $checkboxes = $('.mjet-template-checkbox:not(:disabled)');
					var $selectAll = $('#mjet-select-all');
					var $migrateBtn = $('#mjet-migrate-btn');
					var $selectedContainer = $('#mjet-selected-templates');

					function updateSelected() {
						var selected = [];
						$checkboxes.filter(':checked').each(function() {
							selected.push($(this).val());
						});
						
						$selectedContainer.html('');
						selected.forEach(function(id) {
							$selectedContainer.append('<input type="hidden" name="mjet_templates[]" value="' + id + '">');
						});
						
						$migrateBtn.prop('disabled', selected.length === 0);
					}

					$selectAll.on('change', function() {
						$checkboxes.prop('checked', $(this).is(':checked'));
						updateSelected();
					});

					$checkboxes.on('change', updateSelected);
				});
				</script>
			<?php endif; ?>
		</div>
		<style>
			.mjet-migration-info {
				background: #fff;
				padding: 15px;
				border: 1px solid #c3c4c7;
				margin: 20px 0;
			}
			.mjet-migration-info p {
				margin: 0;
			}
		</style>
		<?php
	}

	/**
	 * Gérer la migration.
	 */
	public function handle_migration() {
		// Gérer la réinitialisation du statut de migration.
		if ( isset( $_GET['mjet_reset_migration'] ) && '1' === $_GET['mjet_reset_migration'] ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mjet_reset_migration' ) && current_user_can( 'manage_options' ) ) {
				delete_option( 'mjet_migrated_template_ids' );
				delete_option( 'mjet_migrated_templates_count' );
				wp_safe_redirect( admin_url( 'admin.php?page=mjet-uae-migration&reset=1' ) );
				exit;
			}
		}

		if ( ! isset( $_POST['mjet_action'] ) || 'migrate_templates' !== $_POST['mjet_action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mjet_migrate_nonce'] ?? '' ) ), 'mjet_migrate_templates' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$migrate_all = isset( $_POST['mjet_migrate_all'] ) && '1' === $_POST['mjet_migrate_all'];
		$template_ids = array();

		if ( $migrate_all ) {
			$templates = $this->get_uae_templates();
			foreach ( $templates as $template ) {
				$template_ids[] = $template->ID;
			}
		} elseif ( isset( $_POST['mjet_templates'] ) && is_array( $_POST['mjet_templates'] ) ) {
			$template_ids = array_map( 'intval', $_POST['mjet_templates'] );
		}

		if ( empty( $template_ids ) ) {
			return;
		}

		$migrated = $this->migrate_templates( $template_ids );

		// Stocker le résultat pour l'affichage.
		set_transient( 'mjet_migration_result', $migrated, 60 );

		// Rediriger pour éviter la resoumission.
		wp_safe_redirect( admin_url( 'admin.php?page=mjet-uae-migration&migrated=' . count( $migrated ) ) );
		exit;
	}

	/**
	 * Migrer les templates.
	 *
	 * @param array $template_ids IDs des templates à migrer.
	 * @return array IDs des nouveaux templates créés.
	 */
	public function migrate_templates( $template_ids ) {
		$migrated_ids     = array();
		$already_migrated = get_option( 'mjet_migrated_template_ids', array() );

		foreach ( $template_ids as $old_id ) {
			// Vérifier si déjà migré.
			if ( in_array( $old_id, $already_migrated, true ) ) {
				continue;
			}

			$new_id = $this->migrate_single_template( $old_id );
			if ( $new_id ) {
				$migrated_ids[]     = $new_id;
				$already_migrated[] = $old_id;
			}
		}

		// Mettre à jour les options.
		update_option( 'mjet_migrated_template_ids', $already_migrated );
		update_option( 'mjet_migrated_templates_count', count( $already_migrated ) );

		// Vider le cache Elementor global pour forcer la régénération.
		$this->clear_elementor_cache();

		return $migrated_ids;
	}

	/**
	 * Vider le cache Elementor après la migration.
	 */
	private function clear_elementor_cache() {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		try {
			$elementor = \Elementor\Plugin::instance();
			
			// Vider le cache des fichiers Elementor.
			if ( isset( $elementor->files_manager ) && method_exists( $elementor->files_manager, 'clear_cache' ) ) {
				$elementor->files_manager->clear_cache();
			}
		} catch ( \Exception $e ) {
			// Silencieux en cas d'erreur.
		}
	}

	/**
	 * Migrer un seul template.
	 *
	 * @param int $old_id ID du template UAE.
	 * @return int|false ID du nouveau template ou false en cas d'erreur.
	 */
	private function migrate_single_template( $old_id ) {
		$old_post = get_post( $old_id );
		if ( ! $old_post || 'elementor-hf' !== $old_post->post_type ) {
			return false;
		}

		// Créer le nouveau post.
		$new_post_data = array(
			'post_title'   => $old_post->post_title,
			'post_content' => $old_post->post_content,
			'post_status'  => $old_post->post_status,
			'post_type'    => 'mjet-template',
			'post_author'  => $old_post->post_author,
		);

		$new_id = wp_insert_post( $new_post_data );
		if ( is_wp_error( $new_id ) ) {
			return false;
		}

		// Copier toutes les métadonnées.
		$this->copy_post_meta( $old_id, $new_id );

		// Convertir les métadonnées spécifiques UAE -> MJET.
		$this->convert_meta( $old_id, $new_id );

		// Régénérer les CSS Elementor pour ce nouveau template.
		$this->regenerate_elementor_css( $new_id );

		return $new_id;
	}

	/**
	 * Régénérer les fichiers CSS Elementor pour un template.
	 *
	 * @param int $post_id ID du template.
	 */
	private function regenerate_elementor_css( $post_id ) {
		// Vérifier qu'Elementor est chargé.
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		try {
			// Supprimer l'ancien CSS mis en cache (qui contenait l'ancien ID).
			delete_post_meta( $post_id, '_elementor_css' );

			// Utiliser Elementor pour régénérer le CSS.
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = \Elementor\Core\Files\CSS\Post::create( $post_id );
				$css_file->update();
			}
		} catch ( \Exception $e ) {
			// Silencieux en cas d'erreur, le CSS sera régénéré au prochain affichage.
		}
	}

	/**
	 * Copier les métadonnées d'un post à un autre.
	 *
	 * @param int $old_id ID du post source.
	 * @param int $new_id ID du post destination.
	 */
	private function copy_post_meta( $old_id, $new_id ) {
		global $wpdb;

		// Copier _elementor_data directement depuis la base de données pour éviter la désérialisation.
		$elementor_data = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_elementor_data' LIMIT 1",
				$old_id
			)
		);

		if ( ! empty( $elementor_data ) ) {
			// Supprimer l'ancienne valeur si elle existe.
			delete_post_meta( $new_id, '_elementor_data' );
			
			// Insérer directement dans la base de données pour éviter le slashing automatique.
			$wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id'    => $new_id,
					'meta_key'   => '_elementor_data',
					'meta_value' => $elementor_data,
				),
				array( '%d', '%s', '%s' )
			);
		}

		// Autres métadonnées à copier normalement.
		$meta_keys_to_copy = array(
			'_elementor_edit_mode',
			'_elementor_page_settings',
			'_elementor_version',
			'_wp_page_template',
			'_thumbnail_id',
		);

		foreach ( $meta_keys_to_copy as $meta_key ) {
			$meta_value = get_post_meta( $old_id, $meta_key, true );
			if ( ! empty( $meta_value ) ) {
				update_post_meta( $new_id, $meta_key, $meta_value );
			}
		}

		// Toujours définir les métadonnées essentielles pour Elementor.
		update_post_meta( $new_id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $new_id, '_elementor_edit_mode', 'builder' );
		
		// S'assurer que la version est définie.
		if ( empty( get_post_meta( $new_id, '_elementor_version', true ) ) && defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $new_id, '_elementor_version', ELEMENTOR_VERSION );
		}
	}

	/**
	 * Convertir les métadonnées UAE vers MJET.
	 *
	 * @param int $old_id ID du template UAE.
	 * @param int $new_id ID du nouveau template MJET.
	 */
	private function convert_meta( $old_id, $new_id ) {
		// Mapping des clés de métadonnées.
		$meta_mapping = array(
			'ehf_template_type'             => 'mjet_template_type',
			'ehf_target_include_locations'  => 'mjet_target_include_locations',
			'ehf_target_exclude_locations'  => 'mjet_target_exclude_locations',
			'ehf_target_user_roles'         => 'mjet_target_user_roles',
			'display-on-canvas-template'    => 'mjet_display_on_canvas',
		);

		foreach ( $meta_mapping as $old_key => $new_key ) {
			$value = get_post_meta( $old_id, $old_key, true );
			if ( ! empty( $value ) || '0' === $value ) {
				update_post_meta( $new_id, $new_key, $value );
			}
		}
	}

	/**
	 * Afficher une notice après la migration.
	 */
	public function show_migration_notice() {
		$screen = get_current_screen();
		if ( 'mj-templates_page_mjet-uae-migration' !== $screen->id ) {
			return;
		}

		if ( isset( $_GET['migrated'] ) ) {
			$count = intval( $_GET['migrated'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %d: number of templates migrated */
						esc_html( _n(
							'%d template migré avec succès.',
							'%d templates migrés avec succès.',
							$count,
							'mj-elementor-templates'
						) ),
						$count
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mjet-template' ) ); ?>">
						<?php esc_html_e( 'Voir les templates', 'mj-elementor-templates' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Afficher une notice si des templates UAE sont disponibles.
	 */
	public function show_uae_available_notice() {
		// Ne pas afficher sur la page de migration.
		$screen = get_current_screen();
		if ( $screen && 'mj-templates_page_mjet-uae-migration' === $screen->id ) {
			return;
		}

		// Vérifier si déjà dismissé.
		if ( get_option( 'mjet_uae_notice_dismissed' ) ) {
			return;
		}

		// Vérifier si des templates UAE existent.
		if ( ! $this->has_uae_templates() ) {
			return;
		}

		// Compter les templates non migrés.
		$already_migrated = get_option( 'mjet_migrated_template_ids', array() );
		$templates        = $this->get_uae_templates();
		$non_migrated     = 0;

		foreach ( $templates as $template ) {
			if ( ! in_array( $template->ID, $already_migrated, true ) ) {
				$non_migrated++;
			}
		}

		if ( 0 === $non_migrated ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'MJ Elementor Templates', 'mj-elementor-templates' ); ?></strong> - 
				<?php
				printf(
					/* translators: %d: number of templates */
					esc_html( _n(
						'%d template UAE détecté.',
						'%d templates UAE détectés.',
						$non_migrated,
						'mj-elementor-templates'
					) ),
					$non_migrated
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mjet-uae-migration' ) ); ?>">
					<?php esc_html_e( 'Importer maintenant', 'mj-elementor-templates' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

// Initialiser la migration.
MJET_UAE_Migration::instance();
