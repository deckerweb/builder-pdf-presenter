<?php
/** Settings schema, validation and cascading values. */
namespace Deckerweb\PDFPresenter;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Owns the shared schema for website, post-type and individual presentation options. */
final class Settings {
	/** Return supported display settings. @return array<string,array> Schema with translated labels. */
	public static function schema(): array {
		return array(
			'design' => array( 'label' => __( 'Card design', 'builder-pdf-presenter' ), 'type' => 'select', 'choices' => array( 'cover' => __( 'Cover card', 'builder-pdf-presenter' ), 'compact' => __( 'Compact card', 'builder-pdf-presenter' ), 'list' => __( 'Document list', 'builder-pdf-presenter' ) ) ),
			'columns' => array( 'label' => __( 'Collection columns', 'builder-pdf-presenter' ), 'type' => 'select', 'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ) ),
			'mode' => array( 'label' => __( 'Open document', 'builder-pdf-presenter' ), 'type' => 'select', 'choices' => array( 'modal' => __( 'Reader in a modal', 'builder-pdf-presenter' ), 'tab' => __( 'Reader in a new tab', 'builder-pdf-presenter' ), 'file' => __( 'PDF directly in a new tab', 'builder-pdf-presenter' ) ) ),
			'ratio' => array( 'label' => __( 'Preview frame', 'builder-pdf-presenter' ), 'type' => 'select', 'choices' => array( 'a4' => __( 'Portrait (A4)', 'builder-pdf-presenter' ), 'portrait' => __( 'Portrait (3:4)', 'builder-pdf-presenter' ), 'square' => __( 'Square', 'builder-pdf-presenter' ), 'landscape' => __( 'Landscape (4:3)', 'builder-pdf-presenter' ), 'auto' => __( 'Original proportions', 'builder-pdf-presenter' ) ) ),
			'show_title' => array( 'label' => __( 'Show title', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'show_read' => array( 'label' => __( 'Show reading button', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'show_download' => array( 'label' => __( 'Show download button', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'cover_link' => array( 'label' => __( 'Make preview clickable', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'show_size' => array( 'label' => __( 'Show file size', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'read_label' => array( 'label' => __( 'Reading button label', 'builder-pdf-presenter' ), 'type' => 'text' ),
			'download_label' => array( 'label' => __( 'Download button label', 'builder-pdf-presenter' ), 'type' => 'text' ),
			'page_navigation' => array( 'label' => __( 'Reader: page navigation', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'zoom' => array( 'label' => __( 'Reader: zoom controls', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'search' => array( 'label' => __( 'Reader: search on current page', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'print' => array( 'label' => __( 'Reader: open PDF for printing', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'fullscreen' => array( 'label' => __( 'Reader: fullscreen', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'reader_download' => array( 'label' => __( 'Reader: download link', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'reader_watermark' => array( 'label' => __( 'Use reader watermark', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'remember_page' => array( 'label' => __( 'Reader: remember page during this visit', 'builder-pdf-presenter' ), 'type' => 'bool' ),
			'initial_zoom' => array( 'label' => __( 'Reader: initial zoom', 'builder-pdf-presenter' ), 'type' => 'select', 'choices' => array( 'fit' => __( 'Fit width', 'builder-pdf-presenter' ), '1' => '100%', '1.25' => '125%', '1.5' => '150%' ) ),
		);
	}

	/** Return baseline values in the current language. @return array<string,mixed> Defaults. */
	public static function defaults(): array {
		return array( 'design' => 'cover', 'columns' => '3', 'reader_watermark' => true, 'remember_page' => true, 'mode' => 'modal', 'ratio' => 'a4', 'show_title' => true, 'show_read' => true, 'show_download' => true, 'cover_link' => true, 'show_size' => true, 'read_label' => __( 'Read', 'builder-pdf-presenter' ), 'download_label' => __( 'Download', 'builder-pdf-presenter' ), 'page_navigation' => true, 'zoom' => true, 'search' => false, 'print' => false, 'fullscreen' => true, 'reader_download' => true, 'initial_zoom' => 'fit' );
	}

	/** Validate only recognized explicit overrides; absent/empty values inherit.
	 * @param mixed $input Untrusted array of values.
	 * @return array<string,mixed> Validated overrides.
	 */
	public static function clean( $input ): array {
		$out = array();
		if ( ! is_array( $input ) ) { return $out; }
		foreach ( self::schema() as $key => $field ) {
			if ( ! array_key_exists( $key, $input ) || ! is_scalar( $input[ $key ] ) || ( ! is_bool( $input[ $key ] ) && '' === (string) $input[ $key ] ) ) { continue; }
			$value = is_bool( $input[ $key ] ) ? ( $input[ $key ] ? '1' : '0' ) : (string) $input[ $key ];
			if ( 'bool' === $field['type'] && in_array( $value, array( '0', '1', 'true', 'false' ), true ) ) { $out[ $key ] = in_array( $value, array( '1', 'true' ), true ); }
			elseif ( 'select' === $field['type'] && isset( $field['choices'][ $value ] ) ) { $out[ $key ] = $value; }
			elseif ( 'text' === $field['type'] ) { $out[ $key ] = mb_substr( sanitize_text_field( $value ), 0, 100 ); }
		}
		return $out;
	}

	/** Return selectable content types, including custom private content types.
	 * @return array<string,\WP_Post_Type> Content types that have an admin UI.
	 */
	public static function types(): array {
		return array_filter( get_post_types( array( 'show_ui' => true ), 'objects' ), static function( $type ) {
			$slug = $type->name;
			$internal = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'meta-box', 'mb-model', 'mb-models', 'mb-view', 'mb-views', 'mb-post-type', 'mb-taxonomy', 'mb-settings-page', 'mb-relationship', 'acf-field', 'acf-field-group', 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page', 'jet-engine', 'jet-engine-booking', 'jet-smart-filters', 'jet-listing-items', 'jet-theme-core', 'elementor_library', 'elementor_snippet', 'e-landing-page', 'bricks_template', 'bricks_fonts', 'bricks_component', 'pdfposter', 'acf-options-page', 'mb-block', 'mb-admin-columns', 'jet-engine-meta-box', 'jet-engine-post-type', 'jet-engine-taxonomy', 'jet-engine-query', 'jet-engine-options', 'jet-popup', 'jet-menu', 'jet-woo-builder', 'e-floating-buttons', 'e-global-styles', 'elementor_font', 'elementor_icons', 'fl-builder-template', 'fl-theme-layout', 'ct_template', 'oxy_user_library', 'breakdance_block', 'breakdance_template', 'breakdance_header', 'breakdance_footer' );
			return ! in_array( $slug, $internal, true ) && ! str_starts_with( $slug, 'wp_' );
		} );
	}

	/** Read saved settings without autoloading large presentation arrays.
	 * @return array<string,mixed> Stored website configuration.
	 */
	public static function stored(): array { $value = get_option( 'bpp_settings', array() ); return is_array( $value ) ? $value : array(); }

	/** Resolve defaults, website, content type, contribution and output overrides.
	 * @param int $post_id Contribution whose settings apply.
	 * @param array $output Explicit block or shortcode overrides.
	 * @return array<string,mixed> Effective validated options.
	 */
	public static function resolve( int $post_id, array $output = array() ): array {
		$saved = self::stored();
		return array_replace( self::defaults(), self::clean( $saved['global'] ?? array() ), self::clean( $saved['types'][ get_post_type( $post_id ) ] ?? array() ), self::clean( get_post_meta( $post_id, '_bpp_options', true ) ), self::clean( $output ) );
	}

	/** Return themed option groups shared by all configuration levels.
	 * @return array<string,array> Translated group labels and setting keys.
	 */
	public static function groups(): array {
		return array(
			'card' => array( 'label' => __( 'Preview and document card', 'builder-pdf-presenter' ), 'keys' => array( 'design', 'columns', 'ratio', 'show_title', 'show_size', 'cover_link' ) ),
			'read' => array( 'label' => __( 'Reading button', 'builder-pdf-presenter' ), 'keys' => array( 'show_read', 'read_label' ) ),
			'download' => array( 'label' => __( 'Download button', 'builder-pdf-presenter' ), 'keys' => array( 'show_download', 'download_label' ) ),
			'reader' => array( 'label' => __( 'Reader and opening behavior', 'builder-pdf-presenter' ), 'keys' => array( 'mode', 'page_navigation', 'zoom', 'initial_zoom', 'search', 'print', 'fullscreen', 'reader_download', 'remember_page', 'reader_watermark' ) ),
		);
	}

	/** Return the administrator-defined maximum number of documents.
	 * @param int $post_id Source contribution or zero for an independent block.
	 * @return int Effective collection limit.
	 */
	public static function limit( int $post_id = 0 ): int {
		$saved = self::stored();
		return max( 1, min( 500, absint( $saved['limits'][ get_post_type( $post_id ) ] ?? 0 ) ?: absint( $saved['limit'] ?? 20 ) ) );
	}

	/** Describe the source of an inherited value to the editor.
	 * @param int $post_id Source contribution or zero for global defaults.
	 * @param bool $include_post Include individual contribution overrides.
	 * @return array<string,string> One source label per setting.
	 */
	public static function origins( int $post_id = 0, bool $include_post = true ): array {
		$saved = self::stored(); $out = array_fill_keys( array_keys( self::schema() ), __( 'Built-in default', 'builder-pdf-presenter' ) );
		$levels = array( __( 'Website defaults', 'builder-pdf-presenter' ) => self::clean( $saved['global'] ?? array() ), __( 'Content type defaults', 'builder-pdf-presenter' ) => self::clean( $saved['types'][ get_post_type( $post_id ) ] ?? array() ) );
		if ( $include_post && $post_id ) { $levels[ __( 'Contribution settings', 'builder-pdf-presenter' ) ] = self::clean( get_post_meta( $post_id, '_bpp_options', true ) ); }
		foreach ( $levels as $label => $values ) { foreach ( $values as $key => $value ) { $out[ $key ] = $label; } }
		return $out;
	}

	/** Validate the complete settings form.
	 * @param mixed $input Untrusted settings input.
	 * @return array<string,mixed> Website and content type configuration.
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$out = array( 'global' => self::clean( $input['global'] ?? array() ), 'enabled' => array(), 'types' => array(), 'limit' => max( 1, min( 500, absint( $input['limit'] ?? 20 ) ) ), 'limits' => array() );
		foreach ( self::types() as $slug => $type ) {
			if ( ! empty( $input['enabled'][ $slug ] ) || in_array( $slug, is_array( $input['enabled'] ?? null ) ? $input['enabled'] : array(), true ) ) { $out['enabled'][] = $slug; }
			$out['limits'][ $slug ] = min( 500, absint( $input['limits'][ $slug ] ?? 0 ) );
			$out['types'][ $slug ] = self::clean( $input['types'][ $slug ] ?? array() );
		}
		$out['watermark'] = Watermark::sanitize( $input['watermark'] ?? array() );
		return $out;
	}
}
