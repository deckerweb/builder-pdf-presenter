<?php
/** Explicit, reversible PDF migration after a per-contribution preview. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Converts only selected, verified supported PDF embeds and retains an exact content backup. */
final class Migration {
	/** Register the conditional tool and nonce-protected administrative operations.
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_post_bpp_migrate', array( self::class, 'apply' ) );
		add_action( 'admin_post_bpp_restore', array( self::class, 'restore_action' ) );
		add_action( 'admin_post_bpp_migration_refresh', array( self::class, 'refresh' ) );
		add_action( 'save_post', array( self::class, 'invalidate' ) );
	}

	/** Clear only this website's cached discovery after content changes.
	 * @param int $post_id Changed contribution, or zero for explicit refresh.
	 * @return void
	 */
	public static function invalidate( int $post_id = 0 ): void {
		if ( $post_id && wp_is_post_revision( $post_id ) ) { return; }
		delete_transient( 'bpp_migration_discovery' );
	}

	/** Query a bounded page of editorial contributions with legacy tokens or saved backups.
	 * @param bool $backups Whether to query backup metadata instead of legacy content.
	 * @param int $page One-based result page.
	 * @param int $size Maximum contributions returned, between one and fifty.
	 * @return \WP_Query Query including total pages for the tool interface.
	 */
	public static function query( bool $backups = false, int $page = 1, int $size = 50 ): \WP_Query {
		$args = array( 'post_type' => array_keys( Settings::types() ), 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'posts_per_page' => max( 1, min( 50, $size ) ), 'paged' => max( 1, $page ), 'orderby' => 'ID', 'order' => 'DESC', 'bpp_legacy_scan' => ! $backups );
		if ( ! $args['post_type'] ) { $args['post__in'] = array( 0 ); }
		if ( $backups ) { $args['post_status'][] = 'trash'; $args['meta_key'] = '_bpp_legacy_backup'; }
		/** Restrict the content search to this query; values are prepared and LIKE-escaped.
		 * @param string $where Existing SQL conditions supplied by WordPress.
		 * @param \WP_Query $query Current query.
		 * @return string Original conditions plus the importer-specific content predicate.
		 */
		$filter = static function( string $where, \WP_Query $query ): string {
			if ( ! $query->get( 'bpp_legacy_scan' ) ) { return $where; }
			global $wpdb;
			return $where . $wpdb->prepare( " AND ({$wpdb->posts}.post_content LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", '%' . $wpdb->esc_like( '<!-- wp:pdfp/pdfposter' ) . '%', '%' . $wpdb->esc_like( '[pdf_embed' ) . '%', '%' . $wpdb->esc_like( '<!-- wp:pdfemb/pdf-embedder-viewer' ) . '%', '%' . $wpdb->esc_like( '[pdf-embedder' ) . '%' );
		};
		add_filter( 'posts_where', $filter, 10, 2 );
		try { return new \WP_Query( $args ); }
		finally { remove_filter( 'posts_where', $filter, 10 ); }
	}

	/** Recognize actual legacy shortcode tags even if PDF Poster is no longer active.
	 * @param string $content Saved contribution content.
	 * @return bool Whether an unescaped pdf_embed shortcode exists.
	 */
	private static function has_shortcode( string $content ): bool {
		preg_match_all( '/' . get_shortcode_regex( array( 'pdf_embed' ) ) . '/s', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) { if ( '[' !== $match[1] || ']' !== $match[6] ) { return true; } }
		return false;
	}

