<?php
/** Administration for document assignments and layered presentation preferences. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Maintains one website settings page and document fields on selected content types. */
final class Admin {
	/** Register admin-only handlers. @return void */
	public static function boot(): void {
		add_action( 'enqueue_block_editor_assets', array( self::class, 'block_assets' ) );
		add_action( 'admin_notices', array( self::class, 'notices' ) );
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'wp_ajax_bpp_save_settings', array( self::class, 'save_settings' ) );
		add_action( 'add_meta_boxes', array( self::class, 'boxes' ) );
		add_action( 'save_post', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( BPP_FILE ), array( self::class, 'links' ) );
	}

	/** Display a single recoverable collection save error for the editing user.
	 * @return void
	 */
	public static function notices(): void {
		$key = 'bpp_settings_imported_' . get_current_user_id();
		if ( current_user_can( 'manage_options' ) && get_transient( $key ) ) { delete_transient( $key ); echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings imported. Review content types and watermark before use.', 'builder-pdf-presenter' ) . '</p></div>'; }
		$message = get_transient( 'bpp_save_error_' . get_current_user_id() );
		if ( $message ) { delete_transient( 'bpp_save_error_' . get_current_user_id() ); echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>'; }
	}

	/** Return translated editor labels shared by native and block collection controls.
	 * @return array<string,string> User-facing labels.
	 */
	public static function labels(): array {
		return array(
			'shared' => __( 'Plugin assignments in editable source posts', 'builder-pdf-presenter' ), 'undo' => __( 'Undo removal', 'builder-pdf-presenter' ), 'removed' => __( 'Removed from this collection. The Media Library file is kept.', 'builder-pdf-presenter' ), 'replaceHelp' => __( 'Replacing changes this assignment only. It does not overwrite the shared Media Library file.', 'builder-pdf-presenter' ), 'sortSize' => __( 'File size', 'builder-pdf-presenter' ), 'sortFilename' => __( 'File name', 'builder-pdf-presenter' ), 'sortMedia' => __( 'Media Library title', 'builder-pdf-presenter' ), 'sortApply' => __( 'Apply order to collection', 'builder-pdf-presenter' ), 'ascending' => __( 'Ascending', 'builder-pdf-presenter' ), 'descending' => __( 'Descending', 'builder-pdf-presenter' ), 'sizeUnknown' => __( 'Size unknown', 'builder-pdf-presenter' ), 'add' => __( 'Add PDFs', 'builder-pdf-presenter' ), 'drop' => __( 'Drop PDF files here or select them from the Media Library.', 'builder-pdf-presenter' ), 'title' => __( 'Display title', 'builder-pdf-presenter' ), 'replace' => __( 'Replace PDF', 'builder-pdf-presenter' ), 'remove' => __( 'Remove from collection', 'builder-pdf-presenter' ), 'up' => __( 'Move up', 'builder-pdf-presenter' ), 'down' => __( 'Move down', 'builder-pdf-presenter' ), 'settings' => __( 'Document settings', 'builder-pdf-presenter' ), 'inherit' => __( 'Inherit', 'builder-pdf-presenter' ), 'on' => __( 'Show / enable', 'builder-pdf-presenter' ), 'off' => __( 'Hide / disable', 'builder-pdf-presenter' ), 'limit' => __( 'Collection limit reached. Existing documents are kept.', 'builder-pdf-presenter' ), 'duplicate' => __( 'Already in this collection', 'builder-pdf-presenter' ), 'missing' => __( 'PDF unavailable: replace it or remove this entry.', 'builder-pdf-presenter' ), 'previewMissing' => __( 'Preview missing. Generate one or select a custom cover.', 'builder-pdf-presenter' ), 'generate' => __( 'Generate preview', 'builder-pdf-presenter' ), 'cover' => __( 'Select cover', 'builder-pdf-presenter' ), 'reset' => __( 'Reset display settings', 'builder-pdf-presenter' ), 'sortTitle' => __( 'Sort by title', 'builder-pdf-presenter' ), 'sortDate' => __( 'Sort by upload date', 'builder-pdf-presenter' ), 'bulkRemove' => __( 'Remove selected entries', 'builder-pdf-presenter' ), 'bulkReset' => __( 'Reset selected settings', 'builder-pdf-presenter' ), 'bulkPreview' => __( 'Generate missing previews', 'builder-pdf-presenter' ), 'retry' => __( 'Retry failed uploads', 'builder-pdf-presenter' ), 'uploadError' => __( 'Upload failed. Successful files were kept.', 'builder-pdf-presenter' ), 'busy' => __( 'Working…', 'builder-pdf-presenter' ), 'source' => __( 'Inherited from', 'builder-pdf-presenter' ), 'empty' => __( 'No documents yet. Add your first PDF.', 'builder-pdf-presenter' )
		);
	}

	/** Add settings action before the standard plugin actions.
	 * @param array $links Existing plugin actions.
	 * @return array Updated actions.
	 */
	public static function links( array $links ): array {
		if ( current_user_can( 'manage_options' ) ) { array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=builder-pdf-presenter' ) ) . '">' . esc_html__( 'Settings', 'builder-pdf-presenter' ) . '</a>' ); }
		return $links;
	}

	/** Register the website settings page. @return void */
	public static function menu(): void { add_options_page( 'Builder PDF Presenter', 'Builder PDF Presenter', 'manage_options', 'builder-pdf-presenter', array( self::class, 'page' ) ); }

	/** Register settings validation. @return void */
	public static function register(): void { register_setting( 'bpp', 'bpp_settings', array( 'type' => 'array', 'sanitize_callback' => array( Settings::class, 'sanitize' ), 'default' => array(), 'show_in_rest' => false ) ); }

	/** Save the complete settings form without losing input on transport errors.
	 * @return void Sends a JSON response; authorization precedes validation and writes.
	 */
	public static function save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message'=>__( 'Settings access denied.', 'builder-pdf-presenter' ) ), 403 ); }
		check_ajax_referer( 'bpp_settings_save', 'nonce' );
		if ( ! isset( $_POST['bpp_settings'] ) || ! is_array( $_POST['bpp_settings'] ) ) { wp_send_json_error( array(), 400 ); }
		$incoming = map_deep( wp_unslash( $_POST['bpp_settings'] ), 'sanitize_text_field' );
		$clean = Settings::sanitize( $incoming );
		update_option( 'bpp_settings', $clean, false );
		if ( Settings::stored() !== $clean ) { wp_send_json_error( array( 'message'=>__( 'Settings could not be stored.', 'builder-pdf-presenter' ) ), 500 ); }
		wp_send_json_success();
	}

	/** Add document boxes to the explicitly selected content types.
	 * @return void
	 */
	public static function boxes(): void {
		foreach ( Settings::stored()['enabled'] ?? array() as $slug ) {
			if ( post_type_exists( $slug ) ) { add_meta_box( 'bpp-document', 'Builder PDF Presenter', array( self::class, 'box' ), $slug, 'normal', 'high', array( '__block_editor_compatible_meta_box' => true ) ); }
		}
	}

	/** Load media selection and small admin assets on relevant screens only.
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function assets( string $hook ): void {
		$screen = get_current_screen();
		$settings = 'settings_page_builder-pdf-presenter' === $hook;
		$editor = $screen && in_array( $screen->post_type, Settings::stored()['enabled'] ?? array(), true ) && in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		if ( ! $settings && ! $editor ) { return; }
		wp_enqueue_style( 'bpp-admin', BPP_URL . 'assets/admin.css', array(), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/admin.css' ) );
		wp_enqueue_script( 'bpp-admin', BPP_URL . 'assets/admin.js', array( 'bpp-manager', 'wp-data' ), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/admin.js' ), true );
		wp_enqueue_media();
		if ( $settings ) { wp_localize_script( 'bpp-admin', 'bppSettingsUI', array( 'ajax'=>admin_url( 'admin-ajax.php' ), 'nonce'=>wp_create_nonce( 'bpp_settings_save' ), 'saved'=>__( 'All changes saved.', 'builder-pdf-presenter' ), 'dirty'=>__( 'Unsaved changes', 'builder-pdf-presenter' ), 'saving'=>__( 'Saving…', 'builder-pdf-presenter' ), 'error'=>__( 'Saving failed. Your changes are still here. Please try again.', 'builder-pdf-presenter' ) ) ); }
		wp_localize_script( 'bpp-manager', 'bppEditor', self::editor_config( $editor ? absint( get_the_ID() ) : 0 ) );
	}

	/** Load shared collection controls for independent Gutenberg blocks on any post type.
	 * @return void
	 */
	public static function block_assets(): void {
		wp_enqueue_media(); wp_enqueue_style( 'bpp-admin', BPP_URL . 'assets/admin.css', array(), BPP_VERSION . '.' . filemtime( BPP_DIR . 'assets/admin.css' ) );
		wp_localize_script( 'bpp-manager', 'bppEditor', self::editor_config( absint( get_the_ID() ) ) );
	}

	/** Build one complete editor configuration for both enqueue paths.
	 * @param int $post_id Current source contribution or zero on settings pages.
	 * @return array Complete translated manager configuration.
	 */
	public static function editor_config( int $post_id ): array {
		return array( 'types' => array_map( static fn( $type )=>$type->labels->name, Settings::types() ), 'sorts' => Library::sorts(), 'watermarkExceptions' => Watermark::exceptions_allowed(), 'schema' => Settings::schema(), 'groups' => Settings::groups(), 'previewNonce' => wp_create_nonce( 'bpp_preview' ), 'blockNonce' => wp_create_nonce( 'bpp_block_preview' ), 'uploadNonce' => wp_create_nonce( 'media-form' ), 'ajax' => admin_url( 'admin-ajax.php' ), 'i18n' => self::labels(), 'postId' => $post_id, 'limit' => Settings::limit( $post_id ), 'defaults' => Settings::resolve( $post_id ), 'origins' => Settings::origins( $post_id ), 'collectionHelp' => self::collection_help() );
	}

	/** Explain the collection term consistently wherever documents are edited.
	 * @return string Translated neutral help text, suitable for informal and formal locales.
	 */
	public static function collection_help(): string {
		return __( 'A PDF collection contains the documents added to a contribution, page or individual PDF block. It can be displayed as a grid or list and may contain just one PDF.', 'builder-pdf-presenter' );
	}

	/** Render reusable inheritable fields.
	 * @param string $prefix Input namespace.
	 * @param array $values Explicit values at this level.
	 * @param bool $inherit Whether an inheritance choice is offered.
	 * @param array $only Optional schema keys to render.
	 * @return void
	 */
	private static function fields_flat( string $prefix, array $values, bool $inherit = true, array $only = array() ): void {
		foreach ( Settings::schema() as $key => $field ) {
			if ( 'reader_watermark' === $key && ! Watermark::exceptions_allowed() ) { continue; }
			if ( $only && ! in_array( $key, $only, true ) ) { continue; }
			$id = sanitize_html_class( $prefix . '-' . $key );
			$value = $values[ $key ] ?? '';
			$name = $prefix . '[' . $key . ']';
			echo '<div class="bpp-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
			if ( 'text' === $field['type'] ) {
				echo '<input type="text" maxlength="100" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr__( 'Inherit', 'builder-pdf-presenter' ) . '">';
			} else {
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				if ( $inherit ) { echo '<option value="" ' . selected( $value, '', false ) . '>' . esc_html__( 'Inherit', 'builder-pdf-presenter' ) . '</option>'; }
				$choices = 'bool' === $field['type'] ? array( '1' => __( 'Show / enable', 'builder-pdf-presenter' ), '0' => __( 'Hide / disable', 'builder-pdf-presenter' ) ) : $field['choices'];
				foreach ( $choices as $option => $label ) {
					$current = is_bool( $value ) ? ( $value ? '1' : '0' ) : (string) $value;
					echo '<option value="' . esc_attr( $option ) . '" ' . selected( $current, (string) $option, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			}
			if ( $inherit ) {
				$saved = Settings::stored(); $post_id = absint( get_the_ID() );
				$base = array_replace( Settings::defaults(), Settings::clean( $saved['global'] ?? array() ) );
				$source = __( 'Website defaults', 'builder-pdf-presenter' );
				if ( 'bpp_options' === $prefix ) { $base = array_replace( $base, Settings::clean( $saved['types'][ get_post_type( $post_id ) ] ?? array() ) ); $source = Settings::origins( $post_id, false )[ $key ]; }
				$display = is_bool( $base[ $key ] ) ? ( $base[ $key ] ? __( 'Show / enable', 'builder-pdf-presenter' ) : __( 'Hide / disable', 'builder-pdf-presenter' ) ) : ( $field['choices'][ $base[ $key ] ] ?? $base[ $key ] );
				echo '<small>' . esc_html__( 'Inherited from', 'builder-pdf-presenter' ) . ': ' . esc_html( $source ) . ' · ' . esc_html( $display ) . '</small>';
			}
			echo '</div>';
		}
	}

	/** Render consistent option groups with per-group inheritance reset controls.
	 * @param string $prefix Form namespace.
	 * @param array $values Explicit values at this scope.
	 * @param bool $inherit Whether empty values inherit.
	 * @return void
	 */
	private static function fields( string $prefix, array $values, bool $inherit = true ): void {
		foreach ( Settings::groups() as $group ) {
			echo '<fieldset class="bpp-option-group"><legend>' . esc_html( $group['label'] ) . '</legend><div class="bpp-fields">';
			self::fields_flat( $prefix, $values, $inherit, $group['keys'] );
			echo '</div>';
			if ( $inherit ) { echo '<button type="button" class="button-link bpp-reset-group">' . esc_html__( 'Use inherited settings for this group', 'builder-pdf-presenter' ) . '</button>'; }
			echo '</fieldset>';
		}
	}

	/** Render website defaults, per-type defaults and concise usage help.
	 * @return void
	 */
	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$saved = Settings::stored();
		?>
		<div class="wrap bpp-admin"><h1 class="screen-reader-text">Builder PDF Presenter</h1><header class="bpp-header"><img class="bpp-brand-icon" src="<?php echo esc_url( BPP_URL . 'assets/branding/icon.svg' ); ?>" width="72" height="72" alt="" aria-hidden="true"><div class="bpp-header-copy"><div class="bpp-header-title">Builder PDF Presenter <small><?php echo esc_html( BPP_VERSION ); ?></small></div><p><?php esc_html_e( 'Your PDFs. Beautifully presented.', 'builder-pdf-presenter' ); ?></p></div></header>
		<hr class="wp-header-end">
		<?php settings_errors(); ?><form id="bpp-settings-form" method="post" action="options.php">
		<?php settings_fields( 'bpp' ); ?>
        <div class="bpp-settings-layout"><nav class="bpp-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'builder-pdf-presenter' ); ?>">
        <?php foreach ( array( 'content'=>__( 'Content and management', 'builder-pdf-presenter' ), 'cards'=>__( 'Cards and grid', 'builder-pdf-presenter' ), 'read'=>__( 'Reading button', 'builder-pdf-presenter' ), 'download'=>__( 'Download button', 'builder-pdf-presenter' ), 'reader'=>__( 'Reader', 'builder-pdf-presenter' ), 'types'=>__( 'Content type overrides', 'builder-pdf-presenter' ), 'watermark'=>__( 'Watermark', 'builder-pdf-presenter' ), 'preview'=>__( 'Design preview', 'builder-pdf-presenter' ), 'integration'=>__( 'Embed PDFs', 'builder-pdf-presenter' ), 'transfer'=>__( 'Transfer settings', 'builder-pdf-presenter' ) ) as $anchor=>$label ) : ?><a href="#bpp-section-<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?></nav><div class="bpp-settings-content">

		<section id="bpp-section-content" class="bpp-panel"><h2><?php esc_html_e( 'Content types', 'builder-pdf-presenter' ); ?></h2><p><?php esc_html_e( 'Choose where the PDF selection and individual settings appear. The block and shortcode are available independently.', 'builder-pdf-presenter' ); ?></p>
		<?php foreach ( Settings::types() as $slug => $type ) : ?>
		<label class="bpp-type"><input type="checkbox" name="bpp_settings[enabled][<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( in_array( $slug, $saved['enabled'] ?? array(), true ) ); ?>> <?php echo esc_html( $type->labels->name ); ?></label>
		<?php endforeach; ?><p><?php echo esc_html( self::collection_help() ); ?></p><p><label><?php esc_html_e( 'Maximum number of PDFs per collection', 'builder-pdf-presenter' ); ?> <input type="number" name="bpp_settings[limit]" min="1" max="500" value="<?php echo absint( $saved['limit'] ?? 20 ); ?>"></label></p><p><?php esc_html_e( 'Reducing a limit never removes existing documents. Editors cannot override limits.', 'builder-pdf-presenter' ); ?></p></section>
		<?php $anchors = array( 'cards', 'read', 'download', 'reader' ); $index = 0; foreach ( Settings::groups() as $group ) : ?>
        <section id="bpp-section-<?php echo esc_attr( $anchors[ $index++ ] ); ?>" class="bpp-panel"><span class="bpp-scope-label"><?php esc_html_e( 'Website defaults', 'builder-pdf-presenter' ); ?></span><h2><?php echo esc_html( $group['label'] ); ?></h2><p class="bpp-section-intro"><?php esc_html_e( 'These defaults apply wherever no individual override is set.', 'builder-pdf-presenter' ); ?></p><div class="bpp-fields">
        <?php self::fields_flat( 'bpp_settings[global]', array_replace( Settings::defaults(), Settings::clean( $saved['global'] ?? array() ) ), false, $group['keys'] ); ?></div></section><?php endforeach; ?>
		<section id="bpp-section-types" class="bpp-panel"><h2><?php esc_html_e( 'Defaults per content type', 'builder-pdf-presenter' ); ?></h2><p><?php esc_html_e( 'Inherit uses the website defaults. Individual contributions can override these values again.', 'builder-pdf-presenter' ); ?></p>
		<?php foreach ( Settings::types() as $slug => $type ) : ?>
		<details><summary><?php echo esc_html( $type->labels->name ); ?></summary><p><label><?php esc_html_e( 'Collection limit (0 = website default)', 'builder-pdf-presenter' ); ?> <input type="number" min="0" max="500" name="bpp_settings[limits][<?php echo esc_attr( $slug ); ?>]" value="<?php echo absint( $saved['limits'][ $slug ] ?? 0 ); ?>"></label></p><div class="bpp-grouped"><?php self::fields( 'bpp_settings[types][' . $slug . ']', Settings::clean( $saved['types'][ $slug ] ?? array() ) ); ?></div></details>
		<?php endforeach; ?></section>
		<?php self::integration_help(); Watermark::fields(); ?><section id="bpp-section-preview" class="bpp-panel"><h2><?php esc_html_e( 'Live design preview', 'builder-pdf-presenter' ); ?></h2><div id="bpp-design-preview" aria-live="polite"></div></section></div></div><div class="bpp-savebar"><span class="bpp-save-status" role="status" aria-live="polite"><?php esc_html_e( 'All changes saved.', 'builder-pdf-presenter' ); ?></span><button type="submit" class="button button-primary"><span class="bpp-save-dot" hidden aria-hidden="true"></span><span><?php esc_html_e( 'Save changes', 'builder-pdf-presenter' ); ?></span></button></div></form>
		<?php Settings_Transfer::panel(); ?>
		<?php if ( Migration::available() ) : ?><p class="description"><a href="<?php echo esc_url( Migration::url() ); ?>"><?php esc_html_e( 'PDF migration and recovery', 'builder-pdf-presenter' ); ?></a></p><?php endif; ?><?php self::footer(); ?>

		</div>
		<?php
	}


	/** Render the host footer once, with localized product and author destinations.
	 * @return void
	 */
	public static function footer(): void {
		$language = str_starts_with( determine_locale(), 'de_' ) ? 'de' : 'en';
		$website = 'https://deckerwebplugins.com/' . $language . '/plugins/builder-pdf-presenter/';
		$doc = BPP_URL . 'docs/index-' . $language . '.html';
		?>
		<footer class="bpp-footer"><div><strong>Builder PDF Presenter</strong> <span><?php echo esc_html( BPP_VERSION ); ?></span><p><?php esc_html_e( 'Your PDFs. Beautifully presented.', 'builder-pdf-presenter' ); ?></p><nav aria-label="<?php esc_attr_e( 'Plugin information', 'builder-pdf-presenter' ); ?>"><a href="<?php echo esc_url( $doc ); ?>"><?php esc_html_e( 'Documentation', 'builder-pdf-presenter' ); ?></a><a href="<?php echo esc_url( $doc . '#part-4' ); ?>"><?php esc_html_e( 'FAQ', 'builder-pdf-presenter' ); ?></a><a href="<?php echo esc_url( BPP_URL . 'readme.txt' ); ?>" data-bpp-changelog><?php esc_html_e( 'Changelog', 'builder-pdf-presenter' ); ?></a></nav></div><div class="bpp-footer-links"><span>© 2026 <a href="<?php echo esc_url( 'https://deckerwebplugins.com/' . $language . '/' ); ?>">David Decker – DECKERWEB</a></span><a href="<?php echo esc_url( $website ); ?>"><?php esc_html_e( 'Plugin website', 'builder-pdf-presenter' ); ?></a><a href="https://github.com/deckerweb/builder-pdf-presenter">GitHub</a></div></footer>
		<dialog class="bpp-changelog" hidden><form method="dialog"><button class="button"><?php esc_html_e( 'Close', 'builder-pdf-presenter' ); ?></button></form><h2><?php esc_html_e( 'Changelog', 'builder-pdf-presenter' ); ?></h2><?php $history = require BPP_DIR . 'includes/changelog.php'; echo wp_kses_post( $history[ str_starts_with( determine_locale(), 'de_' ) ? 'de' : 'en' ] ); ?></dialog>
		<?php
	}

	/** Explain available integration paths without expanding the whole admin guide.
	 * @return void
	 */
	public static function integration_help(): void {
		$de = str_starts_with( determine_locale(), 'de_' );
		$doc = BPP_URL . ( $de ? 'docs/index-de.html' : 'docs/index-en.html' );
		$types = array_filter( Settings::types(), static fn( $type )=>use_block_editor_for_post_type( $type->name ) );
		?>
		<section id="bpp-section-integration" class="bpp-panel"><h2><?php esc_html_e( 'Embed PDFs', 'builder-pdf-presenter' ); ?></h2>
		<details open><summary><?php esc_html_e( 'Shortcodes: available in every editor', 'builder-pdf-presenter' ); ?></summary><p><?php esc_html_e( 'Paste a shortcode where the document or collection should appear. It uses the current post unless another source is selected.', 'builder-pdf-presenter' ); ?></p>
		<?php foreach ( array( '[builder_pdf_presenter]', '[builder_pdf_presenter source="posts" post_type="post" collection_id="newsletters" columns="3" per_post="all"]', '[builder_pdf_filters target="newsletters"]' ) as $example ) : ?><p><code><?php echo esc_html( $example ); ?></code> <button type="button" class="button bpp-copy" data-copy="<?php echo esc_attr( $example ); ?>" data-copied="<?php esc_attr_e( 'Copied', 'builder-pdf-presenter' ); ?>"><?php esc_html_e( 'Copy', 'builder-pdf-presenter' ); ?></button></p><?php endforeach; ?><a href="<?php echo esc_url( $doc . '#shortcodes' ); ?>"><?php esc_html_e( 'All shortcode parameters and examples', 'builder-pdf-presenter' ); ?></a></details>
		<?php if ( $types ) : ?><details><summary><?php esc_html_e( 'Block Editor: blocks and patterns', 'builder-pdf-presenter' ); ?></summary><p><?php esc_html_e( 'Insert Builder PDF Presenter for documents or collections, and PDF filters and search for an optional filter bar. Patterns provide ready-to-use layouts.', 'builder-pdf-presenter' ); ?></p><p><?php echo esc_html( implode( ', ', array_map( static fn( $type )=>$type->labels->name, $types ) ) ); ?></p><a href="<?php echo esc_url( $doc . '#guide' ); ?>"><?php esc_html_e( 'Open guide', 'builder-pdf-presenter' ); ?></a></details><?php endif; ?>
		<?php if ( class_exists( '\Bricks\Elements' ) ) : ?><details><summary>Bricks</summary><p><?php esc_html_e( 'Use PDF document in native post loops, PDF collection for a document library, and PDF filters and search for plugin collections. Reuse configured elements as native Components.', 'builder-pdf-presenter' ); ?></p><a href="<?php echo esc_url( $doc . '#guide' ); ?>"><?php esc_html_e( 'Open guide', 'builder-pdf-presenter' ); ?></a></details><?php endif; ?></section>
		<?php
	}

	/** Render document fields in the contribution editor.
	 * @param \WP_Post $post Current contribution.
	 * @return void
	 */
	public static function box( \WP_Post $post ): void {
		echo '<div class="bpp-post-editor">';
		wp_nonce_field( 'bpp_save_' . $post->ID, 'bpp_nonce' );
		$entries = Documents::editor_data( Documents::get( $post->ID ) );
		$data = array( 'entries' => $entries, 'limit' => Settings::limit( $post->ID ), 'postId' => $post->ID, 'defaults' => Settings::resolve( $post->ID ), 'origins' => Settings::origins( $post->ID ) );
		echo '<p>' . esc_html( self::collection_help() ) . '</p>';
		echo '<div class="bpp-document-manager" data-config="' . esc_attr( wp_json_encode( $data ) ) . '"><input type="hidden" name="bpp_documents" value="' . esc_attr( wp_json_encode( $entries ) ) . '"><div class="bpp-manager-root"></div></div>';
		echo '<details><summary>' . esc_html__( 'Individual display settings', 'builder-pdf-presenter' ) . '</summary><p>' . esc_html__( 'Inherit keeps the defaults for this content type. These values apply to cards rendered from this contribution.', 'builder-pdf-presenter' ) . '</p><div class="bpp-grouped">';
		self::fields( 'bpp_options', Settings::clean( get_post_meta( $post->ID, '_bpp_options', true ) ) );
		echo '</div></details></div>';
	}

	/** Save document metadata only after nonce, capability and type checks.
	 * @param int $post_id Contribution being saved.
	 * @return void
	 */
	public static function save( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) || ! isset( $_POST['bpp_nonce'] ) || ! is_string( $_POST['bpp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bpp_nonce'] ) ), 'bpp_save_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! in_array( get_post_type( $post_id ), Settings::stored()['enabled'] ?? array(), true ) ) { return; }
		if ( isset( $_POST['bpp_documents'] ) && is_string( $_POST['bpp_documents'] ) ) {
			$incoming = json_decode( sanitize_textarea_field( wp_unslash( $_POST['bpp_documents'] ) ), true );
			$clean = Documents::clean( $incoming, Documents::get( $post_id ), Settings::limit( $post_id ) );
			if ( ! is_wp_error( $clean ) ) { update_post_meta( $post_id, '_bpp_documents', $clean ); }
			else { set_transient( 'bpp_save_error_' . get_current_user_id(), $clean->get_error_message(), 120 ); }
		}
		foreach ( array( 'pdf', 'cover' ) as $key ) {
			if ( ! isset( $_POST[ 'bpp_' . $key . '_id' ] ) ) { continue; }
			$id = absint( $_POST[ 'bpp_' . $key . '_id' ] );
			if ( 0 === $id ) { delete_post_meta( $post_id, '_bpp_' . $key . '_id' ); }
			elseif ( ( 'pdf' === $key ? Presenter::valid_pdf( $id ) : wp_attachment_is_image( $id ) ) && current_user_can( 'read_post', $id ) ) { update_post_meta( $post_id, '_bpp_' . $key . '_id', $id ); }
		}
		update_post_meta( $post_id, '_bpp_options', Settings::clean( isset( $_POST['bpp_options'] ) ? map_deep( wp_unslash( $_POST['bpp_options'] ), 'sanitize_text_field' ) : array() ) );
	}
}