	/** Determine tool visibility without scanning content on every admin request.
	 * @return bool PDF Poster active, legacy tokens found, or recoverable backups present.
	 */
	public static function available(): bool {
		$plugins = array_merge( (array) get_option( 'active_plugins', array() ), array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		foreach ( $plugins as $plugin ) { if ( ( str_starts_with( (string) $plugin, 'pdf-poster/' ) || str_starts_with( (string) $plugin, 'pdf-embedder/' ) ) ) { return true; } }
		$cached = get_transient( 'bpp_migration_discovery' );
		if ( is_array( $cached ) && isset( $cached['visible'] ) ) { return (bool) $cached['visible']; }
		$backups = self::query( true, 1, 1 ); $visible = (bool) $backups->post_count;
		$page = 1;
		while ( ! $visible ) {
			$legacy = self::query( false, $page );
			foreach ( $legacy->posts as $post ) {
				if ( self::count_legacy( parse_blocks( $post->post_content ) ) || self::has_shortcode( $post->post_content ) || Embedder_Migration::plan( $post->post_content )['references'] ) { $visible = true; break; }
			}
			if ( $page >= $legacy->max_num_pages ) { break; }
			++$page;
		}
		set_transient( 'bpp_migration_discovery', array( 'visible' => $visible ), 5 * MINUTE_IN_SECONDS );
		return $visible;
	}

	/** Add the tool only when the current website has a reason to offer it.
	 * @return void
	 */
	public static function menu(): void {
		if ( current_user_can( 'manage_options' ) && self::available() ) { add_management_page( __( 'PDF migration', 'builder-pdf-presenter' ), __( 'PDF migration', 'builder-pdf-presenter' ), 'manage_options', 'bpp-pdf-poster', array( self::class, 'page' ) ); }
	}

	/** Enqueue the shared administration layout on this tool only.
	 * @param string $hook Current admin screen hook.
	 * @return void
	 */
	public static function assets( string $hook ): void {
		if ( 'tools_page_bpp-pdf-poster' === $hook ) { wp_enqueue_style( 'bpp-admin', BPP_URL . 'assets/admin.css', array(), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/admin.css' ) ); wp_enqueue_script( 'bpp-tool-admin', BPP_URL . 'assets/admin.js', array(), BPP_VERSION, true ); }
	}

	/** Build the website-scoped tool URL.
	 * @return string Administrative URL without action nonces.
	 */
	public static function url(): string { return admin_url( 'tools.php?page=bpp-pdf-poster' ); }

	/** Return a localized, fixed explanation instead of exposing raw database errors.
	 * @param string $code Known operation outcome code.
	 * @return string User-facing explanation in the currently loaded admin language.
	 */
	public static function reason( string $code ): string {
		$messages = array(
			'migrated' => __( 'Converted successfully. The original content and collection are saved for recovery.', 'builder-pdf-presenter' ),
			'restored' => __( 'Original content and collection restored. PDF files were not changed.', 'builder-pdf-presenter' ),
			'embedder_unresolved' => __( 'PDF Embedder: external, missing or ambiguous PDF. This contribution was not changed.', 'builder-pdf-presenter' ),
			'embedder_protected' => __( 'PDF Embedder: protected PDF skipped. This contribution was not changed.', 'builder-pdf-presenter' ),
			'no_legacy' => __( 'No supported PDF embeds found. Other integrations need manual review.', 'builder-pdf-presenter' ),
			'unresolved' => __( 'A PDF could not be assigned unambiguously. No content was changed.', 'builder-pdf-presenter' ),
			'backup_exists' => __( 'A recovery backup already exists. Review or restore it before converting again.', 'builder-pdf-presenter' ),
			'limit' => __( 'The collection limit would be exceeded. No content was changed.', 'builder-pdf-presenter' ),
			'not_allowed' => __( 'The permissions do not allow this contribution to be changed.', 'builder-pdf-presenter' ),
			'missing_backup' => __( 'No valid recovery backup exists for this contribution.', 'builder-pdf-presenter' ),
			'changed' => __( 'This contribution was edited after migration. Explicit overwrite confirmation is required.', 'builder-pdf-presenter' ),
			'older_backup' => __( 'This older backup cannot identify later edits. Explicit overwrite confirmation is required.', 'builder-pdf-presenter' ),
			'write_failed' => __( 'The change could not be saved. Review this contribution before trying again.', 'builder-pdf-presenter' ),
		);
		return $messages[ $code ] ?? $messages['write_failed'];
	}

	/** Resolve a legacy block using its ID, an exact local URL or an unambiguous saved assignment.
	 * @param array $block Legacy block to resolve.
	 * @param int $post_id Contribution whose explicit PDF assignment may be reused.
	 * @param bool $allow_assignment Whether exactly one unresolved legacy block permits assignment fallback.
	 * @return int Verified PDF attachment ID or zero; never downloads remote content.
	 */
	public static function resolve( array $block, int $post_id = 0, bool $allow_assignment = false ): int {
		$id = absint( $block['attrs']['fileId'] ?? 0 );
		if ( Presenter::valid_pdf( $id ) ) { return $id; }
		if ( $allow_assignment && $post_id ) {
			$saved = get_post_meta( $post_id, '_bpp_documents', true );
			if ( is_array( $saved ) && 1 === count( $saved ) && Presenter::valid_pdf( absint( $saved[0]['pdf'] ?? 0 ) ) ) { return absint( $saved[0]['pdf'] ); }
			$id = absint( get_post_meta( $post_id, '_bpp_pdf_id', true ) );
			if ( Presenter::valid_pdf( $id ) ) { return $id; }
		}
		$url = $block['attrs']['file'] ?? '';
		$upload = wp_upload_dir();
		if ( is_string( $url ) && $url ) {
			$parts = wp_parse_url( $url ); $base = wp_parse_url( $upload['baseurl'] );
			$path = rawurldecode( $parts['path'] ?? '' ); $prefix = trailingslashit( $base['path'] ?? '' );
			if ( ! empty( $parts['host'] ) && strtolower( $parts['host'] ) === strtolower( $base['host'] ?? '' ) && in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) && (int) ( $parts['port'] ?? 0 ) === (int) ( $base['port'] ?? 0 ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && str_starts_with( $path, $prefix ) && ! preg_match( '~(?:^|/)\.\.(?:/|$)|[\\\\\x00]~', $path ) ) {
				$relative = substr( $path, strlen( $prefix ) );
				$ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => '_wp_attached_file', 'meta_value' => $relative ) );
				if ( 1 === count( $ids ) && Presenter::valid_pdf( (int) $ids[0] ) && is_file( get_attached_file( (int) $ids[0] ) ) ) { return (int) $ids[0]; }
			}
		}
		return 0;
	}

	/** Count legacy blocks before allowing an otherwise ambiguous contribution-level fallback.
	 * @param array $blocks Parsed block tree.
	 * @return int Number of PDF Poster blocks.
	 */
	private static function count_legacy( array $blocks ): int {
		$count = 0;
		foreach ( $blocks as $block ) { $count += 'pdfp/pdfposter' === ( $block['blockName'] ?? '' ) ? 1 : 0; $count += self::count_legacy( $block['innerBlocks'] ?? array() ); }
		return $count;
	}

	/** Collect legacy PDF IDs without touching database content.
	 * @param array $blocks Parsed block tree.
	 * @param int $post_id Source contribution for explicit assignment fallback.
	 * @param bool|null $allow_assignment Optional inherited permission for single-block fallback.
	 * @return array Ordered unique IDs with zero for unresolved legacy entries.
	 */
	public static function inspect( array $blocks, int $post_id = 0, ?bool $allow_assignment = null ): array {
		$allow_assignment = $allow_assignment ?? ( 1 === self::count_legacy( $blocks ) );
		$ids = array();
		foreach ( $blocks as $block ) {
			if ( 'pdfp/pdfposter' === ( $block['blockName'] ?? '' ) ) { $ids[] = self::resolve( $block, $post_id, $allow_assignment ); }
			$ids = array_merge( $ids, self::inspect( $block['innerBlocks'] ?? array(), $post_id, $allow_assignment ) );
		}
		return array_values( array_unique( $ids ) );
	}

	/** Convert one selected contribution while keeping the public boolean API compatible.
	 * @param int $id Contribution to convert.
	 * @return bool Whether a conversion was saved.
	 */
	public static function migrate_post( int $id ): bool { return ! is_wp_error( self::migrate_one( $id ) ); }

	/** Convert one verified contribution and record an exact before/after recovery snapshot.
	 * @param int $id Contribution to convert under the current administrator's capabilities.
	 * @return true|\WP_Error Success or a fixed actionable outcome code.
	 */
	public static function migrate_one( int $id ) {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) || ! isset( Settings::types()[ get_post_type( $id ) ] ) ) { return new \WP_Error( 'not_allowed' ); }
		$post = get_post( $id );
		$blocks = parse_blocks( $post->post_content ); $allow = 1 === self::count_legacy( $blocks ); $ids = self::inspect( $blocks, $id, $allow );
		$embedder = Embedder_Migration::plan( $post->post_content );
		if ( $embedder['error'] ) { return new \WP_Error( $embedder['error'] ); }
		$ids = array_values( array_unique( array_merge( $ids, $embedder['ids'] ) ) );
		if ( ! $ids ) { return new \WP_Error( 'no_legacy' ); }
		if ( in_array( 0, $ids, true ) ) { return new \WP_Error( 'unresolved' ); }
		if ( metadata_exists( 'post', $id, '_bpp_legacy_backup' ) ) { return new \WP_Error( 'backup_exists' ); }
		$existing = Documents::get( $id ); $entries = $existing; $seen = array_column( $entries, 'pdf' );
		foreach ( $ids as $pdf ) { if ( ! in_array( $pdf, $seen, true ) ) { $entries[] = array( 'key' => wp_generate_uuid4(), 'pdf' => $pdf, 'title' => '', 'cover' => 0, 'options' => array() ); $seen[] = $pdf; } }
		$clean = Documents::clean( $entries, $existing, Settings::limit( $id ) );
		if ( is_wp_error( $clean ) ) { return new \WP_Error( 'limit' ); }
		$walk = static function( array &$items ) use ( &$walk, $id, $allow ): void {
			foreach ( $items as &$item ) {
				if ( 'pdfp/pdfposter' === ( $item['blockName'] ?? '' ) ) { $item = array( 'blockName' => 'builder-pdf-presenter/document', 'attrs' => array( 'attachmentId' => self::resolve( $item, $id, $allow ) ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ); }
				elseif ( ! empty( $item['innerBlocks'] ) ) { $walk( $item['innerBlocks'] ); }
			}
		};
		$walk( $blocks ); $after_content = serialize_blocks( $blocks );
		if ( $embedder['references'] ) { $after_content = Embedder_Migration::plan( $after_content )['content']; }
		$backup = array( 'schema' => 2, 'content' => $post->post_content, 'documents' => get_post_meta( $id, '_bpp_documents', true ), 'had_documents' => metadata_exists( 'post', $id, '_bpp_documents' ), 'after_content' => $after_content, 'after_documents' => $clean, 'date' => gmdate( 'c' ) );
		// Metadata APIs unslash recursively, so protect JSON escapes and literal backslashes.
		if ( ! add_post_meta( $id, '_bpp_legacy_backup', wp_slash( $backup ), true ) ) { return new \WP_Error( 'write_failed' ); }
		$result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $after_content ) ), true );
		if ( is_wp_error( $result ) ) { delete_post_meta( $id, '_bpp_legacy_backup' ); return new \WP_Error( 'write_failed' ); }
		update_post_meta( $id, '_bpp_documents', wp_slash( $clean ) );
		if ( get_post_meta( $id, '_bpp_documents', true ) !== $clean ) { return new \WP_Error( 'write_failed' ); }
		$backup['after_content'] = get_post_field( 'post_content', $id );
		$backup['after_documents'] = get_post_meta( $id, '_bpp_documents', true );
		update_post_meta( $id, '_bpp_legacy_backup', wp_slash( $backup ) );
		if ( get_post_meta( $id, '_bpp_legacy_backup', true ) !== $backup ) { return new \WP_Error( 'write_failed' ); }
		self::invalidate();
		return true;
	}

	/** Assess whether restoring a backup would overwrite untracked or later edits.
	 * @param int $id Contribution with a recovery backup.
	 * @return string Empty when unchanged, otherwise a fixed reason code.
	 */
	public static function restore_conflict( int $id ): string {
		$backup = get_post_meta( $id, '_bpp_legacy_backup', true );
		if ( ! is_array( $backup ) || ! isset( $backup['content'] ) || ! is_string( $backup['content'] ) || ! array_key_exists( 'documents', $backup ) ) { return 'missing_backup'; }
		if ( 2 !== ( $backup['schema'] ?? 0 ) || ! isset( $backup['after_content'], $backup['after_documents'] ) ) { return 'older_backup'; }
		return get_post_field( 'post_content', $id ) !== $backup['after_content'] || get_post_meta( $id, '_bpp_documents', true ) !== $backup['after_documents'] ? 'changed' : '';
	}

	/** Restore only migration-owned content and collection fields; never delete media.
	 * @param int $id Contribution to restore.
	 * @param bool $overwrite Explicit confirmation to replace later or untracked edits.
	 * @return true|\WP_Error Success or a fixed recoverable error.
	 */
	public static function restore_post( int $id, bool $overwrite = false ) {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) { return new \WP_Error( 'not_allowed' ); }
		$conflict = self::restore_conflict( $id );
		if ( 'missing_backup' === $conflict || ( $conflict && ! $overwrite ) ) { return new \WP_Error( $conflict ); }
		$backup = get_post_meta( $id, '_bpp_legacy_backup', true );
		// Restore the trusted prior selection even if the administrator lowered collection limits.
		remove_filter( 'wp_insert_post_data', array( Maintenance::class, 'validate_blocks' ), 10 );
		try { $result = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $backup['content'] ) ), true ); }
		finally { add_filter( 'wp_insert_post_data', array( Maintenance::class, 'validate_blocks' ), 10, 2 ); }
		if ( is_wp_error( $result ) ) { return new \WP_Error( 'write_failed' ); }
		$had_documents = $backup['had_documents'] ?? is_array( $backup['documents'] ?? null );
		if ( $had_documents ) { update_post_meta( $id, '_bpp_documents', wp_slash( $backup['documents'] ) ); }
		else { delete_post_meta( $id, '_bpp_documents' ); }
		if ( get_post_field( 'post_content', $id ) !== $backup['content'] || ( $had_documents && get_post_meta( $id, '_bpp_documents', true ) !== $backup['documents'] ) || ( ! $had_documents && metadata_exists( 'post', $id, '_bpp_documents' ) ) ) { return new \WP_Error( 'write_failed' ); }
		delete_post_meta( $id, '_bpp_legacy_backup' );
		if ( metadata_exists( 'post', $id, '_bpp_legacy_backup' ) ) { return new \WP_Error( 'write_failed' ); }
		self::invalidate();
		return true;
	}

	/** Store the last twenty compact operation reports on this website, without content copies.
	 * @param string $action Operation label, migrate or restore.
	 * @param array $rows Per-contribution outcome records.
	 * @return void
	 */
	public static function report( string $action, array $rows ): void {
		$reports = get_option( 'bpp_migration_reports', array() );
		$reports = is_array( $reports ) ? $reports : array();
		array_unshift( $reports, array( 'action' => $action, 'date' => time(), 'rows' => $rows ) );
		update_option( 'bpp_migration_reports', array_slice( $reports, 0, 20 ), false );
	}

	/** Perform a bounded explicitly selected migration batch and retain its outcomes.
	 * @return void Redirects to the tool with a persisted result report.
	 */
	public static function apply(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You cannot migrate these documents.', 'builder-pdf-presenter' ), '', array( 'response' => 403 ) ); }
		check_admin_referer( 'bpp_migrate' );
		$ids = isset( $_POST['bpp_posts'] ) && is_array( $_POST['bpp_posts'] ) ? array_unique( array_slice( array_map( 'absint', $_POST['bpp_posts'] ), 0, 50 ) ) : array();
		$rows = array();
		foreach ( $ids as $id ) { $plan = current_user_can( 'edit_post', $id ) ? Embedder_Migration::plan( (string) get_post_field( 'post_content', $id ) ) : array(); $result = self::migrate_one( $id ); $rows[] = array( 'id' => $id, 'title' => current_user_can( 'read_post', $id ) ? get_the_title( $id ) : '#' . $id, 'code' => is_wp_error( $result ) ? $result->get_error_code() : 'migrated', 'embedder_options' => ! is_wp_error( $result ) && ! empty( $plan['unmapped'] ) ); }
		self::report( 'migrate', $rows );
		wp_safe_redirect( self::url() ); exit;
	}

	/** Restore one contribution only after administrator, object capability and nonce checks.
	 * @return void Redirects to the result report.
	 */
	public static function restore_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'builder-pdf-presenter' ), '', array( 'response' => 403 ) ); }
		$id = absint( $_POST['bpp_restore_id'] ?? 0 );
		check_admin_referer( 'bpp_restore_' . $id );
		$result = self::restore_post( $id, '1' === ( isset( $_POST['bpp_overwrite'] ) ? sanitize_text_field( wp_unslash( $_POST['bpp_overwrite'] ) ) : '' ) );
		self::report( 'restore', array( array( 'id' => $id, 'title' => current_user_can( 'read_post', $id ) ? get_the_title( $id ) : '#' . $id, 'code' => is_wp_error( $result ) ? $result->get_error_code() : 'restored' ) ) );
		wp_safe_redirect( self::url() ); exit;
	}

	/** Refresh cached discovery by an explicit nonce-protected request.
	 * @return void Redirects to the tool; opening the page never migrates content.
	 */
	public static function refresh(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'builder-pdf-presenter' ), '', array( 'response' => 403 ) ); }
		check_admin_referer( 'bpp_migration_refresh' ); self::invalidate();
		wp_safe_redirect( self::available() ? self::url() : admin_url( 'tools.php' ) ); exit;
	}

	/** Render page navigation for one independent table without loading the full archive.
	 * @param \WP_Query $query Query whose found_pages value supplies the maximum.
	 * @param string $parameter Distinct request parameter for this table.
	 * @param int $current Current page number.
	 * @return void
	 */
	private static function pagination( \WP_Query $query, string $parameter, int $current ): void {
		if ( $query->max_num_pages < 2 ) { return; }
		echo '<nav class="bpp-pagination" aria-label="' . esc_attr__( 'Result pages', 'builder-pdf-presenter' ) . '">';
		if ( $current > 1 ) { echo '<a class="button" href="' . esc_url( add_query_arg( $parameter, $current - 1, self::url() ) ) . '">' . esc_html__( 'Previous page', 'builder-pdf-presenter' ) . '</a> '; }
		echo esc_html( $current . ' / ' . $query->max_num_pages );
		if ( $current < $query->max_num_pages ) { echo ' <a class="button" href="' . esc_url( add_query_arg( $parameter, $current + 1, self::url() ) ) . '">' . esc_html__( 'Next page', 'builder-pdf-presenter' ) . '</a>'; }
		echo '</nav>';
	}

	/** Validate a read-only navigation parameter; these values never authorize writes.
	 * @param string $name Query parameter name.
	 * @param int $default Fallback when the parameter is absent or invalid.
	 * @return int Nonnegative validated navigation value.
	 */
	private static function navigation( string $name, int $default = 1 ): int {
		$value = filter_input( INPUT_GET, $name, FILTER_VALIDATE_INT );
		return false === $value || null === $value ? $default : max( 0, $value );
	}

	/** Display discovery, selective conversion, recovery and the last operation report.
	 * @return void
	 */
	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$page = max( 1, self::navigation( 'bpp_legacy_page' ) );
		$backup_page = max( 1, self::navigation( 'bpp_backup_page' ) );
		$legacy = self::query( false, $page ); $backups = self::query( true, $backup_page );
		?>
		<div class="wrap bpp-admin"><header class="bpp-header"><img class="bpp-brand-icon" src="<?php echo esc_url( BPP_URL . 'assets/branding/icon.svg' ); ?>" width="72" height="72" alt="" aria-hidden="true"><div class="bpp-header-copy"><h1><?php esc_html_e( 'PDF migration', 'builder-pdf-presenter' ); ?> <small><?php echo esc_html( BPP_VERSION ); ?></small></h1><p><?php esc_html_e( 'Check documents, convert selected contributions and restore saved originals.', 'builder-pdf-presenter' ); ?></p></div></header><hr class="wp-header-end">
		<p><?php esc_html_e( 'Checks PDF Poster blocks and PDF Embedder blocks/shortcodes in contribution content. Only ordinary, unambiguous local Media Library PDFs are converted. PDF Poster shortcodes, HTML/code examples, builder data and templates need manual review. No conversion starts automatically.', 'builder-pdf-presenter' ); ?></p>
		<p class="bpp-note"><?php esc_html_e( 'PDF Embedder: inline viewers become preview cards with a reading action. File order and embedding positions are preserved. Width, height, toolbar, page and zoom options are not transferred; review the new presentation before removing the old plugin. External or protected PDFs are not imported. One unresolved PDF leaves the entire contribution unchanged.', 'builder-pdf-presenter' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="bpp_migration_refresh"><?php wp_nonce_field( 'bpp_migration_refresh' ); submit_button( __( 'Check again', 'builder-pdf-presenter' ), 'secondary', 'submit', false ); ?></form>
		<?php $reports = get_option( 'bpp_migration_reports', array() ); $latest = is_array( $reports ) ? ( $reports[ min( 19, self::navigation( 'bpp_report', 0 ) ) ] ?? null ) : null; if ( is_array( $latest ) ) : ?>
		<section class="bpp-panel"><h2><?php esc_html_e( 'Operation result', 'builder-pdf-presenter' ); ?></h2><p><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $latest['date'] ) ); ?></p>
		<?php $codes = array_column( $latest['rows'], 'code' ); ?>
		<?php if ( count( $reports ) > 1 ) : ?><form method="get" action="<?php echo esc_url( admin_url( 'tools.php' ) ); ?>"><input type="hidden" name="page" value="bpp-pdf-poster"><label><?php esc_html_e( 'Recent operations', 'builder-pdf-presenter' ); ?> <select name="bpp_report"><?php foreach ( $reports as $index => $report ) : ?><option value="<?php echo absint( $index ); ?>" <?php selected( $index, self::navigation( 'bpp_report', 0 ) ); ?>><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $report['date'] ) . ' · ' . ( 'restore' === $report['action'] ? __( 'Restored', 'builder-pdf-presenter' ) : __( 'Converted', 'builder-pdf-presenter' ) ) ); ?></option><?php endforeach; ?></select></label> <?php submit_button( __( 'Show result', 'builder-pdf-presenter' ), 'secondary', 'submit', false ); ?></form><?php endif; ?>
		<p><?php esc_html_e( 'Converted', 'builder-pdf-presenter' ); ?>: <?php echo absint( count( array_keys( $codes, 'migrated', true ) ) ); ?> · <?php esc_html_e( 'Restored', 'builder-pdf-presenter' ); ?>: <?php echo absint( count( array_keys( $codes, 'restored', true ) ) ); ?> · <?php esc_html_e( 'Not changed', 'builder-pdf-presenter' ); ?>: <?php echo absint( count( $codes ) - count( array_keys( $codes, 'migrated', true ) ) - count( array_keys( $codes, 'restored', true ) ) ); ?></p>
		<ul><?php foreach ( $latest['rows'] as $row ) : ?><li><strong><?php echo esc_html( current_user_can( 'read_post', $row['id'] ) ? ( $row['title'] ?: '#' . $row['id'] ) : '#' . $row['id'] ); ?></strong>: <?php echo esc_html( self::reason( $row['code'] ) ); ?><?php if ( ! empty( $row['embedder_options'] ) ) { echo ' ' . esc_html__( 'PDF Embedder viewer options were not transferred. Review the new presentation.', 'builder-pdf-presenter' ); } ?></li><?php endforeach; ?></ul>
		</section><?php endif; ?>
		<section class="bpp-panel"><h2><?php esc_html_e( 'Contributions to review', 'builder-pdf-presenter' ); ?> (<?php echo absint( $legacy->found_posts ); ?>)</h2>
		<?php if ( ! $legacy->posts ) : ?><p><?php esc_html_e( 'No supported PDF references found in the checked content. Before deactivating the source plugin, also review builder templates and other integrations.', 'builder-pdf-presenter' ); ?></p><?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="bpp_migrate"><?php wp_nonce_field( 'bpp_migrate' ); ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Select', 'builder-pdf-presenter' ); ?></th><th><?php esc_html_e( 'Contribution', 'builder-pdf-presenter' ); ?></th><th><?php esc_html_e( 'Source', 'builder-pdf-presenter' ); ?></th><th><?php esc_html_e( 'Review', 'builder-pdf-presenter' ); ?></th></tr></thead><tbody>
		<?php foreach ( $legacy->posts as $post ) : if ( ! current_user_can( 'read_post', $post->ID ) ) { continue; } $embedder = Embedder_Migration::plan( $post->post_content ); $ids = array_values( array_unique( array_merge( self::inspect( parse_blocks( $post->post_content ), $post->ID ), $embedder['ids'] ) ) ); $code = ! $ids ? 'no_legacy' : ( in_array( 0, $ids, true ) ? 'unresolved' : ( metadata_exists( 'post', $post->ID, '_bpp_legacy_backup' ) ? 'backup_exists' : '' ) ); if ( $embedder['error'] ) { $code = $embedder['error']; } if ( ! $code ) { $existing = Documents::get( $post->ID ); $count = count( array_unique( array_merge( array_column( $existing, 'pdf' ), $ids ) ) ); if ( $count > max( count( $existing ), Settings::limit( $post->ID ) ) ) { $code = 'limit'; } } if ( ! current_user_can( 'edit_post', $post->ID ) ) { $code = 'not_allowed'; } ?>
		<tr><td><input aria-label="<?php echo esc_attr( $post->post_title ); ?>" type="checkbox" name="bpp_posts[]" value="<?php echo absint( $post->ID ); ?>" <?php disabled( (bool) $code ); ?>></td><td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></td><td><?php echo esc_html( $embedder['references'] ? ( self::count_legacy( parse_blocks( $post->post_content ) ) ? 'PDF Poster + PDF Embedder' : 'PDF Embedder' ) : 'PDF Poster' ); ?></td><td><?php echo $code ? esc_html( self::reason( $code ) ) : esc_html__( 'PDFs assigned unambiguously; ready to convert.', 'builder-pdf-presenter' ); ?><?php if ( ! $code ) { echo ' · ' . absint( count( $ids ) ) . ' PDF'; } ?><?php if ( ! $code && $embedder['unmapped'] ) { echo ' ' . esc_html__( 'PDF Embedder viewer options will use the new presentation defaults.', 'builder-pdf-presenter' ); } ?></td></tr>
		<?php endforeach; ?></tbody></table><?php submit_button( __( 'Convert selected contributions', 'builder-pdf-presenter' ), 'primary' ); ?></form><?php endif; self::pagination( $legacy, 'bpp_legacy_page', $page ); ?>
		</section>
		<section class="bpp-panel"><h2><?php esc_html_e( 'Recovery backups', 'builder-pdf-presenter' ); ?> (<?php echo absint( $backups->found_posts ); ?>)</h2><p><?php esc_html_e( 'Restore a contribution individually. This replaces its content and PDF collection with the saved original; media files and other contribution settings remain unchanged.', 'builder-pdf-presenter' ); ?></p>
		<?php if ( ! $backups->posts ) : ?><p><?php esc_html_e( 'No recovery backups found.', 'builder-pdf-presenter' ); ?></p><?php endif; ?>
		<?php foreach ( $backups->posts as $post ) : if ( ! current_user_can( 'edit_post', $post->ID ) ) { continue; } $conflict = self::restore_conflict( $post->ID ); ?>
		<details class="bpp-recovery"><summary><?php echo esc_html( $post->post_title ); ?></summary><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="bpp_restore"><input type="hidden" name="bpp_restore_id" value="<?php echo absint( $post->ID ); ?>"><?php wp_nonce_field( 'bpp_restore_' . $post->ID ); ?>
		<?php if ( $conflict ) : ?><p><?php echo esc_html( self::reason( $conflict ) ); ?></p><label><input type="checkbox" name="bpp_overwrite" value="1" required> <?php esc_html_e( 'Replace the current content and PDF collection with this backup, including any later edits.', 'builder-pdf-presenter' ); ?></label><?php endif; ?>
		<?php submit_button( __( 'Restore this contribution', 'builder-pdf-presenter' ), 'secondary' ); ?></form></details>
		<?php endforeach; self::pagination( $backups, 'bpp_backup_page', $backup_page ); ?></section>
		<p class="description"><?php esc_html_e( 'This tool stays available while a supported source plugin is active, legacy references exist or recovery backups remain. Discovery is cached for five minutes; Check again refreshes it immediately.', 'builder-pdf-presenter' ); ?></p>
		<?php Admin::footer(); ?>
		</div>
		<?php
	}
}
